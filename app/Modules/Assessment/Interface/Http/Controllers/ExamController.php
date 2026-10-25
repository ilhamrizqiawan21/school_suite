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
        $questions = ExamQuestion::where('school_id', $schoolId)->when($user->hasRole(['teacher', 'homeroom']), fn ($q) => $q->where('owner_id', $user->id))->latest()->get();
        $exams = Exam::withCount(['items', 'attempts'])->where('school_id', $schoolId)->whereIn('teaching_assignment_id', $ids)->when($user->hasRole('student'), fn ($query) => $query->whereIn('status', ['published', 'results_published']))->latest('starts_at')->get();
        $components = DB::table('assessment_components')->join('assessment_schemes', 'assessment_schemes.id', '=', 'assessment_components.scheme_id')->where('assessment_schemes.school_id', $schoolId)->where('assessment_schemes.is_active', true)->get(['assessment_components.id', 'assessment_components.name', 'assessment_components.teaching_assignment_id']);
        $attempts = $user->hasRole('student') ? ExamAttempt::with('exam:id,title,starts_at,ends_at,status')->whereIn('exam_id', $exams->pluck('id'))->whereIn('enrollment_id', $this->studentEnrollmentIds($request, $term?->id))->get() : collect();

        return Inertia::render('Assessment/Exams/Index', ['assignments' => $assignments, 'questions' => $questions, 'exams' => $exams, 'attempts' => $attempts, 'components' => $components, 'can_manage' => $user->hasRole(['admin', 'operator', 'teacher', 'homeroom']), 'is_student' => $user->hasRole('student')]);
    }

    public function storeQuestion(Request $request): RedirectResponse
    {
        $this->authorizeManager($request);
        $data = $request->validate(['teaching_assignment_id' => ['nullable', 'integer'], 'type' => ['required', 'in:multiple_choice,essay'], 'prompt' => ['required', 'string', 'max:10000'], 'options' => ['nullable', 'array', 'min:2', 'max:8'], 'options.*' => ['string', 'max:1000'], 'correct_answer' => ['nullable', 'string', 'max:2000'], 'explanation' => ['nullable', 'string', 'max:5000'], 'points' => ['required', 'numeric', 'min:.1', 'max:100']]);
        if ($data['type'] === 'multiple_choice') {
            abort_unless(! empty($data['options']) && in_array($data['correct_answer'] ?? null, $data['options'], true), 422, 'Kunci jawaban harus cocok dengan salah satu opsi.');
        }
        if (! empty($data['teaching_assignment_id'])) {
            abort_unless(in_array((int) $data['teaching_assignment_id'], $this->visibleAssignmentIds($request, $this->academicQuery->getActiveTerm((int) $request->user()->school_id)?->id ?? 0), true), 403);
        }
        ExamQuestion::create([...$data, 'school_id' => $request->user()->school_id, 'owner_id' => $request->user()->id, 'is_active' => true]);

        return back()->with('message', 'Soal berhasil disimpan.');
    }

    public function storeExam(Request $request): RedirectResponse
    {
        $this->authorizeManager($request);
        $data = $request->validate(['teaching_assignment_id' => ['required', 'integer'], 'assessment_component_id' => ['nullable', 'exists:assessment_components,id'], 'title' => ['required', 'string', 'max:180'], 'instructions' => ['nullable', 'string', 'max:5000'], 'starts_at' => ['required', 'date'], 'ends_at' => ['required', 'date', 'after:starts_at'], 'duration_minutes' => ['required', 'integer', 'min:1', 'max:480'], 'randomize_questions' => ['required', 'boolean'], 'randomize_options' => ['required', 'boolean'], 'max_violations' => ['required', 'integer', 'min:1', 'max:20'], 'question_ids' => ['required', 'array', 'min:1'], 'question_ids.*' => ['integer']]);
        $term = $this->academicQuery->getActiveTerm((int) $request->user()->school_id);
        abort_unless($term && in_array((int) $data['teaching_assignment_id'], $this->visibleAssignmentIds($request, $term->id), true), 403);
        if (! empty($data['assessment_component_id'])) {
            $validComponent = DB::table('assessment_components')->join('assessment_schemes', 'assessment_schemes.id', '=', 'assessment_components.scheme_id')->where('assessment_components.id', $data['assessment_component_id'])->where('assessment_schemes.school_id', $request->user()->school_id)->where(fn ($query) => $query->whereNull('assessment_components.teaching_assignment_id')->orWhere('assessment_components.teaching_assignment_id', $data['teaching_assignment_id']))->exists();
            abort_unless($validComponent, 422, 'Komponen nilai tidak sesuai sekolah atau penugasan.');
        }
        $questions = ExamQuestion::where('school_id', $request->user()->school_id)->whereIn('id', $data['question_ids'])->get();
        abort_unless($questions->count() === count(array_unique($data['question_ids'])), 422, 'Soal ujian tidak valid.');
        $exam = DB::transaction(function () use ($data, $questions, $request) {
            $exam = Exam::create([...collect($data)->except('question_ids')->all(), 'school_id' => $request->user()->school_id, 'created_by' => $request->user()->id, 'status' => 'draft']);
            foreach ($data['question_ids'] as $i => $id) {
                $q = $questions->firstWhere('id', $id);
                $exam->items()->create(['exam_question_id' => $id, 'sequence' => $i + 1, 'points' => $q->points]);
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
        $attempt->load(['exam.items.question', 'answers']);
        $map = $attempt->exam->items->keyBy('exam_question_id');
        $questions = collect($attempt->question_order)->map(function ($id) use ($map, $attempt) {
            $q = $map->get($id)?->question;
            $options = $q?->options;
            if ($q && $attempt->exam->randomize_options && is_array($options)) {
                mt_srand($attempt->id * 100000 + $q->id);
                shuffle($options);
                mt_srand();
            }

            return ['id' => $q?->id, 'type' => $q?->type, 'prompt' => $q?->prompt, 'options' => $options, 'points' => $map->get($id)?->points, 'answer' => $attempt->answers->firstWhere('exam_question_id', $id)?->answer];
        })->filter()->values();

        return Inertia::render('Assessment/Exams/Attempt', ['attempt' => $attempt->only(['id', 'status', 'expires_at', 'violation_count']), 'exam' => $attempt->exam->only(['id', 'title', 'instructions', 'max_violations']), 'questions' => $questions]);
    }

    public function autosave(Request $request, ExamAttempt $attempt): RedirectResponse
    {
        $this->authorizeAttempt($request, $attempt);
        $this->assertActive($attempt);
        $data = $request->validate(['question_id' => ['required', 'integer'], 'answer' => ['nullable', 'string', 'max:20000']]);
        abort_unless(in_array((int) $data['question_id'], $attempt->question_order, true), 422);
        ExamAnswer::updateOrCreate(['exam_attempt_id' => $attempt->id, 'exam_question_id' => $data['question_id']], ['answer' => $data['answer']]);

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
        $answer->load('attempt.exam', 'question');
        $this->authorizeExamManager($request, $answer->attempt->exam);
        abort_unless($answer->question->type === 'essay', 422);
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
                fputcsv($o, (array) $r);
            }fclose($o);
        }, 'hasil-ujian-'.$exam->id.'.csv', ['Content-Type' => 'text/csv']);
    }

    public function results(Request $request, Exam $exam): Response
    {
        $this->authorizeExamManager($request, $exam);
        $attempts = $exam->attempts()->with(['answers.question'])->get();
        $students = DB::table('enrollments')->join('students', 'students.id', '=', 'enrollments.student_id')->whereIn('enrollments.id', $attempts->pluck('enrollment_id'))->get(['enrollments.id as enrollment_id', 'students.nis', 'students.name'])->keyBy('enrollment_id');

        return Inertia::render('Assessment/Exams/Results', ['exam' => $exam, 'attempts' => $attempts->map(fn ($a) => [...$a->toArray(), 'student' => $students->get($a->enrollment_id)])]);
    }

    private function finalize(ExamAttempt $attempt): void
    {
        $attempt->load('exam.items.question', 'answers');
        foreach ($attempt->exam->items as $item) {
            $answer = $attempt->answers->firstWhere('exam_question_id', $item->exam_question_id) ?? ExamAnswer::create(['exam_attempt_id' => $attempt->id, 'exam_question_id' => $item->exam_question_id]);
            if ($item->question->type === 'multiple_choice') {
                $correct = hash_equals((string) $item->question->correct_answer, (string) $answer->answer);
                $answer->update(['is_correct' => $correct, 'score' => $correct ? $item->points : 0, 'graded_at' => now()]);
            }
        }$attempt->update(['status' => $attempt->exam->items->contains(fn ($i) => $i->question->type === 'essay') ? 'pending_review' : 'graded', 'submitted_at' => now()]);
        $this->recalculate($attempt);
    }

    private function recalculate(ExamAttempt $attempt): void
    {
        $attempt->load('exam.items.question', 'answers');
        $pending = $attempt->exam->items->filter(fn ($i) => $i->question->type === 'essay')->contains(fn ($i) => $attempt->answers->firstWhere('exam_question_id', $i->exam_question_id)?->score === null);
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
            $q->join('enrollments', fn ($j) => $j->on('enrollments.class_group_id', '=', 'teaching_assignments.class_group_id')->where('enrollments.term_id', $termId))->join('students', 'students.id', '=', 'enrollments.student_id')->where('students.user_id', $u->id);
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
