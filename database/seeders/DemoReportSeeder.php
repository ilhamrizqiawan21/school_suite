<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Academic\Application\Services\AcademicQueryService;
use App\Modules\Academic\Infrastructure\Models\Enrollment;
use App\Modules\Academic\Infrastructure\Models\Student;
use App\Modules\Academic\Infrastructure\Models\TeachingAssignment;
use App\Modules\Assessment\Application\Services\ReportPublishingService;
use App\Modules\Assessment\Infrastructure\Models\AssessmentScheme;
use App\Modules\Assessment\Infrastructure\Models\ReportSnapshot;
use App\Modules\Core\Infrastructure\Models\School;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/** Additive report examples for fictional students in the local pilot school. */
class DemoReportSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->command?->warn('Data rapor demo hanya tersedia di lingkungan local/testing.');

            return;
        }

        $school = School::where('npsn', '20109988')->first();
        $term = $school ? app(AcademicQueryService::class)->getActiveTerm((int) $school->id) : null;
        $class = $term ? app(AcademicQueryService::class)->getClassGroups((int) $school->id, (int) $term->academic_year_id)->first() : null;
        if (! $class) {
            $this->command?->warn('Sekolah pilot, semester aktif, atau kelas belum tersedia.');

            return;
        }

        $assignments = TeachingAssignment::with(['subject', 'teacher'])->where('term_id', $term->id)->where('class_group_id', $class->id)->get()->unique('subject_id')->values();
        if ($assignments->isEmpty()) {
            $this->command?->warn('Penugasan mata pelajaran kelas pilot belum tersedia.');

            return;
        }
        $activeScheme = AssessmentScheme::where('school_id', $school->id)->where('is_active', true)->first();
        $publisher = User::where('school_id', $school->id)->where('email', 'admin@pilot.sch.id')->value('id');

        DB::transaction(function () use ($school, $term, $class, $assignments, $activeScheme, $publisher): void {
            foreach (['draft', 'reviewed', 'published'] as $index => $status) {
                $student = Student::firstOrCreate(['school_id' => $school->id, 'nis' => 'DEMO-RAPOR-'.($index + 1)], [
                    'name' => '[DEMO] Siswa Rapor '.($index + 1), 'gender' => $index % 2 ? 'P' : 'L', 'status' => 'active',
                ]);
                $enrollment = Enrollment::firstOrCreate(['student_id' => $student->id, 'term_id' => $term->id], [
                    'class_group_id' => $class->id, 'status' => 'active',
                ]);
                if (ReportSnapshot::where('enrollment_id', $enrollment->id)->where('term_id', $term->id)->exists()) {
                    continue;
                }
                $scheme = ['name' => $activeScheme?->name ?? 'Skema contoh rapor', 'code' => $activeScheme?->code ?? 'DEMO-RAPOR', 'version' => $activeScheme?->version ?? 1];
                $grades = $assignments->map(function ($assignment, $subjectIndex) use ($index): array {
                    $score = $subjectIndex === 0 && $index === 0 ? 68 : 78 + (($subjectIndex * 3 + $index * 5) % 19);

                    return ['subject_id' => $assignment->subject_id, 'subject_name' => $assignment->subject->name, 'subject_code' => $assignment->subject->code, 'teacher_name' => $assignment->teacher?->name ?? 'Guru mata pelajaran', 'passing_grade' => 75, 'competency_description' => '[DEMO] Menunjukkan pemahaman konsep dan mampu menyelesaikan latihan '.strtolower($assignment->subject->name).' secara mandiri.', 'final_score' => $score, 'predicate' => $score >= 90 ? 'A' : ($score >= 80 ? 'B' : ($score >= 75 ? 'C' : 'D')), 'is_passed' => $score >= 75];
                })->all();
                $attendance = ['present' => 94 + $index, 'sick' => 2, 'permitted' => 1, 'absent' => $index === 0 ? 1 : 0];
                $activities = [['name' => 'Pramuka', 'predicate' => 'Baik', 'description' => '[DEMO] Aktif bekerja sama dan mengikuti latihan rutin.'], ['name' => 'Olahraga', 'predicate' => 'Baik', 'description' => '[DEMO] Menunjukkan kedisiplinan dan sportivitas.']];
                $notes = ['achievements' => [['title' => '[DEMO] Lomba literasi', 'level' => 'Sekolah', 'rank' => 'Juara 2']], 'homeroom_notes' => '[DEMO] Pertahankan keteraturan belajar dan keberanian bertanya. Latih kembali materi yang belum dikuasai.', 'attitudes' => ['spiritual' => ['predicate' => 'Baik', 'description' => 'Menunjukkan sikap syukur dan menghargai kegiatan keagamaan.'], 'social' => ['predicate' => 'Baik', 'description' => 'Bekerja sama dengan teman dan bertanggung jawab terhadap tugas kelas.']]];
                $checksum = app(ReportPublishingService::class)->calculateChecksum($scheme, $grades, $attendance, $notes);
                ReportSnapshot::create(['school_id' => $school->id, 'enrollment_id' => $enrollment->id, 'term_id' => $term->id, 'class_group_id' => $class->id, 'student_id' => $student->id, 'status' => $status, 'scheme_snapshot' => $scheme, 'grades_snapshot' => $grades, 'attendance_snapshot' => $attendance, 'notes_snapshot' => $notes, 'extracurricular_snapshot' => $activities, 'checksum' => $checksum, 'published_at' => $status === 'published' ? now() : null, 'published_by_user_id' => $status === 'published' ? $publisher : null]);
            }
        });
        $this->command?->info('Contoh rapor demo tersedia untuk kelas '.$class->name.'.');
    }
}
