<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Application\Services;

use App\Models\User;
use App\Modules\Assessment\Infrastructure\Models\Exam;
use App\Modules\Assessment\Infrastructure\Models\ExamQuestionItem;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class AssessmentAnalyticsService
{
    /** @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function report(User $user, array $filters): array
    {
        $schoolId = (int) $user->school_id;
        $questions = $this->questionQuery($user, $filters);
        $total = (clone $questions)->count('q.id');
        $byType = $this->distribution(clone $questions, 'q.type');
        $bySubject = $this->distribution(
            (clone $questions)->leftJoin('subjects as s', 's.id', '=', 'q.subject_id'),
            "COALESCE(s.name, 'Belum ditentukan')"
        );
        $byCurriculum = $this->distribution(clone $questions, "COALESCE(q.curriculum, 'Belum ditentukan')");
        $byCognitiveLevel = $this->distribution(clone $questions, "COALESCE(q.cognitive_level, 'Belum diklasifikasi')");
        $categoryQuery = DB::query()->fromSub((clone $questions)->select('q.id'), 'visible_questions')
            ->leftJoin('question_category_links as qcl', 'qcl.exam_question_id', '=', 'visible_questions.id')
            ->leftJoin('question_categories as qc', function ($join) use ($schoolId): void {
                $join->on('qc.id', '=', 'qcl.category_id')->where('qc.school_id', '=', $schoolId);
            });
        $byCategory = $categoryQuery->selectRaw("COALESCE(qc.name, 'Tanpa kategori') as label, COUNT(DISTINCT visible_questions.id) as count")
            ->groupBy('label')->orderBy('label')->get()->map(fn ($row) => ['label' => (string) $row->label, 'count' => (int) $row->count])->all();

        $exams = $this->visibleExams($user)->get(['exams.id', 'exams.title', 'exams.status', 'exams.starts_at']);
        $selectedExam = null;
        $examResults = ['available' => false, 'exam' => null, 'total_attempts' => 0, 'graded_attempts' => 0, 'average_score' => null, 'score_distribution' => [], 'items' => null];
        $selectedExam = ! empty($filters['exam_id'])
            ? $exams->firstWhere('id', (int) $filters['exam_id'])
            : $exams->firstWhere('status', 'results_published');

        if (! empty($filters['exam_id'])) {
            abort_unless($selectedExam, 404);
        }

        if ($selectedExam) {
            $filters['exam_id'] = (int) $selectedExam->id;
            $examResults['exam'] = $selectedExam;
            $exam = Exam::query()->findOrFail($selectedExam->id);
            $examResults['total_attempts'] = $exam->attempts()->count();
            if ($exam->status === 'results_published') {
                $graded = $exam->attempts()->whereIn('status', ['graded', 'submitted'])->whereNotNull('score')->get(['id', 'score']);
                $scores = $graded->pluck('score')->map(fn ($score) => (float) $score);
                $count = $scores->count();
                $bands = [
                    ['label' => '0–59', 'min' => 0, 'max' => 60],
                    ['label' => '60–69', 'min' => 60, 'max' => 70],
                    ['label' => '70–79', 'min' => 70, 'max' => 80],
                    ['label' => '80–89', 'min' => 80, 'max' => 90],
                    ['label' => '90–100', 'min' => 90, 'max' => 100, 'inclusive_max' => true],
                ];
                $examResults['available'] = true;
                $examResults['graded_attempts'] = $count;
                $examResults['average_score'] = $count ? round($scores->avg(), 2) : null;
                $examResults['score_distribution'] = collect($bands)->map(function (array $band) use ($scores, $count): array {
                    $bandCount = $scores->filter(fn (float $score) => $score >= $band['min']
                        && (isset($band['inclusive_max']) ? $score <= $band['max'] : $score < $band['max']))->count();

                    return ['label' => $band['label'], 'count' => $bandCount, 'percentage' => $count ? round($bandCount / $count * 100, 1) : null];
                })->all();
                $examResults['items'] = $this->itemStatistics($exam, $graded->pluck('id')->all(), $count);
            }
        }

        return [
            'filters' => $filters,
            'subjects' => DB::table('subjects')->where('school_id', $schoolId)->orderBy('name')->get(['id', 'name']),
            'categories' => DB::table('question_categories')->where('school_id', $schoolId)->orderBy('name')->get(['id', 'name']),
            'exams' => $exams,
            'bank' => [
                'total' => (int) $total,
                'by_type' => $byType,
                'by_subject' => $bySubject,
                'by_category' => $byCategory,
                'by_curriculum' => $byCurriculum,
                'by_cognitive_level' => $byCognitiveLevel,
            ],
            'exam_results' => $examResults,
        ];
    }

    /** @param array<string, mixed> $filters */
    private function questionQuery(User $user, array $filters): Builder
    {
        return DB::table('exam_questions as q')->where('q.school_id', $user->school_id)->where('q.is_active', true)
            ->when($user->hasRole(['teacher', 'homeroom']), function (Builder $query) use ($user): void {
                $query->where(function (Builder $scope) use ($user): void {
                    $scope->where('q.owner_id', $user->id)->orWhereExists(function (Builder $shares) use ($user): void {
                        $shares->selectRaw('1')->from('question_shares')
                            ->whereColumn('question_shares.resource_id', 'q.id')->where('question_shares.resource_type', 'question')
                            ->where('question_shares.school_id', $user->school_id)->where('question_shares.recipient_id', $user->id)
                            ->where('question_shares.status', 'accepted')->where('question_shares.active_key', 'active');
                    });
                });
            })
            ->when($filters['subject_id'] ?? null, fn (Builder $query, $value) => $query->where('q.subject_id', $value))
            ->when($filters['type'] ?? null, fn (Builder $query, $value) => $query->where('q.type', $value))
            ->when($filters['jenjang'] ?? null, fn (Builder $query, $value) => $query->where('q.jenjang', $value))
            ->when($filters['curriculum'] ?? null, fn (Builder $query, $value) => $query->where('q.curriculum', $value))
            ->when($filters['cognitive_level'] ?? null, fn (Builder $query, $value) => $query->where('q.cognitive_level', $value))
            ->when($filters['category_id'] ?? null, fn (Builder $query, $value) => $query->whereExists(function (Builder $category) use ($value, $user): void {
                $category->selectRaw('1')->from('question_category_links as filter_qcl')
                    ->join('question_categories as filter_qc', 'filter_qc.id', '=', 'filter_qcl.category_id')
                    ->whereColumn('filter_qcl.exam_question_id', 'q.id')->where('filter_qc.id', $value)
                    ->where('filter_qc.school_id', $user->school_id);
            }));
    }

    private function distribution(Builder $query, string $expression): array
    {
        return $query->selectRaw($expression.' as label, COUNT(DISTINCT q.id) as count')
            ->groupBy('label')->orderBy('label')->get()
            ->map(fn ($row) => ['label' => (string) $row->label, 'count' => (int) $row->count])->all();
    }

    private function visibleExams(User $user): Builder
    {
        $query = DB::table('exams')->join('teaching_assignments as ta', 'ta.id', '=', 'exams.teaching_assignment_id')
            ->join('class_groups as cg', 'cg.id', '=', 'ta.class_group_id')
            ->where('exams.school_id', $user->school_id)->where('cg.school_id', $user->school_id);
        if ($user->hasRole(['teacher', 'homeroom'])) {
            $query->where(function (Builder $scope) use ($user): void {
                $scope->whereExists(function (Builder $teacher) use ($user): void {
                    $teacher->selectRaw('1')->from('teachers')->whereColumn('teachers.id', 'ta.teacher_id')->where('teachers.user_id', $user->id)->where('teachers.school_id', $user->school_id);
                })->orWhereExists(function (Builder $homeroom) use ($user): void {
                    $homeroom->selectRaw('1')->from('teachers')->whereColumn('teachers.id', 'cg.homeroom_teacher_id')->where('teachers.user_id', $user->id)->where('teachers.school_id', $user->school_id);
                });
            });
        }

        return $query->orderByDesc('exams.starts_at')->orderByDesc('exams.id');
    }

    /** @param list<int> $attemptIds
     * @return list<array<string, mixed>>
     */
    private function itemStatistics(Exam $exam, array $attemptIds, int $gradedCount): array
    {
        $items = ExamQuestionItem::query()->where('exam_id', $exam->id)->with(['questionVersion', 'question'])->orderBy('sequence')->get();
        $answers = $attemptIds === [] ? collect() : DB::table('exam_answers')->whereIn('exam_attempt_id', $attemptIds)
            ->whereIn('exam_question_id', $items->pluck('exam_question_id'))->get()->groupBy('exam_question_id');

        return $items->map(function (ExamQuestionItem $item) use ($answers, $gradedCount): array {
            $version = $item->questionVersion ?? $item->question;
            $itemAnswers = $answers->get($item->exam_question_id, collect());
            $objective = in_array($version?->type, ['multiple_choice', 'true_false', 'matching'], true);
            $objectiveAnswers = $itemAnswers->filter(fn ($answer) => $answer->is_correct !== null);
            $correctCount = $objectiveAnswers->where('is_correct', true)->count();
            $scoreSum = $itemAnswers->sum(fn ($answer) => (float) ($answer->score ?? 0));
            $averagePoints = $gradedCount ? round($scoreSum / $gradedCount, 2) : null;

            return [
                'question_id' => (int) $item->exam_question_id,
                'prompt' => (string) ($version?->prompt ?? ''),
                'type' => (string) ($version?->type ?? 'unknown'),
                'max_points' => (float) $item->points,
                'attempts' => $gradedCount,
                'answered' => $itemAnswers->filter(fn ($answer) => trim((string) $answer->answer) !== '')->count(),
                'correct_count' => $objective ? $correctCount : null,
                'correct_rate' => $objective && $objectiveAnswers->isNotEmpty() ? round($correctCount / $objectiveAnswers->count() * 100, 1) : null,
                'average_points' => $averagePoints,
                'average_percent' => $averagePoints !== null && (float) $item->points > 0 ? round($averagePoints / (float) $item->points * 100, 1) : null,
            ];
        })->all();
    }
}
