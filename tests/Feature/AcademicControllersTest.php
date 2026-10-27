<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Academic\Application\Services\AcademicImportService;
use App\Modules\Academic\Infrastructure\Models\AcademicYear;
use App\Modules\Academic\Infrastructure\Models\ClassGroup;
use App\Modules\Academic\Infrastructure\Models\Student;
use App\Modules\Academic\Infrastructure\Models\Teacher;
use App\Modules\Academic\Infrastructure\Models\Term;
use App\Modules\Core\Infrastructure\Models\Role;
use App\Modules\Core\Infrastructure\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
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

    public function test_admin_can_download_student_pdf_with_active_filters_applied(): void
    {
        Student::create([
            'school_id' => $this->school->id,
            'nis' => '2001',
            'nisn' => '0012345678',
            'name' => 'Nadia Contoh',
            'gender' => 'P',
            'status' => 'active',
            'parent_name' => 'Wali Contoh',
            'parent_phone' => '081234567890',
        ]);
        Student::create([
            'school_id' => $this->school->id,
            'nis' => '2002',
            'name' => 'Siswa Lain',
            'gender' => 'L',
            'status' => 'graduated',
        ]);

        $response = $this->actingAs($this->admin)->get('/akademik/siswa/export/pdf?search=Nadia&status=active');

        $response->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('content-disposition', 'attachment; filename=data-siswa.pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
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

    public function test_admin_can_preview_student_csv_import(): void
    {
        $csvContent = "nis,nisn,name,gender\n2001,0011,Siswa Import 1,L\n2002,0012,Siswa Import 2,P\n";
        $file = UploadedFile::fake()->createWithContent('students.csv', $csvContent);

        $response = $this->actingAs($this->admin)->post('/akademik/siswa/import', [
            'csv_file' => $file,
        ]);

        $response->assertOk()->assertInertia(fn ($page) => $page
            ->component('Academic/Imports/Preview')
            ->where('entity', 'siswa')
            ->where('valid_count', 2)
            ->where('invalid_count', 0)
            ->has('token')
        );
        $this->assertDatabaseMissing('students', ['nis' => '2001']);
    }

    public function test_emadrasah_import_confirms_without_creating_users_and_rejects_wrong_class_term_pair(): void
    {
        $class = ClassGroup::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'grade_level' => 7,
            'name' => '7A',
            'capacity' => 32,
        ]);
        $csv = "NIS,NISN,NIK,Nama Lengkap,Kelas,L/P,Status\n00071,0012345678,3201010101010001,Siti Aminah,7A,P,Aktif\n";
        $file = UploadedFile::fake()->createWithContent('students.csv', $csv);
        $userCount = User::count();

        $preview = $this->actingAs($this->admin)->post('/akademik/siswa/import/preview', [
            'csv_file' => $file,
            'enroll_term_id' => $this->term->id,
            'enroll_class_group_id' => $class->id,
        ]);

        $preview->assertOk()->assertInertia(fn ($page) => $page
            ->component('Academic/Imports/Preview')
            ->where('valid_count', 1)
            ->where('invalid_count', 0)
            ->where('warnings.0', 'Kolom NIK terdeteksi tetapi tidak disimpan oleh data siswa School Suite.')
        );
        $page = $preview->viewData('page');
        $page = is_string($page) ? json_decode($page, true) : $page;
        $token = $page['props']['token'] ?? $page['page']['props']['token'] ?? null;
        $this->assertNotNull($token);

        $this->actingAs($this->admin)->post("/akademik/siswa/import/{$token}/confirm")->assertRedirect();
        $this->assertDatabaseHas('students', ['school_id' => $this->school->id, 'nis' => '00071', 'nisn' => '0012345678', 'user_id' => null]);
        $this->assertDatabaseHas('enrollments', ['term_id' => $this->term->id, 'class_group_id' => $class->id]);
        $this->assertSame($userCount, User::count());

        $retry = UploadedFile::fake()->createWithContent('students.csv', $csv);
        $this->actingAs($this->admin)->post('/akademik/siswa/import/preview', [
            'csv_file' => $retry,
            'enroll_term_id' => $this->term->id,
            'enroll_class_group_id' => $class->id,
        ])->assertInertia(fn ($page) => $page->where('valid_count', 0)->where('invalid_count', 1));
    }

    public function test_student_import_rejects_class_and_term_from_different_academic_years(): void
    {
        $otherYear = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2026/2027',
            'start_date' => '2026-07-15',
            'end_date' => '2027-06-20',
            'is_active' => false,
        ]);
        $class = ClassGroup::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $otherYear->id,
            'grade_level' => 7,
            'name' => '7A',
            'capacity' => 32,
        ]);
        $file = UploadedFile::fake()->createWithContent('students.csv', "NIS,Nama Lengkap,L/P\n1234,Budi,L\n");

        $this->actingAs($this->admin)->post('/akademik/siswa/import/preview', [
            'csv_file' => $file,
            'enroll_term_id' => $this->term->id,
            'enroll_class_group_id' => $class->id,
        ])->assertSessionHasErrors('enroll_class_group_id');
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

    public function test_student_creation_rolls_back_when_enrollment_context_is_inconsistent(): void
    {
        $secondYear = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2026/2027',
            'start_date' => '2026-07-15',
            'end_date' => '2027-06-20',
            'is_active' => false,
        ]);

        $secondTerm = Term::create([
            'academic_year_id' => $secondYear->id,
            'name' => 'Semester Ganjil',
            'semester_type' => 'odd',
            'start_date' => '2026-07-15',
            'end_date' => '2026-12-20',
            'is_active' => false,
        ]);

        $class = ClassGroup::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'grade_level' => 7,
            'name' => '7A',
            'capacity' => 32,
        ]);

        $response = $this->actingAs($this->admin)->post('/akademik/siswa', [
            'nis' => '9001',
            'name' => 'Siswa Rollback',
            'gender' => 'L',
            'class_group_id' => $class->id,
            'term_id' => $secondTerm->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseMissing('students', ['nis' => '9001']);
    }

    public function test_class_creation_rolls_back_when_homeroom_assignment_fails(): void
    {
        $teacher = Teacher::create([
            'school_id' => $this->school->id,
            'name' => 'Wali Kelas Existing',
            'gender' => 'P',
            'is_active' => true,
        ]);

        ClassGroup::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'grade_level' => 7,
            'name' => '7A',
            'homeroom_teacher_id' => $teacher->id,
            'capacity' => 32,
        ]);

        $response = $this->actingAs($this->admin)->post('/akademik/kelas', [
            'academic_year_id' => $this->academicYear->id,
            'grade_level' => 8,
            'name' => '8A',
            'capacity' => 32,
            'homeroom_teacher_id' => $teacher->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseMissing('class_groups', ['name' => '8A']);
    }

    public function test_class_creation_rejects_academic_year_from_another_school(): void
    {
        $otherSchool = School::create([
            'npsn' => '87654321',
            'name' => 'SMP Sekolah Lain',
            'status' => 'active',
        ]);

        $otherYear = AcademicYear::create([
            'school_id' => $otherSchool->id,
            'name' => '2026/2027',
            'start_date' => '2026-07-15',
            'end_date' => '2027-06-20',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->post('/akademik/kelas', [
            'academic_year_id' => $otherYear->id,
            'grade_level' => 8,
            'name' => '8A',
            'capacity' => 32,
        ]);

        $response->assertSessionHasErrors('academic_year_id');
        $this->assertDatabaseMissing('class_groups', ['name' => '8A']);
    }

    public function test_student_list_defaults_to_all_classes_and_counts_school_statuses(): void
    {
        $class = ClassGroup::create(['school_id' => $this->school->id, 'academic_year_id' => $this->academicYear->id, 'name' => '7A', 'grade_level' => 7]);
        $first = Student::create(['school_id' => $this->school->id, 'nis' => '7001', 'name' => 'Siswa Satu', 'gender' => 'L']);
        $second = Student::create(['school_id' => $this->school->id, 'nis' => '7002', 'name' => 'Siswa Dua', 'gender' => 'P', 'status' => 'graduated']);
        $first->enrollments()->create(['term_id' => $this->term->id, 'class_group_id' => $class->id, 'status' => 'active']);
        $this->actingAs($this->admin)->get('/akademik/siswa')->assertInertia(fn ($page) => $page
            ->has('students.data', 2)->where('filters.class_group_id', null)
            ->where('stats.active', 1)->where('stats.graduated', 1)->where('students.data.0.account_status', 'no_account'));
        $this->get('/akademik/siswa?class_group_id='.$class->id)->assertInertia(fn ($page) => $page->has('students.data', 1)->where('students.data.0.id', $first->id));
        $this->get('/akademik/siswa?search=Siswa+Dua&status=graduated')->assertInertia(fn ($page) => $page->has('students.data', 1)->where('students.data.0.id', $second->id));
    }

    public function test_student_reset_issues_temporary_password_and_audits_without_secret(): void
    {
        $role = Role::create(['name' => 'student', 'label' => 'Siswa', 'is_system' => true]);
        $user = User::factory()->create(['school_id' => $this->school->id, 'role_id' => $role->id, 'must_change_password' => false, 'password_changed_at' => now()]);
        $student = Student::create(['school_id' => $this->school->id, 'user_id' => $user->id, 'nis' => '7010', 'name' => 'Siswa Reset', 'gender' => 'L']);
        $response = $this->actingAs($this->admin)->post('/akademik/siswa/'.$student->id.'/reset-password')->assertRedirect();
        preg_match('/Siswa Reset: ([A-Za-z0-9]+)\./', session('message'), $matches);
        $this->assertNotEmpty($matches[1] ?? null);
        $this->assertTrue(Hash::check($matches[1], $user->fresh()->password));
        $this->assertTrue($user->fresh()->must_change_password);
        $this->assertNull($user->fresh()->password_changed_at);
        $this->assertDatabaseHas('audit_events', ['event_type' => 'academic.student.password_reset', 'target_id' => (string) $student->id]);
        $this->assertStringNotContainsString($matches[1], (string) DB::table('audit_events')->where('event_type', 'academic.student.password_reset')->value('new_values'));
        $this->get('/akademik/siswa')->assertInertia(fn ($page) => $page->where('students.data.0.account_status', 'default')->missing('students.data.0.user'));
    }

    public function test_student_actions_reject_other_schools_and_read_only_roles(): void
    {
        $other = School::create(['npsn' => '87654321', 'name' => 'Sekolah Lain', 'status' => 'active']);
        $student = Student::create(['school_id' => $other->id, 'nis' => '7020', 'name' => 'Siswa Lain', 'gender' => 'L']);
        $this->actingAs($this->admin)->delete('/akademik/siswa/'.$student->id)->assertForbidden();
        $this->post('/akademik/siswa/'.$student->id.'/reset-password')->assertForbidden();
        $this->put('/akademik/siswa/'.$student->id, [])->assertForbidden();
        $own = Student::create(['school_id' => $this->school->id, 'nis' => '7021', 'name' => 'Siswa Sendiri', 'gender' => 'P']);
        $this->actingAs($this->teacherUser)->delete('/akademik/siswa/'.$own->id)->assertForbidden();
        $this->post('/akademik/siswa/'.$own->id.'/reset-password')->assertForbidden();
        $this->get('/akademik/siswa/template?format=xlsx')->assertForbidden();
    }

    public function test_student_delete_preserves_academic_history_and_unrelated_account(): void
    {
        $class = ClassGroup::create(['school_id' => $this->school->id, 'academic_year_id' => $this->academicYear->id, 'name' => '7A', 'grade_level' => 7]);
        $student = Student::create(['school_id' => $this->school->id, 'nis' => '7030', 'name' => 'Siswa Riwayat', 'gender' => 'L']);
        $student->enrollments()->create(['term_id' => $this->term->id, 'class_group_id' => $class->id, 'status' => 'active']);
        $this->actingAs($this->admin)->delete('/akademik/siswa/'.$student->id)->assertSessionHasErrors('student');
        $this->assertModelExists($student);
        $empty = Student::create(['school_id' => $this->school->id, 'user_id' => $this->teacherUser->id, 'nis' => '7031', 'name' => 'Siswa Kosong', 'gender' => 'P']);
        $this->delete('/akademik/siswa/'.$empty->id)->assertRedirect()->assertSessionHasNoErrors();
        $this->assertModelMissing($empty);
        $this->assertModelExists($this->teacherUser);
    }

    public function test_student_excel_download_preserves_text_and_current_filters(): void
    {
        Student::create(['school_id' => $this->school->id, 'nis' => '00071', 'nisn' => '0012345678', 'name' => '=SUM(1,2)', 'gender' => 'L']);
        Student::create(['school_id' => $this->school->id, 'nis' => '00072', 'name' => 'Tidak Dipilih', 'gender' => 'P']);
        $response = $this->actingAs($this->admin)->get('/akademik/siswa/export/excel?search=00071')->assertOk();
        $file = tempnam(sys_get_temp_dir(), 'student-export-');
        try {
            file_put_contents($file, $response->streamedContent());
            $book = IOFactory::load($file);
            $sheet = $book->getActiveSheet();
            $this->assertSame(2, $sheet->getHighestDataRow());
            $this->assertSame('00071', $sheet->getCell('A2')->getValue());
            $this->assertSame('0012345678', $sheet->getCell('B2')->getValue());
            $this->assertSame('=SUM(1,2)', $sheet->getCell('C2')->getValue());
            $this->assertSame('s', $sheet->getCell('C2')->getDataType());
            $book->disconnectWorksheets();
            $template = $this->get('/akademik/siswa/template?format=xlsx')->assertOk();
            file_put_contents($file, $template->streamedContent());
            $parsed = app(AcademicImportService::class)->parseStudentImportFile($file, 'xlsx');
            $this->assertSame('1001', $parsed['rows'][0]['nis']);
        } finally {
            unlink($file);
        }
    }

    public function test_student_reset_rejects_missing_or_privileged_accounts(): void
    {
        $student = Student::create(['school_id' => $this->school->id, 'nis' => '7040', 'name' => 'Tanpa Akun', 'gender' => 'L']);
        $this->actingAs($this->admin)->post('/akademik/siswa/'.$student->id.'/reset-password')->assertSessionHasErrors('student');
        $student->update(['user_id' => $this->admin->id]);
        $hash = $this->admin->password;
        $this->post('/akademik/siswa/'.$student->id.'/reset-password')->assertSessionHasErrors('student');
        $this->assertSame($hash, $this->admin->fresh()->password);
    }

    public function test_student_list_and_export_do_not_include_other_school_data(): void
    {
        $other = School::create(['npsn' => '87654320', 'name' => 'Sekolah Asing', 'status' => 'active']);
        Student::create(['school_id' => $other->id, 'nis' => '7050', 'name' => 'Siswa Asing', 'gender' => 'L']);
        $this->actingAs($this->admin)->get('/akademik/siswa')->assertInertia(fn ($page) => $page->has('students.data', 0)->where('stats.active', 0));
        $response = $this->get('/akademik/siswa/export/excel')->assertOk();
        $file = tempnam(sys_get_temp_dir(), 'student-export-');
        try {
            file_put_contents($file, $response->streamedContent());
            $book = IOFactory::load($file);
            $this->assertSame(1, $book->getActiveSheet()->getHighestDataRow());
            $book->disconnectWorksheets();
        } finally {
            unlink($file);
        }
    }

    public function test_teacher_list_filters_and_stats_are_school_scoped(): void
    {
        $teacher = Teacher::create(['school_id' => $this->school->id, 'name' => 'Guru Aktif', 'gender' => 'L', 'employment_status' => 'PNS']);
        Teacher::create(['school_id' => $this->school->id, 'name' => 'Guru Nonaktif', 'gender' => 'P', 'is_active' => false, 'employment_status' => 'GTY']);
        $other = School::create(['npsn' => '87654319', 'name' => 'Sekolah Lain', 'status' => 'active']);
        Teacher::create(['school_id' => $other->id, 'name' => 'Guru Asing', 'gender' => 'L']);
        ClassGroup::create(['school_id' => $this->school->id, 'academic_year_id' => $this->academicYear->id, 'name' => '7A', 'grade_level' => 7, 'homeroom_teacher_id' => $teacher->id]);
        $this->actingAs($this->admin)->get('/akademik/guru?is_active=0&employment_status=GTY')->assertInertia(fn ($page) => $page
            ->has('teachers.data', 1)->where('teachers.data.0.name', 'Guru Nonaktif')
            ->where('stats.total', 2)->where('stats.active', 1)->where('stats.inactive', 1)->where('teachers.data.0.account_status', 'no_account'));
        $this->get('/akademik/guru?search=Guru+Aktif')->assertInertia(fn ($page) => $page->has('teachers.data', 1)->has('teachers.data.0.homeroom_class_groups', 1));
        $this->academicYear->update(['is_active' => false]);
        $this->get('/akademik/guru?search=Guru+Aktif')->assertInertia(fn ($page) => $page->has('teachers.data.0.homeroom_class_groups', 0));
    }

    public function test_teacher_update_and_delete_preserve_assignments_and_accounts(): void
    {
        $teacher = Teacher::create(['school_id' => $this->school->id, 'user_id' => $this->teacherUser->id, 'name' => 'Guru Uji', 'gender' => 'L']);
        $this->actingAs($this->admin)->put('/akademik/guru/'.$teacher->id, ['name' => 'Guru Diubah', 'gender' => 'P', 'is_active' => false, 'phone' => '0812345'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('teachers', ['id' => $teacher->id, 'name' => 'Guru Diubah', 'is_active' => false]);
        $class = ClassGroup::create(['school_id' => $this->school->id, 'academic_year_id' => $this->academicYear->id, 'name' => '7A', 'grade_level' => 7, 'homeroom_teacher_id' => $teacher->id]);
        $this->delete('/akademik/guru/'.$teacher->id)->assertSessionHasErrors('teacher');
        $this->assertModelExists($teacher);
        $class->update(['homeroom_teacher_id' => null]);
        $this->delete('/akademik/guru/'.$teacher->id)->assertSessionHasNoErrors();
        $this->assertModelMissing($teacher);
        $this->assertModelExists($this->teacherUser);
        $this->assertDatabaseHas('audit_events', ['event_type' => 'academic.teacher.deleted']);
    }

    public function test_teacher_write_actions_require_manager_and_same_school(): void
    {
        $other = School::create(['npsn' => '87654318', 'name' => 'Sekolah Lain', 'status' => 'active']);
        $foreign = Teacher::create(['school_id' => $other->id, 'name' => 'Guru Asing', 'gender' => 'L']);
        $this->actingAs($this->admin)->put('/akademik/guru/'.$foreign->id, [])->assertForbidden();
        $this->delete('/akademik/guru/'.$foreign->id)->assertForbidden();
        $this->post('/akademik/guru/'.$foreign->id.'/reset-password')->assertForbidden();
        $own = Teacher::create(['school_id' => $this->school->id, 'name' => 'Guru Sekolah', 'gender' => 'L']);
        $this->actingAs($this->teacherUser)->put('/akademik/guru/'.$own->id, [])->assertForbidden();
        $this->delete('/akademik/guru/'.$own->id)->assertForbidden();
        $this->post('/akademik/guru/'.$own->id.'/reset-password')->assertForbidden();
        $this->get('/akademik/guru/template?format=xlsx')->assertForbidden();
    }

    public function test_teacher_reset_requires_teacher_account_and_hides_credentials(): void
    {
        $teacher = Teacher::create(['school_id' => $this->school->id, 'user_id' => $this->teacherUser->id, 'name' => 'Guru Reset', 'gender' => 'L']);
        $this->actingAs($this->admin)->post('/akademik/guru/'.$teacher->id.'/reset-password')->assertSessionHasNoErrors();
        preg_match('/Guru Reset: ([A-Za-z0-9]+)\./', session('message'), $matches);
        $this->assertTrue(Hash::check($matches[1], $this->teacherUser->fresh()->password));
        $this->assertTrue($this->teacherUser->fresh()->must_change_password);
        $this->assertNull($this->teacherUser->fresh()->password_changed_at);
        $this->get('/akademik/guru')->assertInertia(fn ($page) => $page->where('teachers.data.0.account_status', 'default')->missing('teachers.data.0.user'));
        $this->assertStringNotContainsString($matches[1], (string) DB::table('audit_events')->where('event_type', 'academic.teacher.password_reset')->value('new_values'));
        $teacher->update(['user_id' => $this->admin->id]);
        $this->post('/akademik/guru/'.$teacher->id.'/reset-password')->assertSessionHasErrors('teacher');
        $teacher->update(['user_id' => null]);
        $this->post('/akademik/guru/'.$teacher->id.'/reset-password')->assertSessionHasErrors('teacher');
    }

    public function test_teacher_excel_template_round_trip_and_export_text_safety(): void
    {
        $template = $this->actingAs($this->admin)->get('/akademik/guru/template?format=xlsx')->assertOk();
        $file = tempnam(sys_get_temp_dir(), 'teacher-template-');
        try {
            file_put_contents($file, $template->streamedContent());
            $upload = new UploadedFile($file, 'guru.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
            $preview = $this->post('/akademik/guru/import/preview', ['csv_file' => $upload])->assertInertia(fn ($page) => $page->where('valid_count', 1)->where('invalid_count', 0));
            $token = $preview->original->getData()['page']['props']['token'];
            $this->assertDatabaseMissing('teachers', ['name' => 'Guru Contoh']);
            $this->post('/akademik/guru/import/'.$token.'/confirm')->assertSessionHasNoErrors();
            $this->assertDatabaseHas('teachers', ['nip' => '198501012010011001', 'phone' => '081234567891', 'user_id' => null]);
            Teacher::create(['school_id' => $this->school->id, 'name' => '=SUM(1,2)', 'nip' => '0000123', 'gender' => 'L', 'is_active' => false]);
            $export = $this->get('/akademik/guru/export/excel?is_active=0&search=0000123')->assertOk();
            file_put_contents($file, $export->streamedContent());
            $book = IOFactory::load($file);
            $this->assertSame(2, $book->getActiveSheet()->getHighestDataRow());
            $this->assertSame('0000123', $book->getActiveSheet()->getCell('A2')->getValue());
            $this->assertSame('s', $book->getActiveSheet()->getCell('B2')->getDataType());
            $book->disconnectWorksheets();
        } finally {
            unlink($file);
        }
    }

    public function test_teacher_import_rejects_excessive_rows_and_invalid_fields(): void
    {
        $this->actingAs($this->admin)->post('/akademik/guru/import/preview', ['csv_content' => "nip,name,gender\n".str_repeat(",Guru,L\n", 1001)])->assertSessionHasErrors('csv_file');
        $this->post('/akademik/guru/import/preview', ['csv_content' => 'nip,name,gender,phone'."\n,".str_repeat('G', 151).',L,'.str_repeat('1', 31)])->assertInertia(fn ($page) => $page->where('valid_count', 0)->where('invalid_count', 1));
    }

    public function test_teacher_import_rejects_formula_cells_and_malformed_csv(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'teacher-formula-');
        $book = new Spreadsheet;
        try {
            $book->getActiveSheet()->fromArray([['nip', 'name', 'gender'], ['123', '=1+1', 'L']]);
            (new Xlsx($book))->save($file);
            $upload = new UploadedFile($file, 'guru.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
            $this->actingAs($this->admin)->post('/akademik/guru/import/preview', ['csv_file' => $upload])->assertSessionHasErrors('csv_file');
            $csv = UploadedFile::fake()->createWithContent('guru.csv', "nip,name,gender\n123,Guru,L,kolom tambahan\n");
            $this->post('/akademik/guru/import/preview', ['csv_file' => $csv])->assertSessionHasErrors('csv_file');
            $this->assertDatabaseCount('teachers', 0);
        } finally {
            $book->disconnectWorksheets();
            unlink($file);
        }
    }

    public function test_class_counts_use_selected_semester_without_exposing_student_details(): void
    {
        $class = ClassGroup::create(['school_id' => $this->school->id, 'academic_year_id' => $this->academicYear->id, 'name' => '7A', 'grade_level' => 7]);
        $second = Term::create(['academic_year_id' => $this->academicYear->id, 'name' => 'Genap', 'semester_type' => 'even', 'start_date' => '2026-01-01', 'end_date' => '2026-06-20', 'is_active' => false]);
        $student = Student::create(['school_id' => $this->school->id, 'nis' => '8010', 'name' => 'Siswa Uji', 'gender' => 'L']);
        foreach ([$this->term, $second] as $term) {
            $student->enrollments()->create(['term_id' => $term->id, 'class_group_id' => $class->id, 'status' => 'active']);
        }
        $this->actingAs($this->admin)->get('/akademik/kelas')->assertInertia(fn ($page) => $page
            ->where('classes.0.enrollments_count', 1)->where('stats.students', 1)->where('active_term.id', $this->term->id)->missing('classes.0.enrollments'));
        $this->get('/akademik/kelas?term_id='.$second->id)->assertInertia(fn ($page) => $page->where('active_term.id', $second->id)->where('classes.0.enrollments_count', 1));
        $student->enrollments()->where('term_id', $second->id)->update(['status' => 'withdrawn']);
        $this->get('/akademik/kelas?term_id='.$second->id)->assertInertia(fn ($page) => $page->where('classes.0.enrollments_count', 0));
    }

    public function test_class_update_checks_capacity_per_semester_and_preserves_grade_history(): void
    {
        $class = ClassGroup::create(['school_id' => $this->school->id, 'academic_year_id' => $this->academicYear->id, 'name' => '7A', 'grade_level' => 7, 'capacity' => 32]);
        $second = Term::create(['academic_year_id' => $this->academicYear->id, 'name' => 'Genap', 'semester_type' => 'even', 'start_date' => '2026-01-01', 'end_date' => '2026-06-20', 'is_active' => false]);
        foreach (['8011', '8012'] as $nis) {
            $student = Student::create(['school_id' => $this->school->id, 'nis' => $nis, 'name' => 'Siswa '.$nis, 'gender' => 'L']);
            foreach ([$this->term, $second] as $term) {
                $student->enrollments()->create(['term_id' => $term->id, 'class_group_id' => $class->id, 'status' => 'active']);
            }
        }
        $url = '/akademik/kelas/'.$class->id;
        $this->actingAs($this->admin)->put($url, ['name' => '7B', 'grade_level' => 7, 'capacity' => 1])->assertSessionHasErrors('capacity');
        $this->put($url, ['name' => '7B', 'grade_level' => 8, 'capacity' => 2])->assertSessionHasErrors('grade_level');
        $this->put($url, ['name' => '7B', 'grade_level' => 7, 'capacity' => 2])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('class_groups', ['id' => $class->id, 'name' => '7B', 'capacity' => 2, 'grade_level' => 7]);
        $this->assertDatabaseCount('enrollments', 4);
        $this->assertDatabaseHas('audit_events', ['event_type' => 'academic.class_group.updated']);
    }

    public function test_class_delete_blocks_history_and_homeroom_but_allows_unused_class(): void
    {
        $teacher = Teacher::create(['school_id' => $this->school->id, 'name' => 'Wali Uji', 'gender' => 'P']);
        $class = ClassGroup::create(['school_id' => $this->school->id, 'academic_year_id' => $this->academicYear->id, 'name' => '7A', 'grade_level' => 7, 'homeroom_teacher_id' => $teacher->id]);
        $this->actingAs($this->admin)->delete('/akademik/kelas/'.$class->id)->assertSessionHasErrors('class_group');
        $this->post('/akademik/kelas/'.$class->id.'/wali-kelas', ['teacher_id' => null])->assertSessionHasNoErrors();
        $this->assertNull($class->fresh()->homeroom_teacher_id);
        $this->assertDatabaseHas('audit_events', ['event_type' => 'academic.homeroom_teacher.unassigned']);
        $student = Student::create(['school_id' => $this->school->id, 'nis' => '8020', 'name' => 'Siswa Arsip', 'gender' => 'L']);
        $student->enrollments()->create(['term_id' => $this->term->id, 'class_group_id' => $class->id, 'status' => 'withdrawn']);
        $this->delete('/akademik/kelas/'.$class->id)->assertSessionHasErrors('class_group');
        $this->assertModelExists($class);
        $empty = ClassGroup::create(['school_id' => $this->school->id, 'academic_year_id' => $this->academicYear->id, 'name' => '7C', 'grade_level' => 7]);
        $this->delete('/akademik/kelas/'.$empty->id)->assertSessionHasNoErrors();
        $this->assertModelMissing($empty);
        $this->assertDatabaseHas('audit_events', ['event_type' => 'academic.class_group.deleted']);
    }

    public function test_class_actions_reject_cross_school_and_read_only_roles(): void
    {
        $other = School::create(['npsn' => '87654317', 'name' => 'Sekolah Lain', 'status' => 'active']);
        $year = AcademicYear::create(['school_id' => $other->id, 'name' => '2026/2027', 'start_date' => '2026-07-01', 'end_date' => '2027-06-30']);
        $foreign = ClassGroup::create(['school_id' => $other->id, 'academic_year_id' => $year->id, 'name' => '7A', 'grade_level' => 7]);
        $this->actingAs($this->admin)->put('/akademik/kelas/'.$foreign->id, [])->assertForbidden();
        $this->delete('/akademik/kelas/'.$foreign->id)->assertForbidden();
        $this->post('/akademik/kelas/'.$foreign->id.'/wali-kelas', ['teacher_id' => null])->assertForbidden();
        $this->get('/akademik/kelas?academic_year_id='.$year->id)->assertSessionHasErrors('academic_year_id');
        $own = ClassGroup::create(['school_id' => $this->school->id, 'academic_year_id' => $this->academicYear->id, 'name' => '7B', 'grade_level' => 7]);
        $this->actingAs($this->teacherUser)->put('/akademik/kelas/'.$own->id, [])->assertForbidden();
        $this->delete('/akademik/kelas/'.$own->id)->assertForbidden();
        $this->post('/akademik/kelas/'.$own->id.'/wali-kelas', ['teacher_id' => null])->assertForbidden();
        $this->get('/akademik/kelas')->assertInertia(fn ($page) => $page->where('can_manage', false)->has('classes', 1));
    }

    public function test_class_period_selection_rejects_term_from_another_year_and_handles_no_term(): void
    {
        $year = AcademicYear::create(['school_id' => $this->school->id, 'name' => '2026/2027', 'start_date' => '2026-07-01', 'end_date' => '2027-06-30']);
        $this->actingAs($this->admin)->get('/akademik/kelas?academic_year_id='.$year->id.'&term_id='.$this->term->id)->assertUnprocessable();
        $this->get('/akademik/kelas?academic_year_id='.$year->id)->assertInertia(fn ($page) => $page->where('active_year.id', $year->id)->where('active_term', null)->where('stats.students', 0));
    }

    public function test_class_homeroom_rejects_inactive_or_already_assigned_teacher(): void
    {
        $teacher = Teacher::create(['school_id' => $this->school->id, 'name' => 'Wali Uji', 'gender' => 'P', 'is_active' => false]);
        $class = ClassGroup::create(['school_id' => $this->school->id, 'academic_year_id' => $this->academicYear->id, 'name' => '7A', 'grade_level' => 7]);
        $other = ClassGroup::create(['school_id' => $this->school->id, 'academic_year_id' => $this->academicYear->id, 'name' => '7B', 'grade_level' => 7]);
        $this->actingAs($this->admin)->post('/akademik/kelas/'.$class->id.'/wali-kelas', ['teacher_id' => $teacher->id])->assertSessionHasErrors('teacher_id');
        $teacher->update(['is_active' => true]);
        $this->post('/akademik/kelas/'.$class->id.'/wali-kelas', ['teacher_id' => $teacher->id])->assertSessionHasNoErrors();
        $this->post('/akademik/kelas/'.$other->id.'/wali-kelas', ['teacher_id' => $teacher->id])->assertSessionHasErrors('teacher_id');
        $this->assertNull($other->fresh()->homeroom_teacher_id);
    }
}
