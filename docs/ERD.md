# ERD — School Suite

Diagram ini adalah model konseptual pilot; nama kolom final dikunci melalui migrasi dan ADR bila berubah material.

```mermaid
erDiagram
  SCHOOLS ||--o{ USERS : scopes
  SCHOOLS ||--o{ ACADEMIC_YEARS : owns
  ACADEMIC_YEARS ||--o{ TERMS : contains
  STUDENTS ||--o{ ENROLLMENTS : has
  CLASS_GROUPS ||--o{ ENROLLMENTS : contains
  TERMS ||--o{ ENROLLMENTS : scopes
  TEACHERS ||--o{ TEACHING_ASSIGNMENTS : receives
  SUBJECTS ||--o{ TEACHING_ASSIGNMENTS : assigns
  CLASS_GROUPS ||--o{ TEACHING_ASSIGNMENTS : schedules
  TEACHING_ASSIGNMENTS ||--o{ LESSON_SESSIONS : creates
  LESSON_SESSIONS ||--o{ ATTENDANCE_RECORDS : records
  ENROLLMENTS ||--o{ ATTENDANCE_RECORDS : belongs
  ASSESSMENT_SCHEMES ||--o{ ASSESSMENT_COMPONENTS : defines
  ASSESSMENT_COMPONENTS ||--o{ SCORES : receives
  ENROLLMENTS ||--o{ SCORES : owns
  ENROLLMENTS ||--o{ STUDENT_OBSERVATIONS : has
  ENROLLMENTS ||--o{ REPORT_SNAPSHOTS : publishes
  USERS ||--o{ AUDIT_EVENTS : acts
```

## Aturan data penting

- `students` adalah identitas orang; kelas saat ini ditentukan oleh `enrollments`, bukan kolom pada siswa.
- Semua data akademik memiliki referensi periode yang tidak berubah diam-diam.
- NIS/NISN adalah atribut pencocokan, bukan satu-satunya identitas internal.
- Nilai menyimpan komponen dan versi skema; rapor terbit menyimpan snapshot immutable.
- Absensi harian dan absensi pertemuan berbeda walaupun dapat direkap bersama.
- Catatan BK/privat memakai klasifikasi akses terpisah.
- Penghapusan data penting memakai status/arsip bila histori dibutuhkan; bukan cascade tanpa kajian.

## Entitas integrasi lanjutan

`device_connections`, `id_mappings`, `submission_inbox`, `idempotency_records`, `attachment_transfers`, dan `sync_audit_events`. Pemetaan tetap terkait instalasi sekolah dan pemilik, bukan hanya token/koneksi yang dapat berubah.

