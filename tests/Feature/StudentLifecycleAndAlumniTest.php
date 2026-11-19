<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Academic\Infrastructure\Models\AcademicYear;
use App\Modules\Academic\Infrastructure\Models\AlumniProfile;
use App\Modules\Academic\Infrastructure\Models\ClassGroup;
use App\Modules\Academic\Infrastructure\Models\Enrollment;
use App\Modules\Academic\Infrastructure\Models\GraduationBatch;
use App\Modules\Academic\Infrastructure\Models\GraduationRecord;
use App\Modules\Academic\Infrastructure\Models\Student;
use App\Modules\Academic\Infrastructure\Models\StudentGuardian;
use App\Modules\Academic\Infrastructure\Models\StudentMutation;
use App\Modules\Academic\Infrastructure\Models\Term;
use App\Modules\Administration\Infrastructure\Models\LegalizationRequest;
use App\Modules\Admissions\Infrastructure\Models\AdmissionApplicant;
use App\Modules\Admissions\Infrastructure\Models\AdmissionDocumentRequirement;
use App\Modules\Admissions\Infrastructure\Models\AdmissionPeriod;
use App\Modules\Admissions\Infrastructure\Models\AdmissionTrack;
use App\Modules\Core\Application\Services\RolePermissionRegistryService;
use App\Modules\Core\Domain\RoleEnum;
use App\Modules\Core\Infrastructure\Models\School;
use App\Modules\Core\Infrastructure\Models\ServiceDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class StudentLifecycleAndAlumniTest extends TestCase
{
    use RefreshDatabase;

    private School $schoolA;

    private Term $previousTerm;

    private Term $activeTerm;

    private AcademicYear $yearA;

    private ClassGroup $classX;

    private Student $studentA1;

    private Student $studentA2;

    private User $adminA;

    private User $operatorA;

    private User $parentUser;

    private User $teacherUser;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $roles = app(RolePermissionRegistryService::class)->syncSystemRolesAndPermissions();

        $this->schoolA = School::query()->create([
            'name' => 'MAN Contoh Lifecycle A',
            'npsn' => '30307701',
            'level' => 'MA',
            'timezone' => 'Asia/Makassar',
            'status' => 'active',
        ]);

        $this->adminA = $this->makeUser($this->schoolA, $roles[RoleEnum::ADMIN->value]->id, 'admin.lif@test.sch.id', 'admin_lif');
        $this->operatorA = $this->makeUser($this->schoolA, $roles[RoleEnum::OPERATOR->value]->id, 'op.lif@test.sch.id', 'op_lif');
        $this->parentUser = $this->makeUser($this->schoolA, $roles[RoleEnum::PARENT->value]->id, 'parent.lif@test.sch.id', 'parent_lif');
        $this->teacherUser = $this->makeUser($this->schoolA, $roles[RoleEnum::TEACHER->value]->id, 'guru.lif@test.sch.id', 'guru_lif');

        $prevYear = AcademicYear::query()->create([
            'school_id' => $this->schoolA->id,
            'name' => '2025/2026',
            'start_date' => '2025-07-01',
            'end_date' => '2026-06-30',
            'is_active' => false,
        ]);
        $this->previousTerm = Term::query()->create([
            'academic_year_id' => $prevYear->id,
            'name' => 'Semester Genap 2025/2026',
            'semester' => 2,
            'start_date' => '2026-01-02',
            'end_date' => '2026-06-30',
            'is_active' => false,
        ]);

        $this->yearA = AcademicYear::query()->create([
            'school_id' => $this->schoolA->id,
            'name' => '2026/2027',
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
            'is_active' => true,
        ]);
        $this->activeTerm = Term::query()->create([
            'academic_year_id' => $this->yearA->id,
            'name' => 'Semester Ganjil 2026/2027',
            'semester' => 1,
            'start_date' => '2026-07-01',
            'end_date' => '2026-12-31',
            'is_active' => true,
        ]);

        $prevClass = ClassGroup::query()->create([
            'school_id' => $this->schoolA->id,
            'academic_year_id' => $prevYear->id,
            'grade_level' => 10,
            'name' => 'X-1-2025',
            'capacity' => 36,
        ]);
        $this->classX = ClassGroup::query()->create([
            'school_id' => $this->schoolA->id,
            'academic_year_id' => $this->yearA->id,
            'grade_level' => 11,
            'name' => 'XI-IPA-1',
            'capacity' => 36,
        ]);

        $this->studentA1 = Student::query()->create([
            'school_id' => $this->schoolA->id,
            'nis' => '261001',
            'nisn' => '0089001101',
            'name' => 'Zahra Amalia',
            'gender' => 'P',
            'status' => 'active',
        ]);
        $this->studentA2 = Student::query()->create([
            'school_id' => $this->schoolA->id,
            'nis' => '261002',
            'nisn' => '0089001102',
            'name' => 'Fajar Ramadhan',
            'gender' => 'L',
            'status' => 'active',
        ]);

        Enrollment::query()->create([
            'student_id' => $this->studentA1->id,
            'class_group_id' => $prevClass->id,
            'term_id' => $this->previousTerm->id,
            'status' => 'promoted',
            'roll_number' => 1,
        ]);
        Enrollment::query()->create([
            'student_id' => $this->studentA1->id,
            'class_group_id' => $this->classX->id,
            'term_id' => $this->activeTerm->id,
            'status' => 'active',
            'roll_number' => 1,
        ]);
        Enrollment::query()->create([
            'student_id' => $this->studentA2->id,
            'class_group_id' => $this->classX->id,
            'term_id' => $this->activeTerm->id,
            'status' => 'active',
            'roll_number' => 2,
        ]);

        StudentGuardian::query()->create([
            'school_id' => $this->schoolA->id,
            'student_id' => $this->studentA1->id,
            'user_id' => $this->parentUser->id,
            'guardian_name' => 'Ibu Zahra',
            'relationship' => 'Ibu',
            'phone' => '081234560011',
            'is_primary' => true,
            'is_verified' => true,
            'verified_at' => now(),
        ]);
    }

    public function test_ppdb_candidate_registration_revision_release_gate_and_idempotent_conversion(): void
    {
        $period = AdmissionPeriod::query()->create([
            'school_id' => $this->schoolA->id,
            'name' => 'PPDB 2027/2028',
            'academic_year' => '2027/2028',
            'level' => 'MA',
            'status' => 'draft',
            'registration_start' => now()->subDay()->toDateString(),
            'registration_end' => now()->addDays(10)->toDateString(),
            'announcement_at' => now()->addDays(20)->toDateString(),
            're_registration_start' => now()->addDays(20)->toDateString(),
            're_registration_end' => now()->addDays(25)->toDateString(),
        ]);

        $track = AdmissionTrack::query()->create([
            'period_id' => $period->id,
            'code' => 'REG',
            'name' => 'Jalur Reguler',
            'quota' => 2,
            'is_active' => true,
        ]);

        $req = AdmissionDocumentRequirement::query()->create([
            'track_id' => $track->id,
            'code' => 'KK',
            'label' => 'Kartu Keluarga',
            'is_required' => true,
            'sort_order' => 1,
        ]);

        // Before period opens -> rejected
        $this->post('/ppdb/daftar', [
            'period_id' => $period->id,
            'track_id' => $track->id,
            'name' => 'Calon Sebelum Buka',
            'gender' => 'L',
            'documents' => [$req->id => UploadedFile::fake()->create('kk.pdf', 32, 'application/pdf')],
        ])->assertSessionHasErrors('period_id');

        // Open period
        $period->update(['status' => 'registration_open']);

        // Missing required document -> rejected
        $this->post('/ppdb/daftar', [
            'period_id' => $period->id,
            'track_id' => $track->id,
            'name' => 'Nadia Putri',
            'nisn' => '0099112233',
            'gender' => 'P',
        ])->assertSessionHasErrors("documents.{$req->id}");

        // Valid candidate self-registration with idempotency
        $this->post('/ppdb/daftar', [
            'period_id' => $period->id,
            'track_id' => $track->id,
            'name' => 'Nadia Putri',
            'nisn' => '0099112233',
            'gender' => 'P',
            'birth_place' => 'Banjar',
            'birth_date' => '2011-05-12',
            'parent_name' => 'Hendra',
            'parent_phone' => '081234569999',
            'previous_school' => 'MTsN Contoh',
            'idempotency_key' => 'ppdb-nadia-001',
            'documents' => [$req->id => UploadedFile::fake()->create('kk.pdf', 32, 'application/pdf')],
        ])->assertRedirect('/ppdb');

        $receipt = session('issued_ppdb_receipt');
        $this->assertNotEmpty($receipt['verification_secret']);

        // Idempotent retry creates no duplicate applicant
        $this->post('/ppdb/daftar', [
            'period_id' => $period->id,
            'track_id' => $track->id,
            'name' => 'Nadia Putri',
            'nisn' => '0099112233',
            'gender' => 'P',
            'idempotency_key' => 'ppdb-nadia-001',
            'documents' => [$req->id => UploadedFile::fake()->create('kk.pdf', 32, 'application/pdf')],
        ])->assertRedirect('/ppdb');

        $this->assertSame(1, AdmissionApplicant::query()->count());
        $applicant = AdmissionApplicant::query()->firstOrFail();

        // Proof PDF downloadable by candidate session
        $this->get("/ppdb/bukti/{$applicant->id}/pdf")
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        // Panitia returns candidate for revision -> candidate resubmits
        $this->actingAs($this->operatorA)
            ->post("/spmb/pendaftar/{$applicant->id}/revisi", ['revision_notes' => 'Scan KK buram'])
            ->assertRedirect();

        $this->assertSame('needs_revision', $applicant->fresh()->status);

        $this->post("/ppdb/perbaiki/{$applicant->id}", [
            'name' => 'Nadia Putri',
            'previous_school' => 'MTsN 1 Contoh',
            'documents' => [$req->id => UploadedFile::fake()->create('kk-jelas.pdf', 40, 'application/pdf')],
        ])->assertRedirect();
        $this->assertSame('registered', $applicant->fresh()->status);

        // Panitia finalizes accepted while period is NOT yet announcement -> lookup MUST NOT reveal decision
        $this->actingAs($this->operatorA)
            ->put("/spmb/pendaftar/{$applicant->id}/keputusan", [
                'decision' => 'accepted',
                'override_reason' => 'Memenuhi syarat jalur reguler',
            ])
            ->assertRedirect();

        auth()->logout();
        $this->post('/ppdb/cek-hasil', [
            'registration_no' => $applicant->registration_no,
            'pin' => (string) $receipt['verification_secret'],
        ])->assertRedirect('/ppdb');

        $beforeRelease = session('ppdb_lookup_result');
        $this->assertFalse($beforeRelease['applicant']['is_result_released']);
        $this->assertNull($beforeRelease['applicant']['selection_decision']);

        // Release announcement & open re-registration window
        $period->update([
            'status' => 're_registration',
            'announcement_at' => now()->subDay()->toDateString(),
            're_registration_start' => now()->subDay()->toDateString(),
            're_registration_end' => now()->addDays(5)->toDateString(),
        ]);

        $this->post('/ppdb/cek-hasil', [
            'registration_no' => $applicant->registration_no,
            'pin' => (string) $receipt['verification_secret'],
        ])->assertRedirect('/ppdb');

        $afterRelease = session('ppdb_lookup_result');
        $this->assertTrue($afterRelease['applicant']['is_result_released']);
        $this->assertSame('accepted', $afterRelease['applicant']['selection_decision']);
        $this->assertTrue($afterRelease['applicant']['can_re_register']);

        // Candidate submits re-registration intent, panitia confirms conversion idempotently
        $this->post("/ppdb/daftar-ulang/{$applicant->id}", ['notes' => 'Siap daftar ulang'])
            ->assertRedirect();

        $this->actingAs($this->operatorA)
            ->post("/spmb/pendaftar/{$applicant->id}/daftar-ulang", ['nis' => '270001'])
            ->assertRedirect();

        $this->assertDatabaseHas('students', ['school_id' => $this->schoolA->id, 'nis' => '270001', 'name' => 'Nadia Putri']);
        $this->assertSame(1, Student::query()->where('school_id', $this->schoolA->id)->where('nis', '270001')->count());
    }

    public function test_mutation_incoming_outgoing_preserves_history_and_supports_revocation(): void
    {
        // Parent cannot submit outgoing mutation for unlinked student A2
        $this->actingAs($this->parentUser)
            ->post('/layanan/mutasi', [
                'direction' => 'outgoing',
                'student_id' => $this->studentA2->id,
                'destination_school' => 'MAN 2 Kota',
                'reason' => 'Ikut orang tua',
            ])
            ->assertSessionHasErrors('student_id');

        // Parent submits outgoing mutation for Zahra (A1)
        $this->actingAs($this->parentUser)
            ->post('/layanan/mutasi', [
                'direction' => 'outgoing',
                'student_id' => $this->studentA1->id,
                'destination_school' => 'MAN 2 Contoh',
                'reason' => 'Kepindahan tugas orang tua',
                'attachments' => [UploadedFile::fake()->create('surat-pindah.pdf', 32, 'application/pdf')],
            ])
            ->assertRedirect('/layanan/mutasi');

        $outgoing = StudentMutation::query()->where('student_id', $this->studentA1->id)->firstOrFail();

        // Operator approves outgoing mutation -> active enrollment becomes transferred, prior term promoted enrollment remains untouched
        $this->actingAs($this->operatorA)
            ->post("/akademik/mutasi/{$outgoing->id}/putuskan", [
                'decision' => 'approve',
                'decision_note' => 'Disetujui kepala sekolah',
            ])
            ->assertRedirect();

        $outgoing->refresh();
        $this->assertSame('approved', $outgoing->status);
        $this->assertSame('transferred', $this->studentA1->fresh()->status);

        $this->assertDatabaseHas('enrollments', [
            'student_id' => $this->studentA1->id,
            'term_id' => $this->previousTerm->id,
            'status' => 'promoted',
        ]);
        $this->assertDatabaseHas('enrollments', [
            'student_id' => $this->studentA1->id,
            'term_id' => $this->activeTerm->id,
            'status' => 'transferred',
        ]);

        // Download PDF recommendation letter
        $this->actingAs($this->parentUser)
            ->get("/layanan/mutasi/{$outgoing->id}/surat.pdf")
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $letterDoc = ServiceDocument::query()->findOrFail($outgoing->official_letter_document_id);

        // Revoking outgoing mutation restores student & marks verification token revoked
        $this->actingAs($this->operatorA)
            ->post("/akademik/mutasi/{$outgoing->id}/cabut", ['reason' => 'Batal pindah'])
            ->assertRedirect();

        $this->assertSame('active', $this->studentA1->fresh()->status);
        $this->post('/verifikasi', ['mode' => 'document', 'code' => $letterDoc->verification_token])
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('documentResult.status', 'revoked'));

        // Incoming mutation approved creates new active Student & Enrollment
        $this->post('/layanan/mutasi', [
            'direction' => 'incoming',
            'student_name' => 'Rizky Pindahan',
            'nisn' => '0089008899',
            'gender' => 'L',
            'origin_school' => 'MA Swasta Harapan',
            'target_class_group_id' => $this->classX->id,
            'reason' => 'Pindah domisili',
        ])->assertRedirect('/layanan/mutasi');

        $incoming = StudentMutation::query()->where('direction', 'incoming')->firstOrFail();
        $this->actingAs($this->operatorA)
            ->post("/akademik/mutasi/{$incoming->id}/putuskan", [
                'decision' => 'approve',
                'nis' => '261099',
                'target_class_group_id' => $this->classX->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('students', ['school_id' => $this->schoolA->id, 'nis' => '261099', 'status' => 'active']);
    }

    public function test_graduation_release_gate_skl_reissue_and_public_verification(): void
    {
        $this->actingAs($this->operatorA)
            ->post('/akademik/kelulusan/batch', [
                'academic_year_id' => $this->yearA->id,
                'title' => 'Kelulusan Angkatan 2027',
                'skl_prefix' => 'SKL',
                'release_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
            ])
            ->assertRedirect();

        $batch = GraduationBatch::query()->firstOrFail();

        $this->actingAs($this->operatorA)
            ->post("/akademik/kelulusan/batch/{$batch->id}/siswa", [
                'student_id' => $this->studentA2->id,
                'decision' => 'graduated',
                'average_score' => 89.5,
                'access_pin' => '554433',
            ])
            ->assertRedirect();

        $record = GraduationRecord::query()->firstOrFail();

        // Publish batch for future release date -> before release_at, portal MUST hide verdict & forbid SKL download
        $this->actingAs($this->operatorA)
            ->post("/akademik/kelulusan/batch/{$batch->id}/terbitkan", [
                'signatory_name' => 'Drs. H. Ahmad, M.Pd',
                'signatory_title' => 'Kepala Madrasah',
                'release_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
            ])
            ->assertRedirect();

        auth()->logout();
        $this->post('/layanan/kelulusan', ['nisn' => $this->studentA2->nisn, 'pin' => '554433'])
            ->assertRedirect('/layanan/kelulusan');
        $this->assertFalse(session('graduation_lookup_result')['released']);

        $this->get("/layanan/kelulusan/{$record->id}/skl.pdf")->assertForbidden();

        $oldDoc = ServiceDocument::query()->findOrFail($record->fresh()->skl_document_id);
        foreach ([$oldDoc->verification_token, $oldDoc->document_number] as $code) {
            $this->post('/verifikasi', ['mode' => 'document', 'code' => $code])
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->where('documentResult.status', 'not_found')
                    ->missing('documentResult.document'));
        }
        $this->assertSame('active', $this->studentA2->fresh()->status);

        // Advance release time into the past -> valid PIN unlocks verdict & SKL PDF download
        $batch->update(['release_at' => now()->subMinute()]);

        $this->artisan('school:release-graduations')->assertSuccessful();
        $this->artisan('school:release-graduations')->assertSuccessful();
        $this->assertSame('graduated', $this->studentA2->fresh()->status);
        $this->assertDatabaseHas('enrollments', [
            'id' => $record->enrollment_id,
            'status' => 'graduated',
        ]);
        $this->assertSame(1, DB::table('student_lifecycle_events')
            ->where('student_id', $this->studentA2->id)
            ->where('action', 'graduated')
            ->count());

        // Wrong PIN rejected
        $this->post('/layanan/kelulusan', ['nisn' => $this->studentA2->nisn, 'pin' => '000000'])
            ->assertRedirect('/layanan/kelulusan');
        $this->assertFalse(session('graduation_lookup_result')['found']);

        // Correct PIN succeeds
        $this->post('/layanan/kelulusan', ['nisn' => $this->studentA2->nisn, 'pin' => '554433'])
            ->assertRedirect('/layanan/kelulusan');
        $this->assertTrue(session('graduation_lookup_result')['released']);
        $this->assertSame('graduated', session('graduation_lookup_result')['record']['decision']);

        $this->get("/layanan/kelulusan/{$record->id}/skl.pdf")
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        // Public verification confirms old SKL token is valid before reissue
        $this->post('/verifikasi', ['mode' => 'document', 'code' => $oldDoc->verification_token])
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('documentResult.status', 'valid')
                ->where('documentResult.document.holder_name', 'Fajar Ramadhan')
            );

        // Student status verification returns minimal public info
        $this->post('/verifikasi', ['mode' => 'student', 'nisn' => $this->studentA1->nisn, 'name' => 'Zahra'])
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('studentResult.found', true)
                ->where('studentResult.status_code', 'active')
            );

        // Reissue SKL revokes old SKL & creates new valid token
        $this->actingAs($this->operatorA)
            ->post("/akademik/kelulusan/skl/{$record->id}/terbitkan-ulang", ['reason' => 'Perbaikan nilai transkrip'])
            ->assertRedirect();

        $newDoc = ServiceDocument::query()->findOrFail($record->fresh()->skl_document_id);
        $this->assertNotSame($oldDoc->id, $newDoc->id);

        $this->post('/verifikasi', ['mode' => 'document', 'code' => $oldDoc->verification_token])
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('documentResult.status', 'revoked'));

        $this->post('/verifikasi', ['mode' => 'document', 'code' => $newDoc->verification_token])
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('documentResult.status', 'valid'));

        // Record original physical pickup
        $this->actingAs($this->operatorA)
            ->post("/akademik/kelulusan/skl/{$record->id}/ambil-asli", ['collected_by' => 'Fajar Ramadhan'])
            ->assertRedirect();

        $this->assertNotNull($record->fresh()->original_collected_at);
    }

    public function test_legalization_power_of_attorney_sptjm_and_alumni_tracer_moderation_flow(): void
    {
        // Alumni submits legalization request with proxy pickup
        $this->post('/layanan/legalisir', [
            'applicant_name' => 'Alumni Teladan',
            'applicant_nisn' => '0055667788',
            'graduation_year' => 2024,
            'phone' => '081234567777',
            'document_type' => 'ijazah',
            'certificate_number' => 'DN-Ma/123456',
            'copy_count' => 5,
            'purpose' => 'Melamar CPNS',
            'delivery_method' => 'pickup_proxy',
            'proxy_name' => 'Siti Saudari',
            'proxy_nik' => '6303010101904321',
            'proxy_relation' => 'Kakak Kandung',
            'signature_data' => 'data:text/plain;base64,VFREX0FMVU1OSQ==',
        ])->assertRedirect('/layanan/legalisir');

        $legalization = LegalizationRequest::query()->firstOrFail();

        // Alumni generates linked Surat Kuasa & SPTJM PDFs
        $this->post('/layanan/surat-kuasa', [
            'template_type' => 'power_of_attorney',
            'grantor_name' => 'Alumni Teladan',
            'grantor_nisn' => '0055667788',
            'grantor_nik' => '6303010101909876',
            'grantor_graduation_year' => 2024,
            'grantor_phone' => '081234567777',
            'proxy_name' => 'Siti Saudari',
            'proxy_nik' => '6303010101904321',
            'proxy_relation' => 'Kakak Kandung',
            'purpose' => 'Pengambilan legalisir ijazah',
            'legalization_code' => $legalization->legalization_code,
            'grantor_signature_data' => 'data:text/plain;base64,VFREMQ==',
            'proxy_signature_data' => 'data:text/plain;base64,VFREMg==',
        ])->assertRedirect('/layanan/surat-kuasa');

        $poaDocId = (int) session('generated_auth_document')['document_id'];
        $this->get("/layanan/legalisir/dokumen/{$poaDocId}")
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
        $this->assertSame($poaDocId, $legalization->fresh()->power_of_attorney_document_id);

        // Teacher forbidden from TU legalization admin
        $this->actingAs($this->teacherUser)->get('/administrasi/legalisir')->assertForbidden();

        // Operator verifies original, approves stamp, and records pickup
        $this->actingAs($this->operatorA)
            ->post("/administrasi/legalisir/{$legalization->id}/verifikasi-asli", ['note' => 'Ijazah asli sesuai'])
            ->assertRedirect();
        $this->actingAs($this->operatorA)
            ->post("/administrasi/legalisir/{$legalization->id}/sahkan")
            ->assertRedirect();
        $this->actingAs($this->operatorA)
            ->post("/administrasi/legalisir/{$legalization->id}/ambil", [
                'picked_up_by_name' => 'Siti Saudari',
                'relation' => 'Penerima Kuasa',
            ])
            ->assertRedirect();

        $this->assertSame('completed', $legalization->fresh()->status);

        // Alumni Tracer & Directory Privacy + Moderation
        $this->post('/alumni', ['full_name' => 'Unauthorized', 'graduation_year' => 2024, 'current_status' => 'studying'])->assertForbidden();

        // Register external alumni account
        $this->post('/layanan/ptsp/akun/daftar', [
            'applicant_type' => 'alumni',
            'full_name' => 'Alumni Teladan',
            'email' => 'alumni.teladan@example.test',
            'phone' => '081299990001',
            'password' => 'rahasia1234',
            'password_confirmation' => 'rahasia1234',
        ])->assertRedirect('/layanan/ptsp');

        // Alumni updates tracer with opt_in_directory = true, opt_in_career = true, opt_in_contact = false
        $this->post('/alumni', [
            'full_name' => 'Alumni Teladan',
            'graduation_year' => 2024,
            'major_or_class' => 'XII IPA 1',
            'current_status' => 'studying',
            'institution_or_company' => 'Universitas Lambung Mangkurat',
            'position_or_program' => 'Kedokteran',
            'scholarship_info' => 'Beasiswa KIP-K',
            'phone' => '081299990001',
            'email' => 'alumni.teladan@example.test',
            'opt_in_directory' => true,
            'opt_in_career' => true,
            'opt_in_contact' => false,
            'opt_in_testimonial' => false,
        ])->assertRedirect();

        $alumniProfile = AlumniProfile::query()->firstOrFail();

        // Before moderation approval, alumni MUST NOT appear in public directory
        $this->get('/alumni/direktori')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('alumni', 0));

        // Teacher forbidden from moderating alumni
        $this->actingAs($this->teacherUser)->get('/akademik/alumni')->assertForbidden();

        // Admin approves moderation & issues E-KTA
        $this->actingAs($this->adminA)
            ->post("/akademik/alumni/{$alumniProfile->id}/moderasi", [
                'moderation_status' => 'approved',
                'issue_ekta' => true,
            ])
            ->assertRedirect();

        $alumniProfile->refresh();
        $this->assertNotNull($alumniProfile->ekta_document_id);

        // Public directory shows opted-in career fields but hides contact email/phone
        $this->get('/alumni/direktori')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('alumni', 1)
                ->where('alumni.0.full_name', 'Alumni Teladan')
                ->where('alumni.0.institution_or_company', 'Universitas Lambung Mangkurat')
                ->where('alumni.0.contact_email', null)
            );
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
