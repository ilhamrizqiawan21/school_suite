<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Academic\Infrastructure\Models\AcademicYear;
use App\Modules\Academic\Infrastructure\Models\ClassGroup;
use App\Modules\Academic\Infrastructure\Models\Enrollment;
use App\Modules\Academic\Infrastructure\Models\Student;
use App\Modules\Academic\Infrastructure\Models\Teacher;
use App\Modules\Academic\Infrastructure\Models\Term;
use App\Modules\Core\Domain\PermissionEnum;
use App\Modules\Core\Infrastructure\Models\Permission;
use App\Modules\Core\Infrastructure\Models\Role;
use App\Modules\Core\Infrastructure\Models\School;
use App\Modules\Homeroom\Domain\Enums\AchievementLevelEnum;
use App\Modules\Homeroom\Domain\Enums\NoteAccessLevelEnum;
use App\Modules\Homeroom\Domain\Enums\NoteCategoryEnum;
use App\Modules\Homeroom\Infrastructure\Models\StudentNote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class HomeroomControllersTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    private User $homeroomTeacherUser;

    private User $regularTeacherUser;

    private User $studentUser;

    private Student $student;

    private Term $term;

    private ClassGroup $class7A;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create([
            'npsn' => '12345678',
            'name' => 'SMP Teladan Bangsa',
            'status' => 'active',
        ]);

        $adminRole = Role::create(['name' => 'admin', 'label' => 'Administrator', 'is_system' => true]);
        $teacherRole = Role::create(['name' => 'teacher', 'label' => 'Guru', 'is_system' => true]);
        $homeroomRole = Role::create(['name' => 'homeroom', 'label' => 'Wali Kelas', 'is_system' => true]);
        $studentRole = Role::create(['name' => 'student', 'label' => 'Siswa', 'is_system' => true]);

        // Permissions
        $viewNotes = Permission::create([
            'name' => PermissionEnum::HOMEROOM_NOTES_VIEW->value,
            'label' => 'Lihat Catatan Siswa',
            'module' => 'homeroom',
        ]);
        $manageNotes = Permission::create([
            'name' => PermissionEnum::HOMEROOM_NOTES_MANAGE->value,
            'label' => 'Kelola Catatan Siswa',
            'module' => 'homeroom',
        ]);

        $adminRole->permissions()->attach([$viewNotes->id, $manageNotes->id]);
        $teacherRole->permissions()->attach([$viewNotes->id, $manageNotes->id]);

        $this->admin = User::create([
            'school_id' => $this->school->id,
            'role_id' => $adminRole->id,
            'name' => 'Admin Sekolah',
            'username' => 'admin_test',
            'email' => 'admin@school.test',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        $this->homeroomTeacherUser = User::create([
            'school_id' => $this->school->id,
            'role_id' => $homeroomRole->id,
            'name' => 'Budi Santoso',
            'username' => 'budi_wali',
            'email' => 'homeroom@school.test',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        $this->regularTeacherUser = User::create([
            'school_id' => $this->school->id,
            'role_id' => $teacherRole->id,
            'name' => 'Siti Guru',
            'username' => 'siti_guru',
            'email' => 'otherteacher@school.test',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        $this->studentUser = User::create([
            'school_id' => $this->school->id,
            'role_id' => $studentRole->id,
            'name' => 'Andi Pratama User',
            'username' => 'andi_siswa',
            'email' => 'student@school.test',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        $academicYear = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2026/2027',
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
            'is_active' => true,
        ]);

        $this->term = Term::create([
            'academic_year_id' => $academicYear->id,
            'name' => 'Semester Ganjil',
            'semester' => 1,
            'start_date' => '2026-07-01',
            'end_date' => '2026-12-31',
            'is_active' => true,
        ]);

        $homeroomTeacher = Teacher::create([
            'school_id' => $this->school->id,
            'user_id' => $this->homeroomTeacherUser->id,
            'nip' => '198001012005011001',
            'name' => 'Budi Santoso, S.Pd',
            'gender' => 'L',
            'is_active' => true,
        ]);

        $this->class7A = ClassGroup::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $academicYear->id,
            'grade_level' => 7,
            'name' => 'VII-A',
            'homeroom_teacher_id' => $homeroomTeacher->id,
            'capacity' => 32,
        ]);

        $this->student = Student::create([
            'school_id' => $this->school->id,
            'user_id' => $this->studentUser->id,
            'nis' => '1001',
            'nisn' => '0012345678',
            'name' => 'Andi Pratama',
            'gender' => 'L',
            'enrollment_status' => 'active',
        ]);

        Enrollment::create([
            'student_id' => $this->student->id,
            'class_group_id' => $this->class7A->id,
            'term_id' => $this->term->id,
            'status' => 'active',
            'roll_number' => 1,
        ]);
    }

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $this->get('/wali-kelas')->assertRedirect('/login');
        $this->get('/wali-kelas/catatan')->assertRedirect('/login');
        $this->get('/wali-kelas/kedisiplinan')->assertRedirect('/login');
    }

    public function test_homeroom_dashboard_renders_for_homeroom_teacher(): void
    {
        $response = $this->actingAs($this->homeroomTeacherUser)->get('/wali-kelas');

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Homeroom/Dashboard/Index')
                ->has('selected_class')
                ->where('selected_class.id', $this->class7A->id)
                ->has('summary')
                ->has('stats')
            );
    }

    public function test_unauthorized_user_cannot_access_student_observations(): void
    {
        $response = $this->actingAs($this->studentUser)->get('/wali-kelas/catatan');

        $response->assertForbidden();
    }

    public function test_authorized_user_can_view_student_observations_page(): void
    {
        $response = $this->actingAs($this->homeroomTeacherUser)->get('/wali-kelas/catatan');

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Homeroom/Observations/Index')
                ->has('classes')
                ->has('students')
            );
    }

    public function test_authorized_user_can_create_student_observation_note(): void
    {
        $response = $this->actingAs($this->homeroomTeacherUser)->post('/wali-kelas/catatan', [
            'student_id' => $this->student->id,
            'term_id' => $this->term->id,
            'class_group_id' => $this->class7A->id,
            'title' => 'Perkembangan Minat Belajar',
            'content' => 'Siswa menunjukkan antusiasme tinggi pada pelajaran sains.',
            'category' => 'academic',
            'access_level' => 'public',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('message');

        $this->assertDatabaseHas('student_notes', [
            'student_id' => $this->student->id,
            'title' => 'Perkembangan Minat Belajar',
            'access_level' => 'public',
        ]);
    }

    public function test_observation_note_author_or_admin_can_delete_note(): void
    {
        $note = StudentNote::create([
            'school_id' => $this->school->id,
            'student_id' => $this->student->id,
            'author_id' => $this->homeroomTeacherUser->id,
            'term_id' => $this->term->id,
            'class_group_id' => $this->class7A->id,
            'title' => 'Catatan Percobaan',
            'content' => 'Isi catatan',
            'category' => NoteCategoryEnum::GENERAL,
            'access_level' => NoteAccessLevelEnum::INTERNAL,
        ]);

        $response = $this->actingAs($this->homeroomTeacherUser)->delete("/wali-kelas/catatan/{$note->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('student_notes', ['id' => $note->id]);
    }

    public function test_unauthorized_user_cannot_delete_others_note(): void
    {
        $note = StudentNote::create([
            'school_id' => $this->school->id,
            'student_id' => $this->student->id,
            'author_id' => $this->homeroomTeacherUser->id,
            'term_id' => $this->term->id,
            'class_group_id' => $this->class7A->id,
            'title' => 'Catatan Rahasia',
            'content' => 'Isi catatan rahasia',
            'category' => NoteCategoryEnum::COUNSELING,
            'access_level' => NoteAccessLevelEnum::CONFIDENTIAL,
        ]);

        $response = $this->actingAs($this->regularTeacherUser)->delete("/wali-kelas/catatan/{$note->id}");

        $response->assertForbidden();
        $this->assertDatabaseHas('student_notes', ['id' => $note->id]);
    }

    public function test_authorized_user_can_view_student_discipline_page(): void
    {
        $response = $this->actingAs($this->homeroomTeacherUser)->get('/wali-kelas/kedisiplinan');

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Homeroom/Discipline/Index')
                ->has('classes')
                ->has('students')
                ->has('incidents')
                ->has('achievements')
                ->has('assistances')
                ->has('dispensations')
            );
    }

    public function test_authorized_user_can_record_student_incident(): void
    {
        $response = $this->actingAs($this->homeroomTeacherUser)->post('/wali-kelas/kedisiplinan/insiden', [
            'student_id' => $this->student->id,
            'term_id' => $this->term->id,
            'class_group_id' => $this->class7A->id,
            'incident_date' => '2026-10-25',
            'title' => 'Terlambat Masuk Sekolah',
            'description' => 'Terlambat lebih dari 15 menit tanpa surat keterangan',
            'category' => 'Kedisiplinan Waktu',
            'penalty_points' => 5,
            'follow_up_action' => 'Diberikan peringatan lisan',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('message');

        $this->assertDatabaseHas('student_incidents', [
            'student_id' => $this->student->id,
            'penalty_points' => 5,
        ]);
    }

    public function test_authorized_user_can_record_student_achievement(): void
    {
        $response = $this->actingAs($this->homeroomTeacherUser)->post('/wali-kelas/kedisiplinan/prestasi', [
            'student_id' => $this->student->id,
            'term_id' => $this->term->id,
            'title' => 'Juara 1 Olimpiade Sains Kota',
            'achievement_type' => 'academic',
            'level' => 'city',
            'rank' => 'Juara 1',
            'event_date' => '2026-10-20',
            'organizer' => 'Dinas Pendidikan Kota',
            'notes' => 'Mendapatkan medali emas dan piagam penghargaan',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('message');

        $this->assertDatabaseHas('student_achievements', [
            'student_id' => $this->student->id,
            'rank' => 'Juara 1',
            'level' => AchievementLevelEnum::CITY->value,
        ]);
    }

    public function test_authorized_user_can_record_assistance(): void
    {
        $response = $this->actingAs($this->homeroomTeacherUser)->post('/wali-kelas/kedisiplinan/bantuan', [
            'student_id' => $this->student->id,
            'term_id' => $this->term->id,
            'date' => '2026-10-25',
            'type' => 'counseling',
            'summary' => 'Sesi konseling motivasi belajar siswa',
            'agreed_solution' => 'Siswa membuat jadwal belajar harian di rumah',
            'access_level' => 'internal',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('message');

        $this->assertDatabaseHas('assistance_records', [
            'student_id' => $this->student->id,
            'type' => 'counseling',
            'access_level' => 'internal',
        ]);
    }

    public function test_authorized_user_can_record_dispensation(): void
    {
        $response = $this->actingAs($this->homeroomTeacherUser)->post('/wali-kelas/kedisiplinan/dispensasi', [
            'student_id' => $this->student->id,
            'term_id' => $this->term->id,
            'start_date' => '2026-10-25',
            'end_date' => '2026-10-27',
            'type' => 'event',
            'title' => 'Dispensasi O2SN Atletik Tingkat Kota',
            'reason' => 'Mengikuti pemusatan latihan dan lomba atletik',
            'notes' => 'Surat tugas terlampir',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('message');

        $this->assertDatabaseHas('attendance_dispensations', [
            'student_id' => $this->student->id,
            'type' => 'event',
            'title' => 'Dispensasi O2SN Atletik Tingkat Kota',
            'status' => 'approved',
        ]);
    }
}
