<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Academic\Infrastructure\Models\Student;
use App\Modules\Core\Application\Services\RolePermissionRegistryService;
use App\Modules\Core\Domain\RoleEnum;
use App\Modules\Core\Infrastructure\Models\School;
use App\Modules\Core\Infrastructure\Models\ServiceTicket;
use App\Modules\Integration\Infrastructure\Models\ActivityProposal;
use App\Modules\Integration\Infrastructure\Models\IntegrationConnection;
use App\Modules\Integration\Infrastructure\Models\IntegrationSyncLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProposalAndIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private School $schoolA;

    private School $schoolB;

    private User $adminA;

    private User $operatorA;

    private User $principalA;

    private User $teacherA;

    private User $studentA;

    private User $committeeA;

    private User $operatorB;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('private_files');

        $roles = app(RolePermissionRegistryService::class)->syncSystemRolesAndPermissions();

        $this->schoolA = School::query()->create([
            'name' => 'MAN Integrasi Contoh A',
            'npsn' => '30308801',
            'level' => 'MA',
            'timezone' => 'Asia/Makassar',
            'status' => 'active',
        ]);

        $this->schoolB = School::query()->create([
            'name' => 'MAN Integrasi Contoh B',
            'npsn' => '30308802',
            'level' => 'MA',
            'timezone' => 'Asia/Makassar',
            'status' => 'active',
        ]);

        $this->adminA = $this->makeUser($this->schoolA, $roles[RoleEnum::ADMIN->value]->id, 'admin.int@test.sch.id', 'admin_int');
        $this->operatorA = $this->makeUser($this->schoolA, $roles[RoleEnum::OPERATOR->value]->id, 'op.int@test.sch.id', 'op_int');
        $this->principalA = $this->makeUser($this->schoolA, $roles[RoleEnum::PRINCIPAL->value]->id, 'kepsek.int@test.sch.id', 'kepsek_int');
        $this->teacherA = $this->makeUser($this->schoolA, $roles[RoleEnum::TEACHER->value]->id, 'guru.int@test.sch.id', 'guru_int');
        $this->studentA = $this->makeUser($this->schoolA, $roles[RoleEnum::STUDENT->value]->id, 'siswa.int@test.sch.id', 'siswa_int');
        $this->committeeA = $this->makeUser($this->schoolA, $roles[RoleEnum::COMMITTEE->value]->id, 'komite.int@test.sch.id', 'komite_int');
        $this->operatorB = $this->makeUser($this->schoolB, $roles[RoleEnum::OPERATOR->value]->id, 'op.int.b@test.sch.id', 'op_int_b');

        Student::query()->create([
            'school_id' => $this->schoolA->id,
            'user_id' => $this->studentA->id,
            'nis' => '2026001',
            'nisn' => '0081234567',
            'name' => 'Alya Siswa Contoh',
            'gender' => 'P',
            'status' => 'active',
        ]);
    }

    public function test_teacher_work_proposal_supports_server_rab_recomputation_revisions_and_authorization(): void
    {
        // Students cannot access teacher work proposals
        $this->actingAs($this->studentA)
            ->get('/guru/proposal')
            ->assertForbidden();

        // Reject negative unit prices
        $this->actingAs($this->teacherA)
            ->post('/guru/proposal', [
                'title' => 'Pembinaan Olimpiade Sains',
                'activity_type' => 'academic_event',
                'organizer_name' => 'Tim Sains Madrasah',
                'start_date' => '2026-12-01',
                'purpose' => 'Persiapan KSM Kabupaten',
                'budget_items' => [
                    ['name' => 'Modul', 'qty' => 10, 'unit' => 'eksemplar', 'unit_price' => -5000],
                ],
            ])
            ->assertSessionHasErrors('budget_items.0.unit_price');

        // Submit teacher proposal with valid itemized RAB + attachment + idempotency
        $attachment = UploadedFile::fake()->create('kak-ksm.pdf', 120, 'application/pdf');
        $payload = [
            'title' => 'Pembinaan Intensif KSM Kimia',
            'activity_type' => 'academic_event',
            'organizer_name' => 'Ibu Rahmah, S.Pd.',
            'organizer_unit' => 'MGMP IPA',
            'start_date' => '2026-12-05',
            'end_date' => '2026-12-07',
            'location' => 'Lab Kimia Madrasah',
            'purpose' => 'Meningkatkan kesiapan peserta didik menghadapi KSM tingkat kabupaten.',
            'budget_items' => [
                ['name' => 'Modul Latihan', 'qty' => 20, 'unit' => 'paket', 'unit_price' => 30000],
                ['name' => 'Konsumsi Peserta', 'qty' => 20, 'unit' => 'box', 'unit_price' => 15000],
            ],
            'total_requested_amount' => 1, // Client spoofed value must be ignored
            'attachment' => $attachment,
            'idempotency_key' => 'prop-teacher-001',
        ];

        $this->actingAs($this->teacherA)
            ->post('/guru/proposal', $payload)
            ->assertSessionHasNoErrors();

        $proposal = ActivityProposal::query()->firstOrFail();
        $this->assertSame(ActivityProposal::MODE_TEACHER_WORK, $proposal->proposal_mode);
        $this->assertSame(900000, (int) $proposal->total_requested_amount);
        $this->assertSame(1, (int) $proposal->revision_number);
        $this->assertNotNull($proposal->attachment_document_id);

        // Teacher cannot act as reviewer
        $this->actingAs($this->teacherA)
            ->post("/administrasi/proposal/{$proposal->id}/keputusan", [
                'decision' => 'approved',
                'note' => 'Self-approve attempt',
            ])
            ->assertForbidden();

        // Reviewer requests revision
        $this->actingAs($this->principalA)
            ->post("/administrasi/proposal/{$proposal->id}/keputusan", [
                'decision' => 'needs_revision',
                'note' => 'Mohon pecah rincian modul latihan dan konsumsi.',
            ])
            ->assertSessionHasNoErrors();

        $proposal->refresh();
        $this->assertSame(ActivityProposal::STATUS_NEEDS_REVISION, $proposal->status);

        // Teacher resubmits revised RAB -> revision increments & history preserved
        $this->actingAs($this->teacherA)
            ->post("/proposal/{$proposal->id}/revisi", [
                'title' => 'Pembinaan Intensif KSM Kimia (Revisi)',
                'activity_type' => 'academic_event',
                'organizer_name' => 'Ibu Rahmah, S.Pd.',
                'organizer_unit' => 'MGMP IPA',
                'start_date' => '2026-12-05',
                'end_date' => '2026-12-07',
                'purpose' => 'Persiapan intensif 3 hari.',
                'revision_note' => 'Menyesuaikan jumlah paket konsumsi.',
                'budget_items' => [
                    ['name' => 'Modul Latihan', 'qty' => 20, 'unit' => 'paket', 'unit_price' => 30000],
                    ['name' => 'Konsumsi Peserta', 'qty' => 20, 'unit' => 'box', 'unit_price' => 20000],
                ],
            ])
            ->assertSessionHasNoErrors();

        $proposal->refresh();
        $this->assertSame(2, (int) $proposal->revision_number);
        $this->assertSame(1000000, (int) $proposal->total_requested_amount);
        $this->assertSame(ActivityProposal::STATUS_SUBMITTED, $proposal->status);
        $this->assertGreaterThanOrEqual(3, count($proposal->revision_history ?? []));

        // Reviewer approves final version
        $this->actingAs($this->operatorA)
            ->post("/administrasi/proposal/{$proposal->id}/keputusan", [
                'decision' => 'approved',
                'note' => 'Disetujui sesuai RAB revisi v2.',
            ])
            ->assertSessionHasNoErrors();

        $this->actingAs($this->teacherA)
            ->get('/guru/proposal')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Integration/Proposals/TeacherIndex')
                ->has('proposals', 1)
                ->where('proposals.0.status', 'approved')
                ->where('proposals.0.revision_number', 2));
    }

    public function test_independent_portal_supports_webinar_preset_lpj_upload_and_reviewer_closeout(): void
    {
        // Portal renders sample webinar preset ev_72b43c
        $this->actingAs($this->studentA)
            ->get('/layanan/proposal')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Integration/Proposals/IndependentPortal')
                ->where('samplePreset.preset_code', 'ev_72b43c'));

        // Student submits independent proposal
        $this->actingAs($this->studentA)
            ->post('/layanan/proposal', [
                'title' => 'Webinar Nasional Literasi Digital OSIS',
                'activity_type' => 'workshop',
                'organizer_name' => 'OSIS & MPK Bidang TIK',
                'organizer_unit' => 'OSIS Madrasah',
                'committee_members' => [
                    ['role' => 'Ketua Pelaksana', 'name' => 'Ahmad Fauzan'],
                    ['role' => 'Sekretaris', 'name' => 'Nadia Putri'],
                ],
                'start_date' => '2026-12-12',
                'end_date' => '2026-12-12',
                'location' => 'Aula Madrasah',
                'purpose' => 'Edukasi literasi digital dan verifikasi informasi bagi pelajar.',
                'budget_items' => [
                    ['name' => 'Honor Narasumber', 'qty' => 2, 'unit' => 'sesi', 'unit_price' => 500000],
                    ['name' => 'Konsumsi', 'qty' => 20, 'unit' => 'paket', 'unit_price' => 20000],
                ],
            ])
            ->assertSessionHasNoErrors();

        $proposal = ActivityProposal::query()->where('proposal_mode', ActivityProposal::MODE_INDEPENDENT)->firstOrFail();
        $this->assertSame(1400000, (int) $proposal->total_requested_amount);
        $this->assertCount(2, $proposal->committee_members ?? []);

        // Committee/reviewer approves proposal
        $this->actingAs($this->committeeA)
            ->post("/administrasi/proposal/{$proposal->id}/keputusan", [
                'decision' => 'approved',
                'note' => 'Proposal kegiatan OSIS disetujui.',
            ])
            ->assertSessionHasNoErrors();

        // Reviewer cannot close proposal before LPJ proof is submitted
        $this->actingAs($this->committeeA)
            ->post("/administrasi/proposal/{$proposal->id}/keputusan", [
                'decision' => 'closed',
                'note' => 'Coba tutup tanpa LPJ.',
            ])
            ->assertSessionHasErrors('review');

        // Student uploads LPJ realization proof
        $proofFile = UploadedFile::fake()->create('lpj-webinar-osis.pdf', 180, 'application/pdf');
        $this->actingAs($this->studentA)
            ->post("/proposal/{$proposal->id}/lpj", [
                'lpj_realization_amount' => 1325000,
                'lpj_summary' => 'Webinar diikuti 210 siswa; sisa anggaran tidak digunakan.',
                'proof_file' => $proofFile,
            ])
            ->assertSessionHasNoErrors();

        $proposal->refresh();
        $this->assertSame(ActivityProposal::STATUS_LPJ_SUBMITTED, $proposal->status);
        $this->assertSame(1325000, (int) $proposal->lpj_realization_amount);
        $this->assertCount(1, $proposal->lpj_document_ids ?? []);

        $docId = $proposal->lpj_document_ids[0];

        // Cross-school operator cannot download LPJ attachment
        $this->actingAs($this->operatorB)
            ->get("/proposal/{$proposal->id}/dokumen/{$docId}")
            ->assertForbidden();

        // Owner or reviewer can download LPJ proof
        $this->actingAs($this->studentA)
            ->get("/proposal/{$proposal->id}/dokumen/{$docId}")
            ->assertOk();

        // Reviewer closes LPJ and linked ServiceTicket marks completed
        $this->actingAs($this->committeeA)
            ->post("/administrasi/proposal/{$proposal->id}/keputusan", [
                'decision' => 'closed',
                'note' => 'LPJ lengkap dan diverifikasi.',
            ])
            ->assertSessionHasNoErrors();

        $proposal->refresh();
        $this->assertSame(ActivityProposal::STATUS_CLOSED, $proposal->status);
        $this->assertSame('completed', ServiceTicket::query()->findOrFail($proposal->service_ticket_id)->internal_status);
    }

    public function test_emis_rdm_slims_reconciliation_reports_partial_failures_and_supports_idempotent_retry(): void
    {
        $this->actingAs($this->teacherA)
            ->get('/pengaturan/integrasi')
            ->assertForbidden();

        // Configure EMIS mechanism + custom column mapping
        $this->actingAs($this->operatorA)
            ->put('/pengaturan/integrasi/koneksi/emis', [
                'mechanism' => 'file_exchange',
                'is_enabled' => true,
                'endpoint_url' => 'https://emis.kemenag.go.id/portal',
                'config' => [
                    'field_mapping' => [
                        'nisn' => 'nisn_siswa',
                        'full_name' => 'nama_lengkap',
                        'class_name' => 'rombel',
                        'status' => 'status',
                    ],
                ],
            ])
            ->assertSessionHasNoErrors();

        $csvWithError = implode("\n", [
            'nisn_siswa,nama_lengkap,rombel,status',
            '0081234567,Alya Siswa Contoh,XI IPA 1,active',
            'BAD_NISN,Budi Tanpa NISN,XI IPA 1,active',
        ]);

        // Dry-run preview identifies partial error & recovery action
        $this->actingAs($this->operatorA)
            ->post('/pengaturan/integrasi/sinkronisasi/emis/pratinjau', [
                'payload_text' => $csvWithError,
            ])
            ->assertSessionHas('previewResult', fn (array $preview): bool => $preview['total_rows'] === 2
                && $preview['valid_rows'] === 1
                && $preview['failed_rows'] === 1
                && $preview['failed_details'][0]['row_number'] === 2
                && str_contains($preview['failed_details'][0]['recovery_action'], 'NISN'));

        // Commit batch creates partial_failed log
        $this->actingAs($this->operatorA)
            ->post('/pengaturan/integrasi/sinkronisasi/emis/impor', [
                'payload_text' => $csvWithError,
                'idempotency_key' => 'emis-batch-1',
            ])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', fn (string $message): bool => str_contains($message, 'Data tujuan belum diperbarui.'));

        $log = IntegrationSyncLog::query()->firstOrFail();
        $this->assertSame('partial_failed', $log->status);
        $this->assertSame(1, $log->success_rows);
        $this->assertSame(1, $log->failed_rows);

        // Retry with corrected row resolves batch
        $this->actingAs($this->operatorA)
            ->post("/pengaturan/integrasi/log/{$log->id}/ulang", [
                'corrected_rows' => [
                    [
                        'nisn_siswa' => '0089998887',
                        'nama_lengkap' => 'Budi Diperbaiki',
                        'rombel' => 'XI IPA 1',
                        'status' => 'active',
                    ],
                ],
            ])
            ->assertSessionHasNoErrors();

        $retryCountAfterFirst = IntegrationSyncLog::query()->where('retried_from_log_id', $log->id)->count();
        $this->assertSame(1, $retryCountAfterFirst);

        // Repeating retry on the failed batch is idempotent and does not duplicate completed retries
        $this->actingAs($this->operatorA)
            ->post("/pengaturan/integrasi/log/{$log->id}/ulang", [])
            ->assertSessionHasNoErrors();

        $this->assertSame(1, IntegrationSyncLog::query()->where('retried_from_log_id', $log->id)->count());

        // CSV export works cleanly for EMIS
        $this->actingAs($this->operatorA)
            ->get('/pengaturan/integrasi/sinkronisasi/emis/ekspor')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=utf-8');
    }

    public function test_external_channel_secrets_are_masked_everywhere_and_outage_falls_back_gracefully(): void
    {
        $secretToken = 'wa-secret-token-xyz-9876';

        $this->actingAs($this->adminA)
            ->put('/pengaturan/integrasi/koneksi/whatsapp', [
                'mechanism' => 'provider',
                'is_enabled' => true,
                'endpoint_url' => 'https://wa-gateway.example.test/send',
                'secret_token' => $secretToken,
                'config' => [
                    'provider_name' => 'Madrasah WA Gateway',
                    'sender_number' => '628123456789',
                ],
            ])
            ->assertSessionHasNoErrors();

        $conn = IntegrationConnection::query()
            ->where('school_id', $this->schoolA->id)
            ->where('system_key', 'whatsapp')
            ->firstOrFail();

        $this->assertSame('9876', $conn->secret_last4);
        $this->assertArrayNotHasKey('secret_encrypted', $conn->toArray());

        // Verify raw secret never appears in Inertia props or audit logs
        $pageResponse = $this->actingAs($this->adminA)
            ->get('/pengaturan/integrasi')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Integration/Settings/Index')
                ->where('workspace.channels.4.has_secret', true)
                ->where('workspace.channels.4.secret_masked', '••••9876'));

        $this->assertStringNotContainsString($secretToken, $pageResponse->getContent());

        $auditPayloads = DB::table('audit_events')->pluck('new_values')->map(fn ($v) => (string) $v)->implode(' ');
        $this->assertStringNotContainsString($secretToken, $auditPayloads);

        $this->actingAs($this->adminA)
            ->post('/pengaturan/integrasi/koneksi/whatsapp/uji')
            ->assertSessionHas('healthResult', fn (array $result): bool => $result['status'] === 'configuration_validated');

        // Simulated outage updates health status safely with fallback active
        $this->actingAs($this->adminA)
            ->post('/pengaturan/integrasi/koneksi/whatsapp/uji', [
                'simulate_outage' => true,
            ])
            ->assertSessionHas('healthResult', fn (array $result): bool => $result['status'] === 'offline'
                && $result['fallback_active'] === true
                && $result['core_service_impact'] === 'none'
                && str_contains($result['message'], 'Fallback aktif'));

        $conn->refresh();
        $this->assertSame('offline', $conn->last_health_status);
    }

    private function makeUser(School $school, int $roleId, string $email, string $username): User
    {
        return User::query()->create([
            'school_id' => $school->id,
            'role_id' => $roleId,
            'name' => strtoupper($username),
            'username' => $username,
            'email' => $email,
            'password' => 'Password123!',
            'is_active' => true,
        ]);
    }
}
