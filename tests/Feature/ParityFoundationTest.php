<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Logging\SanitizeSensitiveDataProcessor;
use App\Models\User;
use App\Modules\Academic\Application\Services\AcademicQueryService;
use App\Modules\Academic\Infrastructure\Models\AcademicYear;
use App\Modules\Academic\Infrastructure\Models\ClassGroup;
use App\Modules\Academic\Infrastructure\Models\Enrollment;
use App\Modules\Academic\Infrastructure\Models\Student;
use App\Modules\Academic\Infrastructure\Models\StudentGuardian;
use App\Modules\Academic\Infrastructure\Models\Teacher;
use App\Modules\Academic\Infrastructure\Models\Term;
use App\Modules\Core\Application\Services\AccessScopeService;
use App\Modules\Core\Application\Services\NotificationOutboxService;
use App\Modules\Core\Application\Services\RolePermissionRegistryService;
use App\Modules\Core\Application\Services\SchoolScopeService;
use App\Modules\Core\Application\Services\ServiceDocumentService;
use App\Modules\Core\Application\Services\ServiceTicketService;
use App\Modules\Core\Domain\RoleEnum;
use App\Modules\Core\Domain\ServiceWorkflowMap;
use App\Modules\Core\Infrastructure\Models\Role;
use App\Modules\Core\Infrastructure\Models\School;
use App\Shared\Audit\AuditLogger;
use App\Shared\Jobs\DispatchNotificationOutboxJob;
use DateTimeImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Monolog\Level;
use Monolog\LogRecord;
use RuntimeException;
use Tests\TestCase;

class ParityFoundationTest extends TestCase
{
    use RefreshDatabase;

    private School $schoolA;

    private School $schoolB;

    /** @var array<string, Role> */
    private array $roles = [];

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->roles = app(RolePermissionRegistryService::class)->syncSystemRolesAndPermissions();

        $this->schoolA = School::query()->create([
            'name' => 'MAN Contoh Satu',
            'npsn' => '30101001',
            'level' => 'MA',
            'timezone' => 'Asia/Makassar',
        ]);

        $this->schoolB = School::query()->create([
            'name' => 'MAN Contoh Dua',
            'npsn' => '30101002',
            'level' => 'MA',
            'timezone' => 'Asia/Jakarta',
        ]);
    }

    public function test_roles_sync_idempotently_and_enforce_school_guardian_and_homeroom_scopes(): void
    {
        $secondSync = app(RolePermissionRegistryService::class)->syncSystemRolesAndPermissions();
        $this->assertCount(17, $secondSync);

        $year = AcademicYear::query()->create([
            'school_id' => $this->schoolA->id,
            'name' => '2026/2027',
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
            'is_active' => true,
        ]);
        $term = Term::query()->create([
            'academic_year_id' => $year->id,
            'name' => 'Ganjil',
            'start_date' => '2026-07-01',
            'end_date' => '2026-12-31',
            'is_active' => true,
        ]);

        $homeroomUser = $this->makeUser($this->schoolA, RoleEnum::HOMEROOM, 'homeroom@a.test');
        $teacher = Teacher::query()->create([
            'school_id' => $this->schoolA->id,
            'user_id' => $homeroomUser->id,
            'nip' => '19800101001',
            'name' => 'Wali Kelas X-A',
            'is_active' => true,
        ]);

        $classA = ClassGroup::query()->create([
            'school_id' => $this->schoolA->id,
            'academic_year_id' => $year->id,
            'grade_level' => 10,
            'name' => 'X-A',
            'homeroom_teacher_id' => $teacher->id,
        ]);
        $classB = ClassGroup::query()->create([
            'school_id' => $this->schoolA->id,
            'academic_year_id' => $year->id,
            'grade_level' => 10,
            'name' => 'X-B',
        ]);

        $studentUserA = $this->makeUser($this->schoolA, RoleEnum::STUDENT, 'siswa.a@a.test');
        $studentA = Student::query()->create([
            'school_id' => $this->schoolA->id,
            'user_id' => $studentUserA->id,
            'nis' => '10001',
            'name' => 'Siswa A',
        ]);
        $studentB = Student::query()->create([
            'school_id' => $this->schoolA->id,
            'nis' => '10002',
            'name' => 'Siswa B',
        ]);

        Enrollment::query()->create([
            'term_id' => $term->id,
            'class_group_id' => $classA->id,
            'student_id' => $studentA->id,
            'status' => 'active',
        ]);
        Enrollment::query()->create([
            'term_id' => $term->id,
            'class_group_id' => $classB->id,
            'student_id' => $studentB->id,
            'status' => 'active',
        ]);

        $guardianUser = $this->makeUser($this->schoolA, RoleEnum::PARENT, 'wali@a.test');
        StudentGuardian::query()->create([
            'school_id' => $this->schoolA->id,
            'student_id' => $studentA->id,
            'user_id' => $guardianUser->id,
            'guardian_name' => 'Wali Siswa A',
            'relationship' => 'mother',
            'is_verified' => true,
            'verified_at' => now(),
        ]);
        // Unverified link to studentB must NOT grant access
        StudentGuardian::query()->create([
            'school_id' => $this->schoolA->id,
            'student_id' => $studentB->id,
            'user_id' => $guardianUser->id,
            'guardian_name' => 'Wali Siswa A',
            'relationship' => 'guardian',
            'is_verified' => false,
        ]);

        $ptspOtherSchool = $this->makeUser($this->schoolB, RoleEnum::PTSP, 'ptsp@b.test');

        $scope = app(AccessScopeService::class);
        $this->assertTrue($scope->canAccessStudentScope($homeroomUser, $this->schoolA->id, $studentA->id));
        $this->assertFalse($scope->canAccessStudentScope($homeroomUser, $this->schoolA->id, $studentB->id));
        $this->assertTrue($scope->canAccessStudentScope($guardianUser, $this->schoolA->id, $studentA->id));
        $this->assertFalse($scope->canAccessStudentScope($guardianUser, $this->schoolA->id, $studentB->id));
        $this->assertTrue($scope->canAccessStudentScope($studentUserA, $this->schoolA->id, $studentA->id));
        $this->assertFalse($scope->canAccessStudentScope($studentUserA, $this->schoolA->id, $studentB->id));
        $this->assertFalse($scope->canAccessStudentScope($ptspOtherSchool, $this->schoolA->id, $studentA->id));

        // D-03 Separation of duties: verifier vs final approver / signatory
        $operatorA = $this->makeUser($this->schoolA, RoleEnum::OPERATOR, 'op.duties@a.test');
        $principalA = $this->makeUser($this->schoolA, RoleEnum::PRINCIPAL, 'kepsek.duties@a.test');
        $signatoryA = $this->makeUser($this->schoolA, RoleEnum::SIGNATORY, 'ttd.duties@a.test');

        $this->assertTrue($scope->canVerifyQueue($operatorA, $this->schoolA->id));
        $this->assertFalse($scope->canApproveFinalDecision($operatorA, $this->schoolA->id));
        $this->assertFalse($scope->canSignOfficialDocument($operatorA, $this->schoolA->id));

        $this->assertFalse($scope->canVerifyQueue($principalA, $this->schoolA->id));
        $this->assertTrue($scope->canApproveFinalDecision($principalA, $this->schoolA->id));
        $this->assertTrue($scope->canSignOfficialDocument($principalA, $this->schoolA->id));
        $this->assertTrue($scope->canSignOfficialDocument($signatoryA, $this->schoolA->id));

        // AP-01.11 Post-login return path allowlist (prevents open redirect)
        $allowed = ['/layanan/ptsp', '/layanan/tracer-alumni'];
        $this->assertSame('/layanan/tracer-alumni', $scope->sanitizeReturnPath('/layanan/tracer-alumni', $allowed, '/layanan/ptsp'));
        $this->assertSame('/layanan/ptsp', $scope->sanitizeReturnPath('https://evil.example/phish', $allowed, '/layanan/ptsp'));
        $this->assertSame('/layanan/ptsp', $scope->sanitizeReturnPath('//evil.example/phish', $allowed, '/layanan/ptsp'));
        $this->assertSame('/layanan/ptsp', $scope->sanitizeReturnPath('/pengaturan/pengguna', $allowed, '/layanan/ptsp'));
    }

    public function test_external_applicant_timezone_and_ticket_concurrency_idempotency_and_lookup(): void
    {
        $schoolScope = app(SchoolScopeService::class);
        $this->assertSame('Asia/Makassar', $schoolScope->getTimezone($this->schoolA->id));
        $this->assertSame('Asia/Makassar', $schoolScope->localNow($this->schoolA->id)->timezoneName);

        $ticketService = app(ServiceTicketService::class);
        $ptspStaff = $this->makeUser($this->schoolA, RoleEnum::PTSP, 'ptsp@a.test');
        $otherOfficer = $this->makeUser($this->schoolA, RoleEnum::OPERATOR, 'operator@a.test');

        $external = $ticketService->registerExternalApplicant($this->schoolA->id, [
            'applicant_type' => 'alumni',
            'full_name' => 'Alumni Uji',
            'raw_identifier' => '6303011201000001',
            'phone' => '081234567890',
            'is_verified' => true,
        ]);
        $this->assertSame('0001', $external->identifier_last4);
        $this->assertArrayNotHasKey('identifier_hash', $external->toArray());

        $ticket1 = $ticketService->issueTicket($this->schoolA->id, [
            'service_type' => 'ptsp',
            'prefix' => 'PTSP',
            'external_applicant_id' => $external->id,
            'applicant_snapshot' => [
                'full_name' => 'Alumni Uji',
                'phone' => '081234567890',
                'requested_service' => 'Legalisir',
            ],
            'verification_secret' => '7890',
            'idempotency_key' => 'submit-uuid-123',
        ], $ptspStaff);

        // Retry with same idempotency key returns same ticket
        $ticketRetry = $ticketService->issueTicket($this->schoolA->id, [
            'service_type' => 'ptsp',
            'prefix' => 'PTSP',
            'external_applicant_id' => $external->id,
            'applicant_snapshot' => ['full_name' => 'Alumni Uji'],
            'verification_secret' => '7890',
            'idempotency_key' => 'submit-uuid-123',
        ], $ptspStaff);

        $this->assertSame($ticket1->id, $ticketRetry->id);
        $this->assertMatchesRegularExpression('/^PTSP-\d{4}-[23456789ABCDEFGHJKLMNPQRSTUVWXYZ]{8}$/', $ticket1->ticket_code);

        // Wrong verification secret does not reveal ticket details
        $denied = $ticketService->lookupPublicStatus($this->schoolA->id, $ticket1->ticket_code, '0000');
        $this->assertTrue($denied['found']);
        $this->assertFalse($denied['authorized']);
        $this->assertArrayNotHasKey('ticket', $denied);

        // Valid verification secret exposes ONLY minimum public status fields
        $allowed = $ticketService->lookupPublicStatus($this->schoolA->id, $ticket1->ticket_code, '7890');
        $this->assertTrue($allowed['authorized']);
        $this->assertSame('submitted', $allowed['ticket']['public_status']);
        $this->assertArrayNotHasKey('applicant_snapshot', $allowed['ticket']);

        // Cross-school lookup returns not_found
        $crossSchool = $ticketService->lookupPublicStatus($this->schoolB->id, $ticket1->ticket_code, '7890');
        $this->assertFalse($crossSchool['found']);

        // Transition version check guards against concurrent double-click / race
        $updated = $ticketService->transition(
            $ticket1->id,
            $this->schoolA->id,
            ServiceWorkflowMap::STATUS_IN_REVIEW,
            'in_review',
            null,
            $ptspStaff,
            expectedVersion: 1,
        );
        $this->assertSame(2, $updated->version);

        $this->expectException(RuntimeException::class);
        $ticketService->transition(
            $ticket1->id,
            $this->schoolA->id,
            ServiceWorkflowMap::STATUS_REJECTED,
            'rejected',
            'Berkas kurang',
            $otherOfficer,
            expectedVersion: 1,
        );
    }

    public function test_anonymous_aduan_is_hidden_from_non_task_force_staff(): void
    {
        $ticketService = app(ServiceTicketService::class);
        $ptspStaff = $this->makeUser($this->schoolA, RoleEnum::PTSP, 'ptsp.aduan@a.test');
        $satgas = $this->makeUser($this->schoolA, RoleEnum::TASK_FORCE, 'satgas@a.test');

        $aduanTicket = $ticketService->issueTicket($this->schoolA->id, [
            'service_type' => 'aduan',
            'prefix' => 'ADU',
            'applicant_snapshot' => ['mode' => 'anonymous'],
            'verification_secret' => 'rahasia-1',
            'is_anonymous' => true,
        ]);

        $byPtsp = $ticketService->lookupPublicStatus($this->schoolA->id, $aduanTicket->ticket_code, null, $ptspStaff);
        $this->assertFalse($byPtsp['authorized']);

        $bySatgas = $ticketService->lookupPublicStatus($this->schoolA->id, $aduanTicket->ticket_code, null, $satgas);
        $this->assertTrue($bySatgas['authorized']);

        // Ticket without verification_secret must not be readable by unauthorized viewers or public callers
        $secretlessAduan = $ticketService->issueTicket($this->schoolA->id, [
            'service_type' => 'aduan',
            'prefix' => 'ADU',
            'applicant_snapshot' => ['mode' => 'anonymous'],
            'is_anonymous' => true,
        ]);

        $publicWithoutSecret = $ticketService->lookupPublicStatus($this->schoolA->id, $secretlessAduan->ticket_code);
        $this->assertFalse($publicWithoutSecret['authorized']);
        $this->assertSame('identity_verification_required', $publicWithoutSecret['reason']);

        $ptspWithoutSecret = $ticketService->lookupPublicStatus($this->schoolA->id, $secretlessAduan->ticket_code, null, $ptspStaff);
        $this->assertFalse($ptspWithoutSecret['authorized']);

        $satgasWithoutSecret = $ticketService->lookupPublicStatus($this->schoolA->id, $secretlessAduan->ticket_code, null, $satgas);
        $this->assertTrue($satgasWithoutSecret['authorized']);
    }

    public function test_ticket_snapshot_survives_master_data_changes_and_handles_expired_or_cancelled_tickets(): void
    {
        $year = AcademicYear::query()->create([
            'school_id' => $this->schoolA->id,
            'name' => '2026/2027',
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
            'is_active' => true,
        ]);
        $term = Term::query()->create([
            'academic_year_id' => $year->id,
            'name' => 'Ganjil',
            'start_date' => '2026-07-01',
            'end_date' => '2026-12-31',
            'is_active' => true,
        ]);
        $classGroup = ClassGroup::query()->create([
            'school_id' => $this->schoolA->id,
            'academic_year_id' => $year->id,
            'grade_level' => 10,
            'name' => 'X-MIPA-1',
        ]);
        $student = Student::query()->create([
            'school_id' => $this->schoolA->id,
            'nis' => '2026001',
            'name' => 'Nama Siswa Awal',
        ]);
        Enrollment::query()->create([
            'term_id' => $term->id,
            'class_group_id' => $classGroup->id,
            'student_id' => $student->id,
            'status' => 'active',
        ]);

        $academicQuery = app(AcademicQueryService::class);
        $snapshot = $academicQuery->getStudentIdentitySnapshot($this->schoolA->id, $student->id);
        $this->assertNotNull($snapshot);

        $ticketService = app(ServiceTicketService::class);
        $ptspStaff = $this->makeUser($this->schoolA, RoleEnum::PTSP, 'ptsp.snap@a.test');

        $expiredTicket = $ticketService->issueTicket($this->schoolA->id, [
            'service_type' => 'izin',
            'prefix' => 'IZN',
            'subject_student_id' => $student->id,
            'applicant_snapshot' => $snapshot,
            'verification_secret' => '1122',
            'expires_at' => now()->subMinute(),
        ], $ptspStaff);

        // Mutate master student & deactivate term
        $student->update(['name' => 'Nama Siswa Berubah']);
        $term->update(['is_active' => false]);

        $expiredTicket->refresh();
        $this->assertSame('Nama Siswa Awal', $expiredTicket->applicant_snapshot['name']);
        $this->assertSame('X-MIPA-1', $expiredTicket->applicant_snapshot['class_group_name']);

        $expiredLookup = $ticketService->lookupPublicStatus($this->schoolA->id, $expiredTicket->ticket_code, '1122');
        $this->assertTrue($expiredLookup['authorized']);
        $this->assertSame(ServiceWorkflowMap::STATUS_EXPIRED, $expiredLookup['ticket']['public_status']);

        $cancelledTicket = $ticketService->issueTicket($this->schoolA->id, [
            'service_type' => 'ptsp',
            'prefix' => 'PTSP',
            'applicant_snapshot' => ['full_name' => 'Pemohon Batal'],
            'verification_secret' => '3344',
        ], $ptspStaff);

        $ticketService->transition(
            $cancelledTicket->id,
            $this->schoolA->id,
            ServiceWorkflowMap::STATUS_CANCELLED,
            'cancelled',
            'Dibatalkan oleh pemohon.',
            $ptspStaff,
        );

        $cancelledLookup = $ticketService->lookupPublicStatus($this->schoolA->id, $cancelledTicket->ticket_code, '3344');
        $this->assertTrue($cancelledLookup['authorized']);
        $this->assertSame(ServiceWorkflowMap::STATUS_CANCELLED, $cancelledLookup['ticket']['public_status']);
    }

    public function test_private_document_upload_validation_numbering_download_auth_and_revocation(): void
    {
        $docService = app(ServiceDocumentService::class);
        $uploader = $this->makeUser($this->schoolA, RoleEnum::PARENT, 'wali.doc@a.test');
        $ptspStaff = $this->makeUser($this->schoolA, RoleEnum::PTSP, 'ptsp.doc@a.test');
        $unrelatedTeacher = $this->makeUser($this->schoolA, RoleEnum::TEACHER, 'guru.doc@a.test');
        $otherSchoolAdmin = $this->makeUser($this->schoolB, RoleEnum::ADMIN, 'admin@b.test');

        $validPdf = UploadedFile::fake()->create('syarat.pdf', 120, 'application/pdf');
        $storedDoc = $docService->storePrivateAttachment($this->schoolA->id, $validPdf, 'attachment', null, $uploader);

        $this->assertSame('application/pdf', $storedDoc->mime_type);
        Storage::disk('local')->assertExists($storedDoc->storage_path);

        // Authorized uploader and PTSP staff can get temporary URL
        $url = $docService->temporaryDownloadUrl($ptspStaff, $storedDoc);
        $this->assertStringContainsString('/berkas-privat/unduh', $url);

        // Unrelated teacher and cross-school admin are denied
        $this->assertFalse($docService->canAccessDocument($unrelatedTeacher, $storedDoc));
        $this->assertFalse($docService->canAccessDocument($otherSchoolAdmin, $storedDoc));

        // Issue official numbered document and revoke it with audit trail
        $official = $docService->issueOfficialDocument($this->schoolA->id, [
            'document_kind' => 'letter',
            'number_prefix' => 'SKT',
            'template_key' => 'surat_keterangan_aktif',
            'template_version' => 2,
            'storage_path' => $storedDoc->storage_path,
            'original_name' => 'skt-resmi.pdf',
            'size_bytes' => $storedDoc->size_bytes,
            'sha256' => $storedDoc->sha256,
            'signatory_name' => 'Kepala Madrasah Contoh',
            'signatory_title' => 'Kepala Madrasah',
            'signature_mode' => 'manual_wet',
        ], $ptspStaff);

        $this->assertStringStartsWith('SKT/'.$this->schoolA->id.'/', (string) $official->document_number);
        $this->assertSame('issued', $official->status);

        $revoked = $docService->revokeDocument($official->id, $this->schoolA->id, 'Kesalahan penulisan nama.', $ptspStaff);
        $this->assertSame('revoked', $revoked->status);
        $this->assertSame('Kesalahan penulisan nama.', $revoked->revocation_reason);

        // Reject disguised executable upload
        $this->expectException(InvalidArgumentException::class);
        $badFile = UploadedFile::fake()->create('shell.php', 10, 'text/x-php');
        $docService->storePrivateAttachment($this->schoolA->id, $badFile, 'attachment', null, $uploader);
    }

    public function test_unauthorized_document_download_url_throws_authorization_exception(): void
    {
        $docService = app(ServiceDocumentService::class);
        $uploader = $this->makeUser($this->schoolA, RoleEnum::STUDENT, 'siswa.doc@a.test');
        $outsider = $this->makeUser($this->schoolA, RoleEnum::STUDENT, 'siswa.lain@a.test');

        $pdf = UploadedFile::fake()->create('lampiran.pdf', 50, 'application/pdf');
        $doc = $docService->storePrivateAttachment($this->schoolA->id, $pdf, 'evidence', null, $uploader);

        $this->expectException(AuthorizationException::class);
        $docService->temporaryDownloadUrl($outsider, $doc);
    }

    public function test_notification_outbox_is_idempotent_redacts_sensitive_payload_and_retries_safely(): void
    {
        Bus::fake();

        $outboxService = app(NotificationOutboxService::class);
        $user = $this->makeUser($this->schoolA, RoleEnum::STUDENT, 'notif@a.test');

        $first = $outboxService->enqueue(
            schoolId: $this->schoolA->id,
            eventKey: 'ptsp.status_changed',
            channel: 'in_app',
            recipientType: 'user',
            recipientId: $user->id,
            templateKey: 'ptsp_update',
            payload: [
                'ticket_code' => 'PTSP-2026-ABCD1234',
                'phone' => '081299998888',
                'nik' => '6303011201000001',
                'kronologi' => 'Detail rahasia tidak boleh tersimpan',
            ],
            idempotencyKey: 'notif-ptsp-1-in_review',
        );

        $duplicate = $outboxService->enqueue(
            schoolId: $this->schoolA->id,
            eventKey: 'ptsp.status_changed',
            channel: 'in_app',
            recipientType: 'user',
            recipientId: $user->id,
            templateKey: 'ptsp_update',
            payload: ['ticket_code' => 'PTSP-2026-ABCD1234'],
            idempotencyKey: 'notif-ptsp-1-in_review',
        );

        $this->assertSame($first->id, $duplicate->id);
        Bus::assertDispatched(DispatchNotificationOutboxJob::class, 1);

        $this->assertSame('PTSP-2026-ABCD1234', $first->payload['ticket_code']);
        $this->assertSame('[REDACTED]', $first->payload['phone']);
        $this->assertSame('[REDACTED]', $first->payload['nik']);
        $this->assertSame('[REDACTED]', $first->payload['kronologi']);

        // Simulate failure then safe retry without creating duplicate rows
        $failed = $outboxService->deliver($first->id, function (): void {
            throw new RuntimeException('Gateway timeout');
        });
        $this->assertSame('failed', $failed->status);
        $this->assertSame(1, $failed->attempts);

        $retried = $outboxService->retry($first->id, $this->schoolA->id, dispatchJob: false);
        $this->assertSame('queued', $retried->status);

        $sent = $outboxService->deliver($first->id);
        $this->assertSame('sent', $sent->status);

        // Delivering an already-sent notification is a no-op
        $idempotentDeliver = $outboxService->deliver($first->id);
        $this->assertSame($sent->attempts, $idempotentDeliver->attempts);

        // Verify Monolog processor redacts newly added sensitive keys
        $processor = new SanitizeSensitiveDataProcessor;
        $record = new LogRecord(
            datetime: new DateTimeImmutable,
            channel: 'test',
            level: Level::Info,
            message: 'Audit check',
            context: [
                'kronologi' => 'Isi sensitif',
                'verification_secret' => '1234',
                'guardian_phone' => '08111111',
                'safe_code' => 'PTSP-1',
            ],
        );
        $processed = $processor($record);
        $this->assertSame('***REDACTED***', $processed->context['kronologi']);
        $this->assertSame('***REDACTED***', $processed->context['verification_secret']);
        $this->assertSame('***REDACTED***', $processed->context['guardian_phone']);
        $this->assertSame('PTSP-1', $processed->context['safe_code']);

        // Verify AuditEvent redacts sensitive keys on persistence
        $audit = app(AuditLogger::class)->log(
            eventType: 'parity.privacy_check',
            targetType: 'ServiceTicket',
            targetId: '1',
            newValues: [
                'ticket_code' => 'PTSP-2026-ABCD1234',
                'kronologi' => 'Catatan kronologi rahasia',
                'verification_secret' => '7890',
            ],
            actor: $user,
            schoolId: $this->schoolA->id,
        );

        $this->assertSame('[REDACTED]', $audit->new_values['kronologi']);
        $this->assertSame('[REDACTED]', $audit->new_values['verification_secret']);
        $this->assertSame('PTSP-2026-ABCD1234', $audit->new_values['ticket_code']);
        $this->assertDatabaseHas('audit_events', [
            'school_id' => $this->schoolA->id,
            'event_type' => 'notification.retried',
        ]);
    }

    public function test_verification_supports_qr_code_query_and_dashboard_exposes_scoped_operational_metrics(): void
    {
        $admin = $this->makeUser($this->schoolA, RoleEnum::ADMIN, 'admin-qr@a.test');
        $docService = app(ServiceDocumentService::class);
        $doc = $docService->issueGeneratedDocument(
            schoolId: $this->schoolA->id,
            bytes: '%PDF-1.4 fake pdf content',
            payload: [
                'document_kind' => 'ptsp_letter',
                'number_prefix' => 'PTSP',
                'template_key' => 'ptsp_result',
                'verification_token' => 'VERIF-QR-TEST-001',
                'original_name' => 'ptsp-qr.pdf',
                'signatory_name' => 'Kepala Madrasah',
                'signatory_title' => 'Kepala',
            ],
            actor: $admin,
        );

        $this->get('/verifikasi?code='.$doc->verification_token)
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Public/Verification/Index')
                ->where('documentResult.found', true)
                ->where('documentResult.status', 'valid'));

        $ptspUser = $this->makeUser($this->schoolA, RoleEnum::PTSP, 'ptsp-dash@a.test');
        $this->actingAs($ptspUser)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Dashboard')
                ->has('stats.role_metrics.ptsp_open')
                ->has('stats.role_metrics.external_letters_open'));

        $librarian = $this->makeUser($this->schoolA, RoleEnum::LIBRARIAN, 'lib-dash@a.test');
        $this->actingAs($librarian)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Dashboard')
                ->has('stats.role_metrics.library_collections')
                ->has('stats.role_metrics.active_loans'));
    }

    private function makeUser(School $school, RoleEnum $role, string $email): User
    {
        return User::query()->create([
            'school_id' => $school->id,
            'role_id' => $this->roles[$role->value]->id,
            'name' => 'Pengguna '.$role->label(),
            'username' => str_replace(['@', '.'], '_', $email),
            'email' => $email,
            'password' => 'password123456',
            'is_active' => true,
        ]);
    }
}
