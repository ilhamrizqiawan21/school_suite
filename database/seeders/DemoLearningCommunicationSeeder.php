<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Academic\Infrastructure\Models\Enrollment;
use App\Modules\Academic\Infrastructure\Models\Teacher;
use App\Modules\Academic\Infrastructure\Models\TeachingAssignment;
use App\Modules\Academic\Infrastructure\Models\Term;
use App\Modules\Administration\Infrastructure\Models\AcademicCalendarEvent;
use App\Modules\Administration\Infrastructure\Models\Announcement;
use App\Modules\Administration\Infrastructure\Models\ClassChatMessage;
use App\Modules\Administration\Infrastructure\Models\ClassChatRoom;
use App\Modules\Learning\Infrastructure\Models\AssignmentSubmission;
use App\Modules\Learning\Infrastructure\Models\LearningAssignment;
use App\Modules\Learning\Infrastructure\Models\LearningMaterial;
use App\Modules\Learning\Infrastructure\Models\LearningMaterialRead;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class DemoLearningCommunicationSeeder extends Seeder
{
    public function run(): void
    {
        $teacherUser = User::query()->where('email', 'dewi@pilot.sch.id')->first();
        if (! $teacherUser) {
            $this->command?->warn('Akun demo Dewi tidak ditemukan; data LMS dan komunikasi dilewati.');

            return;
        }

        $teacher = Teacher::query()
            ->where('school_id', $teacherUser->school_id)
            ->where('user_id', $teacherUser->id)
            ->first();
        $term = Term::query()
            ->where('is_active', true)
            ->whereHas('academicYear', fn ($query) => $query->where('school_id', $teacherUser->school_id))
            ->first();

        if (! $teacher || ! $term) {
            $this->command?->warn('Guru atau periode aktif Dewi tidak ditemukan; data LMS dan komunikasi dilewati.');

            return;
        }

        $assignments = TeachingAssignment::query()
            ->with(['classGroup', 'subject'])
            ->where('term_id', $term->id)
            ->where('teacher_id', $teacher->id)
            ->whereHas('classGroup', fn ($query) => $query->where('school_id', $teacherUser->school_id))
            ->orderBy('class_group_id')
            ->orderBy('subject_id')
            ->get();

        if ($assignments->isEmpty()) {
            $this->command?->warn('Penugasan mengajar aktif Dewi tidak ditemukan; data LMS dan komunikasi dilewati.');

            return;
        }

        if (Schema::hasTable('learning_materials')) {
            foreach ($assignments as $assignment) {
                $subject = $assignment->subject?->name ?? 'IPA';
                $className = $assignment->classGroup?->name ?? 'Rombel';

                foreach ([
                    [
                        'title' => "Ringkasan {$subject}: konsep dan istilah kunci",
                        'description' => "Bahan bacaan untuk {$className}. Pelajari konsep utama, catat istilah penting, dan siapkan satu pertanyaan untuk diskusi kelas.",
                    ],
                    [
                        'title' => "Lembar observasi {$subject} di lingkungan sekitar",
                        'description' => "Panduan kegiatan {$className}: lakukan pengamatan sederhana, tuliskan bukti yang ditemukan, lalu susun kesimpulan berdasarkan data.",
                    ],
                    [
                        'title' => "Pengayaan {$subject}: latihan penerapan konsep",
                        'description' => "Materi pengayaan {$className} berisi contoh penerapan konsep pada situasi sehari-hari dan pertanyaan refleksi mandiri.",
                    ],
                ] as $material) {
                    LearningMaterial::query()->firstOrCreate(
                        [
                            'school_id' => $teacherUser->school_id,
                            'teaching_assignment_id' => $assignment->id,
                            'title' => $material['title'],
                        ],
                        [
                            'created_by' => $teacherUser->id,
                            'description' => $material['description'],
                            'published_at' => now()->subDays(2),
                        ]
                    );
                }

                if (Schema::hasTable('learning_material_reads')) {
                    $materials = LearningMaterial::query()
                        ->where('teaching_assignment_id', $assignment->id)
                        ->where('school_id', $teacherUser->school_id)
                        ->orderBy('id')
                        ->get();
                    $enrollments = Enrollment::query()
                        ->where('term_id', $term->id)
                        ->where('class_group_id', $assignment->class_group_id)
                        ->where('status', 'active')
                        ->orderBy('roll_number')
                        ->get();

                    foreach ($materials as $materialIndex => $learningMaterial) {
                        foreach ($enrollments->take(20) as $studentIndex => $enrollment) {
                            // Stagger read coverage so the progress page shows a realistic mix.
                            if (($studentIndex + $materialIndex) % 5 === 4) {
                                continue;
                            }

                            LearningMaterialRead::query()->firstOrCreate(
                                [
                                    'learning_material_id' => $learningMaterial->id,
                                    'enrollment_id' => $enrollment->id,
                                ],
                                ['read_at' => now()->subHours(3 + (($studentIndex + $materialIndex) % 48))]
                            );
                        }
                    }
                }
            }
        }

        $demoAssignment = null;
        if (Schema::hasTable('learning_assignments') && Schema::hasTable('assignment_submissions')) {
            $demoAssignment = $assignments->first();
            $className = $demoAssignment->classGroup?->name ?? 'Rombel';
            $subject = $demoAssignment->subject?->name ?? 'IPA';

            $assignmentDefaults = [
                'created_by' => $teacherUser->id,
                'instructions' => "Untuk {$className}: lakukan pengamatan sederhana yang berkaitan dengan materi. Kumpulkan uraian langkah, dua bukti hasil pengamatan, kesimpulan, dan refleksi singkat. Gunakan bahasa sendiri.",
                'due_at' => now()->addDays(7)->endOfDay(),
                'max_score' => 100,
                'allow_late' => true,
                'published_at' => now()->subDay(),
            ];
            if (Schema::hasColumn('learning_assignments', 'revision_limit')) {
                $assignmentDefaults['revision_limit'] = 2;
            }

            $learningAssignment = LearningAssignment::query()->firstOrCreate(
                [
                    'school_id' => $teacherUser->school_id,
                    'teaching_assignment_id' => $demoAssignment->id,
                    'title' => "Tugas praktik {$subject}: observasi dan kesimpulan - demo",
                ],
                $assignmentDefaults
            );

            $enrollments = Enrollment::query()
                ->with('student')
                ->where('term_id', $term->id)
                ->where('class_group_id', $demoAssignment->class_group_id)
                ->where('status', 'active')
                ->orderBy('roll_number')
                ->get();

            foreach ($enrollments as $index => $enrollment) {
                $status = match ($index % 10) {
                    7, 8 => 'draft',
                    9 => 'late',
                    default => 'submitted',
                };

                $submissionDefaults = [
                    'content' => $status === 'draft'
                        ? 'Draf demo: saya sedang menyusun hasil pengamatan dan kesimpulan.'
                        : 'Hasil pengamatan menunjukkan pola yang dapat dijelaskan dengan konsep yang dipelajari. Bukti dicatat secara runtut dan kesimpulan disusun berdasarkan hasil pengamatan.',
                    'status' => $status,
                    'submitted_at' => $status === 'draft' ? null : now()->subHours(12 + ($index % 36)),
                ];
                if (Schema::hasColumn('assignment_submissions', 'review_status')) {
                    $submissionDefaults['review_status'] = 'pending';
                }
                if (Schema::hasColumn('assignment_submissions', 'revision_count')) {
                    $submissionDefaults['revision_count'] = 0;
                }

                AssignmentSubmission::query()->firstOrCreate(
                    [
                        'learning_assignment_id' => $learningAssignment->id,
                        'enrollment_id' => $enrollment->id,
                    ],
                    $submissionDefaults
                );
            }
        }

        if (Schema::hasTable('class_chat_rooms') && Schema::hasTable('class_chat_messages')) {
            foreach ($assignments as $assignment) {
                $className = $assignment->classGroup?->name ?? 'Rombel';
                $subjectName = $assignment->subject?->name ?? 'Mata Pelajaran';
                $room = ClassChatRoom::query()->firstOrCreate(
                    [
                        'school_id' => $teacherUser->school_id,
                        'teaching_assignment_id' => $assignment->id,
                    ],
                    [
                        'name' => "{$className} · {$subjectName}",
                        'is_active' => true,
                    ]
                );

                foreach ([
                    'Selamat pagi, silakan tuliskan satu hal yang kalian amati sebelum menyusun kesimpulan.',
                    'Ingat, tugas praktik dikumpulkan melalui menu Ruang Belajar sebelum batas waktu yang tercantum.',
                    'Jika ada kendala memahami materi, kirim pertanyaannya di sini. Saya akan membahasnya pada pertemuan berikutnya.',
                ] as $message) {
                    ClassChatMessage::query()->firstOrCreate(
                        [
                            'class_chat_room_id' => $room->id,
                            'user_id' => $teacherUser->id,
                            'body' => $message,
                        ]
                    );
                }
            }
        }

        if (Schema::hasTable('announcements')) {
            foreach ($assignments->unique('class_group_id') as $assignment) {
                $className = $assignment->classGroup?->name ?? 'Rombel';
                Announcement::query()->firstOrCreate(
                    [
                        'school_id' => $teacherUser->school_id,
                        'author_id' => $teacherUser->id,
                        'class_group_id' => $assignment->class_group_id,
                        'audience_role' => 'teacher',
                        'title' => "Pengingat pembelajaran {$className} - demo",
                    ],
                    [
                        'body' => "Pengumuman demo untuk {$className}: silakan membaca materi yang tersedia, menyelesaikan tugas praktik, dan menyampaikan pertanyaan melalui Diskusi Chat.",
                        'published_at' => now()->subHours(2),
                        'expires_at' => now()->addDays(30),
                    ]
                );
            }
        }

        if (Schema::hasTable('academic_calendar_events')) {
            foreach ($assignments->unique('class_group_id') as $assignment) {
                $className = $assignment->classGroup?->name ?? 'Rombel';
                AcademicCalendarEvent::query()->firstOrCreate(
                    [
                        'school_id' => $teacherUser->school_id,
                        'created_by' => $teacherUser->id,
                        'class_group_id' => $assignment->class_group_id,
                        'audience_role' => 'teacher',
                        'title' => "Agenda refleksi pembelajaran {$className} - demo",
                    ],
                    [
                        'description' => 'Agenda demo: diskusi hasil observasi, umpan balik tugas, dan refleksi proses belajar.',
                        'event_type' => 'academic',
                        'starts_at' => now()->addDays(3)->setTime(9, 0),
                        'ends_at' => now()->addDays(3)->setTime(10, 0),
                    ]
                );
            }
        }
    }
}
