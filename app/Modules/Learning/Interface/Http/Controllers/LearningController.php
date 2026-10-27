<?php

declare(strict_types=1);

namespace App\Modules\Learning\Interface\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academic\Application\Services\AcademicQueryService;
use App\Modules\Learning\Infrastructure\Models\AssignmentSubmission;
use App\Modules\Learning\Infrastructure\Models\AssignmentSubmissionFile;
use App\Modules\Learning\Infrastructure\Models\LearningAssignment;
use App\Modules\Learning\Infrastructure\Models\LearningMaterial;
use App\Modules\Learning\Infrastructure\Models\LearningMaterialRead;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LearningController extends Controller
{
    public function __construct(private readonly AcademicQueryService $academicQuery) {}

    public function index(Request $request): Response
    {
        return $this->renderIndex($request, 'all');
    }

    public function materials(Request $request): Response
    {
        return $this->renderIndex($request, 'materials');
    }

    public function assignments(Request $request): Response
    {
        return $this->renderIndex($request, 'assignments');
    }

    private function renderIndex(Request $request, string $section): Response
    {
        Gate::authorize('viewAny', LearningMaterial::class);

        $user = $request->user();
        $schoolId = (int) $user->school_id;
        $activeTerm = $this->academicQuery->getActiveTerm($schoolId);
        $teachingAssignments = collect();
        $enrollment = null;

        if ($activeTerm) {
            if ($user->hasRole('student')) {
                $enrollment = $this->academicQuery->getStudentEnrollmentForTerm((int) $user->id, (int) $activeTerm->id);
                $teachingAssignments = $enrollment
                    ? $this->academicQuery->getLearningTeachingAssignments($activeTerm->id)
                        ->where('class_group_id', $enrollment->class_group_id)
                        ->values()
                    : collect();
            } else {
                $teacherUserId = $user->hasRole(['teacher', 'homeroom']) ? (int) $user->id : null;
                $teachingAssignments = $this->academicQuery->getLearningTeachingAssignments($activeTerm->id, $teacherUserId);
            }
        }

        $assignmentIds = $teachingAssignments->pluck('id');
        $materials = $section === 'assignments'
            ? collect()
            : LearningMaterial::query()
                ->with('teachingAssignment.classGroup:id,name')
                ->with('teachingAssignment.subject:id,name,code')
                ->where('school_id', $schoolId)
                ->whereIn('teaching_assignment_id', $assignmentIds)
                ->latest('published_at')
                ->get();

        $assignments = $section === 'materials'
            ? collect()
            : LearningAssignment::query()
                ->with(['teachingAssignment.classGroup:id,name', 'teachingAssignment.subject:id,name,code'])
                ->with(['submissions' => function ($query) use ($enrollment, $user) {
                    $query->with(['files', 'revisions', 'enrollment.student:id,nis,name']);
                    if ($user->hasRole('student')) {
                        $query->where('enrollment_id', $enrollment?->id ?? 0);
                    }
                }])
                ->where('school_id', $schoolId)
                ->whereIn('teaching_assignment_id', $assignmentIds)
                ->latest('due_at')
                ->get();

        $taskStats = [
            'active_tasks' => $assignments->count(),
            'target_classes' => $assignments->pluck('teaching_assignment_id')->unique()->count(),
            'submissions' => $assignments->sum(fn (LearningAssignment $assignment): int => $assignment->submissions->count()),
            'needs_grading' => $assignments->sum(fn (LearningAssignment $assignment): int => $assignment->submissions->filter(fn (AssignmentSubmission $submission): bool => $submission->review_status !== 'approved' && $submission->status !== 'graded')->count()),
            'overdue' => $assignments->filter(fn (LearningAssignment $assignment): bool => $assignment->due_at?->isPast() === true)->count(),
        ];

        $studentCounts = $activeTerm
            ? $this->academicQuery->getLearningEnrollments((int) $activeTerm->id, $teachingAssignments->pluck('class_group_id')->unique()->map(fn ($id): int => (int) $id)->all())->groupBy('class_group_id')->map->count()
            : collect();
        $taskClasses = $teachingAssignments->map(function ($teachingAssignment) use ($assignments, $studentCounts): array {
            $classAssignments = $assignments->where('teaching_assignment_id', $teachingAssignment->id);
            $submissions = $classAssignments->flatMap(fn (LearningAssignment $assignment) => $assignment->submissions);

            return [
                'id' => $teachingAssignment->id,
                'class_group' => $teachingAssignment->classGroup?->name,
                'subject' => $teachingAssignment->subject?->name,
                'student_count' => $studentCounts->get($teachingAssignment->class_group_id, 0),
                'assignment_count' => $classAssignments->count(),
                'submission_count' => $submissions->count(),
                'needs_grading' => $submissions->filter(fn (AssignmentSubmission $submission): bool => $submission->review_status !== 'approved' && $submission->status !== 'graded')->count(),
                'overdue' => $classAssignments->filter(fn (LearningAssignment $assignment): bool => $assignment->due_at?->isPast() === true)->count(),
                'latest_title' => $classAssignments->sortByDesc('due_at')->first()?->title,
            ];
        })->values();

        return Inertia::render('Learning/Index', [
            'section' => $section,
            'active_term' => $activeTerm,
            'teaching_assignments' => $teachingAssignments,
            'materials' => $materials,
            'assignments' => $assignments,
            'task_stats' => $taskStats,
            'task_classes' => $taskClasses,
            'read_material_ids' => $enrollment ? LearningMaterialRead::where('enrollment_id', $enrollment->id)->pluck('learning_material_id') : [],
            'capabilities' => [
                'manage' => $user->hasRole(['admin', 'operator', 'teacher', 'homeroom']),
                'submit' => $user->hasRole('student'),
            ],
        ]);
    }

    /**
     * Detail tugas untuk guru maupun siswa, mengikuti alur detail pada LMS
     * lama tanpa memisahkan data berdasarkan model akademik yang berbeda.
     */
    public function showAssignment(Request $request, LearningAssignment $assignment): Response
    {
        $assignment->loadMissing([
            'teachingAssignment.classGroup:id,name,grade_level',
            'teachingAssignment.subject:id,name,code',
            'teachingAssignment.teacher:id,name',
        ]);
        Gate::authorize('view', $assignment);

        $submission = null;
        $canSubmit = false;
        if ($request->user()->hasRole('student')) {
            $enrollment = $this->academicQuery->getStudentEnrollmentForClassTerm(
                (int) $request->user()->id,
                (int) $assignment->teachingAssignment->term_id,
                (int) $assignment->teachingAssignment->class_group_id
            );
            $submission = $enrollment
                ? AssignmentSubmission::with('files')->where('learning_assignment_id', $assignment->id)->where('enrollment_id', $enrollment->id)->first()
                : null;
            $canSubmit = $submission === null || $submission->review_status === 'needs_revision';
        }

        $isStudent = $request->user()->hasRole('student');

        return Inertia::render('Learning/TaskShow', [
            'assignment' => [
                'id' => $assignment->id,
                'title' => $assignment->title,
                'instructions' => $assignment->instructions,
                'due_at' => $assignment->due_at?->toIso8601String(),
                'max_score' => $assignment->max_score,
                'allow_late' => $assignment->allow_late,
                'subject' => $assignment->teachingAssignment?->subject?->name,
                'class_group' => $assignment->teachingAssignment?->classGroup?->name,
                'teacher' => $assignment->teachingAssignment?->teacher?->name,
            ],
            'submission' => $submission ? [
                'id' => $submission->id,
                'status' => $submission->status,
                'review_status' => $submission->review_status,
                'content' => $submission->content,
                'submitted_at' => $submission->submitted_at?->toIso8601String(),
                'score' => $submission->score,
                'feedback' => $submission->feedback,
                'files' => $submission->files->map(fn (AssignmentSubmissionFile $file) => [
                    'id' => $file->id,
                    'name' => $file->original_filename,
                    'url' => route('learning.submissions.files.download', $file),
                ])->values()->all(),
            ] : null,
            'can_submit' => $canSubmit,
            'can_manage' => $request->user()->hasRole(['teacher', 'homeroom']),
            'submit_url' => $isStudent
                ? route('siswa.tugas.kumpul', $assignment)
                : route('learning.assignments.submit', $assignment),
            'submissions_url' => route('learning.assignments.submissions', $assignment),
            'back_url' => $isStudent
                ? route('siswa.tugas.index')
                : route('learning.assignments.index'),
        ]);
    }

    /**
     * Daftar pengumpulan per siswa untuk guru. Baris siswa tanpa pengumpulan
     * tetap ditampilkan agar alurnya sama dengan menu Tugas LMS lama.
     */
    public function assignmentSubmissions(Request $request, LearningAssignment $assignment): Response
    {
        $assignment->loadMissing([
            'teachingAssignment.classGroup:id,name,grade_level',
            'teachingAssignment.subject:id,name,code',
        ]);
        Gate::authorize('manage', $assignment);

        $enrollments = $this->academicQuery->getLearningEnrollments(
            (int) $assignment->teachingAssignment->term_id,
            [(int) $assignment->teachingAssignment->class_group_id]
        );
        $submissions = AssignmentSubmission::with('files')
            ->where('learning_assignment_id', $assignment->id)
            ->whereIn('enrollment_id', $enrollments->pluck('id'))
            ->get()
            ->keyBy('enrollment_id');

        return Inertia::render('Learning/TaskSubmissions', [
            'assignment' => [
                'id' => $assignment->id,
                'title' => $assignment->title,
                'subject' => $assignment->teachingAssignment?->subject?->name,
                'class_group' => $assignment->teachingAssignment?->classGroup?->name,
                'due_at' => $assignment->due_at?->toIso8601String(),
                'max_score' => $assignment->max_score,
            ],
            'submissions' => $enrollments->map(function ($enrollment) use ($submissions) {
                $submission = $submissions->get($enrollment->id);

                return [
                    'id' => $submission?->id,
                    'student' => [
                        'name' => $enrollment->student?->name,
                        'nis' => $enrollment->student?->nis,
                    ],
                    'status' => $submission?->status ?? 'not_submitted',
                    'review_status' => $submission?->review_status,
                    'submitted_at' => $submission?->submitted_at?->toIso8601String(),
                    'content' => $submission?->content,
                    'score' => $submission?->score,
                    'feedback' => $submission?->feedback,
                    'files' => $submission?->files->map(fn (AssignmentSubmissionFile $file) => [
                        'id' => $file->id,
                        'name' => $file->original_filename,
                        'url' => route('learning.submissions.files.download', $file),
                    ])->values()->all() ?? [],
                    'grade_url' => $submission ? route('learning.submissions.grade', $submission) : null,
                ];
            })->values()->all(),
            'back_url' => route('learning.assignments.index'),
            'assignment_url' => route('learning.assignments.show', $assignment),
        ]);
    }

    public function markMaterialRead(Request $request, LearningMaterial $material): RedirectResponse
    {
        Gate::authorize('view', $material);
        abort_unless($request->user()->hasRole('student'), 403);
        $enrollment = $this->academicQuery->getStudentEnrollmentForClassTerm(
            (int) $request->user()->id,
            (int) $material->teachingAssignment->term_id,
            (int) $material->teachingAssignment->class_group_id
        );
        abort_unless($enrollment !== null, 404);
        LearningMaterialRead::updateOrCreate(
            ['learning_material_id' => $material->id, 'enrollment_id' => $enrollment->id],
            ['read_at' => now()]
        );

        return back()->with('message', 'Materi ditandai sudah dibaca.');
    }

    public function storeMaterial(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'teaching_assignment_id' => ['required', 'integer', 'exists:teaching_assignments,id'],
            'title' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:5000'],
            // Materi hanya menerima format yang dipakai di perpustakaan bahan
            // ajar. File disimpan privat dengan nama acak dan tidak pernah
            // dieksekusi oleh aplikasi.
            'file' => [
                'required', 'file', 'max:10240',
                'mimes:pdf,docx,jpg,jpeg,png',
                'extensions:pdf,docx,jpg,jpeg,png',
                'mimetypes:application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document,image/jpeg,image/png',
            ],
        ]);

        $teachingAssignment = $this->academicQuery->getTeachingAssignmentById((int) $validated['teaching_assignment_id']);
        abort_unless($teachingAssignment !== null, 404);
        $material = new LearningMaterial([
            ...$validated,
            'school_id' => $request->user()->school_id,
            'created_by' => $request->user()->id,
            'published_at' => now(),
        ]);
        unset($material->file);
        $material->teachingAssignment()->associate($teachingAssignment);
        Gate::authorize('manage', $material);

        if ($request->hasFile('file')) {
            $uploaded = $request->file('file');
            $material->file_path = $uploaded->storeAs('learning/materials', Str::uuid().'.'.$uploaded->extension(), 'local');
            $material->original_filename = $uploaded->getClientOriginalName();
        }

        $material->save();

        return back()->with('message', 'Materi berhasil diterbitkan.');
    }

    public function downloadMaterial(Request $request, LearningMaterial $material): StreamedResponse
    {
        Gate::authorize('view', $material);
        abort_unless($material->file_path, 404);

        return Storage::disk('local')->download($material->file_path, $material->original_filename);
    }

    public function destroyMaterial(LearningMaterial $material): RedirectResponse
    {
        Gate::authorize('manage', $material);
        if ($material->file_path) {
            Storage::disk('local')->delete($material->file_path);
        }
        $material->delete();

        return back()->with('message', 'Materi berhasil dihapus.');
    }

    public function storeAssignment(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'teaching_assignment_id' => ['nullable', 'integer', 'exists:teaching_assignments,id', 'required_without:teaching_assignment_ids'],
            'teaching_assignment_ids' => ['nullable', 'array', 'min:1', 'required_without:teaching_assignment_id'],
            'teaching_assignment_ids.*' => ['integer', 'exists:teaching_assignments,id'],
            'title' => ['required', 'string', 'max:180'],
            'instructions' => ['nullable', 'string', 'max:10000'],
            'due_at' => ['nullable', 'date'],
            'max_score' => ['required', 'integer', 'min:1', 'max:1000'],
            'allow_late' => ['required', 'boolean'],
            'revision_limit' => ['required', 'integer', 'min:0', 'max:10'],
        ]);

        $teachingAssignmentIds = collect($validated['teaching_assignment_ids'] ?? [$validated['teaching_assignment_id']])
            ->filter()
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();
        $activeTerm = $this->academicQuery->getActiveTerm((int) $request->user()->school_id);
        $allowedIds = $activeTerm
            ? $this->academicQuery->getLearningTeachingAssignments($activeTerm->id, (int) $request->user()->id)->pluck('id')
            : collect();
        abort_unless($teachingAssignmentIds->diff($allowedIds)->isEmpty(), 403);

        DB::transaction(function () use ($request, $validated, $teachingAssignmentIds): void {
            foreach ($teachingAssignmentIds as $teachingAssignmentId) {
                $assignment = new LearningAssignment([
                    'school_id' => $request->user()->school_id,
                    'teaching_assignment_id' => $teachingAssignmentId,
                    'created_by' => $request->user()->id,
                    'title' => $validated['title'],
                    'instructions' => $validated['instructions'] ?? null,
                    'due_at' => $validated['due_at'] ?? null,
                    'max_score' => $validated['max_score'],
                    'allow_late' => $validated['allow_late'],
                    'revision_limit' => $validated['revision_limit'],
                    'published_at' => now(),
                ]);
                $teachingAssignment = $this->academicQuery->getTeachingAssignmentById($teachingAssignmentId);
                abort_unless($teachingAssignment !== null, 404);
                $assignment->teachingAssignment()->associate($teachingAssignment);
                Gate::authorize('manage', $assignment);
                $assignment->save();
            }
        });

        return back()->with('message', $teachingAssignmentIds->count() > 1
            ? 'Tugas berhasil diterbitkan ke '.$teachingAssignmentIds->count().' kelas.'
            : 'Tugas berhasil diterbitkan.');
    }

    public function destroyAssignment(LearningAssignment $assignment): RedirectResponse
    {
        Gate::authorize('manage', $assignment);
        $assignment->load('submissions.files');
        foreach ($assignment->submissions as $submission) {
            foreach ($submission->files as $file) {
                Storage::disk('local')->delete($file->file_path);
            }
        }
        $assignment->delete();

        return back()->with('message', 'Tugas berhasil dihapus.');
    }

    public function submitAssignment(Request $request, LearningAssignment $assignment): RedirectResponse
    {
        Gate::authorize('submit', $assignment);
        $validated = $request->validate([
            'content' => ['nullable', 'string', 'max:20000', 'required_without:files'],
            'files' => ['nullable', 'array', 'max:5', 'required_without:content'],
            'files.*' => [
                'file', 'max:5120', 'mimes:pdf,jpg,jpeg', 'extensions:pdf,jpg,jpeg',
                'mimetypes:application/pdf,image/jpeg',
            ],
        ]);

        $totalUploadBytes = collect($request->file('files', []))
            ->sum(fn ($file): int => (int) $file->getSize());
        if ($totalUploadBytes > 20 * 1024 * 1024) {
            return back()->withInput()->withErrors(['files' => 'Total lampiran maksimal 20 MB untuk satu pengumpulan.']);
        }

        $enrollment = $this->academicQuery->getStudentEnrollmentForClassTerm(
            (int) $request->user()->id,
            (int) $assignment->teachingAssignment->term_id,
            (int) $assignment->teachingAssignment->class_group_id
        );
        abort_unless($enrollment !== null, 404);

        if ($assignment->due_at?->isPast() && ! $assignment->allow_late) {
            return back()->with('error', 'Batas pengumpulan tugas telah berakhir.');
        }

        DB::transaction(function () use ($request, $assignment, $enrollment, $validated) {
            $existing = AssignmentSubmission::where('learning_assignment_id', $assignment->id)->where('enrollment_id', $enrollment->id)->first();
            if ($existing && $existing->review_status !== 'needs_revision') {
                abort(422, 'Pengumpulan hanya dapat diperbarui setelah guru meminta revisi.');
            }
            if ($existing && $existing->revision_count >= $assignment->revision_limit) {
                abort(422, 'Batas revisi tugas telah tercapai.');
            }
            if ($existing) {
                $existing->revisions()->create([
                    'revision_number' => $existing->revision_count,
                    'content' => $existing->content,
                    'file_names' => $existing->files()->pluck('original_filename')->all(),
                    'submitted_at' => $existing->submitted_at ?? now(),
                ]);
                foreach ($existing->files as $oldFile) {
                    Storage::disk('local')->delete($oldFile->file_path);
                    $oldFile->delete();
                }
            }
            $submission = AssignmentSubmission::updateOrCreate(
                ['learning_assignment_id' => $assignment->id, 'enrollment_id' => $enrollment->id],
                [
                    'content' => $validated['content'] ?? null,
                    'status' => $assignment->due_at?->isPast() ? 'late' : 'submitted',
                    'review_status' => 'pending',
                    'revision_count' => $existing ? $existing->revision_count + 1 : 0,
                    'submitted_at' => now(),
                    'score' => null,
                    'feedback' => null,
                    'graded_by' => null,
                    'graded_at' => null,
                ]
            );

            foreach ($request->file('files', []) as $uploaded) {
                $path = $uploaded->storeAs('learning/submissions/'.$submission->id, Str::uuid().'.'.$uploaded->extension(), 'local');
                $submission->files()->create([
                    'file_path' => $path,
                    'original_filename' => $uploaded->getClientOriginalName(),
                    'mime_type' => $uploaded->getMimeType(),
                    'size' => $uploaded->getSize(),
                ]);
            }
        });

        return back()->with('message', 'Tugas berhasil dikumpulkan.');
    }

    public function gradeSubmission(Request $request, AssignmentSubmission $submission): RedirectResponse
    {
        Gate::authorize('grade', $submission);
        $validated = $request->validate([
            'score' => ['nullable', 'required_if:review_status,approved', 'numeric', 'min:0', 'max:'.$submission->assignment->max_score],
            'feedback' => ['nullable', 'string', 'max:5000'],
            'review_status' => ['required', 'in:approved,needs_revision'],
        ]);

        $submission->update([
            ...$validated,
            'status' => $validated['review_status'] === 'approved' ? 'graded' : $submission->status,
            'graded_by' => $request->user()->id,
            'graded_at' => now(),
        ]);

        return back()->with('message', 'Nilai tugas berhasil disimpan.');
    }

    public function downloadSubmissionFile(Request $request, AssignmentSubmissionFile $file): StreamedResponse
    {
        $submission = $file->submission()->with(['assignment', 'enrollment.student'])->firstOrFail();
        $canAccess = Gate::forUser($request->user())->allows('grade', $submission)
            || (int) $submission->enrollment->student->user_id === (int) $request->user()->id;
        abort_unless($canAccess, 403);

        return Storage::disk('local')->download($file->file_path, $file->original_filename);
    }
}
