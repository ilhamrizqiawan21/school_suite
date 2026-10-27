<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Academic\Infrastructure\Models\Enrollment;
use App\Modules\Academic\Infrastructure\Models\Subject;
use App\Modules\Academic\Infrastructure\Models\Teacher;
use App\Modules\Academic\Infrastructure\Models\TeachingAssignment;
use App\Modules\Academic\Infrastructure\Models\Term;
use App\Modules\Assessment\Infrastructure\Models\AssessmentScheme;
use App\Modules\Assessment\Infrastructure\Models\Exam;
use App\Modules\Assessment\Infrastructure\Models\ExamAnswer;
use App\Modules\Assessment\Infrastructure\Models\ExamAttempt;
use App\Modules\Assessment\Infrastructure\Models\ExamQuestion;
use App\Modules\Assessment\Infrastructure\Models\ExamQuestionItem;
use App\Modules\Assessment\Infrastructure\Models\ExamQuestionVersion;
use App\Modules\Assessment\Infrastructure\Models\QuestionCategory;
use App\Modules\Assessment\Infrastructure\Models\QuestionPack;
use App\Modules\Assessment\Infrastructure\Models\QuestionPackItem;
use App\Modules\Assessment\Infrastructure\Models\QuestionShare;
use App\Modules\Assessment\Infrastructure\Models\QuestionShareEvent;
use App\Modules\Assessment\Infrastructure\Models\QuestionTag;
use App\Modules\Assessment\Infrastructure\Models\Score;
use App\Modules\Assessment\Infrastructure\Models\StudentAttitude;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class DemoAssessmentContentSeeder extends Seeder
{
    /**
     * Add a small, repeatable assessment sample without changing existing records.
     */
    public function run(): void
    {
        $user = User::query()->where('email', 'dewi@pilot.sch.id')->first();
        if (! $user || ! $user->school_id) {
            $this->command?->warn('Akun demo Dewi tidak ditemukan; data asesmen demo dilewati.');

            return;
        }

        $term = Term::query()
            ->where('is_active', true)
            ->whereHas('academicYear', fn ($query) => $query->where('school_id', $user->school_id)->where('is_active', true))
            ->first();
        $teacher = Teacher::query()->where('school_id', $user->school_id)->where('user_id', $user->id)->first();
        $subject = Subject::query()->where('school_id', $user->school_id)->where('code', 'IPA-01')->first();

        if (! $term || ! $teacher || ! $subject) {
            $this->command?->warn('Periode, profil guru, atau mapel IPA demo belum tersedia; data asesmen dilewati.');

            return;
        }

        $assignments = TeachingAssignment::query()
            ->with('classGroup')
            ->where('term_id', $term->id)
            ->where('teacher_id', $teacher->id)
            ->where('subject_id', $subject->id)
            ->whereHas('classGroup', fn ($query) => $query->where('school_id', $user->school_id))
            ->get()
            ->sortBy(fn (TeachingAssignment $assignment) => sprintf('%03d-%s', $assignment->classGroup?->grade_level ?? 999, $assignment->classGroup?->name ?? ''))
            ->values();

        if ($assignments->isEmpty()) {
            $this->command?->warn('Penugasan IPA aktif Dewi tidak ditemukan; data asesmen dilewati.');

            return;
        }

        $assignment = $assignments->first();
        $questions = $this->seedQuestions($user, $subject, $assignment);
        $pack = QuestionPack::query()->firstOrCreate(
            ['school_id' => $user->school_id, 'created_by' => $user->id, 'name' => 'Demo IPA - Ekosistem dan Perubahan Lingkungan'],
            [
                'description' => 'Paket contoh untuk latihan konsep ekosistem, interaksi makhluk hidup, dan perubahan lingkungan.',
                'jenjang' => 'SMP',
                'curriculum' => 'merdeka',
                'duration_minutes' => 30,
                'randomize_questions' => false,
                'randomize_options' => false,
                'status' => 'active',
            ]
        );

        foreach ($questions as $index => $question) {
            $version = ExamQuestionVersion::query()
                ->where('exam_question_id', $question->id)
                ->where('version_number', $question->current_version)
                ->first();
            if (! $version) {
                continue;
            }

            QuestionPackItem::query()->firstOrCreate(
                ['question_pack_id' => $pack->id, 'exam_question_id' => $question->id],
                ['question_version_id' => $version->id, 'sequence' => $index + 1, 'points' => $question->points]
            );
        }

        $recipient = User::query()->where('school_id', $user->school_id)
            ->where('email', 'budi@pilot.sch.id')->where('is_active', true)->first();
        if ($recipient && $recipient->id !== $user->id) {
            $share = QuestionShare::query()->firstOrCreate(
                [
                    'school_id' => $user->school_id,
                    'sender_id' => $user->id,
                    'recipient_id' => $recipient->id,
                    'resource_type' => 'pack',
                    'resource_id' => $pack->id,
                    'active_key' => 'active',
                ],
                [
                    'permission' => 'view',
                    'status' => 'pending',
                    'request_note' => 'Contoh berbagi paket soal IPA untuk ditinjau bersama.',
                ]
            );

            QuestionShareEvent::query()->firstOrCreate(
                ['question_share_id' => $share->id, 'actor_id' => $user->id, 'event_type' => 'requested'],
                ['new_permission' => 'view', 'new_status' => 'pending', 'note' => 'Data demo berbagi soal.', 'created_at' => now()]
            );
        }

        $exam = Exam::query()->firstOrCreate(
            ['teaching_assignment_id' => $assignment->id, 'created_by' => $user->id, 'title' => 'Demo IPA - Kuis Ekosistem Kelas VII-A'],
            [
                'school_id' => $user->school_id,
                'question_pack_id' => $pack->id,
                'instructions' => 'Baca setiap soal dengan teliti. Pilih jawaban terbaik dan periksa kembali sebelum dikumpulkan.',
                'starts_at' => now()->subMinutes(5),
                'ends_at' => now()->addDays(7),
                'duration_minutes' => 30,
                'randomize_questions' => false,
                'randomize_options' => false,
                'max_violations' => 3,
                'status' => 'published',
            ]
        );
        // Keep this named demo exam available to students whenever seeders are rerun.
        $exam->forceFill([
            'starts_at' => now()->subMinutes(5),
            'ends_at' => now()->addDays(7),
            'status' => 'published',
        ])->save();

        foreach ($questions as $index => $question) {
            $version = ExamQuestionVersion::query()
                ->where('exam_question_id', $question->id)
                ->where('version_number', $question->current_version)
                ->first();
            if (! $version) {
                continue;
            }

            ExamQuestionItem::query()->firstOrCreate(
                ['exam_id' => $exam->id, 'exam_question_id' => $question->id],
                ['question_version_id' => $version->id, 'sequence' => $index + 1, 'points' => $question->points]
            );
        }

        $this->seedPublishedAnalyticsExample($user, $assignment, $questions);

        $this->seedScoresAndAttitudes($user, $term, $assignments);
    }

    /** @param list<ExamQuestion> $questions */
    private function seedPublishedAnalyticsExample(User $user, TeachingAssignment $assignment, array $questions): void
    {
        $exam = Exam::query()->firstOrCreate(
            ['teaching_assignment_id' => $assignment->id, 'created_by' => $user->id, 'title' => 'Demo Analisis IPA - Hasil Terbit'],
            [
                'school_id' => $user->school_id,
                'instructions' => 'Contoh ujian sintetis untuk melihat distribusi nilai dan analisis butir.',
                'starts_at' => now()->subDays(10),
                'ends_at' => now()->subDays(10)->addMinutes(30),
                'duration_minutes' => 30,
                'randomize_questions' => false,
                'randomize_options' => false,
                'max_violations' => 3,
                'status' => 'results_published',
            ]
        );
        $exam->forceFill([
            'starts_at' => now()->subDays(10),
            'ends_at' => now()->subDays(10)->addMinutes(30),
            'status' => 'results_published',
        ])->save();

        $versions = [];
        foreach ($questions as $index => $question) {
            $version = ExamQuestionVersion::query()
                ->where('exam_question_id', $question->id)
                ->where('version_number', $question->current_version)
                ->first();
            if (! $version) {
                continue;
            }

            $exam->items()->firstOrCreate(
                ['exam_question_id' => $question->id],
                ['question_version_id' => $version->id, 'sequence' => $index + 1, 'points' => $question->points]
            );
            $versions[$question->id] = $version;
        }

        $enrollments = Enrollment::query()
            ->where('term_id', $assignment->term_id)
            ->where('class_group_id', $assignment->class_group_id)
            ->where('status', 'active')
            ->orderBy('roll_number')
            ->limit(8)
            ->get();
        $scores = [55, 62, 68, 74, 81, 86, 92, 96];
        $objectiveThresholds = [50, 65, 76, 86, 72, 82];

        foreach ($enrollments as $index => $enrollment) {
            $score = $scores[$index % count($scores)];
            $attempt = ExamAttempt::query()->firstOrCreate(
                ['exam_id' => $exam->id, 'enrollment_id' => $enrollment->id],
                [
                    'status' => 'graded',
                    'started_at' => now()->subDays(10)->addMinutes($index),
                    'expires_at' => now()->subDays(10)->addMinutes(30 + $index),
                    'last_heartbeat_at' => now()->subDays(10)->addMinutes(20 + $index),
                    'submitted_at' => now()->subDays(10)->addMinutes(25 + $index),
                    'score' => $score,
                    'question_order' => array_map('intval', array_keys($versions)),
                ]
            );

            $earnedPoints = 0.0;
            $possiblePoints = 0.0;
            foreach ($questions as $questionIndex => $question) {
                $version = $versions[$question->id] ?? null;
                if (! $version) {
                    continue;
                }

                $isEssay = $version->type === 'essay';
                $correct = ! $isEssay && $score >= ($objectiveThresholds[$questionIndex] ?? 80);
                $answer = $isEssay
                    ? 'Contoh jawaban sintetis siswa untuk analisis dampak dan solusi lingkungan.'
                    : ($correct ? (string) $version->correct_answer : $this->incorrectDemoAnswer($version->options, (string) $version->correct_answer));
                $itemScore = $isEssay
                    ? round((float) $question->points * $score / 100, 2)
                    : ($correct ? (float) $question->points : 0);
                $earnedPoints += $itemScore;
                $possiblePoints += (float) $question->points;

                ExamAnswer::query()->firstOrCreate(
                    ['exam_attempt_id' => $attempt->id, 'exam_question_id' => $question->id],
                    [
                        'question_version_id' => $version->id,
                        'answer' => $answer,
                        'is_correct' => $isEssay ? null : $correct,
                        'score' => $itemScore,
                        'graded_by' => $user->id,
                        'graded_at' => now()->subDays(10)->addMinutes(25 + $index),
                    ]
                );
            }

            $attempt->update(['score' => $possiblePoints > 0 ? round($earnedPoints / $possiblePoints * 100, 2) : 0]);
        }
    }

    /** @param array<mixed>|null $options */
    private function incorrectDemoAnswer(?array $options, string $correctAnswer): string
    {
        foreach ($options ?? [] as $option) {
            if ((string) $option !== $correctAnswer) {
                return (string) $option;
            }
        }

        return $correctAnswer === 'true' ? 'false' : 'true';
    }

    /** @return list<ExamQuestion> */
    private function seedQuestions(User $user, Subject $subject, TeachingAssignment $assignment): array
    {
        $category = QuestionCategory::query()->firstOrCreate(
            ['school_id' => $user->school_id, 'name' => 'Ekosistem dan Lingkungan'],
        );
        $tags = collect(['ekosistem', 'rantai-makanan', 'perubahan-lingkungan'])
            ->map(fn (string $name) => QuestionTag::query()->firstOrCreate(['school_id' => $user->school_id, 'name' => $name]));

        $definitions = [
            [
                'type' => 'multiple_choice',
                'prompt' => 'Dalam sebuah ekosistem sawah, organisme yang berperan sebagai produsen adalah ....',
                'indicator_text' => 'Mengidentifikasi peran organisme dalam ekosistem.',
                'cognitive_level' => 'L1', 'kko_text' => 'mengidentifikasi',
                'options' => ['Padi', 'Belalang', 'Katak', 'Elang'], 'correct_answer' => 'Padi',
                'explanation' => 'Padi membuat makanan sendiri melalui fotosintesis sehingga berperan sebagai produsen.', 'points' => 10,
                'tags' => ['ekosistem', 'rantai-makanan'],
            ],
            [
                'type' => 'multiple_choice',
                'prompt' => 'Urutan rantai makanan yang tepat pada ekosistem sawah adalah ....',
                'indicator_text' => 'Menentukan hubungan makan dan dimakan pada rantai makanan.',
                'cognitive_level' => 'L2', 'kko_text' => 'menentukan',
                'options' => ['Padi → tikus → ular → elang', 'Elang → padi → tikus → ular', 'Tikus → padi → elang → ular', 'Padi → elang → ular → tikus'],
                'correct_answer' => 'Padi → tikus → ular → elang',
                'explanation' => 'Energi berpindah dari produsen ke konsumen tingkat I, II, lalu predator puncak.', 'points' => 10,
                'tags' => ['rantai-makanan'],
            ],
            [
                'type' => 'multiple_choice',
                'prompt' => 'Jika populasi ular di sawah menurun drastis, perubahan yang paling mungkin terjadi terlebih dahulu adalah ....',
                'indicator_text' => 'Menganalisis dampak perubahan populasi terhadap keseimbangan ekosistem.',
                'cognitive_level' => 'L3', 'kko_text' => 'menganalisis',
                'options' => ['Populasi tikus meningkat', 'Populasi padi langsung punah', 'Populasi elang selalu meningkat', 'Jumlah produsen bertambah tanpa batas'],
                'correct_answer' => 'Populasi tikus meningkat',
                'explanation' => 'Berkurangnya predator ular dapat menyebabkan populasi mangsanya, seperti tikus, meningkat.', 'points' => 15,
                'tags' => ['ekosistem', 'rantai-makanan'],
            ],
            [
                'type' => 'multiple_choice',
                'prompt' => 'Tindakan yang paling tepat untuk mengurangi pencemaran sungai oleh sampah rumah tangga adalah ....',
                'indicator_text' => 'Memilih tindakan yang bertanggung jawab terhadap lingkungan.',
                'cognitive_level' => 'L2', 'kko_text' => 'memilih',
                'options' => ['Memilah sampah dan mengolahnya sebelum dibuang', 'Membuang sampah saat malam hari', 'Menutup sampah dengan tanah di tepi sungai', 'Mengalirkan sampah ke bagian sungai yang lebih jauh'],
                'correct_answer' => 'Memilah sampah dan mengolahnya sebelum dibuang',
                'explanation' => 'Pemilahan dan pengolahan sampah mencegah pencemar masuk ke badan air.', 'points' => 15,
                'tags' => ['perubahan-lingkungan'],
            ],
            [
                'type' => 'true_false',
                'prompt' => 'Jamur dan bakteri dapat berperan sebagai pengurai yang mengembalikan unsur hara ke lingkungan.',
                'indicator_text' => 'Menjelaskan peran pengurai dalam aliran materi ekosistem.',
                'cognitive_level' => 'L1', 'kko_text' => 'menjelaskan',
                'options' => null, 'correct_answer' => 'true',
                'explanation' => 'Pengurai memecah sisa organisme dan membantu mengembalikan unsur hara ke lingkungan.', 'points' => 10,
                'tags' => ['ekosistem'],
            ],
            [
                'type' => 'essay',
                'prompt' => 'Jelaskan dua dampak penggunaan pestisida secara berlebihan terhadap ekosistem sawah dan usulkan satu upaya yang lebih ramah lingkungan.',
                'indicator_text' => 'Menganalisis dampak aktivitas manusia dan mengusulkan solusi lingkungan.',
                'cognitive_level' => 'L3', 'kko_text' => 'menganalisis',
                'options' => null, 'correct_answer' => null,
                'explanation' => 'Jawaban dapat memuat dampak terhadap organisme non-target, kualitas tanah atau air, dan alternatif pengendalian hayati/terpadu.',
                'essay_rubric' => 'Nilai penuh jika menyebut dua dampak yang relevan dan satu solusi yang dapat diterapkan dengan alasan yang tepat.', 'points' => 20,
                'tags' => ['ekosistem', 'perubahan-lingkungan'],
            ],
        ];

        $questions = [];
        foreach ($definitions as $definition) {
            $attributes = [
                'school_id' => $user->school_id,
                'subject_id' => $subject->id,
                'teaching_assignment_id' => $assignment->id,
                'owner_id' => $user->id,
                'type' => $definition['type'],
                'jenjang' => 'SMP',
                'curriculum' => 'merdeka',
                'prompt' => $definition['prompt'],
                'indicator_text' => $definition['indicator_text'],
                'cognitive_level' => $definition['cognitive_level'],
                'kko_text' => $definition['kko_text'],
                'options' => $definition['options'],
                'correct_answer' => $definition['correct_answer'],
                'explanation' => $definition['explanation'],
                'essay_rubric' => $definition['essay_rubric'] ?? null,
                'points' => $definition['points'],
                'is_active' => true,
                'current_version' => 1,
            ];

            $question = ExamQuestion::query()->firstOrCreate(
                ['owner_id' => $user->id, 'teaching_assignment_id' => $assignment->id, 'prompt' => $definition['prompt']],
                $attributes
            );
            $version = ExamQuestionVersion::query()->firstOrCreate(
                ['exam_question_id' => $question->id, 'version_number' => $question->current_version],
                [
                    'type' => $definition['type'],
                    'prompt' => $definition['prompt'],
                    'indicator_text' => $definition['indicator_text'],
                    'cognitive_level' => $definition['cognitive_level'],
                    'kko_text' => $definition['kko_text'],
                    'options' => $definition['options'],
                    'correct_answer' => $definition['correct_answer'],
                    'explanation' => $definition['explanation'],
                    'essay_rubric' => $definition['essay_rubric'] ?? null,
                    'points' => $definition['points'],
                    'subject_id' => $subject->id,
                    'subject_code_snapshot' => $subject->code,
                    'subject_name_snapshot' => $subject->name,
                    'jenjang' => 'SMP',
                    'curriculum' => 'merdeka',
                    'created_by' => $user->id,
                ]
            );

            if ($version->wasRecentlyCreated || $question->wasRecentlyCreated) {
                $question->categories()->syncWithoutDetaching([$category->id]);
                $version->categories()->syncWithoutDetaching([$category->id]);
                $tagIds = $tags->whereIn('name', $definition['tags'])->pluck('id')->all();
                $question->tags()->syncWithoutDetaching($tagIds);
                $version->tags()->syncWithoutDetaching($tagIds);
            }

            $questions[] = $question;
        }

        return $questions;
    }

    /** @param Collection<int, TeachingAssignment> $assignments */
    private function seedScoresAndAttitudes(User $user, Term $term, $assignments): void
    {
        $classGroupIds = $assignments->pluck('class_group_id')->unique()->all();
        $enrollments = Enrollment::query()
            ->where('term_id', $term->id)
            ->where('status', 'active')
            ->whereIn('class_group_id', $classGroupIds)
            ->orderBy('class_group_id')
            ->orderBy('roll_number')
            ->limit(8)
            ->get();

        if ($enrollments->isEmpty()) {
            return;
        }

        $scheme = AssessmentScheme::query()->where('school_id', $user->school_id)->where('is_active', true)->first();
        $component = $scheme?->components()->where('code', 'F1')->first();
        if ($component) {
            $scores = [86, 92, 78, 88, 95, 81, 90, 84];
            foreach ($enrollments as $index => $enrollment) {
                Score::query()->firstOrCreate(
                    ['enrollment_id' => $enrollment->id, 'assessment_component_id' => $component->id],
                    [
                        'school_id' => $user->school_id,
                        'raw_score' => $scores[$index],
                        'final_score' => $scores[$index],
                        'notes' => 'Contoh input nilai formatif IPA oleh Guru Dewi.',
                        'recorded_by_user_id' => $user->id,
                    ]
                );
            }
        }

        $attitudes = [
            'spiritual' => [
                ['good', 'Menunjukkan rasa syukur dan kepedulian saat mengamati keteraturan ekosistem.'],
                ['excellent', 'Konsisten menunjukkan rasa syukur serta menjaga makhluk hidup selama kegiatan.'],
                ['good', 'Menghargai lingkungan dan mengikuti kesepakatan kelas saat praktikum.'],
                ['good', 'Menunjukkan sikap peduli terhadap kebersihan lingkungan belajar.'],
                ['excellent', 'Menjadi teladan dalam merawat alat dan lingkungan praktikum.'],
                ['good', 'Menghormati pendapat teman ketika membahas tanggung jawab manusia.'],
                ['good', 'Menunjukkan kepedulian terhadap kelestarian lingkungan sekitar.'],
                ['excellent', 'Aktif mengajak kelompok menjaga kebersihan dan keselamatan kerja.'],
            ],
            'social' => [
                ['good', 'Bekerja sama dan berbagi tugas dalam diskusi kelompok.'],
                ['excellent', 'Mengajak anggota kelompok berpartisipasi dan mendengarkan semua pendapat.'],
                ['good', 'Menyampaikan pendapat dengan santun dan menghargai masukan.'],
                ['good', 'Menyelesaikan tugas kelompok sesuai tanggung jawabnya.'],
                ['excellent', 'Membantu teman memahami diagram rantai makanan dengan sabar.'],
                ['good', 'Mengikuti aturan keselamatan dan berkomunikasi dengan baik.'],
                ['good', 'Berbagi hasil pengamatan secara jujur dan terbuka.'],
                ['excellent', 'Menunjukkan inisiatif, tanggung jawab, dan kolaborasi yang konsisten.'],
            ],
        ];

        foreach ($enrollments as $index => $enrollment) {
            foreach ($attitudes as $type => $samples) {
                StudentAttitude::query()->firstOrCreate(
                    ['term_id' => $term->id, 'enrollment_id' => $enrollment->id, 'type' => $type],
                    [
                        'school_id' => $user->school_id,
                        'predicate' => $samples[$index][0],
                        'description' => $samples[$index][1],
                        'recorded_by' => $user->id,
                    ]
                );
            }
        }
    }
}
