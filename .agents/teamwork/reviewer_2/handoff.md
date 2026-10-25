# Handoff Report: Reviewer 2 (Backend Integration & Architecture Reviewer)

**Agent**: Reviewer 2 (Backend Integration, Architecture & Test Reviewer)  
**Roles**: reviewer, critic  
**Working Directory**: `/home/ilhamzp/Projects/school_suite/.agents/teamwork/reviewer_2/`  
**Date**: 2026-10-25T13:23:00Z  
**Parent Conversation ID**: `8b31b9e2-3264-4135-b1ed-73ff3f845b1e`  

---

## Review Summary

**Verdict**: **REQUEST_CHANGES**

---

## 1. Observation

### 1.1 Automated Tool Execution Results (Verbatim)

1. **Code Style & Formatting (`./vendor/bin/pint --test`)**:
   ```json
   {"tool":"pint","result":"passed"}
   ```
   *Exit code: 0.*

2. **Modular Architecture Boundary Tests (`php artisan test --filter=ModularBoundaryTest`)**:
   ```json
   {"tool":"phpunit","result":"passed","tests":4,"passed":4,"assertions":6,"duration_ms":851}
   ```
   *Exit code: 0.*

3. **Dashboard Access Feature Tests (`php artisan test --filter=DashboardAccessTest`)**:
   ```json
   {"tool":"phpunit","result":"passed","tests":7,"passed":7,"assertions":86,"duration_ms":732}
   ```
   *Exit code: 0.*

4. **Full Test Suite (`php artisan test`)**:
   ```json
   {"tool":"phpunit","result":"passed","tests":173,"passed":173,"assertions":1000,"duration_ms":9871}
   ```
   *Exit code: 0.*

5. **Frontend Typecheck (`npm run typecheck` / `vue-tsc --noEmit`)**:
   ```text
   > typecheck
   > vue-tsc --noEmit
   ```
   *Exit code: 0.*

6. **Frontend Asset Build (`npm run build` / `vite build`)**:
   ```text
   ✓ 699 modules transformed.
   public/build/assets/Dashboard-xX5wWJZ1.js                   24.00 kB │ gzip:  6.57 kB
   public/build/assets/AppLayout-BUGkRIcz.js                   16.93 kB │ gzip:  5.50 kB
   ✓ built in 1.90s
   ```
   *Exit code: 0.*

---

### 1.2 Adversarial Role-Route Matrix Verification (Verbatim)

An automated matrix test was executed against all 32 navigation and workflow action routes across all 6 roles (`admin`, `operator`, `principal`, `teacher`, `homeroom`, `student`) using authenticated sessions on pilot school accounts.

```text
Tested 114 role-route combinations.
FAILURES DETECTED:
1. /akademik/kelas-daring -> Role: homeroom -> HTTP 403 Forbidden
2. /akademik/kelas-daring -> Role: student  -> HTTP 403 Forbidden
3. /penilaian/sikap       -> Role: teacher  -> HTTP 403 Forbidden
4. /komunikasi            -> Role: student  -> HTTP 500 Internal Server Error
   Exception: Class "App\Modules\Administration\Interface\Http\Controllers\Enrollment" not found
5. /pengaturan/pengguna   -> Role: operator -> HTTP 403 Forbidden
6. /pengaturan/sekolah    -> Role: operator -> HTTP 403 Forbidden
7. /pengaturan/sekolah    -> Role: principal-> HTTP 403 Forbidden
```

Specific inspection of source code at these failure locations:

- **`App\Modules\Administration\Interface\Http\Controllers\CommunicationController.php` lines 188–191**:
  ```php
  return Enrollment::query()
      ->whereHas('student', fn ($student) => $student->where('user_id', $user->id))
      ->where('status', 'active')
      ->pluck('class_group_id')->map(fn ($id) => (int) $id)->all();
  ```
  `Enrollment` is never imported at the top of `CommunicationController.php`. Furthermore, directly referencing `Enrollment` in `App\Modules\Administration` directly breaches the modular monolith boundary.

- **`App\Modules\Academic\Interface\Http\Controllers\OnlineClassController.php` lines 105–108**:
  ```php
  private function authorizeAccess(Request $request): void
  {
      abort_unless($request->user()->hasRole(['admin', 'principal', 'operator', 'teacher']), 403);
  }
  ```
  `homeroom` and `student` are omitted from the controller's `hasRole` check, despite route middleware in `app/Modules/Academic/Interface/Routes/web.php:40` declaring `middleware('role:teacher,homeroom,student')` and `SidebarNavigation.vue:108` and `Dashboard.vue:109` exposing the route to them.

- **`App\Modules\Assessment\Interface\Http\Controllers\StudentAttitudeController.php` lines 77–80**:
  ```php
  private function authorizeManage(Request $request): void
  {
      abort_unless($request->user()->hasRole(['admin', 'principal', 'operator', 'homeroom']), 403);
  }
  ```
  `teacher` is omitted from `authorizeManage()`, despite route middleware in `app/Modules/Assessment/Interface/Routes/web.php:22` declaring `middleware('role:teacher,homeroom')`, `SidebarNavigation.vue:118` declaring `attitudes: ['teacher', 'homeroom']`, and `Dashboard.vue:88` having `teacherActions` link to `/penilaian/sikap`.

- **`App\Modules\Core\Application\Policies\UserPolicy.php:39` and `SchoolPolicy.php:39`**:
  Unlike `admin` which has a blanket bypass in `before()`, `UserPolicy::viewAny` and `SchoolPolicy::view` require `core.users.view` and `core.school.view`. In `PilotSchoolSeeder` and the live database, `Role::permissions` is empty for `operator` and `principal`. Consequently, `operator` on `/pengaturan/pengguna` and `operator` / `principal` on `/pengaturan/sekolah` return HTTP 403.

---

## 2. Findings

### [Critical] Finding 1: Fatal Crash (HTTP 500) on Student Access to Communication / Announcements (`/komunikasi`)
- **What**: When any user with role `student` navigates to `/komunikasi` (which is in the student's sidebar navigation and acceptance criteria), the application throws an uncaught fatal error: `Error: Class "App\Modules\Administration\Interface\Http\Controllers\Enrollment" not found`, resulting in an **HTTP 500 Internal Server Error**.
- **Where**: `app/Modules/Administration/Interface/Http/Controllers/CommunicationController.php:188` (`visibleClassIds`).
- **Why**: 
  1. `Enrollment` is referenced without an import in `CommunicationController.php`.
  2. Importing `Enrollment` (an Academic Infrastructure model) directly in the Administration module violates modular monolith architecture.
- **Suggestion**: 
  1. Extend `AcademicQueryService` (e.g. `getStudentEnrollmentClassIds(int $userId): array`) in `app/Modules/Academic/Application/Services/AcademicQueryService.php`.
  2. In `CommunicationController::visibleClassIds()`, delegate the call to `$this->academicQuery->getStudentEnrollmentClassIds((int) $user->id)` instead of referencing `Enrollment::query()`.
  3. Add a feature test verifying that a `student` user can successfully access `GET /komunikasi` with HTTP 200.

---

### [Critical] Finding 2: Broken Authorization (HTTP 403) on Online Classes (`/akademik/kelas-daring`) for Students & Homeroom Teachers
- **What**: Students and Homeroom Teachers are presented with the "Kelas Daring" menu item in `SidebarNavigation.vue` and workflow action cards in `Dashboard.vue`, but clicking it yields **HTTP 403 Forbidden**.
- **Where**: `app/Modules/Academic/Interface/Http/Controllers/OnlineClassController.php:107` (`authorizeAccess`).
- **Why**: The route definition in `app/Modules/Academic/Interface/Routes/web.php` line 40 allows `role:teacher,homeroom,student`, but the controller's internal `authorizeAccess()` only permits `['admin', 'principal', 'operator', 'teacher']`.
- **Suggestion**:
  1. Update `OnlineClassController::authorizeAccess()`:
     ```php
     private function authorizeAccess(Request $request): void
     {
         abort_unless($request->user()->hasRole(['admin', 'principal', 'operator', 'teacher', 'homeroom', 'student']), 403);
     }
     ```
  2. Ensure `store()`, `updateStatus()`, and `recordAttendance()` remain restricted to teachers/admins via `authorizeManage()`.
  3. In `OnlineClassController::index()`, filter sessions appropriately if student (e.g., student only views sessions for their enrolled classes).
  4. Add feature tests verifying that `student` and `homeroom` roles can access `GET /akademik/kelas-daring` with HTTP 200.

---

### [Major] Finding 3: Broken Authorization (HTTP 403) on Attitude Assessment (`/penilaian/sikap`) for Regular Teachers
- **What**: Regular teachers (`teacher`) have "Penilaian Sikap" in `teacherActions` (`Dashboard.vue:88`) and in the sidebar (`SidebarNavigation.vue:118`), but visiting `/penilaian/sikap` results in **HTTP 403 Forbidden**.
- **Where**: `app/Modules/Assessment/Interface/Http/Controllers/StudentAttitudeController.php:79` (`authorizeManage`).
- **Why**: Route middleware allows `role:teacher,homeroom`, and Requirement R1 requires teacher access, but the controller's `authorizeManage()` method only allows `['admin', 'principal', 'operator', 'homeroom']`.
- **Suggestion**:
  1. If teachers are permitted to record/view attitudes for classes they teach, add `'teacher'` to `authorizeManage()` in `StudentAttitudeController.php`.
  2. If attitude assessment is strictly reserved for Homeroom teachers, remove `/penilaian/sikap` from `teacherActions` in `Dashboard.vue` and remove `'teacher'` from `roleVisibility.attitudes` in `SidebarNavigation.vue` to prevent leading teachers into a 403 dead end.

---

### [Major] Finding 4: Permission Mismatch for Operator and Principal on User & School Settings
- **What**: `operatorActions` in `Dashboard.vue` provides quick links to `/pengaturan/pengguna` and `/pengaturan/sekolah`, and `SidebarNavigation.vue` provides `/pengaturan/sekolah` to `principal`. However, live tests on `operator@pilot.sch.id` and `kepsek@pilot.sch.id` return **HTTP 403 Forbidden**.
- **Where**: `app/Modules/Core/Application/Policies/UserPolicy.php:39`, `SchoolPolicy.php:39`, and `database/seeders/PilotSchoolSeeder.php`.
- **Why**: Non-admin roles require explicit permissions (`core.users.view`, `core.school.view`). In `PilotSchoolSeeder`, permissions are instantiated but never attached to `$roles['operator']` or `$roles['principal']`.
- **Suggestion**:
  1. In `PilotSchoolSeeder.php`, attach appropriate default permissions to `operator` (`users.view`, `school.view`, `school.update`) and `principal` (`users.view`, `school.view`), matching the test expectations in `tests/Feature/RoleMatrixAccessTest.php`.
  2. Or, if operator should not manage users/school directly, adjust `operatorActions` and `roleVisibility` accordingly.

---

## 3. Logic Chain

1. **Integrity Check**:
   - Inspected `tests/Feature/DashboardAccessTest.php` and `routes/web.php`.
   - Verified that Worker 1 did not hardcode results or cheat test assertions. The tests use valid Eloquent factories and models.
   - However, Worker 1's handoff claim that all routes are verified without 403/404 issues was invalidated by executing authenticated role requests.

2. **Root Cause Analysis of Runtime Crashes**:
   - `SidebarNavigation.vue` and `Dashboard.vue` expose URLs based solely on UI configuration (`roleVisibility` and action arrays).
   - When a user actually clicks these links, execution reaches the backend controllers.
   - For `/komunikasi` with a student, the unimported `Enrollment` class causes an instant 500 crash.
   - For `/akademik/kelas-daring` and `/penilaian/sikap`, discrepancies between route middleware and internal controller `abort_unless` calls cause instant 403 blocks for valid users.

3. **Conclusion Determination**:
   - A core requirement in `ORIGINAL_REQUEST.md` is "Tidak ada tautan 404/broken" and ensuring non-admin roles have a smooth workflow across LMS, CBT, and Assessment.
   - Exposing menu items and dashboard buttons that crash with HTTP 500 or block with HTTP 403 prevents the milestone from being accepted in its current state.
   - Therefore, the verdict is unequivocally **REQUEST_CHANGES**.

---

## 4. Caveats

- **Admin Dashboard Integrity**: The preservation of `adminActions` and the admin overview grid was verified and remains intact without regression.
- **Frontend Build Quality**: TypeScript typing (`vue-tsc --noEmit`) and Vite build pass cleanly with 0 errors.
- **Existing Tests**: All 173 existing PHPUnit tests pass. The issue is that the existing test suite lacked test cases for student communication access and teacher/homeroom online class controller invocations.

---

## 5. Adversarial Challenge & Stress-Test Results

| Scenario | Expected Behavior | Actual Behavior | Result |
|---|---|---|---|
| Student visits `/komunikasi` | HTTP 200 Inertia Page | HTTP 500 (Class `Enrollment` not found) | **FAIL** |
| Homeroom visits `/akademik/kelas-daring` | HTTP 200 Inertia Page | HTTP 403 Forbidden | **FAIL** |
| Student visits `/akademik/kelas-daring` | HTTP 200 Inertia Page | HTTP 403 Forbidden | **FAIL** |
| Teacher visits `/penilaian/sikap` | HTTP 200 Inertia Page | HTTP 403 Forbidden | **FAIL** |
| Operator visits `/pengaturan/pengguna` | HTTP 200 Inertia Page | HTTP 403 Forbidden | **FAIL** |
| Operator visits `/pengaturan/sekolah` | HTTP 200 Inertia Page | HTTP 403 Forbidden | **FAIL** |
| Principal visits `/pengaturan/sekolah` | HTTP 200 Inertia Page | HTTP 403 Forbidden | **FAIL** |
| Homeroom visits `/wali-kelas/catatan` | HTTP 200 Inertia Page | HTTP 200 Inertia Page | **PASS** |
| Homeroom visits `/wali-kelas/kedisiplinan` | HTTP 200 Inertia Page | HTTP 200 Inertia Page | **PASS** |
| Homeroom visits `/komunikasi/whatsapp` | HTTP 200 Inertia Page | HTTP 200 Inertia Page | **PASS** |
| Teacher visits `/pembelajaran` | HTTP 200 Inertia Page | HTTP 200 Inertia Page | **PASS** |
| Student visits `/pembelajaran/progress` | HTTP 200 Inertia Page | HTTP 200 Inertia Page | **PASS** |
| Principal visits `/laporan` | HTTP 200 Inertia Page | HTTP 200 Inertia Page | **PASS** |
| Principal visits `/penilaian/analisis` | HTTP 200 Inertia Page | HTTP 200 Inertia Page | **PASS** |

---

## 6. Verification Method

To independently reproduce and verify these findings, run the following commands:

```bash
# 1. Reproduce Student HTTP 500 on /komunikasi
php -r '
require "vendor/autoload.php";
$app = require_once "bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$student = App\Models\User::whereHas("role", fn($q) => $q->where("name", "student"))->first();
auth()->login($student);
$req = Illuminate\Http\Request::create("/komunikasi", "GET");
$req->setUserResolver(fn() => $student);
$res = app()->make(Illuminate\Contracts\Http\Kernel::class)->handle($req);
echo "Status: " . $res->getStatusCode() . "\n";
if ($res->exception) echo "Error: " . $res->exception->getMessage() . "\n";
'

# 2. Reproduce Homeroom HTTP 403 on /akademik/kelas-daring
php -r '
require "vendor/autoload.php";
$app = require_once "bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$homeroom = App\Models\User::whereHas("role", fn($q) => $q->where("name", "homeroom"))->first();
auth()->login($homeroom);
$req = Illuminate\Http\Request::create("/akademik/kelas-daring", "GET");
$req->setUserResolver(fn() => $homeroom);
$res = app()->make(Illuminate\Contracts\Http\Kernel::class)->handle($req);
echo "Status: " . $res->getStatusCode() . "\n";
'

# 3. Reproduce Teacher HTTP 403 on /penilaian/sikap
php -r '
require "vendor/autoload.php";
$app = require_once "bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$teacher = App\Models\User::whereHas("role", fn($q) => $q->where("name", "teacher"))->first();
auth()->login($teacher);
$req = Illuminate\Http\Request::create("/penilaian/sikap", "GET");
$req->setUserResolver(fn() => $teacher);
$res = app()->make(Illuminate\Contracts\Http\Kernel::class)->handle($req);
echo "Status: " . $res->getStatusCode() . "\n";
'
```

---

## 7. Action Items for Worker 1

1. **Fix `CommunicationController.php`**:
   - Add method to `AcademicQueryService` to fetch student enrollment class IDs cleanly without cross-module model imports.
   - Use `AcademicQueryService` in `CommunicationController::visibleClassIds()`.
2. **Fix `OnlineClassController.php`**:
   - Update `authorizeAccess()` to include `homeroom` and `student`.
3. **Resolve `StudentAttitudeController.php` vs Teacher Action**:
   - Either allow `teacher` in `StudentAttitudeController::authorizeManage()`, or remove `/penilaian/sikap` from `teacherActions` and `roleVisibility.attitudes` if it is strictly homeroom-only.
4. **Add Feature Test Coverage**:
   - Add feature tests for student accessing `/komunikasi` and `/akademik/kelas-daring`, and teacher accessing their assigned routes, ensuring no regressions.
