<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Academic\Infrastructure\Models\AcademicYear;
use App\Modules\Academic\Infrastructure\Models\AttendanceRecord;
use App\Modules\Academic\Infrastructure\Models\ClassGroup;
use App\Modules\Academic\Infrastructure\Models\Enrollment;
use App\Modules\Academic\Infrastructure\Models\Student;
use App\Modules\Academic\Infrastructure\Models\StudentCard;
use App\Modules\Academic\Infrastructure\Models\StudentGuardian;
use App\Modules\Academic\Infrastructure\Models\Teacher;
use App\Modules\Academic\Infrastructure\Models\Term;
use App\Modules\Core\Application\Services\RolePermissionRegistryService;
use App\Modules\Core\Domain\RoleEnum;
use App\Modules\Core\Infrastructure\Models\School;
use App\Modules\Homeroom\Infrastructure\Models\AttendanceDispensation;
use App\Modules\Homeroom\Infrastructure\Models\GatePass;
use App\Modules\Homeroom\Infrastructure\Models\StudentIncident;
use App\Modules\Homeroom\Infrastructure\Models\StudentPermitToken;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class OperationsPermitKioskPicketBkTest extends TestCase
{
    use RefreshDatabase;

    private School $schoolA;

    private School $schoolB;

    private Term $termA;

    private Term $termB;

    private ClassGroup $class7A;

    private ClassGroup $class7B;

    private ClassGroup $classOtherSchool;

    private Student $studentA1;

    private Student $studentA2;

    private Student $studentB1;

    private User $adminA;

    private User $operatorA;

    private User $homeroom7AUser;

    private User $homeroom7BUser;

    private User $parentA1User;

    private User $studentA1User;

    private User $picketUser;

    private User $guardUser;

    private User $counselorUser;

    private User $teacherUser;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $roles = app(RolePermissionRegistryService::class)->syncSystemRolesAndPermissions();

        $this->schoolA = School::query()->create([
            'name' => 'MAN Contoh Operations A',
            'npsn' => '30308801',
            'level' => 'MA',
            'timezone' => 'Asia/Makassar',
            'status' => 'active',
        ]);

        $this->schoolB = School::query()->create([
            'name' => 'MAN Contoh Operations B',
            'npsn' => '30308802',
            'level' => 'MA',
            'timezone' => 'Asia/Jakarta',
            'status' => 'active',
        ]);

        $this->adminA = $this->makeUser($this->schoolA, $roles[RoleEnum::ADMIN->value]->id, 'admin.a@test.sch.id', 'admin_ops_a');
        $this->operatorA = $this->makeUser($this->schoolA, $roles[RoleEnum::OPERATOR->value]->id, 'operator.a@test.sch.id', 'op_ops_a');
        $this->homeroom7AUser = $this->makeUser($this->schoolA, $roles[RoleEnum::HOMEROOM->value]->id, 'wali7a@test.sch.id', 'wali_7a');
        $this->homeroom7BUser = $this->makeUser($this->schoolA, $roles[RoleEnum::HOMEROOM->value]->id, 'wali7b@test.sch.id', 'wali_7b');
        $this->parentA1User = $this->makeUser($this->schoolA, $roles[RoleEnum::PARENT->value]->id, 'ortu.a1@test.sch.id', 'ortu_a1');
        $this->studentA1User = $this->makeUser($this->schoolA, $roles[RoleEnum::STUDENT->value]->id, 'siswa.a1@test.sch.id', 'siswa_a1');
        $this->picketUser = $this->makeUser($this->schoolA, $roles[RoleEnum::PICKET->value]->id, 'piket@test.sch.id', 'piket_a');
        $this->guardUser = $this->makeUser($this->schoolA, $roles[RoleEnum::SECURITY_GUARD->value]->id, 'satpam@test.sch.id', 'satpam_a');
        $this->counselorUser = $this->makeUser($this->schoolA, $roles[RoleEnum::COUNSELOR->value]->id, 'bk@test.sch.id', 'bk_a');
        $this->teacherUser = $this->makeUser($this->schoolA, $roles[RoleEnum::TEACHER->value]->id, 'guru@test.sch.id', 'guru_a');

        $yearA = AcademicYear::query()->create([
            'school_id' => $this->schoolA->id,
            'name' => '2026/2027',
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
            'is_active' => true,
        ]);
        $this->termA = Term::query()->create([
            'academic_year_id' => $yearA->id,
            'name' => 'Semester Ganjil',
            'semester' => 1,
            'start_date' => '2026-07-01',
            'end_date' => '2026-12-31',
            'is_active' => true,
        ]);

        $yearB = AcademicYear::query()->create([
            'school_id' => $this->schoolB->id,
            'name' => '2026/2027',
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
            'is_active' => true,
        ]);
        $this->termB = Term::query()->create([
            'academic_year_id' => $yearB->id,
            'name' => 'Semester Ganjil',
            'semester' => 1,
            'start_date' => '2026-07-01',
            'end_date' => '2026-12-31',
            'is_active' => true,
        ]);

        $teacher7A = Teacher::query()->create([
            'school_id' => $this->schoolA->id,
            'user_id' => $this->homeroom7AUser->id,
            'nip' => '19810101001',
            'name' => 'Wali Kelas 7A',
            'gender' => 'L',
            'is_active' => true,
        ]);
        $teacher7B = Teacher::query()->create([
            'school_id' => $this->schoolA->id,
            'user_id' => $this->homeroom7BUser->id,
            'nip' => '19810101002',
            'name' => 'Wali Kelas 7B',
            'gender' => 'P',
            'is_active' => true,
        ]);

        $this->class7A = ClassGroup::query()->create([
            'school_id' => $this->schoolA->id,
            'academic_year_id' => $yearA->id,
            'grade_level' => 7,
            'name' => 'X-IPA-1',
            'homeroom_teacher_id' => $teacher7A->id,
            'capacity' => 36,
        ]);
        $this->class7B = ClassGroup::query()->create([
            'school_id' => $this->schoolA->id,
            'academic_year_id' => $yearA->id,
            'grade_level' => 7,
            'name' => 'X-IPS-1',
            'homeroom_teacher_id' => $teacher7B->id,
            'capacity' => 36,
        ]);
        $this->classOtherSchool = ClassGroup::query()->create([
            'school_id' => $this->schoolB->id,
            'academic_year_id' => $yearB->id,
            'grade_level' => 7,
            'name' => 'X-B-1',
            'capacity' => 36,
        ]);

        $this->studentA1 = Student::query()->create([
            'school_id' => $this->schoolA->id,
            'user_id' => $this->studentA1User->id,
            'nis' => '260001',
            'nisn' => '0088001101',
            'name' => 'Aisyah Putri',
            'gender' => 'P',
            'status' => 'active',
        ]);
        $this->studentA2 = Student::query()->create([
            'school_id' => $this->schoolA->id,
            'nis' => '260002',
            'nisn' => '0088001102',
            'name' => 'Bima Sakti',
            'gender' => 'L',
            'status' => 'active',
        ]);
        $this->studentB1 = Student::query()->create([
            'school_id' => $this->schoolB->id,
            'nis' => '269001',
            'nisn' => '0088009901',
            'name' => 'Siswa Sekolah B',
            'gender' => 'L',
            'status' => 'active',
        ]);

        Enrollment::query()->create([
            'student_id' => $this->studentA1->id,
            'class_group_id' => $this->class7A->id,
            'term_id' => $this->termA->id,
            'status' => 'active',
            'roll_number' => 1,
        ]);
        Enrollment::query()->create([
            'student_id' => $this->studentA2->id,
            'class_group_id' => $this->class7B->id,
            'term_id' => $this->termA->id,
            'status' => 'active',
            'roll_number' => 2,
        ]);
        Enrollment::query()->create([
            'student_id' => $this->studentB1->id,
            'class_group_id' => $this->classOtherSchool->id,
            'term_id' => $this->termB->id,
            'status' => 'active',
            'roll_number' => 1,
        ]);

        StudentGuardian::query()->create([
            'school_id' => $this->schoolA->id,
            'student_id' => $this->studentA1->id,
            'user_id' => $this->parentA1User->id,
            'guardian_name' => 'Orang Tua Aisyah',
            'relationship' => 'Ibu',
            'phone' => '081234560001',
            'is_primary' => true,
            'is_verified' => true,
            'verified_at' => now(),
        ]);
    }

    public function test_guardian_submits_permit_with_conflict_cutoff_validation_and_attachment(): void
    {
        // Freeze time at 10:30 local Asia/Makassar (after default 09:00 cutoff for same-day leave)
        Carbon::setTestNow(Carbon::create(2026, 11, 20, 10, 30, 0, 'Asia/Makassar'));

        try {
            $today = Carbon::now('Asia/Makassar')->toDateString();
            $tomorrow = Carbon::now('Asia/Makassar')->addDay()->toDateString();

            // Same-day submission after cutoff should fail validation
            $this->actingAs($this->parentA1User)
                ->post('/layanan/izin-siswa', [
                    'student_id' => $this->studentA1->id,
                    'type' => 'sick',
                    'start_date' => $today,
                    'end_date' => $today,
                    'title' => 'Demam pagi hari',
                    'reason' => 'Sakit demam',
                    'signature_data' => 'data:image/png;base64,iVBORw0KGgo=',
                ])
                ->assertSessionHasErrors('start_date');

            // Unlinked student should fail
            $this->actingAs($this->parentA1User)
                ->post('/layanan/izin-siswa', [
                    'student_id' => $this->studentA2->id,
                    'type' => 'sick',
                    'start_date' => $tomorrow,
                    'end_date' => $tomorrow,
                    'title' => 'Bukan anak sendiri',
                    'reason' => 'Harus ditolak',
                    'signature_data' => 'data:image/png;base64,iVBORw0KGgo=',
                ])
                ->assertSessionHasErrors('student_id');

            // Valid future planned submission with PDF attachment succeeds
            $this->actingAs($this->parentA1User)
                ->post('/layanan/izin-siswa', [
                    'student_id' => $this->studentA1->id,
                    'type' => 'sick',
                    'start_date' => $tomorrow,
                    'end_date' => $tomorrow,
                    'title' => 'Kontrol Dokter',
                    'reason' => 'Surat kontrol klinik gigi pagi.',
                    'contact_name' => 'Ibu Aisyah',
                    'contact_phone' => '081234560001',
                    'signature_data' => 'data:image/png;base64,iVBORw0KGgo=',
                    'attachment' => UploadedFile::fake()->create('surat-dokter.pdf', 64, 'application/pdf'),
                    'idempotency_key' => 'permit-req-001',
                ])
                ->assertRedirect('/layanan/izin-siswa');

            $dispensation = AttendanceDispensation::query()->firstOrFail();
            $this->assertSame('pending', $dispensation->status?->value);
            $this->assertNotNull($dispensation->service_ticket_id);
            $this->assertNotNull($dispensation->attachment_document_id);

            // Idempotency retry returns same record without duplicating
            $this->actingAs($this->parentA1User)
                ->post('/layanan/izin-siswa', [
                    'student_id' => $this->studentA1->id,
                    'type' => 'sick',
                    'start_date' => $tomorrow,
                    'end_date' => $tomorrow,
                    'title' => 'Kontrol Dokter',
                    'reason' => 'Surat kontrol klinik gigi pagi.',
                    'signature_data' => 'data:image/png;base64,iVBORw0KGgo=',
                    'idempotency_key' => 'permit-req-001',
                ])
                ->assertRedirect('/layanan/izin-siswa');
            $this->assertSame(1, AttendanceDispensation::query()->count());

            // Overlapping future date without matching idempotency key is rejected
            $this->actingAs($this->parentA1User)
                ->post('/layanan/izin-siswa', [
                    'student_id' => $this->studentA1->id,
                    'type' => 'personal',
                    'start_date' => $tomorrow,
                    'end_date' => $tomorrow,
                    'title' => 'Izin Tambahan Bentrok',
                    'reason' => 'Tanggal bentrok',
                    'signature_data' => 'data:image/png;base64,iVBORw0KGgo=',
                ])
                ->assertSessionHasErrors('start_date');

            // Authorized guardian can view PDF proof & download attachment
            $this->actingAs($this->parentA1User)
                ->get("/layanan/izin-siswa/{$dispensation->id}/pdf")
                ->assertOk()
                ->assertHeader('Content-Type', 'application/pdf');

            $this->actingAs($this->parentA1User)
                ->get("/layanan/izin-siswa/{$dispensation->id}/lampiran")
                ->assertOk();
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_single_use_permit_token_is_validated_and_consumed_atomically(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 11, 20, 6, 30, 0, 'Asia/Makassar'));

        try {
            $tomorrow = Carbon::now('Asia/Makassar')->addDay()->toDateString();

            $this->actingAs($this->homeroom7AUser)
                ->post('/wali-kelas/izin/token', [
                    'student_id' => $this->studentA1->id,
                    'expires_in_hours' => 24,
                ])
                ->assertRedirect()
                ->assertSessionHas('issued_permit_token');

            $issuedToken = session('issued_permit_token');
            $plainToken = (string) $issuedToken['token'];

            // Wrong student ID rejected and token NOT consumed
            $this->post('/layanan/izin-siswa', [
                'student_id' => $this->studentA2->id,
                'one_time_token' => $plainToken,
                'type' => 'personal',
                'start_date' => $tomorrow,
                'end_date' => $tomorrow,
                'title' => 'Salah Siswa',
                'reason' => 'Uji token siswa lain',
                'signature_data' => 'data:image/png;base64,iVBORw0KGgo=',
            ])->assertSessionHasErrors('one_time_token');

            $tokenRow = StudentPermitToken::query()->firstOrFail();
            $this->assertNull($tokenRow->used_at);

            // Guest submits valid form with single-use token -> succeeds & consumes token
            $this->post('/layanan/izin-siswa', [
                'student_id' => $this->studentA1->id,
                'one_time_token' => $plainToken,
                'type' => 'personal',
                'start_date' => $tomorrow,
                'end_date' => $tomorrow,
                'title' => 'Izin Urusan Keluarga',
                'reason' => 'Menghadiri acara keluarga',
                'contact_name' => 'Paman Aisyah',
                'contact_phone' => '081200001111',
                'signature_data' => 'data:image/png;base64,iVBORw0KGgo=',
            ])->assertRedirect('/layanan/izin-siswa');

            $tokenRow->refresh();
            $this->assertNotNull($tokenRow->used_at);
            $this->assertNotNull($tokenRow->used_by_dispensation_id);

            // Reusing the same token fails
            $nextWeek = Carbon::now('Asia/Makassar')->addDays(4)->toDateString();
            $this->post('/layanan/izin-siswa', [
                'student_id' => $this->studentA1->id,
                'one_time_token' => $plainToken,
                'type' => 'personal',
                'start_date' => $nextWeek,
                'end_date' => $nextWeek,
                'title' => 'Pakai Ulang Token',
                'reason' => 'Harus ditolak',
                'signature_data' => 'data:image/png;base64,iVBORw0KGgo=',
            ])->assertSessionHasErrors('one_time_token');
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_homeroom_verification_revision_approval_and_correction_syncs_attendance_and_audits(): void
    {
        $tomorrow = Carbon::now('Asia/Makassar')->addDay()->toDateString();

        $this->actingAs($this->parentA1User)
            ->post('/layanan/izin-siswa', [
                'student_id' => $this->studentA1->id,
                'type' => 'sick',
                'start_date' => $tomorrow,
                'end_date' => $tomorrow,
                'title' => 'Izin Sakit Flu',
                'reason' => 'Sakit flu sejak malam.',
                'signature_data' => 'data:image/png;base64,iVBORw0KGgo=',
            ])
            ->assertRedirect('/layanan/izin-siswa');

        $dispensation = AttendanceDispensation::query()->firstOrFail();

        // Homeroom 7B cannot decide student 7A's permit
        $this->actingAs($this->homeroom7BUser)
            ->post("/wali-kelas/izin/{$dispensation->id}/putuskan", [
                'decision' => 'approve',
            ])
            ->assertForbidden();

        // Homeroom 7A requests revision without note -> rejected validation
        $this->actingAs($this->homeroom7AUser)
            ->post("/wali-kelas/izin/{$dispensation->id}/putuskan", [
                'decision' => 'request_revision',
            ])
            ->assertSessionHasErrors('decision_note');

        // Homeroom 7A requests revision with note
        $this->actingAs($this->homeroom7AUser)
            ->post("/wali-kelas/izin/{$dispensation->id}/putuskan", [
                'decision' => 'request_revision',
                'decision_note' => 'Mohon lampirkan surat dokter atau detail keluhan.',
            ])
            ->assertRedirect();

        $dispensation->refresh();
        $this->assertSame('needs_revision', $dispensation->status?->value);

        // Guardian resubmits updated permit
        $this->actingAs($this->parentA1User)
            ->post("/layanan/izin-siswa/{$dispensation->id}/perbaiki", [
                'start_date' => $tomorrow,
                'end_date' => $tomorrow,
                'title' => 'Izin Sakit Flu + Surat Dokter',
                'reason' => 'Demam tinggi dan batuk, istirahat 1 hari.',
            ])
            ->assertRedirect();

        $dispensation->refresh();
        $this->assertSame('pending', $dispensation->status?->value);

        // Homeroom 7A approves -> AttendanceRecord created with status='sick'
        $this->actingAs($this->homeroom7AUser)
            ->post("/wali-kelas/izin/{$dispensation->id}/putuskan", [
                'decision' => 'approve',
                'decision_note' => 'Disetujui wali kelas.',
            ])
            ->assertRedirect();

        $dispensation->refresh();
        $this->assertSame('approved', $dispensation->status?->value);
        $enrollmentA1 = Enrollment::query()->where('student_id', $this->studentA1->id)->firstOrFail();
        $this->assertTrue(
            AttendanceRecord::query()
                ->where('school_id', $this->schoolA->id)
                ->where('enrollment_id', $enrollmentA1->id)
                ->whereDate('date', $tomorrow)
                ->where('status', 'sick')
                ->exists()
        );

        // Homeroom 7A corrects approval -> AttendanceRecord reverted to absent and audit written
        $this->actingAs($this->homeroom7AUser)
            ->post("/wali-kelas/izin/{$dispensation->id}/koreksi", [
                'new_status' => 'rejected',
                'correction_reason' => 'Salah tanggal pengajuan, siswa hadir di kelas.',
            ])
            ->assertRedirect();

        $dispensation->refresh();
        $this->assertSame('rejected', $dispensation->status?->value);
        $this->assertFalse(
            AttendanceRecord::query()
                ->where('school_id', $this->schoolA->id)
                ->where('enrollment_id', $enrollmentA1->id)
                ->whereDate('date', $tomorrow)
                ->where('status', 'sick')
                ->exists()
        );
        $this->assertTrue(
            AttendanceRecord::query()
                ->where('school_id', $this->schoolA->id)
                ->where('enrollment_id', $enrollmentA1->id)
                ->whereDate('date', $tomorrow)
                ->where('status', 'absent')
                ->exists()
        );
        $this->assertDatabaseHas('audit_events', [
            'school_id' => $this->schoolA->id,
            'event_type' => 'homeroom.dispensation.corrected',
            'target_id' => (string) $dispensation->id,
        ]);
    }

    public function test_kiosk_card_registry_atomic_dedup_offline_sync_and_revoked_rejection(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 11, 20, 7, 10, 0, 'Asia/Makassar'));

        try {
            // Issue student card for A1 and register kiosk device
            $this->actingAs($this->operatorA)
                ->post('/akademik/kartu-siswa', [
                    'student_id' => $this->studentA1->id,
                    'card_uid' => 'RFID-A1-001',
                    'qr_token' => 'QR-A1-001',
                ])
                ->assertRedirect();

            $this->actingAs($this->operatorA)
                ->post('/akademik/kartu-siswa/perangkat', [
                    'name' => 'Kios Gerbang Utama',
                    'location' => 'Lobi Depan',
                    'gate_enabled' => true,
                ])
                ->assertRedirect();

            $card = StudentCard::query()->where('student_id', $this->studentA1->id)->firstOrFail();

            // Foreign school card must be rejected when scanned at school A
            $cardOther = StudentCard::query()->create([
                'school_id' => $this->schoolB->id,
                'student_id' => $this->studentB1->id,
                'card_uid' => 'RFID-B1-999',
                'qr_token' => 'QR-B1-999',
                'status' => 'active',
                'issued_at' => now(),
            ]);

            // Valid RFID scan -> records attendance and returns minimal student data only
            $scanRes = $this->actingAs($this->picketUser)
                ->postJson('/kios-presensi/scan', [
                    'code' => 'RFID-A1-001',
                    'scan_method' => 'rfid',
                    'client_event_id' => 'evt-kiosk-1',
                ])
                ->assertOk()
                ->assertJsonPath('status', 'recorded')
                ->assertJsonPath('student.name', 'Aisyah Putri')
                ->assertJsonPath('student.class_name', 'X-IPA-1');

            $this->assertArrayNotHasKey('nis', $scanRes->json('student'));
            $this->assertArrayNotHasKey('nisn', $scanRes->json('student'));

            $today = Carbon::now('Asia/Makassar')->toDateString();
            $enrollmentA1 = Enrollment::query()->where('student_id', $this->studentA1->id)->firstOrFail();
            $this->assertTrue(
                AttendanceRecord::query()
                    ->where('school_id', $this->schoolA->id)
                    ->where('enrollment_id', $enrollmentA1->id)
                    ->whereDate('date', $today)
                    ->where('status', 'present')
                    ->exists()
            );

            // Repeat scan within dedup window -> marked duplicate, single attendance record
            $this->actingAs($this->picketUser)
                ->postJson('/kios-presensi/scan', [
                    'code' => 'RFID-A1-001',
                    'scan_method' => 'rfid',
                    'client_event_id' => 'evt-kiosk-2',
                ])
                ->assertOk()
                ->assertJsonPath('status', 'duplicate');

            $this->assertSame(1, AttendanceRecord::query()->where('enrollment_id', $enrollmentA1->id)->count());

            // Offline batch sync including A2 card + idempotent replay
            StudentCard::query()->create([
                'school_id' => $this->schoolA->id,
                'student_id' => $this->studentA2->id,
                'card_uid' => 'RFID-A2-002',
                'qr_token' => 'QR-A2-002',
                'status' => 'active',
                'issued_at' => now(),
            ]);

            $this->actingAs($this->picketUser)
                ->postJson('/kios-presensi/sinkron', [
                    'events' => [
                        [
                            'code' => 'QR-A2-002',
                            'scan_method' => 'qr',
                            'client_event_id' => 'offline-a2-001',
                            'scanned_at' => Carbon::now('Asia/Makassar')->toIso8601String(),
                        ],
                        [
                            'code' => $cardOther->card_uid,
                            'scan_method' => 'rfid',
                            'client_event_id' => 'offline-foreign-001',
                            'scanned_at' => Carbon::now('Asia/Makassar')->toIso8601String(),
                        ],
                    ],
                ])
                ->assertOk()
                ->assertJsonPath('synced_count', 2)
                ->assertJsonPath('results.0.status', 'recorded')
                ->assertJsonPath('results.1.status', 'rejected');

            // Kiosk index renders Inertia component & stats endpoint enforces auth/role
            $this->actingAs($this->picketUser)
                ->get('/kios-presensi')
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->component('Academic/Attendance/Kiosk')
                    ->has('stats.total_students')
                    ->has('stats.present')
                    ->has('stats.absent')
                );

            $this->actingAs($this->picketUser)
                ->getJson('/kios-presensi/stats')
                ->assertOk()
                ->assertJsonPath('success', true)
                ->assertJsonStructure([
                    'success',
                    'stats' => ['total_students', 'present', 'on_time', 'late', 'checked_out', 'absent'],
                ]);

            $this->actingAs($this->studentA1User)
                ->getJson('/kios-presensi/stats')
                ->assertForbidden();

            // Revoke card A1 -> subsequent scan rejected
            $this->actingAs($this->operatorA)
                ->post("/akademik/kartu-siswa/{$card->id}/cabut", [
                    'reason' => 'Kartu hilang di perjalanan',
                ])
                ->assertRedirect();

            Carbon::setTestNow(Carbon::now('Asia/Makassar')->addMinutes(10));

            $this->actingAs($this->picketUser)
                ->postJson('/kios-presensi/scan', [
                    'code' => 'QR-A1-001',
                    'scan_method' => 'qr',
                    'client_event_id' => 'evt-kiosk-revoked',
                ])
                ->assertStatus(422)
                ->assertJsonPath('status', 'rejected')
                ->assertJsonPath('code', 'card_revoked');
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_picket_exit_pass_and_security_guard_pin_lock_and_validation_flow(): void
    {
        $now = Carbon::now('Asia/Makassar');
        $validFrom = $now->copy()->subMinutes(10)->format('Y-m-d H:i:s');
        $validUntil = $now->copy()->addHours(2)->format('Y-m-d H:i:s');

        // Picket issues exit pass
        $this->actingAs($this->picketUser)
            ->post('/piket/izin-keluar', [
                'student_id' => $this->studentA1->id,
                'exit_type' => 'temporary_out',
                'reason' => 'Mengambil dokumen akta di rumah',
                'destination' => 'Rumah Siswa',
                'valid_from' => $validFrom,
                'valid_until' => $validUntil,
            ])
            ->assertRedirect();

        $pass = GatePass::query()->where('student_id', $this->studentA1->id)->firstOrFail();
        $this->assertSame('active', $pass->status);

        // Configure numeric PIN for Security Post
        $this->actingAs($this->adminA)
            ->post('/pos-jaga/atur-pin', ['pin' => '2580'])
            ->assertRedirect();

        // Guard cannot scan while post is locked -> 403
        $this->actingAs($this->guardUser)
            ->postJson('/pos-jaga/scan', ['code' => $pass->pass_code])
            ->assertForbidden()
            ->assertJsonPath('code', 'post_locked');

        // Wrong PIN rejected
        $this->actingAs($this->guardUser)
            ->post('/pos-jaga/buka-akses', ['pin' => '0000'])
            ->assertSessionHasErrors('pin');

        // Valid numeric PIN unlocks post
        $this->actingAs($this->guardUser)
            ->post('/pos-jaga/buka-akses', ['pin' => '2580'])
            ->assertRedirect();

        // First scan marks student EXITED
        $this->actingAs($this->guardUser)
            ->postJson('/pos-jaga/scan', [
                'code' => $pass->pass_code,
                'mode' => 'auto',
                'handover_notes' => 'Dijemput ayah kandung',
            ])
            ->assertOk()
            ->assertJsonPath('status', 'exited')
            ->assertJsonPath('pass.status', 'out');

        // Second scan marks student RETURNED
        $this->actingAs($this->guardUser)
            ->postJson('/pos-jaga/scan', [
                'code' => $pass->pass_code,
                'mode' => 'auto',
            ])
            ->assertOk()
            ->assertJsonPath('status', 'returned')
            ->assertJsonPath('pass.status', 'returned');

        // Reuse of already-returned pass rejected
        $this->actingAs($this->guardUser)
            ->postJson('/pos-jaga/scan', [
                'code' => $pass->pass_code,
                'mode' => 'auto',
            ])
            ->assertStatus(422)
            ->assertJsonPath('status', 'rejected')
            ->assertJsonPath('code', 'already_used');

        // Cancelled & expired passes rejected
        $this->actingAs($this->picketUser)
            ->post('/piket/izin-keluar', [
                'student_id' => $this->studentA2->id,
                'exit_type' => 'early_home',
                'reason' => 'Sakit perut',
                'valid_from' => $validFrom,
                'valid_until' => $validUntil,
            ])
            ->assertRedirect();

        $cancelledPass = GatePass::query()->where('student_id', $this->studentA2->id)->firstOrFail();
        $this->actingAs($this->picketUser)
            ->post("/piket/izin-keluar/{$cancelledPass->id}/batal", ['reason' => 'Batal pulang'])
            ->assertRedirect();

        $this->actingAs($this->guardUser)
            ->postJson('/pos-jaga/scan', ['code' => $cancelledPass->pass_code])
            ->assertStatus(422)
            ->assertJsonPath('code', 'cancelled');

        $expiredPass = GatePass::query()->create([
            'school_id' => $this->schoolA->id,
            'student_id' => $this->studentA2->id,
            'term_id' => $this->termA->id,
            'pass_code' => 'GP-EXPIRED1',
            'exit_type' => 'temporary_out',
            'reason' => 'Kedaluwarsa',
            'valid_from' => $now->copy()->subHours(4),
            'valid_until' => $now->copy()->subHour(),
            'status' => 'active',
            'issued_by_user_id' => $this->picketUser->id,
        ]);

        $this->actingAs($this->guardUser)
            ->postJson('/pos-jaga/scan', ['code' => $expiredPass->pass_code])
            ->assertStatus(422)
            ->assertJsonPath('code', 'expired');
    }

    public function test_bk_confidential_discipline_access_control_evidence_audit_and_csv_export(): void
    {
        // Forbidden roles must get 403 on BK routes
        foreach ([$this->teacherUser, $this->homeroom7AUser, $this->picketUser, $this->guardUser, $this->studentA1User] as $nonBkUser) {
            $this->actingAs($nonBkUser)->get('/bk/kasus')->assertForbidden();
            $this->actingAs($nonBkUser)->get('/bk/kasus/ekspor')->assertForbidden();
        }

        // Counselor searches student via card QR token or NIS
        StudentCard::query()->create([
            'school_id' => $this->schoolA->id,
            'student_id' => $this->studentA1->id,
            'card_uid' => 'RFID-BK-001',
            'qr_token' => 'QR-BK-001',
            'status' => 'active',
            'issued_at' => now(),
        ]);

        $this->actingAs($this->counselorUser)
            ->getJson('/bk/kasus/cari-siswa?q=QR-BK-001')
            ->assertOk()
            ->assertJsonPath('students.0.student_id', $this->studentA1->id);

        // Counselor creates confidential BK case with private evidence
        $this->actingAs($this->counselorUser)
            ->post('/bk/kasus', [
                'student_id' => $this->studentA1->id,
                'incident_date' => '2026-11-20',
                'incident_time' => '10:15',
                'location' => 'Gedung Lab Komputer',
                'category' => 'kedisiplinan',
                'title' => '=SUM(A1:A2) Pengawasan Konseling',
                'description' => 'Catatan konseling privat dengan siswa.',
                'penalty_points' => 10,
                'follow_up_action' => 'Sesi konseling lanjutan.',
                'status' => 'reported',
                'evidence_files' => [
                    UploadedFile::fake()->create('eviden-bk.pdf', 32, 'application/pdf'),
                ],
            ])
            ->assertRedirect();

        $incident = StudentIncident::query()->where('student_id', $this->studentA1->id)->firstOrFail();
        $this->assertTrue($incident->is_bk_confidential);
        $this->assertCount(1, $incident->evidence_document_ids ?? []);
        $docId = (int) $incident->evidence_document_ids[0];

        // Non-BK cannot download BK evidence
        $this->actingAs($this->homeroom7AUser)
            ->get("/bk/kasus/{$incident->id}/eviden/{$docId}")
            ->assertForbidden();

        // Homeroom regular discipline page must NOT expose confidential BK incident
        $this->actingAs($this->homeroom7AUser)
            ->get('/wali-kelas/kedisiplinan')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('incidents', fn ($rows): bool => collect($rows)->where('id', $incident->id)->isEmpty())
            );

        // Counselor updates status and can download evidence + export CSV (formula-neutralized)
        $this->actingAs($this->counselorUser)
            ->put("/bk/kasus/{$incident->id}", [
                'incident_date' => '2026-11-20',
                'category' => 'kedisiplinan',
                'title' => '=SUM(A1:A2) Pengawasan Konseling',
                'description' => 'Diperbarui setelah konseling tahap 1.',
                'penalty_points' => 5,
                'follow_up_action' => 'Selesai pembinaan.',
                'status' => 'resolved',
            ])
            ->assertRedirect();

        $incident->refresh();
        $this->assertSame('resolved', $incident->status);

        $this->actingAs($this->counselorUser)
            ->get("/bk/kasus/{$incident->id}/eviden/{$docId}")
            ->assertOk();

        $csvResponse = $this->actingAs($this->counselorUser)
            ->get('/bk/kasus/ekspor')
            ->assertOk();

        $csvBody = (string) $csvResponse->getContent();
        $this->assertStringContainsString("'=SUM(A1:A2)", $csvBody);

        $this->assertDatabaseHas('audit_events', [
            'school_id' => $this->schoolA->id,
            'event_type' => 'homeroom.bk_case.exported',
        ]);
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
