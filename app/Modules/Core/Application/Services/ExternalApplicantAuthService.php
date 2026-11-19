<?php

declare(strict_types=1);

namespace App\Modules\Core\Application\Services;

use App\Modules\Core\Infrastructure\Models\ExternalApplicant;
use App\Shared\Audit\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Separate external applicant account lifecycle (register, login, profile, recovery)
 * isolated from internal school staff accounts (M4-FND-03, M4-TU-01).
 */
class ExternalApplicantAuthService
{
    public const SESSION_APPLICANT_ID = 'ptsp_external_applicant_id';

    public const SESSION_SCHOOL_ID = 'ptsp_external_applicant_school_id';

    public const ALLOWED_TYPES = ['general', 'guardian', 'student', 'alumni', 'candidate', 'external_agency'];

    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly NotificationOutboxService $outbox,
    ) {}

    /**
     * @param  array{
     *     applicant_type: string,
     *     full_name: string,
     *     email: string,
     *     phone: string,
     *     password: string,
     *     organization_name?: ?string,
     *     raw_identifier?: ?string
     * }  $data
     * @return array<string, mixed>
     */
    public function register(int $schoolId, array $data): array
    {
        $email = Str::lower(trim($data['email']));

        $alreadyRegistered = ExternalApplicant::query()
            ->where('school_id', $schoolId)
            ->where('email', $email)
            ->whereNotNull('password_hash')
            ->exists();

        if ($alreadyRegistered) {
            throw ValidationException::withMessages([
                'email' => 'Email sudah terdaftar sebagai akun pemohon pada sekolah ini.',
            ]);
        }

        $rawIdentifier = isset($data['raw_identifier']) ? trim((string) $data['raw_identifier']) : '';
        $identifierLast4 = $rawIdentifier !== '' ? substr($rawIdentifier, -4) : null;
        $identifierHash = $rawIdentifier !== '' ? hash('sha256', $schoolId.'|'.$rawIdentifier) : null;

        $applicant = ExternalApplicant::query()->create([
            'school_id' => $schoolId,
            'user_id' => null,
            'applicant_type' => in_array($data['applicant_type'], self::ALLOWED_TYPES, true) ? $data['applicant_type'] : 'general',
            'full_name' => trim($data['full_name']),
            'identifier_last4' => $identifierLast4,
            'identifier_hash' => $identifierHash,
            'phone' => trim($data['phone']),
            'email' => $email,
            'password_hash' => Hash::make($data['password']),
            'organization_name' => isset($data['organization_name']) ? trim((string) $data['organization_name']) ?: null : null,
            'is_verified' => true,
            'verified_at' => now(),
            'last_login_at' => now(),
        ]);

        $this->auditLogger->log(
            eventType: 'core.external_applicant.account_registered',
            targetType: 'ExternalApplicant',
            targetId: (string) $applicant->id,
            newValues: [
                'applicant_type' => $applicant->applicant_type,
                'is_verified' => true,
            ],
            schoolId: $schoolId,
        );

        return $this->serializeApplicant($applicant);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function attemptLogin(int $schoolId, string $email, string $password): ?array
    {
        $normalizedEmail = Str::lower(trim($email));

        /** @var ExternalApplicant|null $applicant */
        $applicant = ExternalApplicant::query()
            ->where('school_id', $schoolId)
            ->where('email', $normalizedEmail)
            ->whereNotNull('password_hash')
            ->first();

        if ($applicant === null || ! Hash::check($password, (string) $applicant->password_hash)) {
            $this->auditLogger->log(
                eventType: 'core.external_applicant.login_failed',
                targetType: 'ExternalApplicant',
                targetId: $applicant ? (string) $applicant->id : null,
                newValues: ['email' => $normalizedEmail],
                schoolId: $schoolId,
            );

            return null;
        }

        $applicant->forceFill(['last_login_at' => now()])->save();

        $this->auditLogger->log(
            eventType: 'core.external_applicant.login',
            targetType: 'ExternalApplicant',
            targetId: (string) $applicant->id,
            newValues: ['applicant_type' => $applicant->applicant_type],
            schoolId: $schoolId,
        );

        return $this->serializeApplicant($applicant);
    }

    public function bindSession(Request $request, int $schoolId, int $applicantId): void
    {
        $request->session()->regenerate();
        $request->session()->put(self::SESSION_APPLICANT_ID, $applicantId);
        $request->session()->put(self::SESSION_SCHOOL_ID, $schoolId);
    }

    public function clearSession(Request $request): void
    {
        $request->session()->forget([
            self::SESSION_APPLICANT_ID,
            self::SESSION_SCHOOL_ID,
            'ptsp_receipt_access',
            'ptsp_verified_secret',
            'ptsp_draft_token',
            'external_letter_receipts',
        ]);
        $request->session()->regenerateToken();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function currentFromSession(Request $request, int $schoolId): ?array
    {
        $applicantId = (int) $request->session()->get(self::SESSION_APPLICANT_ID, 0);
        $sessionSchoolId = (int) $request->session()->get(self::SESSION_SCHOOL_ID, 0);

        if ($applicantId <= 0 || $sessionSchoolId !== $schoolId) {
            return null;
        }

        return $this->findSnapshotById($schoolId, $applicantId);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findSnapshotById(int $schoolId, int $applicantId): ?array
    {
        /** @var ExternalApplicant|null $applicant */
        $applicant = ExternalApplicant::query()
            ->where('school_id', $schoolId)
            ->whereKey($applicantId)
            ->first();

        return $applicant ? $this->serializeApplicant($applicant) : null;
    }

    /**
     * @param  array{
     *     full_name: string,
     *     phone: string,
     *     organization_name?: ?string,
     *     applicant_type?: string
     * }  $data
     * @return array<string, mixed>
     */
    public function updateProfile(int $schoolId, int $applicantId, array $data): array
    {
        /** @var ExternalApplicant $applicant */
        $applicant = ExternalApplicant::query()
            ->where('school_id', $schoolId)
            ->whereKey($applicantId)
            ->firstOrFail();

        $applicant->forceFill([
            'full_name' => trim($data['full_name']),
            'phone' => trim($data['phone']),
            'organization_name' => isset($data['organization_name']) ? trim((string) $data['organization_name']) ?: null : $applicant->organization_name,
            'applicant_type' => isset($data['applicant_type']) && in_array($data['applicant_type'], self::ALLOWED_TYPES, true)
                ? $data['applicant_type']
                : $applicant->applicant_type,
        ])->save();

        $this->auditLogger->log(
            eventType: 'core.external_applicant.profile_updated',
            targetType: 'ExternalApplicant',
            targetId: (string) $applicant->id,
            newValues: ['applicant_type' => $applicant->applicant_type],
            schoolId: $schoolId,
        );

        return $this->serializeApplicant($applicant->refresh());
    }

    /**
     * Issue a short-lived hashed recovery token after matching email and verification hint (last 4 digits of phone/identifier).
     *
     * @return array{accepted: bool, token?: string}
     */
    public function requestRecovery(int $schoolId, string $email, string $verificationHint): array
    {
        $normalizedEmail = Str::lower(trim($email));
        $normalizedHint = preg_replace('/\D+/', '', trim($verificationHint)) ?: trim($verificationHint);

        /** @var ExternalApplicant|null $applicant */
        $applicant = ExternalApplicant::query()
            ->where('school_id', $schoolId)
            ->where('email', $normalizedEmail)
            ->whereNotNull('password_hash')
            ->first();

        if ($applicant === null) {
            return ['accepted' => false];
        }

        $phoneDigits = preg_replace('/\D+/', '', (string) $applicant->phone) ?: '';
        $phoneLast4 = $phoneDigits !== '' ? substr($phoneDigits, -4) : '';
        $matchesPhone = $phoneLast4 !== '' && hash_equals($phoneLast4, substr($normalizedHint, -4));
        $matchesIdentifier = $applicant->identifier_last4 !== null && hash_equals($applicant->identifier_last4, substr($normalizedHint, -4));

        if (! $matchesPhone && ! $matchesIdentifier) {
            return ['accepted' => false];
        }

        $rawToken = Str::upper(Str::random(10));

        $applicant->forceFill([
            'recovery_token_hash' => Hash::make($rawToken),
            'recovery_expires_at' => now()->addMinutes(30),
        ])->save();

        $this->outbox->enqueue(
            schoolId: $schoolId,
            eventKey: 'external_applicant.recovery_requested',
            channel: 'email',
            recipientType: 'external_applicant',
            recipientId: (int) $applicant->id,
            templateKey: 'ptsp_account_recovery',
            payload: [
                'email' => $normalizedEmail,
                'expires_in_minutes' => 30,
            ],
            idempotencyKey: sprintf('recovery-%d-%s', $applicant->id, now()->format('YmdHi')),
            dispatchJob: false,
        );

        $this->auditLogger->log(
            eventType: 'core.external_applicant.recovery_requested',
            targetType: 'ExternalApplicant',
            targetId: (string) $applicant->id,
            newValues: ['expires_in_minutes' => 30],
            schoolId: $schoolId,
        );

        return [
            'accepted' => true,
            'token' => $rawToken,
        ];
    }

    public function resetPassword(int $schoolId, string $email, string $token, string $newPassword): bool
    {
        $normalizedEmail = Str::lower(trim($email));
        $candidateToken = strtoupper(trim($token));

        /** @var ExternalApplicant|null $applicant */
        $applicant = ExternalApplicant::query()
            ->where('school_id', $schoolId)
            ->where('email', $normalizedEmail)
            ->whereNotNull('recovery_token_hash')
            ->first();

        if (
            $applicant === null
            || $applicant->recovery_expires_at === null
            || $applicant->recovery_expires_at->isPast()
            || ! Hash::check($candidateToken, (string) $applicant->recovery_token_hash)
        ) {
            return false;
        }

        $applicant->forceFill([
            'password_hash' => Hash::make($newPassword),
            'recovery_token_hash' => null,
            'recovery_expires_at' => null,
        ])->save();

        $this->auditLogger->log(
            eventType: 'core.external_applicant.password_reset',
            targetType: 'ExternalApplicant',
            targetId: (string) $applicant->id,
            schoolId: $schoolId,
        );

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeApplicant(ExternalApplicant $applicant): array
    {
        return [
            'id' => (int) $applicant->id,
            'applicant_type' => (string) $applicant->applicant_type,
            'full_name' => (string) $applicant->full_name,
            'phone' => $applicant->phone,
            'email' => $applicant->email,
            'organization_name' => $applicant->organization_name,
            'identifier_last4' => $applicant->identifier_last4,
            'is_verified' => (bool) $applicant->is_verified,
        ];
    }
}
