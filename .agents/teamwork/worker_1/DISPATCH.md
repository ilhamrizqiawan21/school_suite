# DISPATCH: Worker 1 - Implementation of Navigation, Dashboard Workflows, and Test Verification

Working Directory: /home/ilhamzp/Projects/school_suite/.agents/teamwork/worker_1/
Project Root: /home/ilhamzp/Projects/school_suite

## Critical Warnings & Constraints
1. MANDATORY: Read `/home/ilhamzp/Projects/school_suite/.agents/teamwork/ORIGINAL_REQUEST.md` before starting.
2. DO NOT CHEAT. All implementations must be genuine. DO NOT hardcode test results, create dummy/facade implementations, or circumvent the intended task. A teamwork_preview_auditor will independently verify your work. Integrity violations WILL be detected and your work WILL be rejected.
3. PRESERVE ADMIN ACTIONS: In `resources/js/Pages/Dashboard.vue`, the admin user is actively editing admin actions. DO NOT touch, overwrite, or modify lines 42–53 (`const adminActions = [ ... ]`) or lines 147–159 (`<div v-if="isAdmin" class="admin-overview-grid">`). Keep `adminActions` verbatim.

## Input Evidence & Explorer Handoffs
Read the three explorer handoff reports:
- `/home/ilhamzp/Projects/school_suite/.agents/teamwork/explorer_survey_1/handoff.md` (Sidebar navigation restructuring, missing items, role visibility, dynamic section filtering, exact matching).
- `/home/ilhamzp/Projects/school_suite/.agents/teamwork/explorer_survey_2/handoff.md` (Dashboard role decoupling, fixing 404 `/penilaian/laporan` -> `/laporan`, fixing 403 `/akademik/presensi` for student, complete action arrays for all roles, metrics alignment).
- `/home/ilhamzp/Projects/school_suite/.agents/teamwork/explorer_survey_3/handoff.md` (Architecture boundaries, route audit, baseline commands, pint linting).

## Scope of Work & File Ownership
You exclusively own:
- `resources/js/components/SidebarNavigation.vue`
- `resources/js/Pages/Dashboard.vue`
- `tests/Feature/DashboardAccessTest.php`
- `routes/web.php` (only for running `./vendor/bin/pint` formatting)

### Task 1: Restructure `resources/js/components/SidebarNavigation.vue` (R1)
1. Categorize navigation items into 7 domain categories (+ Beranda):
   - **Akademik**: Data Siswa (`/akademik/siswa`), Data Guru (`/akademik/guru`), Rombel/Kelas (`/akademik/kelas`), Jadwal Pelajaran (`/akademik/jadwal`), Presensi Siswa (`/akademik/presensi`), Jurnal Mengajar (`/akademik/jurnal`), Kelas Daring (`/akademik/kelas-daring`), Siklus Siswa (`/akademik/siklus-siswa`), Pengaturan Akademik (`/akademik/pengaturan`).
   - **Pembelajaran LMS**: Ruang Belajar (`/pembelajaran`), Progress Belajar (`/pembelajaran/progress`).
   - **Asesmen & CBT**: Bank Soal (`/penilaian/bank-soal`), Paket Soal (`/penilaian/paket-soal`), Berbagi Soal (`/penilaian/berbagi-soal`), Ujian CBT (`/ujian`).
   - **Penilaian & Rapor**: Input Nilai (`/penilaian/skor`), Penilaian Sikap (`/penilaian/sikap`), Skema Penilaian (`/penilaian/skema`), Rapor Siswa (`/penilaian/rapor`), Ledger Nilai (`/penilaian/ledger`), Analisis Asesmen (`/penilaian/analisis`), Pusat Laporan (`/laporan`).
   - **Perwalian**: Dashboard Wali Kelas (`/wali-kelas`), Catatan Observasi & BK (`/wali-kelas/catatan`), Kedisiplinan & Prestasi (`/wali-kelas/kedisiplinan`), WhatsApp Orang Tua (`/komunikasi/whatsapp`).
   - **Komunikasi**: Pengumuman & Agenda (`/komunikasi`), Diskusi Chat (`/komunikasi/chat`), WhatsApp Orang Tua (`/komunikasi/whatsapp`).
   - **Pengaturan**: Profil Saya (`/profil`), Pengguna (`/pengaturan/pengguna`), Profil Sekolah (`/pengaturan/sekolah`), Keamanan (`/pengaturan/keamanan`).
2. Include the 4 missing routes:
   - `/komunikasi/chat`
   - `/wali-kelas/catatan`
   - `/wali-kelas/kedisiplinan`
   - `/komunikasi/whatsapp`
3. Update `roleVisibility`:
   - `schedules` (`/akademik/jadwal`): include `student`.
   - `exams` (`/ujian`): include `operator` and `admin`.
   - `learning` (`/pembelajaran`): include `admin` and `principal`.
4. Dynamic section filtering:
   - Ensure categories with 0 visible items for the user's role are NOT rendered (i.e., `.filter(section => section.items.length > 0)`). No empty section headings!
5. Add `exact?: boolean` to avoid sub-path active collisions on base URLs (`/komunikasi`, `/wali-kelas`, `/pembelajaran`).

### Task 2: Update `resources/js/Pages/Dashboard.vue` (R2)
1. Decouple role checks:
   - `isAdmin = computed(() => role.value === 'admin');` (DO NOT touch `adminActions` or admin grid!)
   - `isOperator = computed(() => role.value === 'operator');`
   - `isPrincipal = computed(() => role.value === 'principal');`
   - `isHomeroom = computed(() => role.value === 'homeroom');`
   - `isTeacher = computed(() => role.value === 'teacher');`
   - `isStudent = computed(() => role.value === 'student');`
2. Define dedicated `operatorActions` and `homeroomActions`.
3. Enrich `teacherActions`, `principalActions`, `studentActions`:
   - In `principalActions`, replace `/penilaian/laporan` (404) with `/laporan`.
   - In `studentActions`, remove `/akademik/presensi` (403 Forbidden for students); replace with `/pembelajaran`, `/akademik/jadwal`, and `/pembelajaran/progress`.
4. Update computed properties: `actions`, `focusLabel`, `focusTitle`, `focusCopy`, `focusMetric`, `focusMetricLabel`, and `metricItems` to align with the role metrics from `routes/web.php`.

### Task 3: Test Expansion & Code Quality (R3)
1. In `tests/Feature/DashboardAccessTest.php`, add feature tests for `operator`, `principal`, and `homeroom` accessing the dashboard, checking status 200 and relevant Inertia props/metrics.
2. Run `./vendor/bin/pint` to resolve formatting and unused imports.
3. Run verification suite:
   - `npm run typecheck` (`vue-tsc --noEmit`) -> MUST exit 0.
   - `npm run build` (`vite build`) -> MUST exit 0.
   - `php artisan test` -> MUST pass 100%.
   - `./vendor/bin/pint --test` -> MUST exit 0.

## Deliverable
Write your complete report in `/home/ilhamzp/Projects/school_suite/.agents/teamwork/worker_1/handoff.md` and use `send_message` to report back to parent.
