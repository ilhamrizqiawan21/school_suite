<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Academic\Infrastructure\Models\AcademicYear;
use App\Modules\Academic\Infrastructure\Models\ClassGroup;
use App\Modules\Academic\Infrastructure\Models\Student;
use App\Modules\Academic\Infrastructure\Models\Teacher;
use App\Modules\Academic\Infrastructure\Models\Term;
use App\Modules\Core\Infrastructure\Models\Role;
use App\Modules\Core\Infrastructure\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class AcademicControllersTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    private User $teacherUser;

    private AcademicYear $academicYear;

    private Term $term;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create([
            'npsn' => '12345678',
            'name' => 'SMP Teladan Bangsa',
            'status' => 'active',
        ]);

        $adminRole = Role::create([
            'name' => 'admin',
            'label' => 'Administrator',
            'is_system' => true,
        ]);

        $teacherRole = Role::create([
            'name' => 'teacher',
            'label' => 'Guru',
            'is_system' => true,
        ]);

        $this->admin = User::create([
            'school_id' => $this->school->id,
            'role_id' => $adminRole->id,
            'name' => 'Admin Sekolah',
            'username' => 'admin',
            'email' => 'admin@school.test',
            'password' => bcrypt('AdminPass123!'),
            'is_active' => true,
        ]);

        $this->teacherUser = User::create([
            'school_id' => $this->school->id,
            'role_id' => $teacherRole->id,
            'name' => 'Guru Pengajar',
            'username' => 'guru',
            'email' => 'guru@school.test',
            'password' => bcrypt('GuruPass123!'),
            'is_active' => true,
        ]);

        $this->academicYear = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2025/2026',
            'start_date' => '2025-07-15',
            'end_date' => '2026-06-20',
            'is_active' => true,
        ]);

        $this->term = Term::create([
            'academic_year_id' => $this->academicYear->id,
            'name' => 'Semester Ganjil',
            'semester_type' => 'odd',
            'start_date' => '2025-07-15',
            'end_date' => '2025-12-20',
            'is_active' => true,
        ]);
    }

    public function test_admin_can_view_students_list(): void
    {
        Student::create([
            'school_id' => $this->school->id,
            'nis' => '1001',
            'name' => 'Aditya Pratama',
            'gender' => 'L',
        ]);

        $response = $this->actingAs($this->admin)->get('/akademik/siswa');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Academic/Students/Index')
            ->has('students.data', 1)
        );
    }

    public function test_admin_can_create_student(): void
    {
        $class = ClassGroup::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'grade_level' => 7,
            'name' => '7A',
            'capacity' => 32,
        ]);

        $response = $this->actingAs($this->admin)->post('/akademik/siswa', [
            'nis' => '1002',
            'name' => 'Bella Safira',
            'gender' => 'P',
            'class_group_id' => $class->id,
            'term_id' => $this->term->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('students', [
            'nis' => '1002',
            'name' => 'Bella Safira',
        ]);

        $this->assertDatabaseHas('enrollments', [
            'term_id' => $this->term->id,
            'class_group_id' => $class->id,
        ]);
    }

    public function test_admin_can_import_students_csv(): void
    {
        $csvContent = "nis,nisn,name,gender\n2001,0011,Siswa Import 1,L\n2002,0012,Siswa Import 2,P\n";
        $file = UploadedFile::fake()->createWithContent('students.csv', $csvContent);

        $response = $this->actingAs($this->admin)->post('/akademik/siswa/import', [
            'csv_file' => $file,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('students', ['nis' => '2001', 'name' => 'Siswa Import 1']);
        $this->assertDatabaseHas('students', ['nis' => '2002', 'name' => 'Siswa Import 2']);
    }

    public function test_can_download_student_import_template(): void
    {
        $response = $this->actingAs($this->admin)->get('/akademik/siswa/template');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('nis,nisn,name,gender', $response->getContent());
    }

    public function test_teacher_cannot_create_student(): void
    {
        $response = $this->actingAs($this->teacherUser)->post('/akademik/siswa', [
            'nis' => '9999',
            'name' => 'Siswa Ilegal',
            'gender' => 'L',
        ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_manage_teachers(): void
    {
        $response = $this->actingAs($this->admin)->post('/akademik/guru', [
            'nip' => '198501012010011005',
            'name' => 'Bambang Sudarsono, S.Pd',
            'gender' => 'L',
            'employment_status' => 'PNS',
            'is_active' => true,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('teachers', [
            'nip' => '198501012010011005',
            'name' => 'Bambang Sudarsono, S.Pd',
        ]);
    }

    public function test_admin_can_manage_classes_and_assign_homeroom(): void
    {
        $teacher = Teacher::create([
            'school_id' => $this->school->id,
            'name' => 'Bu Guru Wali',
            'gender' => 'P',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->post('/akademik/kelas', [
            'academic_year_id' => $this->academicYear->id,
            'grade_level' => 8,
            'name' => '8A',
            'capacity' => 30,
        ]);

        $response->assertRedirect();
        $class = ClassGroup::where('name', '8A')->firstOrFail();

        $assignResponse = $this->actingAs($this->admin)->post("/akademik/kelas/{$class->id}/wali-kelas", [
            'teacher_id' => $teacher->id,
        ]);

        $assignResponse->assertRedirect();
        $this->assertDatabaseHas('class_groups', [
            'id' => $class->id,
            'homeroom_teacher_id' => $teacher->id,
        ]);
    }
}
