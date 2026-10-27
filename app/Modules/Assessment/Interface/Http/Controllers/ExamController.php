<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Interface\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academic\Application\Services\AcademicQueryService;
use App\Modules\Assessment\Infrastructure\Models\Exam;
use App\Modules\Assessment\Infrastructure\Models\ExamAnswer;
use App\Modules\Assessment\Infrastructure\Models\ExamAttempt;
use App\Modules\Assessment\Infrastructure\Models\ExamEvent;
use App\Modules\Assessment\Infrastructure\Models\ExamQuestion;
use App\Modules\Assessment\Infrastructure\Models\ExamQuestionVersion;
use App\Modules\Assessment\Infrastructure\Models\QuestionPack;
use App\Modules\Assessment\Infrastructure\Models\Score;
use App\Shared\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExamController extends Controller
{
    public function __construct(private readonly AcademicQueryService $academicQuery, private readonly AuditLogger $audit) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $schoolId = (int) $user->school_id;
        $term = $this->academicQuery->getActiveTerm($schoolId);
        $ids = $term ? $this->visibleAssignmentIds($request, $term->id) : [];
        $assignments = $term ? $this->academicQuery->getLearningTeachingAssignments($term->id, $user->hasRole(['teacher', 'homeroom']) ? (int) $user->id : null)->whereIn('id', $ids)->values() : collect();
        $canManage = $user->hasRole(['admin', 'operator', 'teacher', 'homeroom']);
        $questions = $canManage ? ExamQuestion::where('school_id', $schoolId)->where('is_active', true)->when($user->hasRole(['teacher', 'homeroom']), fn ($q) => $q->where('owner_id', $user->id))->latest()->get(['id', 'type', 'prompt', 'points']) : collect();
        $packs = $canManage ? QuestionPack::query()->where('school_id', $schoolId)->where('status', 'active')->when($user->hasRole(['teacher', 'homeroom']), fn ($q) => $q->where('created_by', $user->id))->with(['items.questionVersion:id,type,prompt,points'])->withCount('items')->orderBy('name')->get()->map(fn (QuestionPack $pack) => [
            'id' => $pack->id,
            'name' => $pack->name,
            'duration_minutes' => $pack->duration_minutes,
            'randomize_questions' => $pack->randomize_questions,
            'randomize_options' => $pack->randomize_options,
            'items_count' => $pack->items_count,
            'items' => $pack->items->map(fn ($item) => ['id' => $item->exam_question_id, 'prompt' => $item->questionVersion?->prompt, 'type' => $item->questionVersion?->type, 'points' => $item->points])->values(),
        ]) : collect();
        $exams = Exam::withCount('items')->when(! $user->hasRole('student'), fn ($query) => $query->withCount('attempts'))->where('school_id', $schoolId)->whereIn('teaching_assignment_id', $ids)->when($user->hasRole('student'), fn ($query) => $query->whereIn('status', ['published', 'results_published']))->latest('starts_at')->get();
        $components = DB::table('assessment_components')->join('assessment_schemes', 'assessment_schemes.id', '=', 'assessment_components.scheme_id')->where('assessment_schemes.school_id', $schoolId)->where('assessment_schemes.is_active', true)->get(['assessment_components.id', 'assessment_components.name', 'assessment_components.teaching_assignment_id']);
        $attempts = $user->hasRole('student') ? ExamAttempt::with('exam:id,title,starts_at,ends_at,status')->whereIn('exam_id', $exams->pluck('id'))->whereIn('enrollment_id', $this->studentEnrollmentIds($request, $term?->id))->get() : collect();
        $canManageQuestionBank = $user->hasRole(['teacher', 'homeroom']);

        return Inertia::render('Assessment/Exams/Index', ['assignments' => $assignments, 'questions' => $questions, 'packs' => $packs, 'exams' => $exams, 'attempts' => $attempts, 'components' => $components, 'can_manage' => $canManage, 'can_manage_question_bank' => $canManageQuestionBank, 'is_student' => $user->hasRole('student')]);
    }

    public function storeExam(Request $request): RedirectResponse
    {
        $this->authorizeManager($request);
        $data = $request->validate(['teaching_assignment_id' => ['required', 'integer'], 'assessment_component_id' => ['nullable', 'exists:assessment_components,id'], 'title' => ['required', 'string', 'max:180'], 'instructions' => ['nullable', 'string', 'max:5000'], 'starts_at' => ['required', 'date'], 'ends_at' => ['required', 'date', 'after:starts_at'], 'duration_minutes' => ['required', 'integer', 'min:1', 'max:480'], 'randomize_questions' => ['required', 'boolean'], 'randomize_options' => ['required', 'boolean'], 'max_violations' => ['required', 'integer', 'min:1', 'max:20'], 'question_pack_id' => ['nullable', 'integer'], 'question_ids' => ['nullable', 'array', 'min:1'], 'question_ids.*' => ['integer']]);
        abort_unless(((bool) ($data['question_pack_id'] ?? null)) !== ((bool) ($data['question_ids'] ?? [])), 422, 'Pilih tepat satu sumber soal: paket atau soal manual.');
        $term = $this->academicQuery->getActiveTerm((int) $request->user()->school_id);
        abort_unless($term && in_array((int) $data['teaching_assignment_id'], $this->visibleAssignmentIds($request, $term->id), true), 403);
        if (! empty($data['assessment_component_id'])) {
            $validComponent = DB::table('assessment_components')->join('assessment_schemes', 'assessment_schemes.id', '=', 'assessment_components.scheme_id')->where('assessment_components.id', $data['assessment_component_id'])->where('assessment_schemes.school_id', $request->user()->school_id)->where(fn ($query) => $query->whereNull('assessment_components.teaching_assignment_id')->orWhere('assessment_components.teaching_assignment_id', $data['teaching_assignment_id']))->exists();
            abort_unless($validComponent, 422, 'Komponen nilai tidak sesuai sekolah atau penugasan.');
        }
        $pack = null;
        if (! empty($data['question_pack_id'])) {
            $pack = QuestionPack::query()->whereKey($data['question_pack_id'])->where('school_id', $request->user()->school_id)->where('status', 'active')
                ->when($request->user()->hasRole(['teacher', 'homeroom']), fn ($query) => $query->where('created_by', $request->user()->id))
                ->with(['items.question', 'items.questionVersion'])->firstOrFail();
            abort_unless($pack->items->isNotEmpty(), 422, 'Paket soal kosong.');
            foreach ($pack->items as $item) {
                abort_unless($item->question && (int) $item->question->school_id === (int) $request->user()->school_id && $item->questionVersion && (int) $item->questionVersion->exam_question_id === (int) $item->exam_question_id, 422, 'Isi paket soal tidak lagi valid.');
            }
            $data['question_ids'] = $pack->items->pluck('exam_question_id')->all();
        }
        $questions = ExamQuestion::where('school_id', $request->user()->school_id)
            ->when(! $pack, fn ($query) => $query->where('is_active', true))
            ->whereIn('id', $data['question_ids'])
            ->when(! $pack && $request->user()->hasRole(['teacher', 'homeroom']), fn ($query) => $query->where('owner_id', $request->user()->id))
            ->get();
        abort_unless($questions->count() === count(array_unique($data['question_ids'])), 422, 'Soal ujian tidak valid.');
        $versionMap = ExamQuestionVersion::whereIn('exam_question_id', $questions->pluck('id'))->get()
            ->keyBy(fn (ExamQuestionVersion $version) => $version->exam_question_id.':'.$version->version_number);
        foreach ($questions as $index => $question) {
            $packItem = $pack?->items->firstWhere('exam_question_id', $question->id);
            if ($packItem) {
                $question->setAttribute('selected_version_id', $packItem->question_version_id);
                $question->setAttribute('selected_points', $packItem->points);

                continue;
            }
            $version = $versionMap->get($question->id.':'.$question->current_version);
            abort_unless($version, 422, 'Versi soal belum tersedia.');
            $question->setAttribute('selected_version_id', $version->id);
        }
        $exam = DB::transaction(function () use ($data, $questions, $request, $pack) {
            $exam = Exam::create([...collect($data)->except(['question_ids', 'question_pack_id'])->all(), 'question_pack_id' => $pack?->id, 'school_id' => $request->user()->school_id, 'created_by' => $request->user()->id, 'status' => 'draft']);
            foreach ($data['question_ids'] as $i => $id) {
                $q = $questions->firstWhere('id', $id);
                $exam->items()->create(['exam_question_id' => $id, 'question_version_id' => $q->getAttribute('selected_version_id'), 'sequence' => $i + 1, 'points' => $q->getAttribute('selected_points') ?? $q->points]);
            }

            return $exam;
        });

        return redirect('/ujian')->with('message', 'Draf ujian berhasil dibuat.');
    }

    public function publish(Request $request, Exam $exam): RedirectResponse
    {
        $this->authorizeExamManager($request, $exam);
        abort_unless($exam->items()->exists(), 422);
        $exam->update(['status' => 'published']);
        $this->audit->log('assessment.exam.published', 'exam', (string) $exam->id, ['status' => 'published'], actor: $request->user(), schoolId: (int) $exam->school_id);

        return back()->with('message', 'Ujian diterbitkan.');
    }

    public function start(Request $request, Exam $exam): RedirectResponse
    {
        abort_unless($request->user()->hasRole('student') && (int) $exam->school_id === (int) $request->user()->school_id, 403);
        abort_unless($exam->status === 'published' && now()->between($exam->starts_at, $exam->ends_at), 422, 'Ujian belum tersedia atau telah berakhir.');
        $assignment = DB::table('teaching_assignments')->find($exam->teaching_assignment_id);
        $enrollment = DB::table('enrollments')->join('students', 'students.id', '=', 'enrollments.student_id')->where('students.user_id', $request->user()->id)->where('enrollments.term_id', $assignment->term_id)->where('enrollments.class_group_id', $assignment->class_group_id)->where('enrollments.status', 'active')->select('enrollments.id')->first();
        abort_unless($enrollment, 403);
        $existing = ExamAttempt::where('exam_id', $exam->id)->where('enrollment_id', $enrollment->id)->first();
        if ($existing) {
            return redirect('/ujian/attempt/'.$existing->id);
        }
        $ids = $exam->items()->pluck('exam_question_id')->all();
        if ($exam->randomize_questions) {
            shuffle($ids);
        }$expires = now()->addMinutes($exam->duration_minutes)->min($exam->ends_at);
        $attempt = ExamAttempt::create(['exam_id' => $exam->id, 'enrollment_id' => $enrollment->id, 'status' => 'in_progress', 'started_at' => now(), 'expires_at' => $expires, 'last_heartbeat_at' => now(), 'question_order' => $ids]);
        ExamEvent::create(['exam_attempt_id' => $attempt->id, 'type' => 'started', 'ip_address' => $request->ip(), 'occurred_at' => now()]);

        return redirect('/ujian/attempt/'.$attempt->id);
    }

    public function attempt(Request $request, ExamAttempt $attempt): Response
    {
        $this->authorizeAttempt($request, $attempt);
        if ($attempt->status === 'in_progress' && now()->greaterThan($attempt->expires_at)) {
            $this->finalize($attempt);
        }
        $attempt->load(['exam.items.question', 'exam.items.questionVersion', 'answers']);
        $map = $attempt->exam->items->keyBy('exam_question_id');
        $questions = collect($attempt->question_order)->map(function ($id) use ($map, $attempt) {
            $item = $map->get($id);
            $q = $item?->questionVersion ?? $item?->question;
            $options = $q?->options;
            if ($q && $attempt->exam->randomize_options && is_array($options)) {
                mt_srand($attempt->id * 100000 + $q->id);
                shuffle($options);
                mt_srand();
            }
            $matchingPairs = $q?->matching_pairs ?? [];
            $matchingOptions = collect($matchingPairs)->pluck('right')->shuffle()->values()->all();

            return ['id' => $id, 'type' => $q?->type, 'prompt' => $q?->prompt, 'options' => $options, 'matching_pairs' => collect($matchingPairs)->map(fn ($pair) => ['left' => $pair['left']])->values(), 'matching_options' => $matchingOptions, 'points' => $item?->points, 'answer' => $attempt->answers->firstWhere('exam_question_id', $id)?->answer];
        })->filter()->values();

        return Inertia::render('Assessment/Exams/Attempt', ['attempt' => $attempt->only(['id', 'status', 'expires_at', 'violation_count']), 'exam' => $attempt->exam->only(['id', 'title', 'instructions', 'max_violations']), 'questions' => $questions]);
    }

    public function autosave(Request $request, ExamAttempt $attempt): RedirectResponse
    {
        $this->authorizeAttempt($request, $attempt);
        $this->assertActive($attempt);
        $data = $request->validate(['question_id' => ['required', 'integer'], 'answer' => ['nullable', 'string', 'max:20000']]);
        abort_unless(in_array((int) $data['question_id'], $attempt->question_order, true), 422);
        $versionId = DB::table('exam_question_items')->where('exam_id', $attempt->exam_id)->where('exam_question_id', $data['question_id'])->value('question_version_id');
        ExamAnswer::updateOrCreate(['exam_attempt_id' => $attempt->id, 'exam_question_id' => $data['question_id']], ['question_version_id' => $versionId, 'answer' => $data['answer']]);

        return back();
    }

    public function heartbeat(Request $request, ExamAttempt $attempt): RedirectResponse
    {
        $this->authorizeAttempt($request, $attempt);
        $this->assertActive($attempt);
        $attempt->update(['last_heartbeat_at' => now()]);

        return back();
    }

    public function violation(Request $request, ExamAttempt $attempt): RedirectResponse
    {
        $this->authorizeAttempt($request, $attempt);
        $this->assertActive($attempt);
        $data = $request->validate(['type' => ['required', 'in:tab_hidden,window_blur,fullscreen_exit']]);
        $attempt->increment('violation_count');
        ExamEvent::create(['exam_attempt_id' => $attempt->id, 'type' => $data['type'], 'ip_address' => $request->ip(), 'occurred_at' => now()]);
        $attempt->refresh();
        if ($attempt->violation_count >= $attempt->exam->max_violations) {
            $this->finalize($attempt);
        }

        return back();
    }

    public function submit(Request $request, ExamAttempt $attempt): RedirectResponse
    {
        $this->authorizeAttempt($request, $attempt);
        if ($attempt->status === 'in_progress') {
            $this->finalize($attempt);
        }

        return redirect('/ujian')->with('message', 'Jawaban ujian berhasil dikirim.');
    }

    public function gradeEssay(Request $request, ExamAnswer $answer): RedirectResponse
    {
        $answer->load('attempt.exam', 'question', 'questionVersion');
        $this->authorizeExamManager($request, $answer->attempt->exam);
        abort_unless(($answer->questionVersion ?? $answer->question)->type === 'essay', 422);
        $max = (float) DB::table('exam_question_items')->where('exam_id', $answer->attempt->exam_id)->where('exam_question_id', $answer->exam_question_id)->value('points');
        $data = $request->validate(['score' => ['required', 'numeric', 'min:0', 'max:'.$max]]);
        $answer->update(['score' => $data['score'], 'graded_by' => $request->user()->id, 'graded_at' => now()]);
        $this->recalculate($answer->attempt);

        return back();
    }

    public function publishResults(Request $request, Exam $exam): RedirectResponse
    {
        $this->authorizeExamManager($request, $exam);
        abort_if($exam->attempts()->where('status', 'pending_review')->exists(), 422, 'Masih ada esai yang belum dikoreksi.');
        DB::transaction(function () use ($exam, $request) {
            $exam->update(['status' => 'results_published']);
            if ($exam->assessment_component_id) {
                foreach ($exam->attempts()->whereIn('status', ['graded', 'submitted'])->get() as $a) {
                    Score::updateOrCreate(['enrollment_id' => $a->enrollment_id, 'assessment_component_id' => $exam->assessment_component_id], ['school_id' => $exam->school_id, 'raw_score' => $a->score, 'final_score' => $a->score, 'recorded_by_user_id' => $request->user()->id, 'notes' => 'Sinkronisasi ujian '.$exam->title]);
                }
            }
        });
        $this->audit->log('assessment.exam.results_published', 'exam', (string) $exam->id, ['assessment_component_id' => $exam->assessment_component_id], actor: $request->user(), schoolId: (int) $exam->school_id);

        return back()->with('message', 'Hasil diterbitkan dan nilai disinkronkan.');
    }

    public function export(Request $request, Exam $exam): StreamedResponse
    {
        $this->authorizeExamManager($request, $exam);
        $rows = DB::table('exam_attempts')->join('enrollments', 'enrollments.id', '=', 'exam_attempts.enrollment_id')->join('students', 'students.id', '=', 'enrollments.student_id')->where('exam_attempts.exam_id', $exam->id)->select('students.nis', 'students.name', 'exam_attempts.status', 'exam_attempts.score', 'exam_attempts.violation_count', 'exam_attempts.submitted_at')->get();

        return response()->streamDownload(function () use ($rows) {
            $o = fopen('php://output', 'w');
            fputcsv($o, ['NIS', 'Nama', 'Status', 'Nilai', 'Pelanggaran', 'Dikirim']);
            foreach ($rows as $r) {
                fputcsv($o, array_map(self::csvSafeCell(...), (array) $r));
            }
            fclose($o);
        }, 'hasil-ujian-'.$exam->id.'.csv', ['Content-Type' => 'text/csv']);
    }

    private static function csvSafeCell(mixed $value): string
    {
        $text = (string) ($value ?? '');

        return preg_match('/^[\x00-\x20]*[=+\-@]/', $text) ? "'".$text : $text;
    }

    public function results(Request $request, Exam $exam): Response
    {
        $this->authorizeExamManager($request, $exam);
        $attempts = $exam->attempts()->with(['answers.question', 'answers.questionVersion'])->get();
        $students = DB::table('enrollments')->join('students', 'students.id', '=', 'enrollments.student_id')->whereIn('enrollments.id', $attempts->pluck('enrollment_id'))->get(['enrollments.id as enrollment_id', 'students.nis', 'students.name'])->keyBy('enrollment_id');

        return Inertia::render('Assessment/Exams/Results', ['exam' => $exam, 'attempts' => $attempts->map(fn ($a) => [...$a->toArray(), 'student' => $students->get($a->enrollment_id)])]);
    }

    private function finalize(ExamAttempt $attempt): void
    {
        $attempt->load('exam.items.question', 'exam.items.questionVersion', 'answers');
        foreach ($attempt->exam->items as $item) {
            $question = $item->questionVersion ?? $item->question;
            $answer = $attempt->answers->firstWhere('exam_question_id', $item->exam_question_id) ?? ExamAnswer::create(['exam_attempt_id' => $attempt->id, 'exam_question_id' => $item->exam_question_id, 'question_version_id' => $item->question_version_id]);
            if (in_array($question->type, ['multiple_choice', 'true_false'], true)) {
                $correct = hash_equals((string) $question->correct_answer, (string) $answer->answer);
                $answer->update(['is_correct' => $correct, 'score' => $correct ? $item->points : 0, 'graded_at' => now()]);
            } elseif ($question->type === 'matching') {
                $expected = json_decode((string) $question->correct_answer, true);
                $given = json_decode((string) $answer->answer, true);
                $correct = false;
                if (is_array($expected) && $expected !== [] && is_array($given)) {
                    ksort($expected);
                    ksort($given);
                    $correct = $given === $expected;
                }
                $answer->update(['is_correct' => $correct, 'score' => $correct ? $item->points : 0, 'graded_at' => now()]);
            }
        }$attempt->update(['status' => $attempt->exam->items->contains(fn ($i) => ($i->questionVersion ?? $i->question)->type === 'essay') ? 'pending_review' : 'graded', 'submitted_at' => now()]);
        $this->recalculate($attempt);
    }

    private function recalculate(ExamAttempt $attempt): void
    {
        $attempt->load('exam.items.question', 'exam.items.questionVersion', 'answers');
        $pending = $attempt->exam->items->filter(fn ($i) => ($i->questionVersion ?? $i->question)->type === 'essay')->contains(fn ($i) => $attempt->answers->firstWhere('exam_question_id', $i->exam_question_id)?->score === null);
        $total = (float) $attempt->exam->items->sum('points');
        $score = $total ? round(((float) $attempt->answers->sum('score') / $total) * 100, 2) : 0;
        $attempt->update(['score' => $score, 'status' => $pending ? 'pending_review' : 'graded']);
    }

    private function assertActive(ExamAttempt $a): void
    {
        abort_unless($a->status === 'in_progress', 422);
        if (now()->greaterThan($a->expires_at)) {
            $this->finalize($a);
            abort(422, 'Waktu ujian telah habis.');
        }
    }

    private function authorizeAttempt(Request $r, ExamAttempt $a): void
    {
        $a->loadMissing('exam');
        $ids = $this->studentEnrollmentIds($r, $this->academicQuery->getActiveTerm((int) $r->user()->school_id)?->id);
        abort_unless($r->user()->hasRole('student') && in_array((int) $a->enrollment_id, $ids, true), 403);
    }

    private function authorizeManager(Request $r): void
    {
        abort_unless($r->user()->hasRole(['admin', 'operator', 'teacher', 'homeroom']), 403);
    }

    private function authorizeExamManager(Request $r, Exam $e): void
    {
        $this->authorizeManager($r);
        $term = $this->academicQuery->getActiveTerm((int) $r->user()->school_id);
        abort_unless((int) $e->school_id === (int) $r->user()->school_id && $term && in_array((int) $e->teaching_assignment_id, $this->visibleAssignmentIds($r, $term->id), true), 403);
    }

    /** @return list<int> */
    private function visibleAssignmentIds(Request $r, int $termId): array
    {
        $u = $r->user();
        $q = DB::table('teaching_assignments')->join('class_groups', 'class_groups.id', '=', 'teaching_assignments.class_group_id')->where('teaching_assignments.term_id', $termId)->where('class_groups.school_id', $u->school_id);
        if ($u->hasRole(['teacher', 'homeroom'])) {
            $q->join('teachers', 'teachers.id', '=', 'teaching_assignments.teacher_id')->where('teachers.user_id', $u->id);
        } elseif ($u->hasRole('student')) {
            $q->join('enrollments', fn ($j) => $j->on('enrollments.class_group_id', '=', 'teaching_assignments.class_group_id')->where('enrollments.term_id', $termId)->where('enrollments.status', 'active'))->join('students', 'students.id', '=', 'enrollments.student_id')->where('students.user_id', $u->id);
        } elseif (! $u->hasRole(['admin', 'operator', 'principal'])) {
            return [];
        }

        return $q->distinct()->pluck('teaching_assignments.id')->map(fn ($id) => (int) $id)->all();
    }

    /** @return list<int> */
    private function studentEnrollmentIds(Request $r, ?int $termId): array
    {
        if (! $termId) {
            return [];
        }

        return DB::table('enrollments')->join('students', 'students.id', '=', 'enrollments.student_id')->where('students.user_id', $r->user()->id)->where('enrollments.term_id', $termId)->where('enrollments.status', 'active')->pluck('enrollments.id')->map(fn ($id) => (int) $id)->all();
    }
}
