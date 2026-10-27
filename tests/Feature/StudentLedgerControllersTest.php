<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Academic\Infrastructure\Models\AcademicYear;
use App\Modules\Academic\Infrastructure\Models\ClassGroup;
use App\Modules\Academic\Infrastructure\Models\Enrollment;
use App\Modules\Academic\Infrastructure\Models\Student;
use App\Modules\Academic\Infrastructure\Models\StudentLedgerDocument;
use App\Modules\Academic\Infrastructure\Models\Term;
use App\Modules\Core\Infrastructure\Models\Role;
use App\Modules\Core\Infrastructure\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StudentLedgerControllersTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    private User $operator;

    private User $teacher;

    private AcademicYear $year;

    private Term $term;

    private ClassGroup $class;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create(['npsn' => '12345678', 'name' => 'SMP Teladan Bangsa', 'status' => 'active']);
        $adminRole = Role::create(['name' => 'admin', 'label' => 'Administrator', 'is_system' => true]);
        $operatorRole = Role::create(['name' => 'operator', 'label' => 'Operator', 'is_system' => true]);
        $teacherRole = Role::create(['name' => 'teacher', 'label' => 'Guru', 'is_system' => true]);

        $this->admin = $this->makeUser($adminRole, 'admin', 'admin@school.test');
        $this->operator = $this->makeUser($operatorRole, 'operator', 'operator@school.test');
        $this->teacher = $this->makeUser($teacherRole, 'teacher', 'teacher@school.test');

        $this->year = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2025/2026',
            'start_date' => '2025-07-15',
            'end_date' => '2026-06-20',
            'is_active' => true,
        ]);
        $this->term = Term::create([
            'academic_year_id' => $this->year->id,
            'name' => 'Semester Ganjil',
            'semester_type' => 'odd',
            'start_date' => '2025-07-15',
            'end_date' => '2025-12-20',
            'is_active' => true,
        ]);
        $this->class = ClassGroup::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'grade_level' => 7,
            'name' => 'VII-A',
            'capacity' => 32,
        ]);
    }

    public function test_admin_can_search_and_filter_school_students_in_the_ledger(): void
    {
        $student = $this->makeStudent('1001', 'Alya Putri', '0012345678');
        Enrollment::create([
            'term_id' => $this->term->id,
            'class_group_id' => $this->class->id,
            'student_id' => $student->id,
            'roll_number' => 1,
            'status' => 'active',
        ]);

        $foreignSchool = School::create(['npsn' => '87654321', 'name' => 'SMP Lain', 'status' => 'active']);
        $foreignStudent = Student::create(['school_id' => $foreignSchool->id, 'nis' => '2001', 'name' => 'Budi Luar', 'gender' => 'L']);

        $this->actingAs($this->admin)->get('/akademik/buku-induk?search=Alya&class_group_id='.$this->class->id)
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Academic/Students/Ledger/Index')
                ->has('students.data', 1)
                ->where('students.data.0.id', $student->id)
                ->where('students.data.0.class_name', 'VII-A')
                ->where('students.data.0.nisn', '0012345678')
            );

        $this->assertDatabaseHas('students', ['id' => $foreignStudent->id]);
    }

    public function test_operator_can_view_read_only_ledger_and_class_history(): void
    {
        $student = $this->makeStudent('1002', 'Dimas Pratama');
        Enrollment::create([
            'term_id' => $this->term->id,
            'class_group_id' => $this->class->id,
            'student_id' => $student->id,
            'roll_number' => 2,
            'status' => 'active',
        ]);

        $this->actingAs($this->operator)->get('/akademik/buku-induk/'.$student->id)
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Academic/Students/Ledger/Show')
                ->where('student.name', 'Dimas Pratama')
                ->where('student.parent_name', 'Orang Tua Dimas Pratama')
                ->has('class_history', 1)
                ->where('class_history.0.class_name', 'VII-A')
                ->where('class_history.0.academic_year', '2025/2026')
            );

        $this->assertDatabaseCount('students', 1);
        $this->assertDatabaseCount('enrollments', 1);
    }

    public function test_non_management_roles_and_other_schools_cannot_open_ledger_records(): void
    {
        $student = $this->makeStudent('1003', 'Siti Nisa');
        $otherSchool = School::create(['npsn' => '87654321', 'name' => 'SMP Lain', 'status' => 'active']);
        $otherStudent = Student::create(['school_id' => $otherSchool->id, 'nis' => '2002', 'name' => 'Siswa Sekolah Lain', 'gender' => 'P']);

        $this->actingAs($this->teacher)->get('/akademik/buku-induk')->assertForbidden();
        $this->actingAs($this->admin)->get('/akademik/buku-induk/'.$otherStudent->id)->assertNotFound();
        $this->actingAs($this->admin)->get('/akademik/buku-induk/'.$student->id.'/pdf')->assertOk()->assertHeader('content-type', 'application/pdf');

        $this->assertDatabaseHas('audit_events', [
            'event_type' => 'academic.student_ledger.exported',
            'target_id' => (string) $student->id,
            'school_id' => $this->school->id,
        ]);
    }

    public function test_operator_can_save_supplemental_details_without_changing_student_identity(): void
    {
        $student = $this->makeStudent('1004', 'Nadia Zahra');

        $this->actingAs($this->operator)->put("/akademik/buku-induk/{$student->id}/detail", [
            'nickname' => 'Nadia',
            'nik' => '3201234567890123',
            'blood_type' => 'O',
            'address_rt' => '02',
            'address_rw' => '05',
            'village' => 'Sukamaju',
            'district' => 'Cibiru',
            'sports_hobby' => 'Bulu tangkis',
            'father_name' => 'Ayah Nadia',
            'father_occupation' => 'Guru',
            'mother_name' => 'Ibu Nadia',
            'mother_occupation' => 'Dokter',
            'previous_school' => 'SD Negeri 1',
            'diploma_number' => 'DN-123',
            'diploma_date' => '2025-06-20 00:00:00',
        ])->assertRedirect();

        $this->assertDatabaseCount('students', 1);
        $this->assertDatabaseHas('students', ['id' => $student->id, 'name' => 'Nadia Zahra', 'parent_name' => 'Orang Tua Nadia Zahra']);
        $this->assertDatabaseHas('administration_student_ledger_details', [
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'nickname' => 'Nadia',
            'nik' => '3201234567890123',
            'blood_type' => 'O',
            'address_rt' => '02',
            'address_rw' => '05',
            'village' => 'Sukamaju',
            'district' => 'Cibiru',
            'sports_hobby' => 'Bulu tangkis',
            'father_name' => 'Ayah Nadia',
            'mother_occupation' => 'Dokter',
            'previous_school' => 'SD Negeri 1',
            'diploma_number' => 'DN-123',
            'diploma_date' => '2025-06-20 00:00:00',
        ]);
        $this->actingAs($this->operator)->get("/akademik/buku-induk/{$student->id}")
            ->assertInertia(fn ($page) => $page->where('detail.father_name', 'Ayah Nadia')->where('detail.diploma_date', '2025-06-20')->where('detail.nickname', 'Nadia')->where('detail.nik', '3201234567890123'));
        $this->assertDatabaseHas('audit_events', ['event_type' => 'academic.student_ledger.details_updated', 'school_id' => $this->school->id]);
    }

    public function test_supplemental_detail_validation_and_school_isolation(): void
    {
        $student = $this->makeStudent('1005', 'Raka');
        $otherSchool = School::create(['npsn' => '87654321', 'name' => 'SMP Lain', 'status' => 'active']);
        $otherStudent = Student::create(['school_id' => $otherSchool->id, 'nis' => '2003', 'name' => 'Siswa Lain', 'gender' => 'L']);

        $this->actingAs($this->admin)->put("/akademik/buku-induk/{$student->id}/detail", ['father_name' => str_repeat('a', 101), 'diploma_date' => 'bukan-tanggal'])
            ->assertSessionHasErrors(['father_name', 'diploma_date']);
        $this->actingAs($this->admin)->put("/akademik/buku-induk/{$student->id}/detail", ['nik' => '123', 'blood_type' => 'C'])
            ->assertSessionHasErrors(['nik', 'blood_type']);
        $this->actingAs($this->admin)->put("/akademik/buku-induk/{$otherStudent->id}/detail", ['father_name' => 'Tidak boleh'])->assertNotFound();
        $this->assertDatabaseCount('administration_student_ledger_details', 0);
    }

    public function test_private_documents_can_be_uploaded_downloaded_and_deleted_with_audit(): void
    {
        Storage::fake('local');
        $student = $this->makeStudent('1006', 'Dewi');

        $this->actingAs($this->operator)->post("/akademik/buku-induk/{$student->id}/dokumen", [
            'document_type' => 'Akta kelahiran',
            'file' => UploadedFile::fake()->create('akta.pdf', 120, 'application/pdf'),
        ])->assertRedirect();

        $document = StudentLedgerDocument::query()->firstOrFail();
        $this->assertSame($this->school->id, $document->school_id);
        $this->assertTrue(Storage::disk('local')->exists($document->file_path));
        $this->assertStringStartsWith("student-ledger/{$this->school->id}/{$student->id}/", $document->file_path);
        $this->assertDatabaseHas('audit_events', ['event_type' => 'academic.student_ledger.document_uploaded', 'school_id' => $this->school->id]);

        $this->actingAs($this->admin)->get("/akademik/buku-induk/{$student->id}/dokumen/{$document->id}")->assertOk();
        $this->assertDatabaseHas('audit_events', ['event_type' => 'academic.student_ledger.document_downloaded', 'target_id' => (string) $document->id]);

        $otherSchool = School::create(['npsn' => '87654321', 'name' => 'SMP Lain', 'status' => 'active']);
        $otherStudent = Student::create(['school_id' => $otherSchool->id, 'nis' => '2004', 'name' => 'Siswa Lain', 'gender' => 'L']);
        $this->actingAs($this->admin)->get("/akademik/buku-induk/{$otherStudent->id}/dokumen/{$document->id}")->assertNotFound();
        $this->actingAs($this->teacher)->get("/akademik/buku-induk/{$student->id}/dokumen/{$document->id}")->assertForbidden();

        $this->actingAs($this->operator)->delete("/akademik/buku-induk/{$student->id}/dokumen/{$document->id}")->assertRedirect();
        $this->assertDatabaseMissing('administration_student_ledger_documents', ['id' => $document->id]);
        Storage::disk('local')->assertMissing($document->file_path);
        $this->assertDatabaseHas('audit_events', ['event_type' => 'academic.student_ledger.document_deleted', 'target_id' => (string) $document->id]);
    }

    public function test_student_photo_is_private_school_scoped_and_replaceable(): void
    {
        Storage::fake('local');
        $student = $this->makeStudent('1007', 'Fajar');

        $this->actingAs($this->operator)->post("/akademik/buku-induk/{$student->id}/foto", [
            'photo' => UploadedFile::fake()->image('fajar.png', 400, 400),
        ])->assertRedirect();

        $oldPhotoPath = DB::table('administration_student_ledger_details')->where('student_id', $student->id)->value('photo_path');
        $this->assertNotNull($oldPhotoPath);
        $this->assertStringStartsWith("student-ledger/{$this->school->id}/{$student->id}/profile/", $oldPhotoPath);
        Storage::disk('local')->assertExists($oldPhotoPath);
        $this->assertDatabaseHas('audit_events', ['event_type' => 'academic.student_ledger.photo_uploaded', 'target_id' => (string) $student->id]);

        $this->actingAs($this->operator)->post("/akademik/buku-induk/{$student->id}/foto", [
            'photo' => UploadedFile::fake()->image('fajar-baru.png', 400, 400),
        ])->assertRedirect();
        $photoPath = DB::table('administration_student_ledger_details')->where('student_id', $student->id)->value('photo_path');
        $this->assertNotSame($oldPhotoPath, $photoPath);
        Storage::disk('local')->assertMissing($oldPhotoPath);
        Storage::disk('local')->assertExists($photoPath);

        $this->actingAs($this->admin)->get("/akademik/buku-induk/{$student->id}/foto")
            ->assertOk()
            ->assertHeader('content-type', 'image/png')
            ->assertHeader('cache-control', 'no-store, private');
        $this->actingAs($this->teacher)->get("/akademik/buku-induk/{$student->id}/foto")->assertForbidden();

        $otherSchool = School::create(['npsn' => '87654321', 'name' => 'SMP Lain', 'status' => 'active']);
        $otherStudent = Student::create(['school_id' => $otherSchool->id, 'nis' => '2005', 'name' => 'Siswa Lain', 'gender' => 'L']);
        $this->actingAs($this->admin)->get("/akademik/buku-induk/{$otherStudent->id}/foto")->assertNotFound();

        $this->actingAs($this->operator)->delete("/akademik/buku-induk/{$student->id}/foto")->assertRedirect();
        $this->assertDatabaseHas('administration_student_ledger_details', ['student_id' => $student->id, 'photo_path' => null]);
        Storage::disk('local')->assertMissing($photoPath);
        $this->assertDatabaseHas('audit_events', ['event_type' => 'academic.student_ledger.photo_deleted', 'target_id' => (string) $student->id]);
    }

    private function makeUser(Role $role, string $username, string $email): User
    {
        return User::create([
            'school_id' => $this->school->id,
            'role_id' => $role->id,
            'name' => ucfirst($username),
            'username' => $username,
            'email' => $email,
            'password' => bcrypt('TestPass123!'),
            'is_active' => true,
        ]);
    }

    private function makeStudent(string $nis, string $name, ?string $nisn = null): Student
    {
        return Student::create([
            'school_id' => $this->school->id,
            'nis' => $nis,
            'nisn' => $nisn,
            'name' => $name,
            'gender' => 'P',
            'birth_place' => 'Bandung',
            'birth_date' => '2013-02-10',
            'religion' => 'Islam',
            'address' => 'Jl. Pendidikan No. 1',
            'parent_name' => 'Orang Tua '.$name,
            'parent_phone' => '081234567890',
        ]);
    }
}
