<?php

declare(strict_types=1);

namespace App\Modules\Academic\Interface\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academic\Application\Services\AcademicQueryService;
use App\Modules\Academic\Application\Services\StudentMutationService;
use App\Modules\Academic\Infrastructure\Models\ClassGroup;
use App\Modules\Academic\Infrastructure\Models\StudentMutation;
use App\Modules\Academic\Infrastructure\Models\Term;
use App\Modules\Core\Application\Services\AccessScopeService;
use App\Modules\Core\Application\Services\SchoolScopeService;
use App\Modules\Core\Application\Services\ServiceDocumentService;
use App\Modules\Core\Domain\RoleEnum;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StudentMutationController extends Controller
{
    public function __construct(
        private readonly StudentMutationService $mutationService,
        private readonly AcademicQueryService $academicQuery,
        private readonly AccessScopeService $accessScope,
        private readonly SchoolScopeService $schoolScope,
        private readonly ServiceDocumentService $documentService,
    ) {}

    public function publicIndex(Request $request): Response
    {
        $school = $this->schoolScope->getPrimaryPublicSnapshot();
        $schoolId = (int) $school['id'];
        $user = $request->user();

        $activeTerm = Term::query()
            ->whereHas('academicYear', fn ($q) => $q->where('school_id', $schoolId))
            ->where('is_active', true)
            ->first();

        $students = [];
        $myMutations = [];
        if ($user !== null && (int) $user->school_id === $schoolId) {
            if ($user->hasRole([RoleEnum::PARENT->value, RoleEnum::STUDENT->value])) {
                $students = $this->academicQuery->getStudentsForUserScope($schoolId, $user);
            } elseif ($user->hasRole([RoleEnum::ADMIN->value, RoleEnum::OPERATOR->value, RoleEnum::PTSP->value])) {
                $students = $this->academicQuery->searchActiveStudentsForStaff($schoolId, '', 30);
            }

            $myMutations = StudentMutation::query()
                ->where('school_id', $schoolId)
                ->where('submitted_by_user_id', $user->id)
                ->latest('id')
                ->limit(25)
                ->get()
                ->map(fn (StudentMutation $m): array => $this->serialize($m))
                ->values()
                ->all();
        }

        $classes = ClassGroup::query()
            ->where('school_id', $schoolId)
            ->orderBy('grade_level')
            ->orderBy('name')
            ->get(['id', 'name', 'grade_level']);

        return Inertia::render('Public/Services/Mutation', [
            'school' => [
                'name' => $school['name'],
                'logo_url' => ! empty($school['logo_path']) ? route('branding.school', [$school['id'], 'logo']) : null,
                'theme' => $school['theme'] ?? [],
            ],
            'contentBlocks' => $school['content_blocks'] ?? [],
            'periodAvailable' => $activeTerm !== null,
            'activeTermName' => $activeTerm?->name,
            'students' => $students,
            'classes' => $classes,
            'myMutations' => $myMutations,
            'issuedReceipt' => $request->session()->get('issued_mutation_receipt'),
        ]);
    }

    public function publicSubmit(Request $request): RedirectResponse
    {
        $school = $this->schoolScope->getPrimaryPublicSnapshot();
        $schoolId = (int) $school['id'];

        $validated = $request->validate([
            'direction' => ['required', Rule::in(['incoming', 'outgoing'])],
            'student_id' => ['nullable', 'integer'],
            'student_name' => ['required_if:direction,incoming', 'nullable', 'string', 'max:150'],
            'nis' => ['nullable', 'string', 'max:30'],
            'nisn' => ['nullable', 'string', 'max:30'],
            'gender' => ['nullable', Rule::in(['L', 'P'])],
            'birth_date' => ['nullable', 'date'],
            'target_class_group_id' => ['nullable', 'integer'],
            'origin_school' => ['required_if:direction,incoming', 'nullable', 'string', 'max:180'],
            'destination_school' => ['required_if:direction,outgoing', 'nullable', 'string', 'max:180'],
            'reason' => ['required', 'string', 'max:2000'],
            'applicant_name' => ['nullable', 'string', 'max:150'],
            'applicant_phone' => ['nullable', 'string', 'max:30'],
            'idempotency_key' => ['nullable', 'string', 'max:80'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $files = array_values(array_filter(
            (array) $request->file('attachments', []),
            static fn (mixed $f): bool => $f instanceof UploadedFile,
        ));

        $result = $this->mutationService->submitMutation(
            schoolId: $schoolId,
            data: $validated,
            attachments: $files,
            actor: $request->user(),
        );

        $mutation = $result['mutation'];
        $request->session()->put("mutation_access.{$mutation->id}", true);

        return redirect()->route('academic.mutations.public.index')
            ->with('message', "Permohonan mutasi berhasil dikirim ({$mutation->mutation_code}).")
            ->with('issued_mutation_receipt', [
                'id' => (int) $mutation->id,
                'mutation_code' => $mutation->mutation_code,
                'ticket_code' => $result['ticket']['ticket_code'] ?? null,
                'verification_secret' => $result['plain_verification_secret'],
            ]);
    }

    public function publicResubmit(Request $request, StudentMutation $mutation): RedirectResponse
    {
        $school = $this->schoolScope->getPrimaryPublicSnapshot();
        $schoolId = (int) $school['id'];
        $this->authorizeViewer($request, $schoolId, $mutation);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:2000'],
            'school_name' => ['nullable', 'string', 'max:180'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $files = array_values(array_filter(
            (array) $request->file('attachments', []),
            static fn (mixed $f): bool => $f instanceof UploadedFile,
        ));

        $this->mutationService->resubmitRevision(
            schoolId: $schoolId,
            mutation: $mutation,
            reason: $validated['reason'],
            schoolName: $validated['school_name'] ?? null,
            attachments: $files,
            actor: $request->user(),
        );

        return back()->with('message', 'Perbaikan permohonan mutasi berhasil dikirim ulang.');
    }

    public function downloadOfficialLetter(Request $request, StudentMutation $mutation): BinaryFileResponse
    {
        $school = $this->schoolScope->getPrimaryPublicSnapshot();
        $schoolId = (int) $school['id'];
        abort_unless((int) $mutation->school_id === $schoolId && $mutation->official_letter_document_id !== null, 404);
        $this->authorizeViewer($request, $schoolId, $mutation);

        $file = $this->documentService->resolveDownloadByDocumentId($schoolId, (int) $mutation->official_letter_document_id);
        abort_if($file === null, 404);

        $response = response()->download($file['absolute_path'], $file['original_name'], [
            'Content-Type' => $file['mime_type'],
        ]);
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        return $response;
    }

    public function adminIndex(Request $request): Response
    {
        $schoolId = (int) $request->user()->school_id;
        $direction = trim((string) $request->query('direction', ''));
        $status = trim((string) $request->query('status', ''));

        $mutations = StudentMutation::query()
            ->where('school_id', $schoolId)
            ->when($direction !== '', fn ($q) => $q->where('direction', $direction))
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->latest('id')
            ->limit(60)
            ->get()
            ->map(fn (StudentMutation $m): array => $this->serialize($m))
            ->values()
            ->all();

        $classes = ClassGroup::query()
            ->where('school_id', $schoolId)
            ->orderBy('grade_level')
            ->orderBy('name')
            ->get(['id', 'name', 'grade_level']);

        return Inertia::render('Academic/Mutations/Index', [
            'canPublish' => $this->accessScope->canApproveFinalDecision($request->user(), $schoolId) && $this->accessScope->canSignOfficialDocument($request->user(), $schoolId),
            'mutations' => $mutations,
            'classes' => $classes,
            'filters' => ['direction' => $direction, 'status' => $status],
        ]);
    }

    public function decide(Request $request, StudentMutation $mutation): RedirectResponse
    {
        $validated = $request->validate([
            'decision' => ['required', Rule::in(['approve', 'request_revision', 'reject'])],
            'decision_note' => ['nullable', 'string', 'max:1500'],
            'nis' => ['nullable', 'string', 'max:30'],
            'target_class_group_id' => ['nullable', 'integer'],
            'effective_date' => ['nullable', 'date'],
        ]);

        $this->mutationService->decide(
            actor: $request->user(),
            mutation: $mutation,
            decision: $validated['decision'],
            note: $validated['decision_note'] ?? null,
            options: $validated,
        );

        return back()->with('message', 'Keputusan permohonan mutasi berhasil diproses.');
    }

    public function revoke(Request $request, StudentMutation $mutation): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $this->mutationService->revokeApproval($request->user(), $mutation, $validated['reason']);

        return back()->with('message', 'Surat persetujuan mutasi berhasil dicabut.');
    }

    private function authorizeViewer(Request $request, int $schoolId, StudentMutation $mutation): void
    {
        $user = $request->user();
        if ($user !== null && (int) $user->school_id === $schoolId) {
            if ($user->hasRole([RoleEnum::ADMIN->value, RoleEnum::OPERATOR->value, RoleEnum::PTSP->value, RoleEnum::PRINCIPAL->value, RoleEnum::SIGNATORY->value])) {
                return;
            }
            if ($mutation->submitted_by_user_id !== null && (int) $mutation->submitted_by_user_id === (int) $user->id) {
                return;
            }
            if ($mutation->student_id !== null && $this->accessScope->canAccessStudentScope($user, $schoolId, (int) $mutation->student_id)) {
                return;
            }
        }

        if ($request->session()->get("mutation_access.{$mutation->id}") === true) {
            return;
        }

        abort(403, 'Anda tidak berhak mengakses dokumen mutasi ini.');
    }

    /**
     * @return array<string, mixed>
     */
    private function serialize(StudentMutation $m): array
    {
        return [
            'id' => (int) $m->id,
            'mutation_code' => (string) $m->mutation_code,
            'direction' => (string) $m->direction,
            'student_id' => $m->student_id,
            'student_name' => (string) $m->student_name,
            'nis' => $m->nis,
            'nisn' => $m->nisn,
            'origin_school' => $m->origin_school,
            'destination_school' => $m->destination_school,
            'reason' => (string) $m->reason,
            'status' => (string) $m->status,
            'decision_note' => $m->decision_note,
            'revision_note' => $m->revision_note,
            'revocation_reason' => $m->revocation_reason,
            'has_official_letter' => $m->official_letter_document_id !== null,
            'created_at' => $m->created_at?->toDateString(),
        ];
    }
}
