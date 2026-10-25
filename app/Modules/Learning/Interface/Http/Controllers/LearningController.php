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
        Gate::authorize('viewAny', LearningMaterial::class);

        $user = $request->user();
        $schoolId = (int) $user->school_id;
        $activeTerm = $this->academicQuery->getActiveTerm($schoolId);
        $teachingAssignments = collect();
        $enrollment = null;

        if ($activeTerm) {
            $teachingAssignments = TeachingAssignment::query()
                ->with(['classGroup:id,name,grade_level', 'subject:id,name,code', 'teacher:id,name,user_id'])
                ->where('term_id', $activeTerm->id)
                ->when($user->hasRole(['teacher', 'homeroom']), fn ($query) => $query->whereHas('teacher', fn ($teacher) => $teacher->where('user_id', $user->id)))
                ->when($user->hasRole('student'), function ($query) use ($user, $activeTerm, &$enrollment) {
                    $enrollment = Enrollment::query()
                        ->where('term_id', $activeTerm->id)
                        ->where('status', 'active')
                        ->whereHas('student', fn ($student) => $student->where('user_id', $user->id))
                        ->first();

                    $query->where('class_group_id', $enrollment?->class_group_id ?? 0);
                })
                ->orderBy('class_group_id')
                ->orderBy('subject_id')
                ->get();
        }

        $assignmentIds = $teachingAssignments->pluck('id');
        $materials = LearningMaterial::query()
            ->with('teachingAssignment.classGroup:id,name')
            ->with('teachingAssignment.subject:id,name,code')
            ->where('school_id', $schoolId)
            ->whereIn('teaching_assignment_id', $assignmentIds)
            ->latest('published_at')
            ->get();

        $assignments = LearningAssignment::query()
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

        return Inertia::render('Learning/Index', [
            'active_term' => $activeTerm,
            'teaching_assignments' => $teachingAssignments,
            'materials' => $materials,
            'assignments' => $assignments,
            'read_material_ids' => $enrollment ? LearningMaterialRead::where('enrollment_id', $enrollment->id)->pluck('learning_material_id') : [],
            'capabilities' => [
                'manage' => $user->hasRole(['admin', 'operator', 'teacher', 'homeroom']),
                'submit' => $user->hasRole('student'),
            ],
        ]);
    }

    public function markMaterialRead(Request $request, LearningMaterial $material): RedirectResponse
    {
        Gate::authorize('view', $material);
        abort_unless($request->user()->hasRole('student'), 403);
        $enrollment = Enrollment::where('term_id', $material->teachingAssignment->term_id)
            ->where('class_group_id', $material->teachingAssignment->class_group_id)
            ->where('status', 'active')
            ->whereHas('student', fn ($query) => $query->where('user_id', $request->user()->id))
            ->firstOrFail();
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
            'file' => ['nullable', 'file', 'max:10240', 'mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,txt,jpg,jpeg,png'],
        ]);

        $teachingAssignment = TeachingAssignment::findOrFail($validated['teaching_assignment_id']);
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
            'teaching_assignment_id' => ['required', 'integer', 'exists:teaching_assignments,id'],
            'title' => ['required', 'string', 'max:180'],
            'instructions' => ['nullable', 'string', 'max:10000'],
            'due_at' => ['nullable', 'date'],
            'max_score' => ['required', 'integer', 'min:1', 'max:1000'],
            'allow_late' => ['required', 'boolean'],
            'revision_limit' => ['required', 'integer', 'min:0', 'max:10'],
        ]);

        $assignment = new LearningAssignment([
            ...$validated,
            'school_id' => $request->user()->school_id,
            'created_by' => $request->user()->id,
            'published_at' => now(),
        ]);
        $assignment->teachingAssignment()->associate(TeachingAssignment::findOrFail($validated['teaching_assignment_id']));
        Gate::authorize('manage', $assignment);
        $assignment->save();

        return back()->with('message', 'Tugas berhasil diterbitkan.');
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
            'files.*' => ['file', 'max:10240', 'mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,txt,jpg,jpeg,png,zip'],
        ]);

        $enrollment = Enrollment::query()
            ->where('term_id', $assignment->teachingAssignment->term_id)
            ->where('class_group_id', $assignment->teachingAssignment->class_group_id)
            ->whereHas('student', fn ($query) => $query->where('user_id', $request->user()->id))
            ->firstOrFail();

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
