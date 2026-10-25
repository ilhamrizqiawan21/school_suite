<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Academic\Infrastructure\Models\AcademicYear;
use App\Modules\Academic\Infrastructure\Models\AttendanceRecord;
use App\Modules\Academic\Infrastructure\Models\ClassGroup;
use App\Modules\Academic\Infrastructure\Models\Enrollment;
use App\Modules\Academic\Infrastructure\Models\LessonSession;
use App\Modules\Academic\Infrastructure\Models\Schedule;
use App\Modules\Academic\Infrastructure\Models\TeachingAssignment;
use App\Modules\Academic\Infrastructure\Models\TeachingJournal;
use App\Modules\Academic\Infrastructure\Models\Term;
use App\Modules\Core\Infrastructure\Models\School;
use Illuminate\Database\Seeder;

class DemoAcademicContentSeeder extends Seeder
{
    public function run(): void
    {
        $schoolId = (int) AcademicYear::where('is_active', true)->value('school_id');
        $school = School::findOrFail($schoolId);
        $term = Term::whereHas('academicYear', fn ($query) => $query->where('school_id', $school->id))
            ->where('is_active', true)
            ->firstOrFail();

        $classes = ClassGroup::where('school_id', $school->id)
            ->where('academic_year_id', $term->academic_year_id)
            ->orderBy('grade_level')
            ->orderBy('name')
            ->get();

        $assignments = TeachingAssignment::where('term_id', $term->id)
            ->with(['classGroup', 'subject', 'teacher'])
            ->get()
            ->keyBy(fn (TeachingAssignment $assignment): string => implode(':', [
                $assignment->class_group_id,
                $assignment->subject_id,
            ]));

        $slots = [
            ['day' => 1, 'start' => '07:00', 'end' => '08:30', 'subject' => 'MAT-01', 'room' => 'R-01'],
            ['day' => 1, 'start' => '08:45', 'end' => '10:15', 'subject' => 'BIN-01', 'room' => 'R-01'],
            ['day' => 2, 'start' => '07:00', 'end' => '08:30', 'subject' => 'IPA-01', 'room' => 'Lab IPA'],
            ['day' => 3, 'start' => '08:45', 'end' => '10:15', 'subject' => 'BIG-01', 'room' => 'R-02'],
            ['day' => 4, 'start' => '07:00', 'end' => '08:30', 'subject' => 'INF-01', 'room' => 'Lab Komputer'],
            ['day' => 5, 'start' => '08:45', 'end' => '10:15', 'subject' => 'PJK-01', 'room' => 'Lapangan'],
        ];

        $schedules = collect();
        foreach ($classes->take(2) as $class) {
            foreach ($slots as $slot) {
                $subjectId = $assignments
                    ->first(fn (TeachingAssignment $assignment): bool => $assignment->class_group_id === $class->id
                        && $assignment->subject?->code === $slot['subject'])
                    ?->subject_id;

                $assignment = $subjectId
                    ? $assignments->get("{$class->id}:{$subjectId}")
                    : null;

                if (! $assignment) {
                    continue;
                }

                $schedules->push(Schedule::firstOrCreate(
                    [
                        'school_id' => $school->id,
                        'term_id' => $term->id,
                        'class_group_id' => $class->id,
                        'subject_id' => $assignment->subject_id,
                        'teacher_id' => $assignment->teacher_id,
                        'day_of_week' => $slot['day'],
                        'start_time' => $slot['start'],
                    ],
                    [
                        'end_time' => $slot['end'],
                        'room' => $slot['room'],
                        'status' => 'published',
                    ]
                ));
            }
        }

        $today = now()->toDateString();
        $todayDay = (int) now()->isoWeekday();
        $todaySchedules = $schedules->filter(fn (Schedule $schedule): bool => $schedule->day_of_week === $todayDay);

        if ($todaySchedules->isEmpty()) {
            $todaySchedules = $schedules->take(2);
        }

        foreach ($todaySchedules as $schedule) {
            $assignment = $assignments->get("{$schedule->class_group_id}:{$schedule->subject_id}");
            $session = LessonSession::firstOrCreate(
                ['schedule_id' => $schedule->id, 'date' => $today],
                [
                    'school_id' => $school->id,
                    'teaching_assignment_id' => $assignment?->id,
                    'term_id' => $term->id,
                    'class_group_id' => $schedule->class_group_id,
                    'subject_id' => $schedule->subject_id,
                    'teacher_id' => $schedule->teacher_id,
                    'start_time' => $schedule->start_time,
                    'end_time' => $schedule->end_time,
                    'meeting_number' => 4,
                    'status' => 'completed',
                ]
            );

            $enrollments = Enrollment::where('term_id', $term->id)
                ->where('class_group_id', $schedule->class_group_id)
                ->orderBy('roll_number')
                ->get();

            foreach ($enrollments->take(8) as $index => $enrollment) {
                AttendanceRecord::firstOrCreate(
                    [
                        'enrollment_id' => $enrollment->id,
                        'lesson_session_id' => $session->id,
                        'date' => $today,
                        'type' => 'session',
                    ],
                    [
                        'school_id' => $school->id,
                        'status' => $index === 3 ? 'sick' : 'present',
                        'notes' => $index === 3 ? 'Izin sakit dari orang tua.' : null,
                        'recorded_by_user_id' => $schedule->teacher?->user_id,
                    ]
                );
            }

            TeachingJournal::firstOrCreate(
                ['lesson_session_id' => $session->id],
                [
                    'topic' => 'Operasi bilangan dan pemecahan masalah',
                    'learning_objectives' => 'Siswa mampu menerapkan konsep pada soal kontekstual.',
                    'notes' => 'Diskusi kelompok berjalan aktif; beberapa siswa perlu penguatan pada langkah perhitungan.',
                    'attendance_summary' => ['present' => 7, 'sick' => 1, 'permitted' => 0, 'absent' => 0],
                ]
            );
        }
    }
}
