# DISPATCH: Worker 2 - Iteration 2 Fix Implementation & Verification

Working Directory: /home/ilhamzp/Projects/school_suite/.agents/teamwork/worker_2/
Project Root: /home/ilhamzp/Projects/school_suite

## Critical Warnings & Constraints
1. MANDATORY: Read `/home/ilhamzp/Projects/school_suite/.agents/teamwork/ORIGINAL_REQUEST.md` before starting.
2. DO NOT CHEAT. All implementations must be genuine. DO NOT hardcode test results, create dummy/facade implementations, or circumvent the intended task. A teamwork_preview_auditor will independently verify your work. Integrity violations WILL be detected and your work WILL be rejected.
3. PRESERVE ADMIN ACTIONS: In `resources/js/Pages/Dashboard.vue`, `adminActions` (lines 51–62) and `<div v-if="isAdmin" class="admin-overview-grid">` MUST remain untouched verbatim.

## Input Evidence & Explorer Handoffs
Review the findings and pre-validated patches from:
1. `/home/ilhamzp/Projects/school_suite/.agents/teamwork/explorer_it2_1/handoff.md` and `/home/ilhamzp/Projects/school_suite/.agents/teamwork/explorer_it2_1/sidebar_active_highlight.patch`
2. `/home/ilhamzp/Projects/school_suite/.agents/teamwork/explorer_it2_2/handoff.md` and `/home/ilhamzp/Projects/school_suite/.agents/teamwork/explorer_it2_2/backend_authorization_fixes.patch`
3. `/home/ilhamzp/Projects/school_suite/.agents/teamwork/explorer_it2_3/handoff.md` and `/home/ilhamzp/Projects/school_suite/.agents/teamwork/explorer_it2_3/proposed_changes.patch`

## Tasks to Execute

### Task 1: Fix Frontend Active Highlight Collision in `SidebarNavigation.vue`
Apply the path segment boundary check from `explorer_it2_1`:
In `isItemActive`:
```ts
if (item.activePrefix) {
    return currentPath === item.activePrefix || currentPath.startsWith(item.activePrefix + '/');
}
```
Verify that `/akademik/kelas-daring` only highlights `Kelas Daring` and does NOT highlight `Rombel / Kelas`.

### Task 2: Fix Authorization in Controllers
1. In `app/Modules/Academic/Interface/Http/Controllers/OnlineClassController.php:107`:
   Include `'homeroom'` and `'student'` in `authorizeAccess()`.
2. In `app/Modules/Assessment/Interface/Http/Controllers/StudentAttitudeController.php:79`:
   Include `'teacher'` in `authorizeManage()`.

### Task 3: Fix Modular Student Class Retrieval for Communication
1. In `app/Modules/Academic/Application/Services/AcademicQueryService.php`:
   Add `getStudentClassGroupIds(int $studentUserId, ?int $termId = null, ?int $schoolId = null): array` (and any related helper if needed).
2. In `app/Modules/Administration/Interface/Http/Controllers/CommunicationController.php:188`:
   Replace unimported `Enrollment::query()` with call to `$this->academicQuery->getStudentClassGroupIds()`.
3. In `app/Modules/Administration/Application/Services/CommunicationService.php`:
   Clean up any unimported model references using `AcademicQueryService`.

### Task 4: Tests & Verification Suite
1. In `tests/Feature/RouteAndPermissionStressTest.php`:
   Update test assertions to verify HTTP 200 for:
   - Student accessing `/komunikasi`
   - Student & Homeroom accessing `/akademik/kelas-daring`
   - Teacher accessing `/penilaian/sikap`
2. Run code style: `./vendor/bin/pint`
3. Run verification commands:
   - `npm run typecheck` (`vue-tsc --noEmit`) -> MUST exit 0.
   - `npm run build` (`vite build`) -> MUST exit 0.
   - `./vendor/bin/pint --test` -> MUST exit 0.
   - `php artisan test --filter=ModularBoundaryTest` -> MUST pass 100%.
   - `php artisan test --filter=RouteAndPermissionStressTest` -> MUST pass 100%.
   - `php artisan test` -> MUST pass 100% (all tests pass).

## Deliverable
Document all actions, diffs, and verification outputs in `/home/ilhamzp/Projects/school_suite/.agents/teamwork/worker_2/handoff.md`.
Call `send_message` when complete.


## 2026-10-25T13:36:19Z
You are Worker 2 (Iteration 2 Remediation Worker).
Your working directory is: /home/ilhamzp/Projects/school_suite/.agents/teamwork/worker_2/
The project root is: /home/ilhamzp/Projects/school_suite

Read your assignment in:
/home/ilhamzp/Projects/school_suite/.agents/teamwork/worker_2/DISPATCH.md
and
/home/ilhamzp/Projects/school_suite/.agents/teamwork/ORIGINAL_REQUEST.md

MANDATORY INTEGRITY WARNING:
DO NOT CHEAT. All implementations must be genuine. DO NOT hardcode test results, create dummy/facade implementations, or circumvent the intended task. A teamwork_preview_auditor will independently verify your work. Integrity violations WILL be detected and your work WILL be rejected.

CRITICAL INSTRUCTION:
In resources/js/Pages/Dashboard.vue, DO NOT touch, modify, or overwrite adminActions (lines 51-62) or the admin overview grid (lines 250-259) verbatim.

Execute Tasks 1, 2, 3, 4 per DISPATCH.md using the provided explorer patches.
Run all verification suites (npm run typecheck, npm run build, ./vendor/bin/pint --test, php artisan test) and document results in /home/ilhamzp/Projects/school_suite/.agents/teamwork/worker_2/handoff.md.
When finished, call send_message to report back to parent.
