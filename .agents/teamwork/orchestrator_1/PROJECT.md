# Project: School Suite Non-Admin Navigation & Dashboard Workflows

## Architecture
- **Framework**: Laravel 12 + Inertia.js v3 + Vue 3 (Composition API, `<script setup lang="ts">`) + Tailwind CSS.
- **Architectural Style**: Modular Monolith with 9 registered domain modules (`Core`, `Academic`, `Homeroom`, `Learning`, `Assessment`, `Admissions`, `Library`, `Administration`, `Integration`).
- **Key Frontend Entry Points**:
  - `resources/js/components/SidebarNavigation.vue`: Global application navigation sidebar.
  - `resources/js/Pages/Dashboard.vue`: Main role-based dashboard landing page.
- **Routing & Controllers**:
  - `routes/web.php`: Root dashboard aggregation (`GET /`) and metrics provider.
  - `app/Modules/Assessment/Interface/Routes/web.php`: Reporting routes (`/laporan`, `/laporan/nilai`, etc.).
  - `app/Modules/Administration/Interface/Routes/web.php`: Engagement routes (`/komunikasi/chat`, `/komunikasi/whatsapp`).
  - `app/Modules/Homeroom/Interface/Routes/web.php`: Homeroom observation notes and discipline routes (`/wali-kelas/catatan`, `/wali-kelas/kedisiplinan`).

## Feature Inventory
| # | Feature | Description | Milestone | Source | Status |
|---|---------|-------------|-----------|--------|--------|
| F1 | Domain Categorization in Sidebar | Group navigation into 7 domain categories: Akademik, Pembelajaran LMS, Asesmen & CBT, Penilaian & Rapor, Perwalian, Komunikasi, Pengaturan (+ Beranda). | M1 | R1, Survey | DONE |
| F2 | Suppress Empty Category Headings | Ensure that categories with zero accessible items for a role are omitted dynamically (`.filter(s => s.items.length > 0)`). | M1 | R1, AC1, Survey | DONE |
| F3 | Missing Navigation Links Integration | Add `/komunikasi/chat`, `/wali-kelas/catatan`, `/wali-kelas/kedisiplinan`, `/komunikasi/whatsapp` to `SidebarNavigation.vue`. | M1 | R1, AC2, AC3 | DONE |
| F4 | Role Permissions in Sidebar | Add `student` to `schedules` (`/akademik/jadwal`), `operator` and `admin` to `exams` (`/ujian`), `admin` and `principal` to `learning` (`/pembelajaran`). | M1 | R1, AC2, Survey | DONE |
| F5 | Fix Broken Reporting Route | Replace 404 route `/penilaian/laporan` with `/laporan` in `Dashboard.vue:61` (`principalActions`). | M2 | R2, AC4, Survey | DONE |
| F6 | Decouple Role Computing in Dashboard | Isolate `isAdmin` to `role.value === 'admin'` (preserving `adminActions` untouched). Decouple `isOperator`, `isHomeroom`, `isTeacher`, `isPrincipal`, `isStudent`. | M2 | R2, Survey | DONE |
| F7 | Enrich Non-Admin Workflow Actions | Define dedicated `operatorActions` and `homeroomActions`, and enrich `teacherActions`, `principalActions`, `studentActions` (replacing forbidden 403 `/akademik/presensi` with `/pembelajaran`, `/akademik/jadwal`, `/pembelajaran/progress`). | M2 | R2, AC2, AC3, Survey | DONE |
| F8 | Role Metrics and Focus Copy Alignment | Harmonize focus copy, title, focus metric, and `metricItems` for each role with metrics provided in `routes/web.php`. | M2 | R2, Survey | DONE |
| F9 | Preserve `adminActions` Boundary | Keep `adminActions` (lines 51–62) and `admin-overview-grid` (lines 250–259) completely untouched. | M2 | R2 Constraint | DONE |
| F10 | Backend Test Coverage for All Roles | Expand `tests/Feature/DashboardAccessTest.php` to test operator, principal, homeroom dashboard access and metrics. | M3 | R3, Survey | DONE |
| F11 | Code Style & Lint Cleanliness | Run `./vendor/bin/pint` to fix unused imports in `tests/Feature/DashboardAccessTest.php` and unordered import in `routes/web.php`. | M3 | R3, Survey | DONE |
| F12 | Complete Test Suite & Build Verification | Verify `npm run typecheck` passes, `npm run build` passes, and `php artisan test` passes 100% (184 tests, 1,401 assertions). | M3 | R3, Survey | DONE |

## Milestones
| # | Name | Scope | Dependencies | Status |
|---|------|-------|-------------|--------|
| M1 | Sidebar Navigation Restructuring | F1, F2, F3, F4 in `SidebarNavigation.vue` | none | DONE |
| M2 | Dashboard Actions & Role Decoupling | F5, F6, F7, F8, F9 in `Dashboard.vue` | none | DONE |
| M3 | Testing, Code Style & Final Verification | F10, F11, F12 in `tests/Feature/DashboardAccessTest.php`, `routes/web.php`, `AcademicQueryService.php` | M1, M2 | DONE |

## Code Layout
- `resources/js/components/SidebarNavigation.vue`: Grouped navigation categories, dynamic section filtering, path boundary checking.
- `resources/js/Pages/Dashboard.vue`: Decoupled role actions and workflows, preserved `adminActions` verbatim.
- `app/Modules/Academic/Application/Services/AcademicQueryService.php`: Modular student class, user, and enrollment query methods.
- `app/Modules/Academic/Interface/Http/Controllers/OnlineClassController.php`: Student and homeroom online class access.
- `app/Modules/Assessment/Interface/Http/Controllers/StudentAttitudeController.php`: Teacher attitude assessment access.
- `app/Modules/Administration/Interface/Http/Controllers/CommunicationController.php`: Modular student class group resolution.
- `app/Modules/Administration/Application/Services/CommunicationService.php`: Modular student class group resolution.
- `app/Modules/Learning/Interface/Routes/web.php`: Role middleware updated to allow manager roles.
- `app/Modules/Learning/Interface/Http/Controllers/LearningController.php`: Encapsulated via `AcademicQueryService`.
- `app/Modules/Learning/Application/Policies/LearningPolicy.php`: Injected `AcademicQueryService`.
- `tests/Feature/DashboardAccessTest.php`: Feature tests for all roles.
- `tests/Feature/RouteAndPermissionStressTest.php`: Empirical route stress tests across 6 roles.
