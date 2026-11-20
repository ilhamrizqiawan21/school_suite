<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Academic\Infrastructure\Models\Teacher;
use App\Modules\Administration\Infrastructure\Models\CorrespondenceRecord;
use App\Modules\Administration\Infrastructure\Models\GuestbookVisit;
use App\Modules\Administration\Infrastructure\Models\PtspRequest;
use App\Modules\Administration\Infrastructure\Models\PtspService;
use App\Modules\Core\Application\Services\ExternalApplicantAuthService;
use App\Modules\Core\Application\Services\RolePermissionRegistryService;
use App\Modules\Core\Application\Services\ServiceTicketService;
use App\Modules\Core\Domain\RoleEnum;
use App\Modules\Core\Infrastructure\Models\School;
use App\Modules\Core\Infrastructure\Models\ServiceTicket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PtspAndTataUsahaTest extends TestCase
{
    use RefreshDatabase;

    private School $schoolA;

    private School $schoolB;

    private User $operatorA;

    private User $ptspOfficerA;

    private User $receptionistA;

    private User $teacherA;

    private User $operatorB;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $roles = app(RolePermissionRegistryService::class)->syncSystemRolesAndPermissions();

        $this->schoolA = School::query()->create([
            'name' => 'MAN Contoh Banjar',
            'npsn' => '30309901',
            'level' => 'MA',
            'timezone' => 'Asia/Makassar',
            'address' => 'Jl. Pendidikan No. 1',
            'phone' => '0511-555123',
        ]);

        $this->schoolB = School::query()->create([
            'name' => 'MAN Contoh Lain',
            'npsn' => '30309902',
            'level' => 'MA',
            'timezone' => 'Asia/Jakarta',
        ]);

        $this->operatorA = $this->createUser($this->schoolA, $roles[RoleEnum::OPERATOR->value]->id, 'operator.a@test.sch.id', 'op_a');
        $this->ptspOfficerA = $this->createUser($this->schoolA, $roles[RoleEnum::PTSP->value]->id, 'ptsp.a@test.sch.id', 'ptsp_a');
        $this->receptionistA = $this->createUser($this->schoolA, $roles[RoleEnum::RECEPTIONIST->value]->id, 'resepsionis.a@test.sch.id', 'rec_a');
        $this->teacherA = $this->createUser($this->schoolA, $roles[RoleEnum::TEACHER->value]->id, 'guru.a@test.sch.id', 'guru_a');
        $this->operatorB = $this->createUser($this->schoolB, $roles[RoleEnum::OPERATOR->value]->id, 'operator.b@test.sch.id', 'op_b');
    }

    public function test_external_applicant_register_login_profile_and_password_recovery_flow(): void
    {
        $this->post('/layanan/ptsp/akun/daftar', [
            'applicant_type' => 'guardian',
            'full_name' => 'Siti Aminah',
            'email' => 'siti.wali@example.test',
            'phone' => '081234567890',
            'organization_name' => 'Wali Siswa X-A',
            'raw_identifier' => '6303010101900012',
            'password' => 'rahasia1234',
            'password_confirmation' => 'rahasia1234',
        ])->assertRedirect('/layanan/ptsp');

        $this->assertDatabaseHas('external_applicants', [
            'school_id' => $this->schoolA->id,
            'email' => 'siti.wali@example.test',
            'identifier_last4' => '0012',
        ]);

        // External applicant session cannot access internal staff workspace
        $this->get('/administrasi/ptsp')->assertRedirect('/login');
        $this->get('/dashboard')->assertRedirect('/login');

        // Update profile
        $this->patch('/layanan/ptsp/akun/profil', [
            'applicant_type' => 'guardian',
            'full_name' => 'Siti Aminah Pemohon',
            'phone' => '081234569999',
            'organization_name' => 'Wali Siswa X-B',
        ])->assertRedirect();

        $this->assertDatabaseHas('external_applicants', [
            'email' => 'siti.wali@example.test',
            'full_name' => 'Siti Aminah Pemohon',
            'phone' => '081234569999',
        ]);

        // Logout then test wrong login & valid login
        $this->post('/layanan/ptsp/akun/keluar')->assertRedirect('/layanan/ptsp/akun');
        $this->post('/layanan/ptsp/akun/masuk', [
            'email' => 'siti.wali@example.test',
            'password' => 'salah-sandi',
        ])->assertSessionHasErrors('email');

        $this->post('/layanan/ptsp/akun/masuk', [
            'email' => 'siti.wali@example.test',
            'password' => 'rahasia1234',
        ])->assertRedirect('/layanan/ptsp');

        $this->post('/layanan/ptsp/akun/keluar');

        // Account recovery: mismatch hint rejected, valid hint issues token and resets password
        $this->post('/layanan/ptsp/akun/pulihkan', [
            'email' => 'siti.wali@example.test',
            'verification_hint' => '0000',
        ])->assertSessionHasErrors('verification_hint');

        $recovery = app(ExternalApplicantAuthService::class)->requestRecovery(
            $this->schoolA->id,
            'siti.wali@example.test',
            '9999',
        );
        $this->assertTrue($recovery['accepted']);
        $this->assertNotEmpty($recovery['token']);

        // Wrong token fails
        $this->post('/layanan/ptsp/akun/reset-sandi', [
            'email' => 'siti.wali@example.test',
            'recovery_token' => 'WRONGTOKEN',
            'password' => 'sandibaru123',
            'password_confirmation' => 'sandibaru123',
        ])->assertSessionHasErrors('recovery_token');

        // Valid token succeeds
        $this->post('/layanan/ptsp/akun/reset-sandi', [
            'email' => 'siti.wali@example.test',
            'recovery_token' => $recovery['token'],
            'password' => 'sandibaru123',
            'password_confirmation' => 'sandibaru123',
        ])->assertRedirect('/layanan/ptsp/akun');

        $this->post('/layanan/ptsp/akun/masuk', [
            'email' => 'siti.wali@example.test',
            'password' => 'sandibaru123',
        ])->assertRedirect('/layanan/ptsp');
    }

    public function test_ptsp_catalog_snapshot_immutability_draft_submit_idempotency_and_revision_flow(): void
    {
        // Staff creates active PTSP service with mandatory document
        $this->actingAs($this->ptspOfficerA)
            ->post('/administrasi/ptsp/katalog', [
                'code' => 'SK-AKTIF',
                'name' => 'Surat Keterangan Aktif Belajar',
                'category' => 'persuratan',
                'description' => 'Penerbitan surat keterangan aktif untuk keperluan beasiswa atau administrasi.',
                'requirements' => ['Kartu pelajar atau surat pengantar'],
                'required_documents' => [
                    ['key' => 'kartu_pelajar', 'label' => 'Scan Kartu Pelajar', 'required' => true],
                ],
                'estimated_duration' => '2 Hari Kerja',
                'service_hours' => '07.30 - 15.00',
                'delivery_mode' => 'both',
                'requires_verification_secret' => true,
                'is_active' => true,
                'is_published' => true,
            ])
            ->assertRedirect();

        /** @var PtspService $service */
        $service = PtspService::query()->where('code', 'SK-AKTIF')->firstOrFail();

        // Also create a closed service
        $closedService = PtspService::query()->create([
            'school_id' => $this->schoolA->id,
            'code' => 'SK-TUTUP',
            'name' => 'Layanan Ditutup Sementara',
            'category' => 'umum',
            'estimated_duration' => '3 Hari',
            'delivery_mode' => 'digital',
            'is_active' => false,
            'is_published' => true,
        ]);

        auth()->logout();

        // Register external applicant
        $this->post('/layanan/ptsp/akun/daftar', [
            'applicant_type' => 'student',
            'full_name' => 'Ahmad Zaini',
            'email' => 'ahmad.zaini@example.test',
            'phone' => '081111222333',
            'password' => 'password1234',
            'password_confirmation' => 'password1234',
        ]);

        // Submitting to closed service must fail
        $this->post('/layanan/ptsp/draf', [
            'ptsp_service_id' => $closedService->id,
            'applicant_type' => 'student',
            'applicant_name' => 'Ahmad Zaini',
            'purpose' => 'Uji layanan tutup',
        ])->assertSessionHasErrors('ptsp_service_id');

        // Save draft on active service without required attachment yet
        $this->post('/layanan/ptsp/draf', [
            'ptsp_service_id' => $service->id,
            'applicant_type' => 'student',
            'applicant_name' => 'Ahmad Zaini',
            'applicant_phone' => '081111222333',
            'applicant_email' => 'ahmad.zaini@example.test',
            'purpose' => 'Syarat pengajuan beasiswa daerah',
        ])->assertRedirect();

        /** @var PtspRequest $draft */
        $draft = PtspRequest::query()->firstOrFail();
        $this->assertSame('draft', $draft->status);

        // Submitting draft without mandatory document must be rejected
        $this->post('/layanan/ptsp/ajukan', [
            'request_id' => $draft->id,
            'agreement_accepted' => true,
        ])->assertSessionHasErrors('attachments.kartu_pelajar');

        // Catalog requirement changes AFTER draft created; existing draft keeps frozen snapshot!
        $this->actingAs($this->operatorA)
            ->put("/administrasi/ptsp/katalog/{$service->id}", [
                'code' => 'SK-AKTIF',
                'name' => 'Surat Keterangan Aktif (Versi Baru)',
                'category' => 'persuratan',
                'required_documents' => [
                    ['key' => 'kartu_pelajar', 'label' => 'Scan Kartu Pelajar', 'required' => true],
                    ['key' => 'kk_baru', 'label' => 'Kartu Keluarga Terbaru', 'required' => true],
                ],
                'estimated_duration' => '5 Hari Kerja',
                'delivery_mode' => 'both',
                'is_active' => true,
                'is_published' => true,
            ])->assertRedirect();

        $this->assertCount(1, $draft->fresh()->service_snapshot['required_documents']);

        // Re-login external applicant and submit with the original required document
        auth()->logout();
        $this->post('/layanan/ptsp/akun/masuk', [
            'email' => 'ahmad.zaini@example.test',
            'password' => 'password1234',
        ]);

        $pdf = UploadedFile::fake()->create('kartu-pelajar.pdf', 120, 'application/pdf');
        $this->post('/layanan/ptsp/ajukan', [
            'request_id' => $draft->id,
            'applicant_type' => 'student',
            'applicant_name' => 'Ahmad Zaini',
            'purpose' => 'Syarat pengajuan beasiswa daerah',
            'verification_secret' => 'PIN987',
            'agreement_accepted' => true,
            'idempotency_key' => 'ptsp-idem-001',
            'attachments' => [
                'kartu_pelajar' => $pdf,
            ],
        ])->assertRedirect();

        $draft->refresh();
        $this->assertSame('submitted', $draft->status);
        $this->assertNotNull($draft->service_ticket_id);

        /** @var ServiceTicket $ticket */
        $ticket = ServiceTicket::query()->findOrFail($draft->service_ticket_id);
        $this->assertStringStartsWith('PTSP-', $ticket->ticket_code);

        // Duplicate submit produces no second ticket or request
        $this->post('/layanan/ptsp/ajukan', [
            'request_id' => $draft->id,
            'agreement_accepted' => true,
            'idempotency_key' => 'ptsp-idem-001',
        ])->assertRedirect();
        $this->assertSame(1, PtspRequest::query()->count());
        $this->assertSame(1, ServiceTicket::query()->where('service_type', 'ptsp')->count());

        // Receipt page & PDF accessible to owner
        $this->get("/layanan/ptsp/resi/{$ticket->ticket_code}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/Services/PtspReceipt')
                ->where('ptspRequest.ticket_code', $ticket->ticket_code)
            );
        $this->get("/layanan/ptsp/resi/{$ticket->ticket_code}/pdf")
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        // Unauthorized guest without secret cannot open receipt
        $this->post('/layanan/ptsp/akun/keluar');
        $this->get("/layanan/ptsp/resi/{$ticket->ticket_code}")->assertForbidden();

        // Officer processes, requests revision, applicant revises, officer dispositions, issues result, records pickup
        $this->actingAs($this->ptspOfficerA)
            ->post("/administrasi/ptsp/permohonan/{$draft->id}/tinjau", [
                'expected_version' => $ticket->version,
            ])->assertRedirect();

        $this->actingAs($this->ptspOfficerA)
            ->post("/administrasi/ptsp/permohonan/{$draft->id}/revisi", [
                'reason' => 'Foto kartu pelajar buram, mohon unggah ulang.',
                'expected_version' => $ticket->fresh()->version,
            ])->assertRedirect();

        $this->assertSame('needs_revision', $draft->fresh()->status);

        // Cross-school operator cannot access school A request
        $this->actingAs($this->operatorB)
            ->post("/administrasi/ptsp/permohonan/{$draft->id}/tinjau", [])
            ->assertNotFound();

        // Non-TU teacher cannot access PTSP staff workspace
        $this->actingAs($this->teacherA)
            ->get('/administrasi/ptsp')
            ->assertForbidden();

        // Applicant logs back in and submits revision
        auth()->logout();
        $this->post('/layanan/ptsp/akun/masuk', [
            'email' => 'ahmad.zaini@example.test',
            'password' => 'password1234',
        ]);

        $newPdf = UploadedFile::fake()->create('kartu-pelajar-jelas.pdf', 140, 'application/pdf');
        $this->post("/layanan/ptsp/{$ticket->ticket_code}/perbaiki", [
            'purpose' => 'Syarat pengajuan beasiswa daerah (berkas diperbaiki)',
            'revision_reply' => 'Sudah diunggah scan resolusi tinggi.',
            'attachments' => ['kartu_pelajar' => $newPdf],
        ])->assertRedirect("/layanan/ptsp/resi/{$ticket->ticket_code}");

        $this->assertSame('submitted', $draft->fresh()->status);

        // Officer assigns disposition, issues physical-ready result, then records pickup
        $this->actingAs($this->ptspOfficerA)
            ->post("/administrasi/ptsp/permohonan/{$draft->id}/disposisi", [
                'disposition_to' => 'Kaur Tata Usaha',
                'disposition_note' => 'Mohon siapkan tanda tangan kepala madrasah.',
                'expected_version' => $ticket->fresh()->version,
            ])->assertRedirect();

        $this->actingAs($this->ptspOfficerA)
            ->post("/administrasi/ptsp/permohonan/{$draft->id}/terbitkan", [
                'delivery_mode' => 'both',
                'mark_completed' => false,
                'result_summary' => 'Surat Keterangan Aktif sudah terbit dan siap diambil.',
                'signatory_name' => 'H. Abdul Karim, M.Pd.',
                'signatory_title' => 'Kepala Madrasah',
                'expected_version' => $ticket->fresh()->version,
            ])->assertRedirect();

        $draft->refresh();
        $this->assertSame('approved', $draft->status);
        $this->assertNotNull($draft->result_document_id);

        $this->actingAs($this->ptspOfficerA)
            ->post("/administrasi/ptsp/permohonan/{$draft->id}/ambil-fisik", [
                'physical_pickup_name' => 'Ahmad Zaini',
                'physical_pickup_notes' => 'Diambil langsung dengan menunjukkan kartu pelajar.',
                'expected_version' => $ticket->fresh()->version,
            ])->assertRedirect();

        $this->assertSame('completed', $draft->fresh()->status);
        $this->assertSame('Ahmad Zaini', $draft->fresh()->physical_pickup_name);

        // Authorized applicant can download issued result PDF
        auth()->logout();
        $this->post('/layanan/ptsp/akun/masuk', [
            'email' => 'ahmad.zaini@example.test',
            'password' => 'password1234',
        ]);
        $this->get("/layanan/ptsp/{$ticket->ticket_code}/dokumen/{$draft->result_document_id}")
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_unified_tracking_handles_categories_verification_expiration_and_anonymous_reports(): void
    {
        $ticketService = app(ServiceTicketService::class);

        $ptspTicket = $ticketService->issueTicket($this->schoolA->id, [
            'service_type' => 'ptsp',
            'prefix' => 'PTSP',
            'applicant_snapshot' => ['name' => 'Siswa Uji', 'service_name' => 'Surat Keterangan'],
            'verification_secret' => '112233',
            'public_summary' => 'Sedang diproses PTSP.',
        ]);

        $izinTicket = $ticketService->issueTicket($this->schoolA->id, [
            'service_type' => 'izin',
            'prefix' => 'IZIN',
            'applicant_snapshot' => ['name' => 'Wali Uji'],
            'verification_secret' => 'IZIN99',
            'public_summary' => 'Izin siswa menunggu wali kelas.',
        ]);

        $expiredTicket = $ticketService->issueTicket($this->schoolA->id, [
            'service_type' => 'legalisir',
            'prefix' => 'LGL',
            'applicant_snapshot' => ['name' => 'Alumni Uji'],
            'verification_secret' => 'EXP123',
            'expires_at' => now()->subDay(),
        ]);

        $anonAduan = $ticketService->issueTicket($this->schoolA->id, [
            'service_type' => 'aduan',
            'prefix' => 'ADU',
            'applicant_snapshot' => ['name' => 'RAHASIA PELAPOR'],
            'verification_secret' => 'ADU777',
            'is_anonymous' => true,
            'public_summary' => 'Laporan diterima Satgas.',
        ]);

        // Wrong secret does NOT expose status or documents
        $this->post('/lacak', [
            'code' => $ptspTicket->ticket_code,
            'category' => 'ptsp',
            'verification_secret' => 'SALAH',
        ])->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Public/Tracking/Index')
            ->where('result.found', true)
            ->where('result.authorized', false)
            ->where('result.reason', 'identity_verification_failed')
            ->missing('result.ticket.public_summary')
        );

        // Category mismatch hides result
        $this->post('/lacak', [
            'code' => $ptspTicket->ticket_code,
            'category' => 'mutasi',
            'verification_secret' => '112233',
        ])->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('result.found', false)
            ->where('result.reason', 'category_mismatch')
        );

        // Valid secret under category filter succeeds
        $this->post('/lacak', [
            'code' => $izinTicket->ticket_code,
            'category' => 'izin',
            'verification_secret' => 'IZIN99',
        ])->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('result.found', true)
            ->where('result.authorized', true)
            ->where('result.ticket.service_type', 'izin')
            ->where('result.ticket.public_status', 'submitted')
        );

        // Expired ticket reports expired
        $this->post('/lacak', [
            'code' => $expiredTicket->ticket_code,
            'category' => 'legalisir',
            'verification_secret' => 'EXP123',
        ])->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('result.authorized', true)
            ->where('result.ticket.public_status', 'expired')
        );

        // Anonymous complaint never exposes reporter identity
        $this->post('/lacak', [
            'code' => $anonAduan->ticket_code,
            'category' => 'aduan',
            'verification_secret' => 'ADU777',
        ])->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('result.authorized', true)
            ->where('result.ticket.service_type', 'aduan')
            ->where('result.ticket.meta', [])
        );
    }

    public function test_external_official_letter_enters_correspondence_agenda_without_duplication_and_syncs_status(): void
    {
        $primaryPdf = UploadedFile::fake()->create('undangan-dinas.pdf', 200, 'application/pdf');
        $supporting = UploadedFile::fake()->create('lampiran-jadwal.pdf', 80, 'application/pdf');

        $payload = [
            'agency_name' => 'Kantor Wilayah Kemenag Provinsi',
            'contact_person' => 'H. Syamsuddin',
            'contact_position' => 'Kasubag Umum',
            'contact_phone' => '081355667788',
            'contact_email' => 'umum@kemenag.example.test',
            'letter_number' => 'B-421/Kw.17/2026',
            'letter_date' => '2026-11-18',
            'urgency' => 'important',
            'classification' => 'Kedinas-an',
            'subject' => 'Undangan Rapat Koordinasi Kepala Madrasah',
            'summary' => 'Permohonan kehadiran kepala madrasah pada rapat koordinasi bulanan.',
            'primary_pdf' => $primaryPdf,
            'supporting_files' => [$supporting],
            'verification_secret' => 'SRT555',
            'statement_accepted' => true,
            'idempotency_key' => 'ext-idem-2026-1',
        ];

        $this->post('/layanan/surat-dinas', $payload)->assertRedirect();

        /** @var CorrespondenceRecord $record */
        $record = CorrespondenceRecord::query()->firstOrFail();
        $this->assertSame('incoming', $record->letter_type);
        $this->assertSame('received', $record->status);
        $this->assertNotNull($record->service_ticket_id);
        $this->assertStringStartsWith('IN/', (string) $record->agenda_number);

        /** @var ServiceTicket $ticket */
        $ticket = ServiceTicket::query()->findOrFail($record->service_ticket_id);
        $this->assertSame('surat_eksternal', $ticket->service_type);

        // Receipt page and PDF accessible to submitting session
        $this->get("/layanan/surat-dinas/tanda-terima/{$ticket->ticket_code}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/Services/ExternalLetterReceipt')
                ->where('receipt.record.agenda_number', $record->agenda_number)
            );
        $this->get("/layanan/surat-dinas/tanda-terima/{$ticket->ticket_code}/pdf")
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        // Re-submitting with same idempotency_key creates NO duplicate correspondence entry
        $this->post('/layanan/surat-dinas', [
            ...$payload,
            'primary_pdf' => UploadedFile::fake()->create('undangan-dinas.pdf', 200, 'application/pdf'),
            'supporting_files' => [],
        ])->assertRedirect();
        $this->assertSame(1, CorrespondenceRecord::query()->count());

        // TU dispositions & completes incoming letter -> unified tracking reflects TU status!
        $this->actingAs($this->operatorA)
            ->patch("/administrasi/persuratan/surat/{$record->id}/status", [
                'status' => 'disposition',
                'disposition' => 'Diteruskan ke Kepala Madrasah untuk ditindaklanjuti.',
            ])->assertRedirect();

        $this->assertSame('in_review', $ticket->fresh()->public_status);

        $this->actingAs($this->operatorA)
            ->patch("/administrasi/persuratan/surat/{$record->id}/status", [
                'status' => 'done',
            ])->assertRedirect();

        $this->assertSame('completed', $ticket->fresh()->public_status);

        auth()->logout();
        $this->post('/lacak', [
            'code' => $ticket->ticket_code,
            'category' => 'surat_eksternal',
            'verification_secret' => 'SRT555',
        ])->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('result.authorized', true)
            ->where('result.ticket.public_status', 'completed')
            ->where('result.ticket.meta.agenda_number', $record->agenda_number)
        );
    }

    public function test_guestbook_records_visits_isolates_public_data_and_supports_receptionist_checkout_and_export(): void
    {
        $teacher = Teacher::query()->create([
            'school_id' => $this->schoolA->id,
            'nip' => '198501012010011005',
            'name' => 'Drs. Hasan Basri',
            'is_active' => true,
        ]);

        // Public visitor self-registration works and returns only own confirmation slip
        $this->post('/layanan/buku-tamu', [
            'visitor_name' => '=cmd|calc!A0 Budi Tamu',
            'visitor_organization' => 'Komite Madrasah',
            'visitor_phone' => '081900001111',
            'category' => 'tamu_dinas',
            'purpose' => 'Koordinasi rapat komite',
            'host_teacher_id' => $teacher->id,
        ])->assertRedirect('/layanan/buku-tamu');

        $this->assertDatabaseHas('guestbook_visits', [
            'school_id' => $this->schoolA->id,
            'visitor_name' => '=cmd|calc!A0 Budi Tamu',
            'host_name' => 'Drs. Hasan Basri',
            'status' => 'checked_in',
        ]);

        // Public GET /layanan/buku-tamu never exposes guest list or other visitors' data
        $this->get('/layanan/buku-tamu')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/Services/Guestbook')
                ->missing('visits')
                ->where('hosts.0.name', 'Drs. Hasan Basri')
                ->missing('hosts.0.nip')
            );

        // Unauthorized teacher cannot access internal guestbook
        $this->actingAs($this->teacherA)
            ->get('/administrasi/buku-tamu')
            ->assertForbidden();

        // Receptionist can view guestbook, cannot access PTSP or students
        $this->actingAs($this->receptionistA)
            ->get('/administrasi/buku-tamu')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Administration/Guestbook/Index')
                ->has('visits', 1)
            );
        $this->actingAs($this->receptionistA)->get('/administrasi/ptsp')->assertForbidden();
        $this->actingAs($this->receptionistA)->get('/akademik/siswa')->assertForbidden();

        /** @var GuestbookVisit $visit */
        $visit = GuestbookVisit::query()->firstOrFail();

        // Receptionist checks out visitor
        $this->actingAs($this->receptionistA)
            ->post("/administrasi/buku-tamu/{$visit->id}/pulang", [
                'notes' => 'Selesai bertemu pukul 11.00.',
            ])->assertRedirect();

        $this->assertSame('checked_out', $visit->fresh()->status);
        $this->assertNotNull($visit->fresh()->checked_out_at);

        // CSV export escapes formula injection
        $csvResponse = $this->actingAs($this->receptionistA)->get('/administrasi/buku-tamu/ekspor');
        $csvResponse->assertOk();
        $this->assertStringContainsString("'=cmd|calc!A0 Budi Tamu", (string) $csvResponse->getContent());
    }

    public function test_ptsp_draft_attachment_removal_officer_rejection_and_revoked_ticket_tracking(): void
    {
        $service = PtspService::query()->create([
            'school_id' => $this->schoolA->id,
            'code' => 'SK-UMUM',
            'name' => 'Surat Keterangan Umum',
            'category' => 'umum',
            'required_documents' => [
                ['key' => 'pengantar', 'label' => 'Surat Pengantar', 'required' => true],
            ],
            'estimated_duration' => '1 Hari Kerja',
            'delivery_mode' => 'digital',
            'is_active' => true,
            'is_published' => true,
        ]);

        // Guest saves draft in session, attaches file, removes it, then re-attaches & submits
        $file1 = UploadedFile::fake()->create('pengantar-1.pdf', 80, 'application/pdf');
        $this->post('/layanan/ptsp/draf', [
            'ptsp_service_id' => $service->id,
            'applicant_type' => 'general',
            'applicant_name' => 'Pemohon Tamu',
            'purpose' => 'Keperluan administrasi umum',
            'attachments' => ['pengantar' => $file1],
        ])->assertRedirect();

        /** @var PtspRequest $draft */
        $draft = PtspRequest::query()->firstOrFail();
        $docId = (int) $draft->attachment_document_ids[0]['document_id'];

        $this->delete("/layanan/ptsp/draf/{$draft->id}/lampiran/{$docId}")->assertRedirect();
        $this->assertCount(0, $draft->fresh()->attachment_document_ids);

        $file2 = UploadedFile::fake()->create('pengantar-2.pdf', 85, 'application/pdf');
        $this->post('/layanan/ptsp/ajukan', [
            'request_id' => $draft->id,
            'applicant_type' => 'general',
            'applicant_name' => 'Pemohon Tamu',
            'purpose' => 'Keperluan administrasi umum',
            'verification_secret' => 'TAMU88',
            'agreement_accepted' => true,
            'attachments' => ['pengantar' => $file2],
        ])->assertRedirect();

        $draft->refresh();
        /** @var ServiceTicket $ticket */
        $ticket = ServiceTicket::query()->findOrFail($draft->service_ticket_id);

        // Staff queue filter finds ticket by code
        $this->actingAs($this->ptspOfficerA)
            ->get('/administrasi/ptsp?search='.$ticket->ticket_code.'&status=submitted')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Administration/Ptsp/Index')
                ->has('requests', 1)
                ->where('requests.0.ticket_code', $ticket->ticket_code)
            );

        // Officer rejects with mandatory reason
        $this->actingAs($this->ptspOfficerA)
            ->post("/administrasi/ptsp/permohonan/{$draft->id}/tolak", [
                'reason' => 'Surat pengantar tidak sesuai format instansi.',
                'expected_version' => $ticket->version,
            ])->assertRedirect();

        $this->assertSame('rejected', $draft->fresh()->status);

        // Revoked ticket on unified tracking reports cancelled without exposing documents
        $ticket->forceFill(['revoked_at' => now()])->save();
        auth()->logout();

        $this->post('/lacak', [
            'code' => $ticket->ticket_code,
            'category' => 'ptsp',
            'verification_secret' => 'TAMU88',
        ])->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('result.authorized', true)
            ->where('result.ticket.public_status', 'cancelled')
            ->where('result.ticket.documents', [])
        );
    }

    public function test_guestbook_photo_is_private_and_restricted_to_authorized_school_staff(): void
    {
        $payload = [
            'visitor_name' => 'Tamu Contoh Foto',
            'visitor_phone' => '0800000000',
            'category' => 'tamu_dinas',
            'purpose' => 'Kunjungan contoh',
            'photo' => UploadedFile::fake()->image('foto.jpg', 640, 480),
        ];

        $this->post('/layanan/buku-tamu', $payload)->assertRedirect('/layanan/buku-tamu');
        $visit = GuestbookVisit::query()->where('visitor_name', 'Tamu Contoh Foto')->sole();
        $photos = Storage::disk('local')->files("schools/{$this->schoolA->id}/guestbook/{$visit->id}");
        $this->assertCount(1, $photos);
        Storage::disk('public')->assertMissing($photos[0]);

        $url = "/administrasi/buku-tamu/{$visit->id}/foto";
        $this->get($url)->assertRedirect('/login');
        $this->actingAs($this->teacherA)->get($url)->assertForbidden();
        $this->actingAs($this->operatorB)->get($url)->assertNotFound();
        $this->actingAs($this->receptionistA)->get($url)
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff');

        $this->actingAs($this->receptionistA)->get('/administrasi/buku-tamu')
            ->assertInertia(fn (Assert $page) => $page
                ->where('visits.0.photo_url', route('administration.guestbook.admin.photo', $visit->id)));
    }

    public function test_guestbook_rejects_invalid_photo_before_recording_the_visit(): void
    {
        $this->post('/layanan/buku-tamu', [
            'visitor_name' => 'Foto Tidak Valid',
            'visitor_phone' => '0800000000',
            'category' => 'tamu_dinas',
            'purpose' => 'Kunjungan contoh',
            'photo' => UploadedFile::fake()->create('foto.jpg', 20, 'text/plain'),
        ])->assertSessionHasErrors('photo');

        $this->assertDatabaseMissing('guestbook_visits', ['visitor_name' => 'Foto Tidak Valid']);
    }

    private function createUser(School $school, int $roleId, string $email, string $username): User
    {
        return User::query()->create([
            'school_id' => $school->id,
            'role_id' => $roleId,
            'name' => $username,
            'username' => $username,
            'email' => $email,
            'password' => 'password1234',
            'is_active' => true,
        ]);
    }
}
