<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Academic\Infrastructure\Models\AcademicYear;
use App\Modules\Academic\Infrastructure\Models\ClassGroup;
use App\Modules\Academic\Infrastructure\Models\Enrollment;
use App\Modules\Academic\Infrastructure\Models\Student;
use App\Modules\Academic\Infrastructure\Models\StudentCard;
use App\Modules\Academic\Infrastructure\Models\Term;
use App\Modules\Core\Application\Services\RolePermissionRegistryService;
use App\Modules\Core\Domain\RoleEnum;
use App\Modules\Core\Infrastructure\Models\School;
use App\Modules\Core\Infrastructure\Models\ServiceDocument;
use App\Modules\Homeroom\Infrastructure\Models\ComplaintReport;
use App\Modules\Library\Infrastructure\Models\LibraryClearanceCertificate;
use App\Modules\Library\Infrastructure\Models\LibraryCollection;
use App\Modules\Library\Infrastructure\Models\LibraryLoan;
use App\Modules\Library\Infrastructure\Models\LibraryProcurementRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ComplaintsAndLibraryTest extends TestCase
{
    use RefreshDatabase;

    private School $schoolA;

    private Student $studentA1;

    private User $taskForceUser;

    private User $librarianUser;

    private User $teacherUser;

    private User $studentUser;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $roles = app(RolePermissionRegistryService::class)->syncSystemRolesAndPermissions();

        $this->schoolA = School::query()->create([
            'name' => 'MAN Contoh Serv A',
            'npsn' => '30306601',
            'level' => 'MA',
            'timezone' => 'Asia/Makassar',
            'status' => 'active',
        ]);

        $this->taskForceUser = $this->makeUser($this->schoolA, $roles[RoleEnum::TASK_FORCE->value]->id, 'satgas@test.sch.id', 'satgas_a');
        $this->librarianUser = $this->makeUser($this->schoolA, $roles[RoleEnum::LIBRARIAN->value]->id, 'pustaka@test.sch.id', 'pustaka_a');
        $this->teacherUser = $this->makeUser($this->schoolA, $roles[RoleEnum::TEACHER->value]->id, 'guru.serv@test.sch.id', 'guru_serv');
        $this->studentUser = $this->makeUser($this->schoolA, $roles[RoleEnum::STUDENT->value]->id, 'siswa.serv@test.sch.id', 'siswa_serv');

        $year = AcademicYear::query()->create([
            'school_id' => $this->schoolA->id,
            'name' => '2026/2027',
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
            'is_active' => true,
        ]);
        $term = Term::query()->create([
            'academic_year_id' => $year->id,
            'name' => 'Semester Ganjil',
            'semester' => 1,
            'start_date' => '2026-07-01',
            'end_date' => '2026-12-31',
            'is_active' => true,
        ]);
        $class = ClassGroup::query()->create([
            'school_id' => $this->schoolA->id,
            'academic_year_id' => $year->id,
            'grade_level' => 10,
            'name' => 'X-A',
            'capacity' => 36,
        ]);

        $this->studentA1 = Student::query()->create([
            'school_id' => $this->schoolA->id,
            'user_id' => $this->studentUser->id,
            'nis' => '262001',
            'nisn' => '0090001101',
            'name' => 'Ahmad Rifai',
            'gender' => 'L',
            'status' => 'active',
        ]);

        Enrollment::query()->create([
            'student_id' => $this->studentA1->id,
            'class_group_id' => $class->id,
            'term_id' => $term->id,
            'status' => 'active',
            'roll_number' => 1,
        ]);
    }

    public function test_siap_lapor_anonymous_and_named_reports_satgas_handling_and_transparency(): void
    {
        // Logged-in student submits ANONYMOUS bullying report -> NO reporter identity may be persisted
        $this->actingAs($this->studentUser)
            ->post('/layanan/lapor', [
                'category' => 'perundungan',
                'is_anonymous' => true,
                'reporter_name' => 'Ahmad Rifai Rahasia',
                'reporter_contact' => '081200009999',
                'title' => 'Laporan Intimidasi Jam Istirahat',
                'description' => 'Terjadi intimidasi di koridor belakang lantai 2.',
                'incident_date' => '2026-11-19',
                'location' => 'Koridor Lantai 2',
                'idempotency_key' => 'adu-anon-001',
                'evidence_files' => [UploadedFile::fake()->create('bukti-koridor.pdf', 32, 'application/pdf')],
            ])
            ->assertRedirect('/layanan/lapor');

        $anonReceipt = session('issued_complaint_receipt');
        $this->assertTrue($anonReceipt['is_anonymous']);

        $report = ComplaintReport::query()->firstOrFail();
        $this->assertTrue($report->is_anonymous);
        $this->assertNull($report->reporter_user_id);
        $this->assertNull($report->reporter_name);
        $this->assertNull($report->reporter_contact);
        $docId = (int) $report->evidence_document_ids[0];

        // Non-task-force roles get 403 on Satgas workspace and evidence download
        foreach ([$this->teacherUser, $this->librarianUser, $this->studentUser] as $nonSatgas) {
            $this->actingAs($nonSatgas)->get('/satgas/aduan')->assertForbidden();
            $this->actingAs($nonSatgas)->get("/satgas/aduan/{$report->id}/eviden/{$docId}")->assertForbidden();
        }

        // Task Force handles report, replies, attaches proof, and publishes PII-free summary
        $this->actingAs($this->taskForceUser)
            ->post("/satgas/aduan/{$report->id}/tangani", [
                'status' => 'completed',
                'assigned_to_user_id' => $this->taskForceUser->id,
                'task_force_reply' => 'Tim Satgas telah melakukan pembinaan dan patroli titik rawan.',
                'resolution_summary' => 'Pembinaan selesai dilakukan secara tertutup.',
                'moderated_public_summary' => 'Laporan kategori perundungan telah ditindaklanjuti dengan pembinaan dan pengawasan koridor.',
                'publish_to_transparency' => true,
                'resolution_files' => [UploadedFile::fake()->create('berita-acara.pdf', 24, 'application/pdf')],
            ])
            ->assertRedirect();

        $this->actingAs($this->taskForceUser)
            ->get("/satgas/aduan/{$report->id}/eviden/{$docId}")
            ->assertOk();

        // Public transparency renders aggregate counts + moderated summary WITHOUT reporter PII or raw title
        $this->get('/lapor/transparansi')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('transparency.totals.total', 1)
                ->where('transparency.totals.resolved', 1)
                ->has('transparency.published_summaries', 1)
                ->where('transparency.published_summaries.0.summary', 'Laporan kategori perundungan telah ditindaklanjuti dengan pembinaan dan pengawasan koridor.')
            );

        // Anonymous reporter tracks reply via Unified Tracking with ticket_code + verification_secret
        auth()->logout();
        $this->post('/lacak', [
            'code' => (string) $anonReceipt['ticket_code'],
            'category' => 'aduan',
            'verification_secret' => (string) $anonReceipt['verification_secret'],
        ])->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('result.authorized', true)
            ->where('result.ticket.public_status', 'completed')
        );
    }

    public function test_reassigned_complaint_revokes_the_previous_handler_access_immediately(): void
    {
        $this->post('/layanan/lapor', [
            'category' => 'perundungan', 'is_anonymous' => true,
            'title' => 'Aduan penanganan terbatas', 'description' => 'Laporan yang hanya boleh ditangani petugas terpilih.',
            'idempotency_key' => 'scope-assignment-001',
            'evidence_files' => [UploadedFile::fake()->create('bukti.pdf', 16, 'application/pdf')],
        ])->assertRedirect();
        $report = ComplaintReport::query()->firstOrFail();
        $documentId = $report->evidence_document_ids[0];
        $other = $this->makeUser($this->schoolA, $this->taskForceUser->role_id, 'satgas.other@example.test', 'satgas_other');
        $report->update(['assigned_to_user_id' => $this->taskForceUser->id]);
        $this->actingAs($this->taskForceUser)->get('/satgas/aduan')->assertInertia(fn (Assert $page) => $page->has('reports', 1));
        $this->get('/satgas/aduan/'.$report->id.'/eviden/'.$documentId)->assertOk();
        $report->update(['assigned_to_user_id' => $other->id]);
        $this->get('/satgas/aduan')->assertInertia(fn (Assert $page) => $page->has('reports', 0));
        $this->get('/satgas/aduan/'.$report->id.'/eviden/'.$documentId)->assertForbidden();
        $this->post('/satgas/aduan/'.$report->id.'/tangani', [
            'status' => 'completed', 'resolution_summary' => 'Perubahan yang tidak diizinkan.',
        ])->assertForbidden();
        $this->assertSame('submitted', $report->fresh()->status);
        $this->actingAs($other)->get('/satgas/aduan/'.$report->id.'/eviden/'.$documentId)->assertOk();
    }

    public function test_library_catalog_access_visits_procurement_loans_and_clearance_flow(): void
    {
        // Teacher & student cannot access librarian workspace
        foreach ([$this->teacherUser, $this->studentUser, $this->taskForceUser] as $nonLib) {
            $this->actingAs($nonLib)->get('/perpustakaan/kelola')->assertForbidden();
        }

        // Librarian creates public, members_only, and staff_only collections
        $this->actingAs($this->librarianUser)
            ->post('/perpustakaan/kelola/koleksi', [
                'code' => 'BK-PUB-001',
                'title' => 'Fiqih Sunnah Terpadu',
                'author' => 'Sabiq',
                'category' => 'agama',
                'format' => 'hybrid',
                'total_copies' => 2,
                'access_level' => 'public',
                'ebook_file' => UploadedFile::fake()->create('fiqih-ringkas.pdf', 32, 'application/pdf'),
            ])->assertRedirect();

        $this->actingAs($this->librarianUser)
            ->post('/perpustakaan/kelola/koleksi', [
                'code' => 'BK-MEM-002',
                'title' => 'Modul Olimpiade Matematika',
                'category' => 'referensi',
                'format' => 'ebook',
                'total_copies' => 1,
                'access_level' => 'members_only',
                'ebook_file' => UploadedFile::fake()->create('olimpiade-math.pdf', 32, 'application/pdf'),
            ])->assertRedirect();

        $this->actingAs($this->librarianUser)
            ->post('/perpustakaan/kelola/koleksi', [
                'code' => 'BK-STF-003',
                'title' => 'Panduan Khusus Kurikulum Staf',
                'category' => 'referensi',
                'format' => 'ebook',
                'total_copies' => 1,
                'access_level' => 'staff_only',
                'ebook_file' => UploadedFile::fake()->create('panduan-staf.pdf', 32, 'application/pdf'),
            ])->assertRedirect();

        $pubCol = LibraryCollection::query()->where('code', 'BK-PUB-001')->firstOrFail();
        $memCol = LibraryCollection::query()->where('code', 'BK-MEM-002')->firstOrFail();
        $stfCol = LibraryCollection::query()->where('code', 'BK-STF-003')->firstOrFail();

        // Guest sees ONLY public metadata (1), blocked 403 on members_only/staff_only
        auth()->logout();
        $this->get('/perpustakaan/portal')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('collections', 1)
                ->where('sourceStatus.mode', 'local_fallback')
            );

        $this->get("/perpustakaan/koleksi/{$pubCol->id}/unduh")->assertOk();
        $this->get("/perpustakaan/koleksi/{$memCol->id}/unduh")->assertForbidden();
        $this->get("/perpustakaan/koleksi/{$stfCol->id}/unduh")->assertForbidden();

        // Logged-in student sees public + members_only (2, still NOT staff_only) & can download members_only
        $this->actingAs($this->studentUser)
            ->get('/perpustakaan/portal')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('collections', 2));

        $this->actingAs($this->studentUser)
            ->get("/perpustakaan/koleksi/{$memCol->id}/unduh")
            ->assertOk();
        $this->actingAs($this->studentUser)
            ->get("/perpustakaan/koleksi/{$stfCol->id}/unduh")
            ->assertForbidden();

        // Visit check-in via student card QR token
        StudentCard::query()->create([
            'school_id' => $this->schoolA->id,
            'student_id' => $this->studentA1->id,
            'card_uid' => 'RFID-LIB-01',
            'qr_token' => 'QR-LIB-01',
            'status' => 'active',
            'issued_at' => now(),
        ]);

        $this->post('/perpustakaan/kunjungan', [
            'card_token' => 'QR-LIB-01',
            'purpose' => 'Membaca referensi ujian',
        ])->assertRedirect();
        $this->assertDatabaseHas('library_visits', [
            'school_id' => $this->schoolA->id,
            'student_id' => $this->studentA1->id,
            'check_in_method' => 'qr',
        ]);

        // Procurement proposal & librarian decision
        $this->actingAs($this->studentUser)
            ->post('/perpustakaan/usulan', [
                'title' => 'Pengantar Kecerdasan Buatan',
                'author' => 'Tim Penulis',
                'reason' => 'Referensi informatika kelas XII',
                'estimated_copies' => 3,
            ])->assertRedirect();

        $proc = LibraryProcurementRequest::query()->firstOrFail();
        $this->actingAs($this->librarianUser)
            ->post("/perpustakaan/kelola/usulan/{$proc->id}/putuskan", ['status' => 'approved'])
            ->assertRedirect();
        $this->assertSame('approved', $proc->fresh()->status);

        // Librarian lends physical book -> available_copies decrements 2 -> 1
        $this->actingAs($this->librarianUser)
            ->post('/perpustakaan/kelola/pinjam', [
                'collection_id' => $pubCol->id,
                'student_id' => $this->studentA1->id,
                'due_at' => now()->addDays(7)->toDateString(),
            ])->assertRedirect();

        $this->assertSame(1, $pubCol->fresh()->available_copies);
        $loan = LibraryLoan::query()->firstOrFail();

        // Active loan MUST block Bebas Pustaka issuance
        $this->actingAs($this->studentUser)
            ->post('/perpustakaan/bebas-pustaka', ['student_id' => $this->studentA1->id])
            ->assertRedirect();

        $blocked = LibraryClearanceCertificate::query()->latest('id')->firstOrFail();
        $this->assertSame('blocked_active_loan', $blocked->status);
        $this->assertSame(1, $blocked->active_loan_count);
        $this->assertNull($blocked->clearance_document_id);

        // Librarian returns loan -> available_copies restored 1 -> 2
        $this->actingAs($this->librarianUser)
            ->post("/perpustakaan/kelola/pinjam/{$loan->id}/kembali")
            ->assertRedirect();
        $this->assertSame(2, $pubCol->fresh()->available_copies);

        // Now student passes Bebas Pustaka, gets PDF, and public verification confirms token!
        $this->actingAs($this->studentUser)
            ->post('/perpustakaan/bebas-pustaka', ['student_id' => $this->studentA1->id])
            ->assertRedirect();

        $cleared = LibraryClearanceCertificate::query()->where('status', 'cleared')->firstOrFail();
        $this->actingAs($this->studentUser)
            ->get("/perpustakaan/bebas-pustaka/{$cleared->id}/pdf")
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $doc = ServiceDocument::query()->findOrFail($cleared->clearance_document_id);
        $this->post('/verifikasi', ['mode' => 'document', 'code' => $doc->verification_token])
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('documentResult.status', 'valid'));
    }

    private function makeUser(School $school, int $roleId, string $email, string $username): User
    {
        return User::query()->create([
            'school_id' => $school->id,
            'role_id' => $roleId,
            'name' => $username,
            'username' => $username,
            'email' => $email,
            'password' => 'password123',
            'is_active' => true,
        ]);
    }
}
