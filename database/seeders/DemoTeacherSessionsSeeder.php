<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Academic\Infrastructure\Models\AttendanceRecord;
use App\Modules\Academic\Infrastructure\Models\Enrollment;
use App\Modules\Academic\Infrastructure\Models\LessonSession;
use App\Modules\Academic\Infrastructure\Models\OnlineAttendanceRecord;
use App\Modules\Academic\Infrastructure\Models\OnlineClassSession;
use App\Modules\Academic\Infrastructure\Models\Schedule;
use App\Modules\Academic\Infrastructure\Models\Teacher;
use App\Modules\Academic\Infrastructure\Models\TeachingAssignment;
use App\Modules\Academic\Infrastructure\Models\TeachingJournal;
use App\Modules\Academic\Infrastructure\Models\Term;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class DemoTeacherSessionsSeeder extends Seeder
{
    /**
     * Seed isolated, repeatable timetable and journal examples for Dewi.
     */
    public function run(): void
    {
        $user = User::query()->where('email', 'dewi@pilot.sch.id')->first();
        if (! $user) {
            $this->command?->warn('Akun demo Dewi tidak ditemukan; jadwal dan jurnal demo dilewati.');

            return;
        }

        $teacher = Teacher::query()->where('school_id', $user->school_id)->where('user_id', $user->id)->first();
        $term = Term::query()
            ->whereHas('academicYear', fn ($query) => $query->where('school_id', $user->school_id))
            ->where('is_active', true)
            ->first();

        if (! $teacher || ! $term) {
            $this->command?->warn('Guru Dewi atau periode akademik aktif belum tersedia; jadwal dan jurnal demo dilewati.');

            return;
        }

        $assignments = TeachingAssignment::query()
            ->with(['classGroup', 'subject'])
            ->where('term_id', $term->id)
            ->where('teacher_id', $teacher->id)
            ->whereHas('classGroup', fn ($query) => $query->where('school_id', $user->school_id))
            ->whereHas('subject', fn ($query) => $query->where('school_id', $user->school_id)->where('code', 'IPA-01'))
            ->orderBy('class_group_id')
            ->get()
            ->filter(fn (TeachingAssignment $assignment): bool => Enrollment::query()
                ->where('term_id', $term->id)
                ->where('class_group_id', $assignment->class_group_id)
                ->where('status', 'active')
                ->exists())
            ->values();

        if ($assignments->isEmpty()) {
            $this->command?->warn('Penugasan IPA dan enrollment aktif Dewi belum tersedia; jadwal dan jurnal demo dilewati.');

            return;
        }

        $schedules = $this->seedSchedules($assignments, (int) $user->school_id, $term, (int) $teacher->id);
        $this->seedLessonSessions($schedules, $assignments, (int) $user->school_id, $term, (int) $teacher->id, (int) $user->id);
        $this->seedOnlineClasses($assignments, (int) $user->school_id, $term, (int) $user->id);
    }

    /**
     * @param  Collection<int, TeachingAssignment>  $assignments
     * @return Collection<int, Schedule>
     */
    private function seedSchedules($assignments, int $schoolId, Term $term, int $teacherId)
    {
        $slots = [
            ['start' => '07:00:00', 'end' => '08:30:00'],
            ['start' => '08:45:00', 'end' => '10:15:00'],
            ['start' => '10:30:00', 'end' => '12:00:00'],
            ['start' => '13:00:00', 'end' => '14:30:00'],
            ['start' => '14:45:00', 'end' => '16:15:00'],
        ];
        $today = (int) now()->isoWeekday();
        $schedules = collect();

        foreach ($assignments as $index => $assignment) {
            $days = collect(range(0, 6))->map(fn (int $offset): int => (($today - 1 + $offset) % 7) + 1);
            $preferredSlot = $index % count($slots);
            $created = null;

            foreach ($days as $day) {
                $orderedSlots = collect(range(0, count($slots) - 1))
                    ->map(fn (int $offset): int => ($preferredSlot + $offset) % count($slots));

                foreach ($orderedSlots as $slotIndex) {
                    $slot = $slots[$slotIndex];
                    $identity = [
                        'school_id' => $schoolId,
                        'term_id' => $term->id,
                        'class_group_id' => $assignment->class_group_id,
                        'subject_id' => $assignment->subject_id,
                        'teacher_id' => $teacherId,
                        'day_of_week' => $day,
                        'start_time' => $slot['start'],
                    ];
                    $existing = Schedule::query()->where($identity)->first();

                    if ($existing) {
                        if ($existing->status === 'published') {
                            $created = $existing;
                            break 2;
                        }

                        continue;
                    }

                    $overlap = Schedule::query()
                        ->where('term_id', $term->id)
                        ->where('day_of_week', $day)
                        ->where('start_time', '<', $slot['end'])
                        ->where('end_time', '>', $slot['start'])
                        ->where(fn ($query) => $query->where('teacher_id', $teacherId)
                            ->orWhere('class_group_id', $assignment->class_group_id))
                        ->exists();

                    if ($overlap) {
                        continue;
                    }

                    $created = Schedule::query()->firstOrCreate($identity, [
                        'end_time' => $slot['end'],
                        'room' => null,
                        'status' => 'published',
                    ]);
                    break 2;
                }
            }

            if ($created) {
                $schedules->push($created);
            } else {
                $this->command?->warn("Tidak ada slot jadwal bebas untuk penugasan demo kelas {$assignment->classGroup?->name}.");
            }
        }

        return $schedules->unique('id')->values();
    }

    /**
     * @param  Collection<int, Schedule>  $schedules
     * @param  Collection<int, TeachingAssignment>  $assignments
     */
    private function seedLessonSessions($schedules, $assignments, int $schoolId, Term $term, int $teacherId, int $userId): void
    {
        $assignmentByClass = $assignments->keyBy(fn (TeachingAssignment $assignment): string => $assignment->class_group_id.':'.$assignment->subject_id);
        $today = CarbonImmutable::today();
        $firstDate = CarbonImmutable::parse($term->start_date)->max($today->subDays(27));
        $lastDate = CarbonImmutable::parse($term->end_date)->min($today);
        if ($firstDate->gt($lastDate)) {
            return;
        }

        $meetingNumbers = [];
        foreach ($schedules as $schedule) {
            $assignment = $assignmentByClass->get($schedule->class_group_id.':'.$schedule->subject_id);
            if (! $assignment) {
                continue;
            }

            $meetingNumbers[$assignment->id] ??= (int) LessonSession::query()
                ->where('teaching_assignment_id', $assignment->id)
                ->max('meeting_number') + 1;

            for ($date = $firstDate; $date->lte($lastDate); $date = $date->addDay()) {
                if ($date->isoWeekday() !== (int) $schedule->day_of_week) {
                    continue;
                }

                $isToday = $date->isSameDay($today);
                $session = LessonSession::query()->firstOrCreate(
                    ['schedule_id' => $schedule->id, 'date' => $date->toDateString()],
                    [
                        'school_id' => $schoolId,
                        'teaching_assignment_id' => $assignment->id,
                        'term_id' => $term->id,
                        'class_group_id' => $assignment->class_group_id,
                        'subject_id' => $assignment->subject_id,
                        'teacher_id' => $teacherId,
                        'start_time' => $schedule->start_time,
                        'end_time' => $schedule->end_time,
                        'meeting_number' => $meetingNumbers[$assignment->id],
                        'status' => $isToday ? 'scheduled' : 'completed',
                    ]
                );

                if (! $session->wasRecentlyCreated) {
                    continue;
                }

                $meetingNumbers[$assignment->id]++;
                if ($isToday) {
                    continue;
                }

                $this->seedJournal($session, $assignment, $userId);
            }
        }
    }

    private function seedJournal(LessonSession $session, TeachingAssignment $assignment, int $userId): void
    {
        $className = $assignment->classGroup?->name ?? 'rombel';
        $subjectName = $assignment->subject?->name ?? 'IPA';
        TeachingJournal::query()->firstOrCreate(
            ['lesson_session_id' => $session->id],
            [
                'topic' => 'Pengamatan ekosistem dan hubungan antarmakhluk hidup',
                'theme' => 'Menjaga keseimbangan lingkungan sekitar '.$className,
                'period_count' => 2,
                'learning_objectives' => "Peserta didik mampu mengidentifikasi komponen biotik dan abiotik, menjelaskan hubungan antarkomponen ekosistem, serta menyusun kesimpulan berdasarkan hasil pengamatan pada pembelajaran {$subjectName}.",
                'opening_activity' => 'Guru membuka pembelajaran, memeriksa kesiapan kelas, mengaitkan materi dengan lingkungan sekolah, dan menyampaikan tujuan serta kriteria keberhasilan.',
                'main_activity' => 'Peserta didik mengamati gambar dan lingkungan sekitar, mencatat komponen biotik-abiotik, berdiskusi dalam kelompok, lalu menyajikan rantai makanan dan bukti hubungan antarkomponen.',
                'closing_activity' => 'Kelas menyusun kesimpulan bersama, melakukan refleksi satu hal yang dipahami dan satu pertanyaan lanjutan, lalu menerima arahan tugas pengamatan sederhana di rumah.',
                'notes' => 'Diskusi kelompok berjalan aktif. Sebagian peserta didik masih perlu penguatan dalam membedakan individu, populasi, dan komunitas; guru memberi contoh visual tambahan.',
                'follow_up' => 'Berikan lembar penguatan tentang tingkat organisasi kehidupan bagi peserta didik yang memerlukan bimbingan, serta tugas pengayaan membuat diagram ekosistem lokal.',
                'attendance_notes' => 'Presensi contoh dicatat untuk seluruh peserta didik aktif di rombel; status bervariasi agar rekap dapat ditinjau.',
                'observations' => 'Kolaborasi dan rasa ingin tahu tampak baik. Pastikan setiap anggota kelompok mendapat giliran menyampaikan hasil pengamatan.',
                'reflection' => 'Contoh kontekstual membantu pemahaman. Pada pertemuan berikutnya, alokasikan waktu lebih untuk presentasi dan klarifikasi istilah.',
                'attendance_summary' => $this->seedSessionAttendance($session, $assignment, $userId),
            ]
        );
    }

    /**
     * @return array{present:int,sick:int,permitted:int,absent:int}
     */
    private function seedSessionAttendance(LessonSession $session, TeachingAssignment $assignment, int $userId): array
    {
        $summary = ['present' => 0, 'sick' => 0, 'permitted' => 0, 'absent' => 0];
        $enrollments = Enrollment::query()
            ->where('term_id', $session->term_id)
            ->where('class_group_id', $assignment->class_group_id)
            ->where('status', 'active')
            ->orderBy('roll_number')
            ->get();

        foreach ($enrollments as $index => $enrollment) {
            $status = match ($index % 20) {
                5 => 'sick',
                11 => 'permitted',
                17 => 'absent',
                default => 'present',
            };
            $summary[$status]++;

            AttendanceRecord::query()->firstOrCreate(
                [
                    'enrollment_id' => $enrollment->id,
                    'lesson_session_id' => $session->id,
                    'date' => $session->date->toDateString(),
                    'type' => 'session',
                ],
                [
                    'school_id' => $session->school_id,
                    'status' => $status,
                    'notes' => $status === 'sick' ? 'Izin sakit (data demo).' : ($status === 'permitted' ? 'Izin keluarga (data demo).' : ($status === 'absent' ? 'Tidak hadir (data demo).' : null)),
                    'recorded_by_user_id' => $userId,
                ]
            );
        }

        return $summary;
    }

    /**
     * @param  Collection<int, TeachingAssignment>  $assignments
     */
    private function seedOnlineClasses($assignments, int $schoolId, Term $term, int $userId): void
    {
        $eligible = $assignments->filter(fn (TeachingAssignment $assignment): bool => Enrollment::query()
            ->where('term_id', $term->id)
            ->where('class_group_id', $assignment->class_group_id)
            ->where('status', 'active')
            ->exists())
            ->take(2);
        $today = CarbonImmutable::today();

        foreach ($eligible as $index => $assignment) {
            foreach ([['date' => $today->subDays(3), 'status' => 'completed'], ['date' => $today, 'status' => 'scheduled']] as $meeting) {
                $startsAt = $meeting['date']->setTime($index === 0 ? 13 : 14, 0);
                if ($startsAt->lt(CarbonImmutable::parse($term->start_date)) || $startsAt->gt(CarbonImmutable::parse($term->end_date)->endOfDay())) {
                    continue;
                }

                $session = OnlineClassSession::query()->firstOrCreate(
                    [
                        'school_id' => $schoolId,
                        'term_id' => $term->id,
                        'class_group_id' => $assignment->class_group_id,
                        'teaching_assignment_id' => $assignment->id,
                        'starts_at' => $startsAt,
                    ],
                    [
                        'title' => 'Demo IPA: Eksplorasi Ekosistem Virtual — '.$assignment->classGroup?->name,
                        'provider' => 'google_meet',
                        'meeting_url' => 'https://meet.google.com/demo-ipa-'.$assignment->class_group_id,
                        'ends_at' => $startsAt->addMinutes(90),
                        'status' => $meeting['status'],
                        'created_by' => $userId,
                    ]
                );

                if ($meeting['status'] !== 'completed') {
                    continue;
                }

                $enrollments = Enrollment::query()
                    ->where('term_id', $term->id)
                    ->where('class_group_id', $assignment->class_group_id)
                    ->where('status', 'active')
                    ->orderBy('roll_number')
                    ->get();

                foreach ($enrollments as $studentIndex => $enrollment) {
                    $status = match ($studentIndex % 12) {
                        6 => 'late',
                        9 => 'excused',
                        11 => 'absent',
                        default => 'present',
                    };
                    OnlineAttendanceRecord::query()->firstOrCreate(
                        ['online_class_session_id' => $session->id, 'enrollment_id' => $enrollment->id],
                        [
                            'status' => $status,
                            'joined_at' => $status === 'absent' ? null : $startsAt->addMinutes($status === 'late' ? 8 : 0),
                            'left_at' => $status === 'absent' ? null : $startsAt->addMinutes(85),
                            'notes' => match ($status) {
                                'late' => 'Terlambat bergabung; tetap mengikuti kegiatan sampai selesai.',
                                'excused' => 'Izin mengikuti kegiatan dari perangkat keluarga.',
                                'absent' => 'Tidak hadir pada pertemuan daring.',
                                default => null,
                            },
                            'recorded_by' => $userId,
                        ]
                    );
                }
            }
        }
    }
}
