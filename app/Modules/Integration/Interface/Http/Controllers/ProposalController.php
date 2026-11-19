<?php

declare(strict_types=1);

namespace App\Modules\Integration\Interface\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Integration\Application\Services\ProposalLpjService;
use App\Modules\Integration\Infrastructure\Models\ActivityProposal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProposalController extends Controller
{
    public function __construct(
        private readonly ProposalLpjService $service,
    ) {}

    public function teacherIndex(Request $request): Response
    {
        $actor = $request->user();
        $this->service->assertCanSubmitTeacherWork($actor);

        return Inertia::render('Integration/Proposals/TeacherIndex', [
            'proposals' => $this->service->listForTeacher($actor),
            'activityTypes' => ProposalLpjService::ACTIVITY_TYPES,
            'statusLabels' => ProposalLpjService::STATUS_LABELS,
            'canReview' => $this->service->canReview($actor),
        ]);
    }

    public function independentIndex(Request $request): Response
    {
        $actor = $request->user();
        $this->service->assertCanSubmitIndependent($actor);

        return Inertia::render('Integration/Proposals/IndependentPortal', [
            'proposals' => $this->service->listForIndependentPortal($actor),
            'samplePreset' => $this->service->sampleWebinarPreset(),
            'activityTypes' => ProposalLpjService::ACTIVITY_TYPES,
            'statusLabels' => ProposalLpjService::STATUS_LABELS,
            'canReview' => $this->service->canReview($actor),
        ]);
    }

    public function reviewQueue(Request $request): Response
    {
        $actor = $request->user();
        $this->service->assertCanReview($actor);

        $modeFilter = $request->string('mode')->toString() ?: null;
        $proposals = $this->service->listReviewQueue($actor, $modeFilter);

        return Inertia::render('Integration/Proposals/ReviewQueue', [
            'proposals' => $proposals,
            'activeMode' => $modeFilter,
            'activityTypes' => ProposalLpjService::ACTIVITY_TYPES,
            'statusLabels' => ProposalLpjService::STATUS_LABELS,
        ]);
    }

    public function storeTeacher(Request $request): RedirectResponse
    {
        $actor = $request->user();
        $this->service->assertCanSubmitTeacherWork($actor);

        return $this->handleCreate($request, ActivityProposal::MODE_TEACHER_WORK);
    }

    public function storeIndependent(Request $request): RedirectResponse
    {
        $actor = $request->user();
        $this->service->assertCanSubmitIndependent($actor);

        return $this->handleCreate($request, ActivityProposal::MODE_INDEPENDENT);
    }

    public function resubmit(Request $request, ActivityProposal $proposal): RedirectResponse
    {
        $validated = $this->validateProposalPayload($request, includeRevisionNote: true);

        try {
            $attachment = $request->file('attachment');
            $this->service->resubmitProposal(
                $request->user(),
                $proposal,
                $validated,
                $attachment instanceof UploadedFile ? $attachment : null,
            );
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['proposal' => $e->getMessage()]);
        }

        return back()->with('success', 'Revisi proposal berhasil dikirim ulang untuk ditelaah.');
    }

    public function review(Request $request, ActivityProposal $proposal): RedirectResponse
    {
        $this->service->assertCanReview($request->user());

        $validated = $request->validate([
            'decision' => ['required', Rule::in([
                ActivityProposal::STATUS_IN_REVIEW,
                ActivityProposal::STATUS_NEEDS_REVISION,
                ActivityProposal::STATUS_APPROVED,
                ActivityProposal::STATUS_REJECTED,
                ActivityProposal::STATUS_CLOSED,
            ])],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $this->service->reviewProposal(
                $request->user(),
                $proposal,
                (string) $validated['decision'],
                $validated['note'] ?? null,
            );
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['review' => $e->getMessage()]);
        }

        return back()->with('success', 'Keputusan telaah proposal berhasil dicatat.');
    }

    public function submitLpj(Request $request, ActivityProposal $proposal): RedirectResponse
    {
        $validated = $request->validate([
            'lpj_realization_amount' => ['required', 'integer', 'min:0'],
            'lpj_summary' => ['required', 'string', 'max:3000'],
            'proof_files' => ['nullable', 'array'],
            'proof_files.*' => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'proof_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        $files = [];
        foreach ($request->file('proof_files', []) as $file) {
            if ($file instanceof UploadedFile) {
                $files[] = $file;
            }
        }
        $single = $request->file('proof_file');
        if ($single instanceof UploadedFile) {
            $files[] = $single;
        }

        try {
            $this->service->submitLpj(
                $request->user(),
                $proposal,
                [
                    'lpj_realization_amount' => (int) $validated['lpj_realization_amount'],
                    'lpj_summary' => (string) $validated['lpj_summary'],
                ],
                $files,
            );
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['lpj' => $e->getMessage()]);
        }

        return back()->with('success', 'Bukti realisasi LPJ berhasil diunggah dan menunggu penutupan reviewer.');
    }

    public function downloadDocument(Request $request, ActivityProposal $proposal, int $documentId): BinaryFileResponse
    {
        $resolved = $this->service->resolveProposalDocument($request->user(), $proposal, $documentId);

        return response()->download($resolved['absolute_path'], $resolved['original_name'], [
            'Content-Type' => $resolved['mime_type'],
        ]);
    }

    private function handleCreate(Request $request, string $mode): RedirectResponse
    {
        $validated = $this->validateProposalPayload($request);

        try {
            $attachment = $request->file('attachment');
            $proposal = $this->service->createProposal(
                $request->user(),
                $mode,
                $validated,
                $attachment instanceof UploadedFile ? $attachment : null,
            );
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['budget_items' => $e->getMessage()])->withInput();
        }

        return back()->with('success', 'Proposal '.$proposal->proposal_code.' berhasil diajukan.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateProposalPayload(Request $request, bool $includeRevisionNote = false): array
    {
        $rules = [
            'title' => ['required', 'string', 'max:200'],
            'activity_type' => ['required', Rule::in(array_keys(ProposalLpjService::ACTIVITY_TYPES))],
            'organizer_name' => ['required', 'string', 'max:150'],
            'organizer_unit' => ['nullable', 'string', 'max:120'],
            'committee_members' => ['nullable', 'array'],
            'committee_members.*.role' => ['nullable', 'string', 'max:80'],
            'committee_members.*.name' => ['nullable', 'string', 'max:120'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'location' => ['nullable', 'string', 'max:180'],
            'purpose' => ['required', 'string', 'max:4000'],
            'budget_items' => ['required', 'array', 'min:1'],
            'budget_items.*.name' => ['required', 'string', 'max:150'],
            'budget_items.*.qty' => ['required', 'integer', 'min:1'],
            'budget_items.*.unit' => ['nullable', 'string', 'max:40'],
            'budget_items.*.unit_price' => ['required', 'integer', 'min:0'],
            'budget_items.*.note' => ['nullable', 'string', 'max:250'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'idempotency_key' => ['nullable', 'string', 'max:96'],
        ];

        if ($includeRevisionNote) {
            $rules['revision_note'] = ['nullable', 'string', 'max:1000'];
        }

        return $request->validate($rules);
    }
}
