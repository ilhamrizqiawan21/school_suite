<?php

declare(strict_types=1);

namespace App\Modules\Homeroom\Interface\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academic\Application\Services\AcademicQueryService;
use App\Modules\Core\Application\Services\AccessScopeService;
use App\Modules\Core\Application\Services\SchoolScopeService;
use App\Modules\Core\Application\Services\ServiceDocumentService;
use App\Modules\Core\Domain\RoleEnum;
use App\Modules\Homeroom\Application\Services\PermitWorkflowService;
use App\Modules\Homeroom\Domain\Enums\DispensationTypeEnum;
use App\Modules\Homeroom\Infrastructure\Models\AttendanceDispensation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StudentPermitController extends Controller
{
    public function __construct(
        private readonly PermitWorkflowService $permitService,
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

        $students = ($user !== null && (int) $user->school_id === $schoolId && $user->hasRole([RoleEnum::PARENT->value, RoleEnum::STUDENT->value]))
            ? $this->academicQuery->getStudentsForUserScope($schoolId, $user)
            : [];

        $myPermits = $user !== null && (int) $user->school_id === $schoolId
            ? $this->permitService->listPermitsForApplicant($schoolId, $user)
            : [];

        return Inertia::render('Public/Services/StudentPermit', [
            'school' => [
                'name' => $school['name'],
                'logo_url' => ! empty($school['logo_path']) ? route('branding.school', [$school['id'], 'logo']) : null,
                'theme' => $school['theme'] ?? [],
            ],
            'students' => $students,
            'myPermits' => $myPermits,
            'types' => collect(DispensationTypeEnum::cases())
                ->map(static fn (DispensationTypeEnum $t): array => ['value' => $t->value, 'label' => $t->label()])
                ->values()
                ->all(),
            'operations' => $this->schoolScope->getOperationsSettings($schoolId),
            'timezone' => $this->schoolScope->getTimezone($schoolId),
            'issuedReceipt' => $request->session()->get('issued_permit_receipt'),
        ]);
    }

    public function submit(Request $request): RedirectResponse
    {
        $school = $this->schoolScope->getPrimaryPublicSnapshot();
        $schoolId = (int) $school['id'];

        $validated = $request->validate([
            'student_id' => ['required', 'integer'],
            'one_time_token' => ['nullable', 'string', 'max:60'],
            'type' => ['required', Rule::in(['sick', 'event', 'personal', 'other'])],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'title' => ['required', 'string', 'max:200'],
            'reason' => ['required', 'string', 'max:3000'],
            'contact_name' => ['nullable', 'string', 'max:150'],
            'contact_phone' => ['nullable', 'string', 'max:30'],
            'signature_data' => ['required', 'string', 'max:50000'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
            'idempotency_key' => ['nullable', 'string', 'max:80'],
        ]);

        $result = $this->permitService->submitPermit(
            schoolId: $schoolId,
            data: $validated,
            attachment: $request->file('attachment'),
            actor: $request->user(),
        );

        $dispId = (int) $result['dispensation']->id;
        $request->session()->put("permit_receipt_access.{$dispId}", true);

        return redirect()->route('homeroom.permits.public.index')
            ->with('message', 'Permohonan izin siswa berhasil diajukan.')
            ->with('issued_permit_receipt', [
                'id' => $dispId,
                'ticket_code' => $result['ticket']['ticket_code'] ?? null,
                'verification_secret' => $result['plain_verification_secret'],
            ]);
    }

    public function resubmit(Request $request, AttendanceDispensation $dispensation): RedirectResponse
    {
        $school = $this->schoolScope->getPrimaryPublicSnapshot();
        $schoolId = (int) $school['id'];

        $this->authorizePermitViewer($request, $schoolId, $dispensation);

        $validated = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'title' => ['required', 'string', 'max:200'],
            'reason' => ['required', 'string', 'max:3000'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $this->permitService->resubmitPermit(
            schoolId: $schoolId,
            dispensation: $dispensation,
            data: $validated,
            attachment: $request->file('attachment'),
            actor: $request->user(),
        );

        return back()->with('message', 'Perbaikan permohonan izin berhasil dikirim ulang.');
    }

    public function proofPdf(Request $request, AttendanceDispensation $dispensation): HttpResponse
    {
        $school = $this->schoolScope->getPrimaryPublicSnapshot();
        $schoolId = (int) $school['id'];
        abort_unless((int) $dispensation->school_id === $schoolId, 404);

        $this->authorizePermitViewer($request, $schoolId, $dispensation);

        $bytes = $this->permitService->renderProofPdfBytes($schoolId, $dispensation);

        return response($bytes, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('inline; filename="bukti-izin-%d.pdf"', $dispensation->id),
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function downloadAttachment(Request $request, AttendanceDispensation $dispensation): BinaryFileResponse
    {
        $school = $this->schoolScope->getPrimaryPublicSnapshot();
        $schoolId = (int) $school['id'];
        abort_unless((int) $dispensation->school_id === $schoolId, 404);

        $this->authorizePermitViewer($request, $schoolId, $dispensation);
        abort_if($dispensation->attachment_document_id === null, 404);

        $file = $dispensation->service_ticket_id !== null
            ? $this->documentService->resolveDownloadForVerifiedTicket($schoolId, (int) $dispensation->service_ticket_id, (int) $dispensation->attachment_document_id)
            : null;
        abort_if($file === null, 404);

        $response = response()->download($file['absolute_path'], $file['original_name'], [
            'Content-Type' => $file['mime_type'],
        ]);
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        return $response;
    }

    public function queueIndex(Request $request): Response
    {
        $user = $request->user();
        $schoolId = (int) $user->school_id;
        $statusFilter = trim((string) $request->query('status', ''));

        $students = $user->hasRole(RoleEnum::HOMEROOM->value)
            ? $this->academicQuery->getStudentsSnapshotsByIds(
                $schoolId,
                $this->academicQuery->getHomeroomStudentIdsForUser($schoolId, (int) $user->id),
            )
            : $this->academicQuery->searchActiveStudentsForStaff($schoolId, '', 50);

        return Inertia::render('Homeroom/Permits/Index', [
            'permits' => $this->permitService->listPermitsForStaffQueue($schoolId, $user, $statusFilter),
            'students' => array_values($students),
            'filters' => ['status' => $statusFilter],
            'issuedToken' => $request->session()->get('issued_permit_token'),
        ]);
    }

    public function issueToken(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'student_id' => ['required', 'integer'],
            'expires_in_hours' => ['nullable', 'integer', 'min:1', 'max:168'],
        ]);

        $issued = $this->permitService->issuePermitToken(
            $request->user(),
            (int) $validated['student_id'],
            (int) ($validated['expires_in_hours'] ?? 48),
        );

        return back()
            ->with('message', 'Token izin sekali pakai berhasil diterbitkan.')
            ->with('issued_permit_token', $issued);
    }

    public function decide(Request $request, AttendanceDispensation $dispensation): RedirectResponse
    {
        $validated = $request->validate([
            'decision' => ['required', Rule::in(['approve', 'request_revision', 'reject'])],
            'decision_note' => ['nullable', 'string', 'max:1500'],
        ]);

        $this->permitService->decidePermit(
            $request->user(),
            $dispensation,
            $validated['decision'],
            $validated['decision_note'] ?? null,
        );

        return back()->with('message', 'Keputusan permohonan izin berhasil disimpan.');
    }

    public function correct(Request $request, AttendanceDispensation $dispensation): RedirectResponse
    {
        $validated = $request->validate([
            'new_status' => ['required', Rule::in(['approved', 'rejected', 'cancelled'])],
            'correction_reason' => ['required', 'string', 'max:1500'],
        ]);

        $this->permitService->correctPermitDecision(
            $request->user(),
            $dispensation,
            $validated['new_status'],
            $validated['correction_reason'],
        );

        return back()->with('message', 'Koreksi keputusan izin berhasil dicatat beserta pembaruan presensi.');
    }

    private function authorizePermitViewer(Request $request, int $schoolId, AttendanceDispensation $dispensation): void
    {
        $user = $request->user();
        if ($user !== null && (int) $user->school_id === $schoolId) {
            if ($user->hasRole([RoleEnum::ADMIN->value, RoleEnum::OPERATOR->value, RoleEnum::PICKET->value])) {
                return;
            }
            if ($this->accessScope->canAccessStudentScope($user, $schoolId, (int) $dispensation->student_id)) {
                return;
            }
        }

        if ($request->session()->get("permit_receipt_access.{$dispensation->id}") === true) {
            return;
        }

        abort(403, 'Anda tidak berhak mengakses dokumen izin siswa ini.');
    }
}
