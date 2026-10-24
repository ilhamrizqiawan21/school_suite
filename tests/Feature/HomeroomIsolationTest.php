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
use App\Modules\Core\Infrastructure\Models\Role;
use App\Modules\Core\Infrastructure\Models\School;
use App\Modules\Homeroom\Application\Services\StudentObservationService;
use App\Modules\Homeroom\Domain\Enums\NoteAccessLevelEnum;
use App\Modules\Homeroom\Domain\Enums\NoteCategoryEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeroomIsolationTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    private User $principal;

    private User $homeroomTeacherUser;

    private User $regularTeacherUser;

    private User $studentUser;

    private Student $student;

    private Term $term;

    private ClassGroup $class7A;

    private StudentObservationService $observationService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create([
            'npsn' => '12345678',
            'name' => 'SMP Teladan Bangsa',
            'status' => 'active',
        ]);

        $adminRole = Role::create(['name' => 'admin', 'label' => 'Administrator', 'is_system' => true]);
        $principalRole = Role::create(['name' => 'principal', 'label' => 'Kepala Sekolah', 'is_system' => true]);
        $homeroomRole = Role::create(['name' => 'homeroom', 'label' => 'Wali Kelas', 'is_system' => true]);
        $teacherRole = Role::create(['name' => 'teacher', 'label' => 'Guru', 'is_system' => true]);
        $studentRole = Role::create(['name' => 'student', 'label' => 'Siswa', 'is_system' => true]);

        $this->admin = User::create([
            'school_id' => $this->school->id,
            'role_id' => $adminRole->id,
            'name' => 'Admin User',
            'username' => 'admin',
            'email' => 'admin@school.test',
            'password' => bcrypt('Admin123!'),
            'is_active' => true,
        ]);

        $this->principal = User::create([
            'school_id' => $this->school->id,
            'role_id' => $principalRole->id,
            'name' => 'Kepala Sekolah',
            'username' => 'kepsek',
            'email' => 'kepsek@school.test',
            'password' => bcrypt('Kepsek123!'),
            'is_active' => true,
        ]);

        $this->homeroomTeacherUser = User::create([
            'school_id' => $this->school->id,
            'role_id' => $homeroomRole->id,
            'name' => 'Wali Kelas 7A',
            'username' => 'walikelas7a',
            'email' => 'wali7a@school.test',
            'password' => bcrypt('Wali123!'),
            'is_active' => true,
        ]);

        $this->regularTeacherUser = User::create([
            'school_id' => $this->school->id,
            'role_id' => $teacherRole->id,
            'name' => 'Guru Biasa',
            'username' => 'gurubiasa',
            'email' => 'guru@school.test',
            'password' => bcrypt('Guru123!'),
            'is_active' => true,
        ]);

        $this->studentUser = User::create([
            'school_id' => $this->school->id,
            'role_id' => $studentRole->id,
            'name' => 'Siswa A',
            'username' => 'siswa_a',
            'email' => 'siswa@school.test',
            'password' => bcrypt('Siswa123!'),
            'is_active' => true,
        ]);

        $homeroomTeacher = Teacher::create([
            'school_id' => $this->school->id,
            'user_id' => $this->homeroomTeacherUser->id,
            'nip' => '198001012005011005',
            'name' => 'Ibu Wali Kelas 7A, S.Pd',
            'gender' => 'P',
        ]);

        $year = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2025/2026',
            'start_date' => '2025-07-15',
            'end_date' => '2026-06-20',
            'is_active' => true,
        ]);

        $this->term = Term::create([
            'academic_year_id' => $year->id,
            'name' => 'Semester Ganjil',
            'semester_type' => 'odd',
            'start_date' => '2025-07-15',
            'end_date' => '2025-12-20',
            'is_active' => true,
        ]);

        $this->class7A = ClassGroup::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $year->id,
            'grade_level' => 7,
            'name' => '7A',
            'homeroom_teacher_id' => $homeroomTeacher->id,
            'capacity' => 32,
        ]);

        $this->student = Student::create([
            'school_id' => $this->school->id,
            'user_id' => $this->studentUser->id,
            'nis' => '1001',
            'name' => 'Dimas Arya',
            'gender' => 'L',
        ]);

        Enrollment::create([
            'term_id' => $this->term->id,
            'class_group_id' => $this->class7A->id,
            'student_id' => $this->student->id,
            'roll_number' => 1,
        ]);

        $this->observationService = app(StudentObservationService::class);
    }

    public function test_ss_f_006_confidential_notes_isolated_from_regular_teachers_and_students(): void
    {
        // 1. Buat catatan Publik
        $this->observationService->createNote([
            'student_id' => $this->student->id,
            'term_id' => $this->term->id,
            'class_group_id' => $this->class7A->id,
            'title' => 'Catatan Kerajinan Siswa',
            'content' => 'Siswa selalu aktif bertanya dalam kelas.',
            'category' => NoteCategoryEnum::ACADEMIC->value,
            'access_level' => NoteAccessLevelEnum::PUBLIC->value,
        ], $this->homeroomTeacherUser);

        // 2. Buat catatan Internal
        $this->observationService->createNote([
            'student_id' => $this->student->id,
            'term_id' => $this->term->id,
            'class_group_id' => $this->class7A->id,
            'title' => 'Catatan Kedisiplinan Guru',
            'content' => 'Perlu diingatkan mengenai kerapian seragam pramuka.',
            'category' => NoteCategoryEnum::BEHAVIOR->value,
            'access_level' => NoteAccessLevelEnum::INTERNAL->value,
        ], $this->homeroomTeacherUser);

        // 3. Buat catatan Rahasia / Confidential (BK / Kondisi Khusus)
        $this->observationService->createNote([
            'student_id' => $this->student->id,
            'term_id' => $this->term->id,
            'class_group_id' => $this->class7A->id,
            'title' => 'Catatan Khusus Keluarga Siswa (BK)',
            'content' => 'Orang tua sedang dalam proses perceraian, siswa membutuhkan pendampingan psikologis.',
            'category' => NoteCategoryEnum::COUNSELING->value,
            'access_level' => NoteAccessLevelEnum::CONFIDENTIAL->value,
        ], $this->homeroomTeacherUser);

        // BUKTI 1: Wali kelas 7A dapat melihat seluruh 3 catatan termasuk Confidential
        $homeroomNotes = $this->observationService->getNotesForStudent($this->student->id, $this->homeroomTeacherUser);
        $this->assertCount(3, $homeroomNotes);
        $this->assertTrue($homeroomNotes->contains('access_level', NoteAccessLevelEnum::CONFIDENTIAL->value));

        // BUKTI 2: Kepala sekolah dapat melihat catatan Confidential
        $principalNotes = $this->observationService->getNotesForStudent($this->student->id, $this->principal);
        $this->assertCount(3, $principalNotes);

        // BUKTI 3: Guru biasa (bukan wali kelas) HANYA dapat melihat Public dan Internal (Confidential terisolasi)
        $teacherNotes = $this->observationService->getNotesForStudent($this->student->id, $this->regularTeacherUser);
        $this->assertCount(2, $teacherNotes);
        $this->assertFalse($teacherNotes->contains('access_level', NoteAccessLevelEnum::CONFIDENTIAL->value));

        // BUKTI 4: Siswa HANYA dapat melihat catatan Public
        $studentNotes = $this->observationService->getNotesForStudent($this->student->id, $this->studentUser);
        $this->assertCount(1, $studentNotes);
        $this->assertEquals(NoteAccessLevelEnum::PUBLIC->value, $studentNotes->first()->access_level);
    }

    public function test_ss_f_006_report_export_strictly_excludes_confidential_and_internal_notes(): void
    {
        $this->observationService->createNote([
            'student_id' => $this->student->id,
            'term_id' => $this->term->id,
            'class_group_id' => $this->class7A->id,
            'title' => 'Catatan Rapor Resmi',
            'content' => 'Tingkatkan motivasi belajar mandiri di rumah.',
            'category' => NoteCategoryEnum::GENERAL->value,
            'access_level' => NoteAccessLevelEnum::PUBLIC->value,
        ], $this->homeroomTeacherUser);

        $this->observationService->createNote([
            'student_id' => $this->student->id,
            'term_id' => $this->term->id,
            'class_group_id' => $this->class7A->id,
            'title' => 'Catatan Rahasia',
            'content' => 'Informasi sangat privat tidak boleh tercetak.',
            'category' => NoteCategoryEnum::COUNSELING->value,
            'access_level' => NoteAccessLevelEnum::CONFIDENTIAL->value,
        ], $this->homeroomTeacherUser);

        $exportNotes = $this->observationService->getNotesForReportExport($this->term->id, $this->class7A->id);

        $this->assertCount(1, $exportNotes);
        $this->assertEquals('Catatan Rapor Resmi', $exportNotes->first()->title);
        $this->assertEquals(NoteAccessLevelEnum::PUBLIC->value, $exportNotes->first()->access_level);
    }
}
