# Handoff Report: Survey Phase — Explorer 2 (Dashboard Workflows & Actions)

**Agent**: Explorer 2 (Dashboard Actions Explorer)  
**Working Directory**: `/home/ilhamzp/Projects/school_suite/.agents/teamwork/explorer_survey_2/`  
**Milestone**: Survey Phase  
**Timestamp**: 2026-10-25T12:48:00Z  

---

## 1. Observation

### 1.1 Current Architecture & File Analysis in `resources/js/Pages/Dashboard.vue`
Direct inspection of `resources/js/Pages/Dashboard.vue` (197 lines) reveals:

1. **Role Classification Couplings (Lines 29–32)**:
   ```ts
   const isAdmin = computed(() => ['admin', 'operator'].includes(role.value));
   const isPrincipal = computed(() => role.value === 'principal');
   const isTeacher = computed(() => ['teacher', 'homeroom'].includes(role.value));
   const isStudent = computed(() => role.value === 'student');
   ```
   - `operator` is bundled directly into `isAdmin`.
   - `homeroom` is bundled directly into `isTeacher`.
   - Neither `isOperator` nor `isHomeroom` exists as an independent computed property.

2. **Action Array Definitions (Lines 42–74)**:
   - `adminActions` is defined on lines 42–53 (10 items).
   - `teacherActions` is defined on lines 54–59 (4 items: Input Nilai `/penilaian/skor`, Presensi Siswa `/akademik/presensi`, Bank Soal `/penilaian/bank-soal`, Ujian `/ujian`).
   - `principalActions` is defined on lines 60–64 (3 items: Lihat laporan sekolah `/penilaian/laporan`, Komunikasi `/komunikasi`, Data akademik `/akademik/siswa`).
   - `studentActions` is defined on lines 65–69 (3 items: Lihat ujian `/ujian`, Lihat nilai `/penilaian/rapor`, Presensi saya `/akademik/presensi`).
   - `fallbackActions` is defined on lines 70–74 (3 items).
   - **Missing**: There is NO `homeroomActions` array defined anywhere in `Dashboard.vue`.
   - **Missing**: There is NO `operatorActions` array defined anywhere in `Dashboard.vue`.

3. **`adminActions` Boundary & Template Isolation (Lines 42–53, 147–166)**:
   - `adminActions` definition: Lines 42–53.
   - Template rendering for `isAdmin`:
     ```html
     <div v-if="isAdmin" class="section-heading"><div><span class="eyebrow">Pantauan harian</span><h2>Pengumuman dan log login</h2></div><span class="workflow-count">2 area penting</span></div>
     <div v-if="isAdmin" class="admin-overview-grid">
         <Link href="/komunikasi" class="admin-overview-card announcement-card">...</Link>
         <Link href="/pengaturan/keamanan" class="admin-overview-card login-card">...</Link>
     </div>
     <div v-else class="section-heading"><div><span class="eyebrow">Alur kerja</span><h2>{{ isPrincipal ? 'Pantauan pimpinan' : isTeacher ? 'Pekerjaan mengajar' : isStudent ? 'Perjalanan belajar' : 'Akses cepat' }}</h2></div><span class="workflow-count">{{ secondaryActions.length }} langkah lanjutan</span></div>
     <div v-if="!isAdmin" class="workflow-list">
         <Link v-for="(action, index) in secondaryActions" :key="action.href" :href="action.href" class="workflow-row">...
     ```
   - Because `isAdmin` currently includes `operator`, `operator` is locked out of the secondary workflow list and instead gets the hardcoded admin overview cards (`/komunikasi` and `/pengaturan/keamanan`).
   - Requirement note: *"Pengguna (Admin) sedang mengedit menu khusus admin; jangan menimpa atau merusak bagian adminActions yang sedang dikerjakan admin."*
   - Therefore, lines 42–53 (`adminActions`) and lines 147–159 (`v-if="isAdmin"`) represent an active user work boundary that must be preserved without modification.

### 1.2 Route Audit & Broken/Unauthorized Links Identified

1. **Broken Link in `principalActions` (Line 61)**:
   - Path in `principalActions`: `href: '/penilaian/laporan'`.
   - Cross-referencing against `php artisan route:list`:
     - Registered route: `/laporan` (`reporting.index`, controller `ReportingController@index`).
     - Subroutes: `/laporan/nilai`, `/laporan/presensi`, `/laporan/sikap`, `/laporan/tugas`.
     - Route `/penilaian/laporan` **DOES NOT EXIST** anywhere in the application routes. Clicking it results in HTTP 404 Not Found.
     - Note that in `adminActions` (Line 49), the route is correctly set to `/laporan`.

2. **Forbidden (403) Link in `studentActions` (Line 68)**:
   - Path in `studentActions`: `{ title: 'Presensi saya', href: '/akademik/presensi', ... }`.
   - Inspection of `app/Modules/Academic/Interface/Routes/web.php` line 54:
     ```php
     Route::get('presensi', [AttendanceController::class, 'index'])->middleware('role:teacher,homeroom')->name('attendance.index');
     ```
   - Inspection of `app/Modules/Academic/Application/Policies/AttendancePolicy.php` line 26:
     ```php
     public function viewAny(User $actor): bool {
         return $actor->hasPermission(PermissionEnum::ACADEMIC_ATTENDANCE_RECORD->value)
             || $actor->hasRole('operator')
             || $actor->hasRole('principal')
             || $actor->hasRole('teacher')
             || $actor->hasRole('homeroom');
     }
     ```
   - Role `student` is explicitly forbidden from `/akademik/presensi` by both route middleware (`role:teacher,homeroom`) and policy authorization. Clicking this link produces HTTP 403 Forbidden.
   - Conversely, student attendance and study tracking is natively handled within `/pembelajaran/progress` (`LearningProgressController@index`), where lines 65–73 aggregate `personalAttendance` (`present`, `sick`, `permitted`, `absent`, `percentage`) specifically for students.

### 1.3 Backend Role Metrics in `routes/web.php` (Lines 40–68)
The backend endpoint `GET /` (`routes/web.php:21`) injects `$stats['role_metrics']` into Inertia props:
- For `['admin', 'operator', 'principal']`:
  - `students`: Active students count
  - `active_users`: Active users count
  - `recent_announcements`: Announcements in last 30 days
  - `active_exams`: Exams published / results published
  - `teachers`: Active teachers count
  - `active_classes`: Active class groups in current academic year
- For `['teacher', 'homeroom']`:
  - `teaching_assignments`: Count of assignments for user in active term
  - `active_questions`: Question count owned by user
  - `upcoming_exams`: Draft & published exams created by user
  - `recorded_scores`: Scores recorded by user
- For `student`:
  - `active_enrollments`: Active enrollments count
  - `upcoming_exams`: Exams awaiting completion
  - `completed_exams`: Submitted or graded exam attempts
  - `recorded_scores`: Final scores count

### 1.4 Baseline Test Status
- `php artisan test`: 170 passed, 959 assertions (11.45s) — Green 100%.
- `npm run typecheck` (`vue-tsc --noEmit`): Exited 0 with no errors.
- `npm run build` (`vite build`): Exited 0, built cleanly in 1.98s.

---

## 2. Logic Chain

```
[Observation 1.1: adminActions actively edited by admin user]
   │
   ├──> Isolation Boundary: adminActions definition (Dashboard.vue:42-53) and admin overview grid (Dashboard.vue:147-159) must remain intact.
   │
   └──> Decouple isAdmin: isAdmin = computed(() => role.value === 'admin')
        This frees operator from being forced into admin's view and allows operator to have dedicated operatorActions.

[Observation 1.1 & R2: homeroom bundled into isTeacher]
   │
   ├──> Decouple homeroom: isHomeroom = computed(() => role.value === 'homeroom')
   │    isTeacher = computed(() => role.value === 'teacher')
   │
   └──> Define homeroomActions:
        Includes teacher workflows + homeroom specific tools:
        - Dashboard Perwalian (/wali-kelas)
        - Catatan Observasi Siswa (/wali-kelas/catatan)
        - Kedisiplinan & Prestasi (/wali-kelas/kedisiplinan)
        - WhatsApp Orang Tua (/komunikasi/whatsapp)

[Observation 1.2: Broken link /penilaian/laporan in principalActions]
   │
   └──> Route /penilaian/laporan returns 404. Registered route is /laporan (ReportingController@index).
        Fix: Change href in principalActions to /laporan.

[Observation 1.2: Forbidden link /akademik/presensi in studentActions]
   │
   └──> Route /akademik/presensi returns 403 for students (role:teacher,homeroom middleware).
        Student attendance & progress is located at /pembelajaran/progress.
        Fix: Replace /akademik/presensi with Ruang Belajar (/pembelajaran), Jadwal Pelajaran (/akademik/jadwal), and Progress Belajar (/pembelajaran/progress).

[Observation 1.3: Alignment of role metrics and focus copy]
   │
   └──> Map available backend metrics to focus and quick actions for each non-admin role:
        - operator: focusMetric active_classes, metricItems (students, teachers, active_classes, active_users)
        - homeroom: focusMetric teaching_assignments, metricItems (teaching_assignments, active_questions, upcoming_exams, recorded_scores)
        - teacher: focusMetric teaching_assignments, metricItems (teaching_assignments, active_questions, upcoming_exams, recorded_scores)
        - principal: focusMetric students, metricItems (students, teachers, active_exams, recent_announcements)
        - student: focusMetric active_enrollments, metricItems (active_enrollments, upcoming_exams, completed_exams, recorded_scores)
```

---

## 3. Caveats

1. **Admin Customization Scope**:
   The admin user is actively modifying admin actions. While `adminActions` is isolated, any global changes to the layout or shared CSS classes must not disrupt the admin's view. Retaining `v-if="isAdmin"` for the announcement and login card layout ensures full backwards compatibility.
2. **Backend Metrics Schema**:
   `routes/web.php` provides specific metrics per role group (`['admin', 'operator', 'principal']`, `['teacher', 'homeroom']`, `['student']`). The frontend dashboard computed `metricItems` must strictly use existing keys (`students`, `teachers`, `active_classes`, `recent_announcements`, `active_exams`, `active_users`, `teaching_assignments`, `active_questions`, `upcoming_exams`, `recorded_scores`, `active_enrollments`, `completed_exams`). No backend query additions are required.
3. **Sidebar Coordination**:
   This report covers `resources/js/Pages/Dashboard.vue`. Navigation items in `resources/js/components/SidebarNavigation.vue` are simultaneously surveyed by Explorer 1. The route names and URLs used in both components must stay 100% harmonized.

---

## 4. Conclusion & Actionable Specifications

### 4.1 Strict Boundary Rule for `adminActions`
To prevent overwriting or breaking admin-specific work:
- **DO NOT TOUCH** lines 42–53:
  ```ts
  const adminActions = [ ... ];
  ```
- **DO NOT TOUCH** lines 147–159:
  ```html
  <div v-if="isAdmin" class="section-heading">...</div>
  <div v-if="isAdmin" class="admin-overview-grid">...</div>
  ```
- **ONLY UPDATE** `isAdmin` definition to isolate admin from operator:
  ```ts
  const isAdmin = computed(() => role.value === 'admin');
  ```

### 4.2 Proposed Action Arrays for Non-Admin Roles

#### 1. `operatorActions` (Dedicated for Operator Sekolah)
```ts
const operatorActions = [
    { title: 'Data siswa & rombel', description: 'Kelola data pokok siswa, import, dan penempatan kelas.', href: '/akademik/siswa', icon: 'M4 5.5A2.5 2.5 0 016.5 3h11A2.5 2.5 0 0120 5.5v13a2.5 2.5 0 01-2.5 2.5h-11A2.5 2.5 0 014 18.5v-13zM8 7h8M8 11h8M8 15h5' },
    { title: 'Penugasan guru', description: 'Atur data guru aktif dan penugasan mengajar semester.', href: '/akademik/guru', icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857' },
    { title: 'Rombongan belajar', description: 'Kelola rombel, kuota kelas, dan penetapan wali kelas.', href: '/akademik/kelas', icon: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1' },
    { title: 'Jadwal pelajaran', description: 'Susun jadwal mingguan, cek bentrok, dan terbitkan jadwal.', href: '/akademik/jadwal', icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z' },
    { title: 'Bank soal & CBT', description: 'Tinjau katalog soal, paket ujian, dan kesiapan asesmen.', href: '/penilaian/bank-soal', icon: 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18 5.754 18 7.5 18s3.332.477 4.5 1.253' },
    { title: 'Skema penilaian', description: 'Atur komponen nilai, bobot evaluasi, dan format rapor.', href: '/penilaian/skema', icon: 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z' },
    { title: 'Kelola pengguna', description: 'Atur akun siswa, guru, dan staf sekolah.', href: '/pengaturan/pengguna', icon: 'M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2m6-10a4 4 0 100-8 4 4 0 000 8zm14 10v-2a3 3 0 00-3-3.87M16 3.13a4 4 0 010 7.75' },
    { title: 'Identitas sekolah', description: 'Periksa profil kelembagaan dan konfigurasi dasar.', href: '/pengaturan/sekolah', icon: 'M12 15.5a3.5 3.5 0 100-7 3.5 3.5 0 000 7zM19.4 15a1.7 1.7 0 00.34 1.88l.06.06-1.9 1.9-.06-.06a1.7 1.7 0 00-1.88-.34 1.7 1.7 0 00-1.03 1.56V20h-2.68v-.09a1.7 1.7 0 00-1.03-1.56 1.7 1.7 0 00-1.88.34l-.06.06-1.9-1.9.06-.06A1.7 1.7 0 007.8 15a1.7 1.7 0 00-1.56-1.03H6v-2.68h.24A1.7 1.7 0 007.8 10a1.7 1.7 0 00-.34-1.88L7.4 8.06l1.9-1.9.06.06a1.7 1.7 0 001.88.34 1.7 1.7 0 001.88-.34l.06-.06 1.9 1.9-.06.06A1.7 1.7 0 0019.4 15z' },
];
```

#### 2. `homeroomActions` (Dedicated for Wali Kelas)
```ts
const homeroomActions = [
    { title: 'Dashboard perwalian', description: 'Pantau ringkasan kelas binaan, absensi, dan profil siswa.', href: '/wali-kelas', icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z' },
    { title: 'Catatan observasi & BK', description: 'Catat pembinaan karakter dan koordinasi guru bimbingan konseling.', href: '/wali-kelas/catatan', icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4' },
    { title: 'Kedisiplinan & prestasi', description: 'Rekam poin prestasi, catatan dispensasi, dan penanganan insiden.', href: '/wali-kelas/kedisiplinan', icon: 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z' },
    { title: 'WhatsApp orang tua', description: 'Kirim notifikasi presensi dan pengumuman ke kontak orang tua.', href: '/komunikasi/whatsapp', icon: 'M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z' },
    { title: 'Input nilai pelajaran', description: 'Lengkapi capaian belajar dan tugas pada kelas yang Anda ampu.', href: '/penilaian/skor', icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2m-2-2a2 2 0 00-2 2v2h6V5a2 2 0 00-2-2h-2zm-1 9h4m-4 4h4' },
    { title: 'Penilaian sikap', description: 'Evaluasi catatan karakter dan sikap kelas binaan.', href: '/penilaian/sikap', icon: 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z' },
    { title: 'Rapor kelas', description: 'Tinjau rekapan nilai rapor dan kesiapan cetak hasil belajar.', href: '/penilaian/rapor', icon: 'M5 4h14v16H5zM8 8h8M8 12h8M8 16h5' },
    { title: 'Presensi kelas', description: 'Catat dan verifikasi kehadiran harian siswa.', href: '/akademik/presensi', icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2m-4 0a2 2 0 104 0 2 2 0 00-4 0zm-1 9 2 2 4-4' },
];
```

#### 3. `teacherActions` (Enriched for Guru Pengajar)
```ts
const teacherActions = [
    { title: 'Ruang belajar LMS', description: 'Kelola materi pembelajaran, modul, dan penugasan kelas.', href: '/pembelajaran', icon: 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253' },
    { title: 'Presensi siswa', description: 'Catat kehadiran tatap muka dan tinjau rekap pertemuan.', href: '/akademik/presensi', icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2m-4 0a2 2 0 104 0 2 2 0 00-4 0zm-1 9 2 2 4-4' },
    { title: 'Jurnal mengajar', description: 'Dokumentasikan agenda pembelajaran dan catatan kelas harian.', href: '/akademik/jurnal', icon: 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18 5.754 18 7.5 18s3.332.477 4.5 1.253' },
    { title: 'Input nilai', description: 'Lengkapi capaian belajar, tugas harian, dan remedial siswa.', href: '/penilaian/skor', icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2m-2-2a2 2 0 00-2 2v2h6V5a2 2 0 00-2-2h-2zm-1 9h4m-4 4h4' },
    { title: 'Penilaian sikap', description: 'Catat observasi sikap dan capaian profil pelajar.', href: '/penilaian/sikap', icon: 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z' },
    { title: 'Bank soal cerdas', description: 'Susun butir soal, paket asesmen, dan berbagi bersama rekan guru.', href: '/penilaian/bank-soal', icon: 'M5 4h14v16H5zM8 8h8M8 12h8M8 16h5' },
    { title: 'Ujian CBT', description: 'Jadwalkan ujian online dan pantau langsung pengerjaan siswa.', href: '/ujian', icon: 'M6 3h12v18H6zM9 7h6M9 11h6M9 15h4' },
    { title: 'Kelas daring', description: 'Buka sesi tatap muka interaktif dan catat presensi digital.', href: '/akademik/kelas-daring', icon: 'M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z' },
];
```

#### 4. `principalActions` (Fixed Route & Expanded)
```ts
const principalActions = [
    { title: 'Pusat laporan sekolah', description: 'Pantau rekapitulasi nilai, presensi, dan ringkasan akademik.', href: '/laporan', icon: 'M9 17v-2m3 2v-4m3 4v-6m4 10H5a2 2 0 01-2-2V5a2 2 0 012-2h14v14z' },
    { title: 'Analisis asesmen', description: 'Tinjau analisis butir soal, sebaran nilai, dan ketuntasan ujian.', href: '/penilaian/analisis', icon: 'M3 3v18h18M8 15l4-4 4 3 5-7' },
    { title: 'Monitoring siswa', description: 'Pantau sebaran peserta didik aktif dan riwayat siklus siswa.', href: '/akademik/siswa', icon: 'M4 5.5A2.5 2.5 0 016.5 3h11A2.5 2.5 0 0120 5.5v13a2.5 2.5 0 01-2.5 2.5h-11A2.5 2.5 0 014 18.5v-13zM8 7h8M8 11h8M8 15h5' },
    { title: 'Tenaga pendidik', description: 'Tinjau data guru, beban mengajar, dan penugasan akademik.', href: '/akademik/guru', icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857' },
    { title: 'Jadwal pelajaran', description: 'Periksa distribusi jadwal belajar dan keterisian rombel.', href: '/akademik/jadwal', icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z' },
    { title: 'Komunikasi sekolah', description: 'Tinjau pengumuman dan agenda resmi warga sekolah.', href: '/komunikasi', icon: 'M4 5h16v11H8l-4 4V5zm4 4h8m-8 3h5' },
    { title: 'Keamanan & log akses', description: 'Pantau aktivitas login dan stabilitas keamanan sistem.', href: '/pengaturan/keamanan', icon: 'M12 11c1.657 0 3-1.343 3-3V5a3 3 0 00-6 0v3c0 1.657 1.343 3 3 3zm0 0v2m-7 8h14a2 2 0 002-2v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2z' },
];
```
*(Notice: line 1 changes `/penilaian/laporan` to `/laporan`, eliminating the 404 error).*

#### 5. `studentActions` (Fixed 403 Route & Complete LMS Integration)
```ts
const studentActions = [
    { title: 'Ruang belajar LMS', description: 'Akses materi pelajaran, modul ajar, dan kumpulkan tugas kelas.', href: '/pembelajaran', icon: 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253' },
    { title: 'Jadwal pelajaran', description: 'Lihat agenda belajar harian, waktu sesi, dan ruang kelas.', href: '/akademik/jadwal', icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z' },
    { title: 'Ujian CBT', description: 'Kerjakan asesmen online yang siap diikuti dan tinjau hasilnya.', href: '/ujian', icon: 'M6 3h12v18H6zM9 7h6M9 11h6M9 15h4' },
    { title: 'Rapor & nilai saya', description: 'Tinjau capaian hasil belajar dan nilai semester aktif.', href: '/penilaian/rapor', icon: 'M5 4h14v16H5zM8 8h8M8 12h8M8 16h5' },
    { title: 'Progress belajar mandiri', description: 'Pantau ketuntasan materi, tugas terkirim, dan persentase kehadiran.', href: '/pembelajaran/progress', icon: 'M9 17v-6m4 6V7m4 10V4M5 21h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z' },
    { title: 'Diskusi & chat kelas', description: 'Berinteraksi dengan guru dan teman dalam ruang kelas.', href: '/komunikasi/chat', icon: 'M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 4v-4z' },
    { title: 'Kelas daring', description: 'Masuk ke ruang pertemuan virtual interaktif.', href: '/akademik/kelas-daring', icon: 'M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z' },
];
```
*(Notice: `/akademik/presensi` which triggered HTTP 403 Forbidden is replaced with legitimate student endpoints: `/pembelajaran`, `/akademik/jadwal`, and `/pembelajaran/progress` which includes personal attendance).*

### 4.3 Computed Mappings for Focus & Metrics

```ts
const actions = computed(() => {
    if (isAdmin.value) return adminActions;
    if (isOperator.value) return operatorActions;
    if (isPrincipal.value) return principalActions;
    if (isHomeroom.value) return homeroomActions;
    if (isTeacher.value) return teacherActions;
    if (isStudent.value) return studentActions;
    return fallbackActions;
});

const focusLabel = computed(() => {
    if (isAdmin.value) return 'Operasional sekolah';
    if (isOperator.value) return 'Administrasi akademik';
    if (isPrincipal.value) return 'Kinerja sekolah';
    if (isHomeroom.value) return 'Ruang perwalian';
    if (isTeacher.value) return 'Ruang mengajar';
    if (isStudent.value) return 'Perjalanan belajar';
    return 'Ruang kerja sekolah';
});

const focusTitle = computed(() => {
    if (isAdmin.value) return 'Jaga sekolah tetap bergerak.';
    if (isOperator.value) return 'Pusat pengelolaan data dan konfigurasi.';
    if (isPrincipal.value) return 'Lihat sekolah dari gambaran utuh.';
    if (isHomeroom.value) return 'Dampingi tumbuh kembang kelas binaan.';
    if (isTeacher.value) return 'Mulai dari pekerjaan yang paling penting.';
    if (isStudent.value) return 'Belajar dengan arah yang lebih jelas.';
    return 'Semua pekerjaan sekolah, dari satu tempat.';
});

const focusCopy = computed(() => {
    if (isAdmin.value) return 'Pantau warga sekolah, komunikasi, dan data akademik tanpa berpindah-pindah konteks.';
    if (isOperator.value) return 'Kelola master data siswa, guru, rombel, jadwal, dan skema penilaian secara terpadu.';
    if (isPrincipal.value) return 'Pantau denyut akademik, laporan capaian, dan kesiapan operasional dari satu ruang pimpinan.';
    if (isHomeroom.value) return 'Pantau kehadiran, catatan pembinaan, kedisiplinan, serta komunikasi langsung dengan orang tua siswa.';
    if (isTeacher.value) return 'Kelola materi pembelajaran, presensi, bank soal, dan evaluasi hasil belajar siswa.';
    if (isStudent.value) return 'Akses materi pelajaran, jadwal kelas, ujian CBT, dan pantau capaian belajarmu.';
    return 'Akses modul yang tersedia sesuai hak akses akun Anda.';
});

const focusMetric = computed(() => {
    if (isAdmin.value || isPrincipal.value) return roleMetrics.value.students;
    if (isOperator.value) return roleMetrics.value.active_classes;
    if (isTeacher.value || isHomeroom.value) return roleMetrics.value.teaching_assignments;
    if (isStudent.value) return roleMetrics.value.active_enrollments;
    return props.stats?.modules_ready;
});

const focusMetricLabel = computed(() => {
    if (isAdmin.value || isPrincipal.value) return 'siswa aktif';
    if (isOperator.value) return 'kelas aktif';
    if (isHomeroom.value) return 'tugas kelas';
    if (isTeacher.value) return 'kelas diampu';
    if (isStudent.value) return 'kelas aktif';
    return 'modul tersedia';
});

const metricItems = computed(() => {
    if (isAdmin.value) return [
        { label: 'Siswa aktif', value: roleMetrics.value.students, note: 'terdaftar di sekolah' },
        { label: 'Guru aktif', value: roleMetrics.value.teachers, note: 'tenaga pengajar' },
        { label: 'Kelas aktif', value: roleMetrics.value.active_classes, note: 'periode akademik aktif' },
        { label: 'Pengumuman', value: roleMetrics.value.recent_announcements, note: '30 hari terakhir' },
    ];
    if (isOperator.value) return [
        { label: 'Siswa aktif', value: roleMetrics.value.students, note: 'terdaftar di sekolah' },
        { label: 'Guru aktif', value: roleMetrics.value.teachers, note: 'tenaga pendidik' },
        { label: 'Kelas aktif', value: roleMetrics.value.active_classes, note: 'periode berjalan' },
        { label: 'Pengguna aktif', value: roleMetrics.value.active_users, note: 'akun warga sekolah' },
    ];
    if (isPrincipal.value) return [
        { label: 'Siswa aktif', value: roleMetrics.value.students, note: 'terdaftar di sekolah' },
        { label: 'Guru aktif', value: roleMetrics.value.teachers, note: 'tenaga pengajar' },
        { label: 'Ujian aktif', value: roleMetrics.value.active_exams, note: 'sedang berjalan' },
        { label: 'Pengumuman', value: roleMetrics.value.recent_announcements, note: '30 hari terakhir' },
    ];
    if (isHomeroom.value) return [
        { label: 'Kelas binaan/diampu', value: roleMetrics.value.teaching_assignments, note: 'periode aktif' },
        { label: 'Soal aktif', value: roleMetrics.value.active_questions, note: 'di bank soal' },
        { label: 'Ujian mendatang', value: roleMetrics.value.upcoming_exams, note: 'draft dan terbit' },
        { label: 'Nilai tersimpan', value: roleMetrics.value.recorded_scores, note: 'rekaman Anda' },
    ];
    if (isTeacher.value) return [
        { label: 'Kelas diampu', value: roleMetrics.value.teaching_assignments, note: 'periode aktif' },
        { label: 'Soal aktif', value: roleMetrics.value.active_questions, note: 'di bank soal' },
        { label: 'Ujian mendatang', value: roleMetrics.value.upcoming_exams, note: 'draft dan terbit' },
        { label: 'Nilai tersimpan', value: roleMetrics.value.recorded_scores, note: 'rekaman Anda' },
    ];
    if (isStudent.value) return [
        { label: 'Kelas aktif', value: roleMetrics.value.active_enrollments, note: 'periode berjalan' },
        { label: 'Ujian mendatang', value: roleMetrics.value.upcoming_exams, note: 'siap dikerjakan' },
        { label: 'Ujian selesai', value: roleMetrics.value.completed_exams, note: 'sudah dikumpulkan' },
        { label: 'Nilai tercatat', value: roleMetrics.value.recorded_scores, note: 'periode aktif' },
    ];
    return [
        { label: 'Periode aktif', value: activePeriod.value, note: 'konteks kerja saat ini' },
        { label: 'Modul tersedia', value: props.stats?.modules_ready, note: 'siap digunakan' },
        { label: 'Status akses', value: 'Aktif', note: roleLabel.value },
    ];
});
```

---

## 5. Verification Method

### 5.1 Verification Commands
The implementation must pass all of the following verification checks without regressions:

1. **Backend Unit & Feature Tests**:
   ```bash
   php artisan test
   ```
   *Expected*: 170+ tests passing, 0 failures.

2. **Frontend Type Checking**:
   ```bash
   npm run typecheck
   ```
   *Expected*: `vue-tsc --noEmit` exits with status code 0.

3. **Frontend Production Build**:
   ```bash
   npm run build
   ```
   *Expected*: `vite build` completes successfully, generating production chunks.

4. **Specific Route Verification Command**:
   ```bash
   php -r '
   $routes = ["/laporan", "/penilaian/analisis", "/akademik/siswa", "/akademik/guru", "/akademik/kelas", "/akademik/jadwal", "/pembelajaran", "/pembelajaran/progress", "/komunikasi", "/komunikasi/chat", "/komunikasi/whatsapp", "/wali-kelas", "/wali-kelas/catatan", "/wali-kelas/kedisiplinan", "/penilaian/skor", "/penilaian/sikap", "/penilaian/rapor", "/penilaian/bank-soal", "/ujian"];
   $all = json_decode(shell_exec("php artisan route:list --json"), true);
   $uris = array_map(fn($r) => "/".$r["uri"], $all);
   foreach ($routes as $route) {
       $found = in_array($route, $uris);
       echo ($found ? "✅" : "❌") . " $route\n";
   }
   '
   ```
   *Expected*: All 19 routes return ✅. Route `/penilaian/laporan` is not in this list as it must be replaced by `/laporan`.

### 5.2 Invalidation Conditions
This survey and its proposed specifications are invalidated if:
1. `adminActions` definition (lines 42–53) is edited or renamed.
2. Any route listed in the proposed actions requires permissions not assigned to that role in `app/Modules/*/Application/Policies/*` or `RoleMatrixAccessTest`.
3. An action link results in a 404 or 403 response when accessed by an authenticated user of that role.
