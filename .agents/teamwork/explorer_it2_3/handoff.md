# Handoff Report: Modular Monolith Integrity for CommunicationController

## 1. Observation

- **Observation 1 (Direct Reference in Controller)**:
  File: `app/Modules/Administration/Interface/Http/Controllers/CommunicationController.php:188-192`
  ```php
  return Enrollment::query()
      ->whereHas('student', fn ($student) => $student->where('user_id', $user->id))
      ->where('status', 'active')
      ->pluck('class_group_id')->map(fn ($id) => (int) $id)->all();
  ```
  `Enrollment` is called directly without a `use` statement in namespace `App\Modules\Administration\Interface\Http\Controllers`.

- **Observation 2 (Runtime Fatal Error 500)**:
  From `storage/logs/laravel.log`:
  `[2026-10-25 13:29:34] testing.ERROR: Class "App\Modules\Administration\Interface\Http\Controllers\Enrollment" not found {"userId":6,"exception":"[object] (Error(code: 0): Class \"App\\Modules\\Administration\\Interface\\Http\\Controllers\\Enrollment\" not found at /home/ilhamzp/Projects/school_suite/app/Modules/Administration/Interface/Http/Controllers/CommunicationController.php:188)`

- **Observation 3 (Architecture Invariant & Boundary Enforcement)**:
  File: `tests/Architecture/ModularBoundaryTest.php:96-138`
  ```php
  // Disallow direct import of another module's Infrastructure (Models, Repositories, Migrations)
  $forbiddenPrefix = "App\\Modules\\{$targetModule}\\Infrastructure";

  if (str_starts_with($importedClass, $forbiddenPrefix)) {
      $violations[] = sprintf(
          'File [%s] in module [%s] illegally imports [%s] from module [%s]. Cross-module interactions must go through Application layer.',
          $filePath,
          $currentModule,
          $importedClass,
          $targetModule
      );
  }
  ```
  Importing `App\Modules\Academic\Infrastructure\Models\Enrollment` inside `CommunicationController.php` triggers an immediate test failure in `ModularBoundaryTest`.

- **Observation 4 (Existing Application Layer Bridge)**:
  File: `app/Modules/Administration/Interface/Http/Controllers/CommunicationController.php:8` and `:28`
  `use App\Modules\Academic\Application\Services\AcademicQueryService;`
  `AcademicQueryService` is already injected in the constructor:
  ```php
  public function __construct(
      private readonly CommunicationService $communication,
      private readonly AcademicQueryService $academicQuery,
  ) {}
  ```

- **Observation 5 (Service Capabilities in Academic Module)**:
  File: `app/Modules/Academic/Application/Services/AcademicQueryService.php`
  `AcademicQueryService` resides in `App\Modules\Academic\Application\Services` and can legally interact with `Academic\Infrastructure\Models\Enrollment` and `Academic\Infrastructure\Models\Student`. It provides queries for assignments, terms, and enrollments, but currently lacks a lightweight query returning active class IDs for a student user (`list<int>`).

- **Observation 6 (Latent Sibling Bug in CommunicationService)**:
  File: `app/Modules/Administration/Application/Services/CommunicationService.php:52-66`
  In `audience()`:
  ```php
  $studentUserIds = Enrollment::query()
      ->where('class_group_id', $classGroupId)
      ...
  $teacherUserIds = TeachingAssignment::query()
      ->where('class_group_id', $classGroupId)
      ...
  ```
  Both `Enrollment` and `TeachingAssignment` are referenced without imports and cannot legally import from `Academic\Infrastructure`.

- **Observation 7 (Empirical Challenge Test in Test Suite)**:
  File: `tests/Feature/RouteAndPermissionStressTest.php:755-764`
  ```php
  public function test_challenge_vulnerability_communication_controller_fatal_500_for_student(): void
  {
      $response = $this->actingAs($this->studentUser)->get('/komunikasi');
      $this->assertEquals(500, $response->getStatusCode());
  }
  ```
  This test was created during Iteration 1 to empirically verify the 500 error.

---

## 2. Logic Chain

1. **Root Cause of 500 Error**: (From Obs 1 & Obs 2)
   When a student visits `/komunikasi`, `CommunicationController::index()` calls `visibleClassIds($request)`. Line 188 attempts to call `Enrollment::query()`. Because `Enrollment` is not imported, PHP attempts to resolve `App\Modules\Administration\Interface\Http\Controllers\Enrollment`, which does not exist. PHP raises a fatal error resulting in HTTP 500.

2. **Constraint on Quick Fixes**: (From Obs 3)
   A developer might attempt to fix this by adding `use App\Modules\Academic\Infrastructure\Models\Enrollment;` to `CommunicationController.php`. However, `ModularBoundaryTest` strictly forbids any module from importing classes under `App\Modules\{OtherModule}\Infrastructure\*`. Direct model queries across modular boundaries violate the architecture and cause `ModularBoundaryTest` to fail.

3. **Available Application Layer Bridge**: (From Obs 4 & Obs 5)
   `CommunicationController` already imports and injects `AcademicQueryService` (`App\Modules\Academic\Application\Services\AcademicQueryService`). `AcademicQueryService` belongs to the Application layer of the `Academic` module, which is the designated integration boundary between modules.

4. **Service Encapsulation**: (From Obs 5)
   Adding a method `getStudentClassGroupIds(int $studentUserId, ?int $termId = null, ?int $schoolId = null): array` to `AcademicQueryService`:
   - Queries `Enrollment` and `Student` inside the Academic module (100% compliant with modular monolith rules).
   - Scopes queries by tenant (`school_id`) and term (`term_id` when active).
   - Returns a pure primitive array of integer IDs (`list<int>`), completely decoupling the `Administration` module from Academic Eloquent models.

5. **Resolution in CommunicationController**: (From Obs 4 & Step 4)
   In `CommunicationController::visibleClassIds()`, replacing lines 188-192 with:
   ```php
   $schoolId = (int) $user->school_id;
   $activeTerm = $this->academicQuery->getActiveTerm($schoolId);

   return $this->academicQuery->getStudentClassGroupIds(
       (int) $user->id,
       $activeTerm?->id,
       $schoolId
   );
   ```
   This eliminates the fatal error, avoids cross-module model leakage, and maintains 100% compliance with `ModularBoundaryTest`.

6. **Impact on Test Suite**: (From Obs 7)
   Once fixed, `/komunikasi` will return HTTP 200 for students. The test `test_challenge_vulnerability_communication_controller_fatal_500_for_student` in `tests/Feature/RouteAndPermissionStressTest.php` must be updated to assert status 200 (or replaced with `test_student_can_access_communication_index_successfully`).

---

## 3. Caveats

1. **Active Term Fallback**:
   In `getStudentClassGroupIds`, `$termId` is optional (`?int $termId = null`). When `$activeTerm` is null (e.g. between academic semesters or in test suites without seeded active terms), passing `null` ensures the query falls back to returning all active enrollments for the student rather than an empty list.
2. **Sibling Latent Bug in CommunicationService**:
   `app/Modules/Administration/Application/Services/CommunicationService.php:52-66` contains unimported references to `Enrollment` and `TeachingAssignment` when publishing class-scoped announcements. While not directly tested by `/komunikasi` index route, this is an adjacent risk that can be resolved using the same pattern (`AcademicQueryService::getClassGroupStudentUserIds()` and `AcademicQueryService::getClassGroupTeacherUserIds()`).
3. **Purity of Return Type**:
   `AcademicQueryService::getStudentClassGroupIds()` must return `list<int>`, not Eloquent models, to prevent leaky abstractions across module boundaries.

---

## 4. Conclusion

The solution to the Student HTTP 500 error on `/komunikasi` that complies 100% with `ModularBoundaryTest` and modular monolith design is:

1. **Add to `app/Modules/Academic/Application/Services/AcademicQueryService.php`**:
   ```php
   /**
    * Ambil ID rombel aktif untuk seorang siswa berdasarkan user_id.
    *
    * @return list<int>
    */
   public function getStudentClassGroupIds(int $studentUserId, ?int $termId = null, ?int $schoolId = null): array
   {
       return Enrollment::query()
           ->whereHas('student', fn ($student) => $student
               ->where('user_id', $studentUserId)
               ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
           )
           ->where('status', 'active')
           ->when($termId, fn ($query) => $query->where('term_id', $termId))
           ->distinct()
           ->pluck('class_group_id')
           ->map(fn ($id) => (int) $id)
           ->values()
           ->all();
   }
   ```

2. **Update in `app/Modules/Administration/Interface/Http/Controllers/CommunicationController.php` (lines 185-192)**:
   ```php
   $schoolId = (int) $user->school_id;
   $activeTerm = $this->academicQuery->getActiveTerm($schoolId);

   return $this->academicQuery->getStudentClassGroupIds(
       (int) $user->id,
       $activeTerm?->id,
       $schoolId
   );
   ```

3. **Machine-Applicable Patch Prepared**:
   A verified patch file has been generated and validated with `git apply --check`:
   `/home/ilhamzp/Projects/school_suite/.agents/teamwork/explorer_it2_3/proposed_changes.patch`

4. **Test Adjustment**:
   In `tests/Feature/RouteAndPermissionStressTest.php:755-764`, update `test_challenge_vulnerability_communication_controller_fatal_500_for_student` to assert status 200:
   `$this->assertEquals(200, $response->getStatusCode());`

---

## 5. Verification Method

To independently verify the fix:

1. **Modular Boundary Invariant Check**:
   Run:
   ```bash
   php artisan test --filter ModularBoundaryTest
   ```
   *Expected Result*: 4 tests passed, 0 boundary violations.

2. **Route and Access Verification**:
   Run:
   ```bash
   php artisan test --filter RouteAndPermissionStressTest
   ```
   *Expected Result*: All role-based routes succeed with HTTP 200, including Student access to `/komunikasi`.

3. **Code Style & Formatting**:
   Run:
   ```bash
   ./vendor/bin/pint --test
   ```
   *Expected Result*: No lint or style violations.

4. **Invalidation Conditions**:
   - Any `use App\Modules\Academic\Infrastructure\...` imported in `CommunicationController.php` or `CommunicationService.php` will invalidate this solution by failing `ModularBoundaryTest`.
   - Returning Eloquent `Enrollment` models from `AcademicQueryService` into `CommunicationController` would leak Infrastructure concerns across boundaries.
