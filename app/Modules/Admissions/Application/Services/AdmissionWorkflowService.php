<?php

declare(strict_types=1);

namespace App\Modules\Admissions\Application\Services;

use App\Models\User;
use App\Modules\Academic\Application\Services\StudentProvisioningService;
use App\Modules\Admissions\Infrastructure\Models\AdmissionApplicant;
use App\Modules\Admissions\Infrastructure\Models\AdmissionPeriod;
use App\Modules\Admissions\Infrastructure\Models\AdmissionReRegistration;
use App\Modules\Admissions\Infrastructure\Models\AdmissionSelectionResult;
use App\Shared\Audit\AuditLogger;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AdmissionWorkflowService
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly StudentProvisioningService $studentProvisioning,
    ) {}

    public function openPeriod(AdmissionPeriod $period, User $actor): AdmissionPeriod
    {
        $tracks = $period->tracks()->where('is_active', true)->withCount(['requirements', 'criteria'])->get();

        if ($tracks->isEmpty() || $tracks->contains(fn ($track): bool => (int) $track->quota < 1 || (int) $track->requirements_count < 1 || (int) $track->criteria_count < 1)) {
            throw new InvalidArgumentException('Periode harus memiliki jalur aktif, kuota, persyaratan berkas, dan kriteria seleksi.');
        }

        $oldStatus = $period->status;
        $period->update(['status' => 'registration_open']);
        $this->auditLogger->log('admissions.period.opened', 'admission_period', (string) $period->id, ['status' => $period->status], ['status' => $oldStatus], $actor, (int) $period->school_id);

        return $period->fresh();
    }

    public function rank(AdmissionPeriod $period, User $actor): void
    {
        DB::transaction(function () use ($period, $actor): void {
            $applicants = $period->applicants()->with(['scores.criterion'])->get();
            foreach ($applicants as $applicant) {
                $requiredIds = $applicant->track?->requirements()->where('is_required', true)->pluck('id') ?? collect();
                $validIds = $applicant->documents()->where('status', 'valid')->whereIn('requirement_id', $requiredIds)->pluck('requirement_id');
                if ($requiredIds->diff($validIds)->isNotEmpty()) {
                    throw new InvalidArgumentException("Berkas wajib pendaftar {$applicant->registration_no} belum lengkap atau belum valid.");
                }
            }
            $ranked = $applicants->map(function (AdmissionApplicant $applicant): array {
                $total = $applicant->scores->sum(function ($score): float {
                    $maxScore = max((float) $score->criterion->max_score, 1.0);

                    return ((float) $score->score / $maxScore) * (float) $score->criterion->weight;
                });

                return ['applicant' => $applicant, 'total' => round($total, 2)];
            })->sort(function (array $left, array $right): int {
                $scoreOrder = $right['total'] <=> $left['total'];
                if ($scoreOrder !== 0) {
                    return $scoreOrder;
                }

                return $left['applicant']->id <=> $right['applicant']->id;
            })->values();

            foreach ($ranked as $index => $row) {
                AdmissionSelectionResult::updateOrCreate(
                    ['applicant_id' => $row['applicant']->id],
                    ['total_score' => $row['total'], 'rank' => $index + 1]
                );
            }

            $this->auditLogger->log('admissions.selection.ranked', 'admission_period', (string) $period->id, ['count' => $ranked->count()], null, $actor, (int) $period->school_id);
        });
    }

    public function finalize(AdmissionApplicant $applicant, string $decision, ?string $overrideReason, User $actor): AdmissionSelectionResult
    {
        return DB::transaction(function () use ($applicant, $decision, $overrideReason, $actor): AdmissionSelectionResult {
            $result = $applicant->selectionResult()->lockForUpdate()->firstOrCreate([], ['total_score' => 0]);
            $oldDecision = $result->decision;

            if ($decision === 'accepted' && $oldDecision !== 'accepted') {
                $acceptedCount = AdmissionSelectionResult::query()
                    ->whereHas('applicant', fn ($query) => $query->where('track_id', $applicant->track_id))
                    ->where('decision', 'accepted')
                    ->count();
                $quota = (int) $applicant->track?->quota;
                if ($quota > 0 && $acceptedCount >= $quota) {
                    throw new InvalidArgumentException('Kuota jalur sudah penuh.');
                }
            }

            $result->update([
                'decision' => $decision,
                'override_reason' => $overrideReason,
                'finalized_by' => $actor->id,
                'finalized_at' => now(),
            ]);
            $applicant->update(['status' => match ($decision) {
                'accepted' => 'accepted',
                'rejected' => 'rejected',
                'waitlisted' => 'waitlisted',
                default => 'selection_review',
            }]);

            $this->auditLogger->log('admissions.selection.finalized', 'admission_applicant', (string) $applicant->id, ['decision' => $decision, 'override_reason' => $overrideReason], ['decision' => $oldDecision], $actor, (int) $applicant->school_id);

            return $result->fresh();
        });
    }

    public function confirmReRegistration(AdmissionApplicant $applicant, string $nis, User $actor): AdmissionReRegistration
    {
        return DB::transaction(function () use ($applicant, $nis, $actor): AdmissionReRegistration {
            $existing = $applicant->reRegistration()->lockForUpdate()->first();
            if ($existing?->status === 'confirmed') {
                return $existing;
            }

            if ($applicant->selectionResult?->decision !== 'accepted') {
                throw new InvalidArgumentException('Hanya pendaftar yang diterima yang dapat melakukan daftar ulang.');
            }

            $student = $this->studentProvisioning->findBySchoolAndNis((int) $applicant->school_id, $nis);

            if ($student === null) {
                $student = $this->studentProvisioning->createFromAdmission([
                    'school_id' => $applicant->school_id,
                    'nis' => $nis,
                    'nisn' => $applicant->nisn,
                    'name' => $applicant->name,
                    'gender' => $applicant->gender,
                    'birth_date' => $applicant->birth_date,
                    'parent_phone' => $applicant->parent_phone,
                    'parent_name' => $applicant->parent_name,
                ]);
            }

            $registration = AdmissionReRegistration::updateOrCreate(
                ['applicant_id' => $applicant->id],
                ['status' => 'confirmed', 'student_id' => $student->id, 'confirmed_by' => $actor->id, 'confirmed_at' => now()]
            );
            $applicant->update(['status' => 're_registered']);

            $this->auditLogger->log('admissions.re_registration.confirmed', 'admission_applicant', (string) $applicant->id, ['student_id' => $student->id, 'status' => 'confirmed'], null, $actor, (int) $applicant->school_id);

            return $registration->fresh();
        });
    }
}
