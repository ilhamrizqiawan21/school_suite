<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Academic\Application\Services\EnrollmentService;
use App\Modules\Academic\Application\Services\TeacherOperationsService;
use App\Modules\Academic\Infrastructure\Models\Enrollment;
use App\Modules\Academic\Infrastructure\Models\Schedule;
use App\Modules\Academic\Infrastructure\Models\Student;
use App\Modules\Academic\Infrastructure\Models\StudentLifecycleEvent;
use App\Modules\Academic\Infrastructure\Models\Teacher;
use App\Modules\Academic\Infrastructure\Models\TeacherAttendanceRecord;
use App\Modules\Academic\Infrastructure\Models\TeacherLeaveRequest;
use App\Modules\Academic\Infrastructure\Models\TeacherSubstituteAssignment;
use App\Modules\Academic\Infrastructure\Models\TeachingAssignment;
use App\Modules\Academic\Infrastructure\Models\Term;
use App\Modules\Assessment\Application\Services\ScoreService;
use App\Modules\Assessment\Infrastructure\Models\AssessmentComponent;
use App\Modules\Assessment\Infrastructure\Models\AssessmentScheme;
use App\Modules\Assessment\Infrastructure\Models\Score;
use App\Modules\Core\Infrastructure\Models\School;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Additive examples for the pilot school only; existing records are never updated. */
class DemoSchoolOperationsSeeder extends Seeder
{
    public function run(): void
    {
        $school = School::query()->where('npsn', '20109988')->first();
        $admin = $school ? User::query()->where('school_id', $school->id)->where('email', 'admin@pilot.sch.id')->first() : null;
        if (! $admin) {
            $this->command?->warn('Sekolah dan admin pilot belum tersedia; data operasional demo dilewati.');

            return;
        }

        DB::transaction(function () use ($admin): void {
            $this->seedTeachers($admin);
            $this->seedAdministration($admin);
            $this->seedHomeroom($admin);
            $this->seedStudentExamples($admin);
            $this->seedSubstitute($admin);
        });
    }

    private function seedTeachers(User $admin): void
    {
        $teachers = Teacher::query()->where('school_id', $admin->school_id)->where('is_active', true)
            ->whereHas('user', fn ($query) => $query->where('email', 'like', '%@pilot.sch.id'))
            ->orderBy('id')->get();

        foreach ($teachers as $index => $teacher) {
            TeacherAttendanceRecord::query()->whereDate('date', today())->firstOrCreate([
                'school_id' => $admin->school_id, 'teacher_id' => $teacher->id,
            ], [
                'date' => today()->toDateString(),
                'status' => 'present', 'notes' => '[DEMO] Contoh presensi guru.', 'recorded_by_user_id' => $admin->id,
            ]);

            if ($index >= 4) {
                continue;
            }

            $type = array_keys(TeacherLeaveRequest::TYPES)[$index];
            $status = ['pending', 'approved', 'rejected', 'pending'][$index];
            $reason = '[DEMO] '.['Keperluan keluarga', 'Pemulihan kesehatan', 'Rencana cuti pribadi', 'Pelatihan pembelajaran'][$index];
            // Use distinct future dates and skip existing pending/approved requests that overlap.
            $date = today()->addDays(7 + $index * 3);
            if ($date->isSunday()) {
                $date->addDay();
            }
            $identity = ['school_id' => $admin->school_id, 'teacher_id' => $teacher->id, 'reason' => $reason];
            if (TeacherLeaveRequest::query()->where($identity)->exists()) {
                continue;
            }
            if (TeacherLeaveRequest::query()->where('school_id', $admin->school_id)->where('teacher_id', $teacher->id)
                ->whereIn('status', ['pending', 'approved'])->whereDate('start_date', '<=', $date)
                ->whereDate('end_date', '>=', $date)->exists()) {
                continue;
            }
            if ($status === 'approved' && TeacherAttendanceRecord::query()->where('school_id', $admin->school_id)
                ->where('teacher_id', $teacher->id)->whereDate('date', $date)->exists()) {
                continue;
            }
            $leave = TeacherLeaveRequest::query()->create($identity + [
                'type' => $type, 'start_date' => $date->toDateString(), 'end_date' => $date->toDateString(), 'status' => $status,
                'reviewed_by_user_id' => $status === 'pending' ? null : $admin->id,
                'reviewed_at' => $status === 'pending' ? null : now(),
                'review_note' => match ($status) {
                    'approved' => '[DEMO] Disetujui untuk contoh rekap izin dan presensi.',
                    'rejected' => '[DEMO] Silakan ajukan kembali pada tanggal alternatif.',
                    default => null,
                },
            ]);
            if ($leave->status === 'approved') {
                TeacherAttendanceRecord::query()->whereDate('date', $date)->firstOrCreate([
                    'school_id' => $admin->school_id, 'teacher_id' => $teacher->id,
                ], [
                    'date' => $date->toDateString(),
                    'status' => $type === 'sick' ? 'sick' : 'permitted',
                    'notes' => '[DEMO] '.TeacherLeaveRequest::TYPES[$type].' (disetujui)', 'recorded_by_user_id' => $admin->id,
                ]);
            }
        }
    }

    private function seedAdministration(User $admin): void
    {
        foreach (['Proyektor ruang belajar', 'Laptop administrasi', 'Pengeras suara kegiatan'] as $index => $name) {
            $assetId = $this->insertExample('facility_assets', [
                'school_id' => $admin->school_id, 'inventory_code' => 'DEMO-SAR-'.($index + 1),
            ], [
                'created_by' => $admin->id, 'name' => '[DEMO] '.$name, 'category' => 'Elektronik',
                'location' => 'Ruang sarana', 'condition' => 'good', 'status' => $index === 0 ? 'borrowed' : 'available',
                'notes' => '[DEMO] Aset contoh, bukan inventaris resmi.',
            ]);
            if ($index === 0) {
                $this->insertExample('facility_loans', ['school_id' => $admin->school_id, 'asset_id' => $assetId, 'notes' => '[DEMO] Peminjaman untuk kegiatan kelas.'], [
                    'recorded_by' => $admin->id, 'borrower_name' => 'Panitia demo', 'borrower_reference' => 'DEMO',
                    'borrowed_at' => today()->toDateString(), 'due_at' => today()->addDays(3)->toDateString(),
                ]);
            }
        }
        foreach (['incoming', 'outgoing'] as $index => $type) {
            $this->insertExample('correspondence_records', ['school_id' => $admin->school_id, 'agenda_number' => 'DEMO-SURAT-'.($index + 1)], [
                'created_by' => $admin->id, 'letter_type' => $type, 'subject' => '[DEMO] Undangan koordinasi kegiatan sekolah',
                'letter_number' => 'DEMO/001/'.($index + 1), 'letter_date' => today()->toDateString(),
                'received_at' => $type === 'incoming' ? today()->toDateString() : null,
                'sender' => 'Panitia demo', 'recipient' => 'Sekolah percontohan', 'status' => $type === 'incoming' ? 'received' : 'draft',
                'summary' => '[DEMO] Contoh surat tanpa dokumen lampiran.',
            ]);
        }
        $this->insertExample('admission_committee_members', ['school_id' => $admin->school_id, 'user_id' => $admin->id], ['assigned_by' => $admin->id]);
        foreach (['registered', 'documents_verified', 'accepted', 'rejected'] as $index => $status) {
            $this->insertExample('admission_applicants', ['school_id' => $admin->school_id, 'registration_no' => 'DEMO-PPDB-'.($index + 1)], [
                'created_by' => $admin->id, 'name' => '[DEMO] Calon Siswa '.($index + 1), 'gender' => $index % 2 === 0 ? 'L' : 'P',
                'previous_school' => 'SD Percontohan Demo', 'status' => $status, 'notes' => '[DEMO] Pendaftar fiktif tanpa identitas pribadi.',
            ]);
        }
        foreach (['assigned', 'in_progress', 'completed'] as $index => $status) {
            $this->insertExample('administration_staff_tasks', ['school_id' => $admin->school_id, 'title' => '[DEMO] '.['Pendataan perlengkapan kelas', 'Penyusunan arsip semester', 'Pemeriksaan ruang belajar'][$index]], [
                'created_by' => $admin->id, 'assignee_id' => $admin->id, 'category' => 'Administrasi', 'priority' => 'normal',
                'status' => $status, 'progress' => [0, 50, 100][$index], 'due_at' => now()->addDays(7),
                'completed_at' => $status === 'completed' ? now() : null, 'description' => '[DEMO] Tugas contoh untuk memeriksa alur penugasan.',
            ]);
        }
    }

    private function seedHomeroom(User $admin): void
    {
        $term = Term::query()->where('is_active', true)->whereHas('academicYear', fn ($query) => $query->where('school_id', $admin->school_id))->first();
        if (! $term) {
            return;
        }
        $enrollments = Enrollment::query()->where('term_id', $term->id)->where('status', 'active')
            ->whereHas('student', fn ($query) => $query->where('school_id', $admin->school_id)->whereHas('user', fn ($user) => $user->where('email', 'like', '%@pilot.sch.id')))
            ->orderBy('id')->limit(3)->get();
        foreach ($enrollments as $enrollment) {
            $identity = ['school_id' => $admin->school_id, 'term_id' => $term->id, 'student_id' => $enrollment->student_id];
            $this->insertExample('administration_student_ledger_details', ['school_id' => $admin->school_id, 'student_id' => $enrollment->student_id], [
                'father_name' => '[DEMO] Orang tua laki-laki', 'mother_name' => '[DEMO] Orang tua perempuan', 'previous_school' => 'SD Percontohan Demo',
            ]);
            $this->insertExample('attendance_dispensations', $identity + ['title' => '[DEMO] Izin kegiatan siswa'], [
                'submitted_by_user_id' => $admin->id, 'start_date' => today()->addDays(7)->toDateString(), 'end_date' => today()->addDays(7)->toDateString(),
                'type' => 'event', 'reason' => '[DEMO] Mengikuti kegiatan sekolah.', 'status' => 'pending',
            ]);
            $this->insertExample('student_notes', $identity + ['title' => '[DEMO] Perkembangan kegiatan belajar'], [
                'class_group_id' => $enrollment->class_group_id, 'author_id' => $admin->id,
                'content' => '[DEMO] Siswa aktif berdiskusi dan perlu menjaga jadwal belajar.', 'category' => 'academic', 'access_level' => 'internal',
            ]);
            $this->insertExample('student_incidents', $identity + ['title' => '[DEMO] Keterlambatan kegiatan'], [
                'class_group_id' => $enrollment->class_group_id, 'recorded_by_user_id' => $admin->id,
                'incident_date' => today()->toDateString(), 'description' => '[DEMO] Kasus fiktif untuk memeriksa tindak lanjut.',
                'category' => 'Kedisiplinan', 'penalty_points' => 0, 'status' => 'resolved', 'follow_up_action' => '[DEMO] Pembinaan selesai.',
            ]);
            $this->insertExample('student_achievements', $identity + ['title' => '[DEMO] Lomba literasi sekolah'], [
                'achievement_type' => 'academic', 'level' => 'school', 'rank' => 'Juara 2', 'event_date' => today()->toDateString(),
                'organizer' => 'Panitia demo', 'notes' => '[DEMO] Prestasi fiktif.',
            ]);
            $this->insertExample('assistance_records', $identity + ['summary' => '[DEMO] Pendampingan pengaturan waktu belajar.'], [
                'facilitator_id' => $admin->id, 'date' => today()->toDateString(), 'type' => 'counseling',
                'access_level' => 'internal', 'status' => 'completed', 'agreed_solution' => '[DEMO] Menyusun jadwal belajar mingguan.',
            ]);
        }
    }

    private function seedStudentExamples(User $admin): void
    {
        $assignment = TeachingAssignment::query()->whereHas('teacher', fn ($query) => $query->where('school_id', $admin->school_id))
            ->whereHas('term', fn ($query) => $query->where('is_active', true))->with('classGroup')->first();
        $scheme = AssessmentScheme::query()->where('school_id', $admin->school_id)->where('is_active', true)->first();
        if (! $assignment || ! $scheme) {
            return;
        }
        foreach (['REMEDIAL', 'LIFECYCLE'] as $kind) {
            $student = Student::query()->firstOrCreate(['school_id' => $admin->school_id, 'nis' => 'DEMO-'.$kind], [
                'name' => '[DEMO] Siswa '.($kind === 'REMEDIAL' ? 'Remedial' : 'Riwayat Keluar'), 'gender' => 'L', 'status' => 'active',
            ]);
            $enrollment = Enrollment::query()->where('student_id', $student->id)->where('term_id', $assignment->term_id)->first();
            if (! $enrollment) {
                $enrollment = app(EnrollmentService::class)->enrollStudent((int) $assignment->term_id, (int) $assignment->class_group_id, (int) $student->id, actorId: (int) $admin->id);
            }
            if ($kind === 'LIFECYCLE') {
                if (! StudentLifecycleEvent::query()->where('student_id', $student->id)->exists() && $student->wasRecentlyCreated) {
                    app(EnrollmentService::class)->processLifecycle([
                        'enrollment_id' => $enrollment->id, 'action' => 'withdrawn', 'effective_date' => today()->toDateString(),
                        'notes' => '[DEMO] Riwayat keluar fiktif; tidak mengubah siswa lain.',
                    ], $admin);
                }

                continue;
            }
            $component = AssessmentComponent::query()->firstOrCreate(['scheme_id' => $scheme->id, 'code' => 'DEMO-REMEDIAL'], [
                'teaching_assignment_id' => $assignment->id, 'name' => '[DEMO] Latihan remedial', 'weight_percentage' => 0,
                'passing_grade' => 75, 'max_score' => 100, 'sequence' => 99,
            ]);
            if (! Score::query()->where('enrollment_id', $enrollment->id)->where('assessment_component_id', $component->id)->exists()) {
                app(ScoreService::class)->saveBatchScores((int) $admin->school_id, (int) $component->id, [[
                    'enrollment_id' => $enrollment->id, 'raw_score' => 60, 'remedial_score' => 85,
                    'notes' => '[DEMO] Nilai contoh khusus siswa fiktif remedial.',
                ]], $admin);
            }
        }
        $this->insertExample('user_notifications', ['user_id' => $admin->id, 'title' => '[DEMO] Data operasional siap ditinjau'], [
            'body' => '[DEMO] Tinjau izin guru, sarana, PPDB, dan contoh riwayat siswa melalui menu terkait.',
            'action_url' => null,
        ]);
    }

    private function seedSubstitute(User $admin): void
    {
        if (TeacherSubstituteAssignment::query()->where('school_id', $admin->school_id)->where('notes', '[DEMO] Riwayat pengganti guru yang dapat ditinjau.')->exists()) {
            return;
        }
        $service = app(TeacherOperationsService::class);
        $schedules = Schedule::query()->where('school_id', $admin->school_id)->where('status', 'published')
            ->whereHas('teacher.user', fn ($query) => $query->where('email', 'like', '%@pilot.sch.id'))->with('term')->get();
        foreach ($schedules as $schedule) {
            $date = today();
            while ($date->dayOfWeekIso !== $schedule->day_of_week) {
                $date->subDay();
            }
            if ($date->lt($schedule->term->start_date) || $date->gt($schedule->term->end_date)) {
                continue;
            }
            $attendance = TeacherAttendanceRecord::query()->where('school_id', $admin->school_id)->where('teacher_id', $schedule->teacher_id)->whereDate('date', $date)->first();
            if ($attendance && ! str_starts_with((string) $attendance->notes, '[DEMO]')) {
                continue;
            }
            $attendance ??= TeacherAttendanceRecord::query()->create([
                'school_id' => $admin->school_id, 'teacher_id' => $schedule->teacher_id, 'date' => $date->toDateString(),
                'status' => 'sick', 'notes' => '[DEMO] Presensi untuk contoh guru pengganti.', 'recorded_by_user_id' => $admin->id,
            ]);
            if ($attendance->status === 'present') {
                continue;
            }
            foreach ($service->availableSubstitutes($attendance, $schedule) as $teacher) {
                try {
                    $service->assignSubstitute($attendance, (int) $schedule->id, (int) $teacher->id, $admin, '[DEMO] Riwayat pengganti guru yang dapat ditinjau.');

                    return;
                } catch (ValidationException) {
                    continue;
                }
            }
        }
        $this->command?->warn('Contoh guru pengganti dilewati: belum ada jadwal/calon pengganti yang bebas benturan.');
    }

    /** @param array<string, mixed> $identity @param array<string, mixed> $defaults */
    private function insertExample(string $table, array $identity, array $defaults): int
    {
        $existing = DB::table($table)->where($identity)->value('id');

        return $existing ? (int) $existing : (int) DB::table($table)->insertGetId($identity + $defaults + ['created_at' => now(), 'updated_at' => now()]);
    }
}
