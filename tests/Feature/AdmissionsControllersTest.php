<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Academic\Infrastructure\Models\Teacher;
use App\Modules\Admissions\Infrastructure\Models\AdmissionApplicant;
use App\Modules\Core\Infrastructure\Models\School;
use Database\Seeders\PilotSchoolSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdmissionsControllersTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $teacher;

    private User $unassignedTeacher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PilotSchoolSeeder::class);
        $this->admin = User::where('email', 'admin@pilot.sch.id')->firstOrFail();
        $this->teacher = User::whereHas('role', fn ($query) => $query->where('name', 'teacher'))->firstOrFail();
        $this->unassignedTeacher = User::whereHas('role', fn ($query) => $query->where('name', 'homeroom'))->firstOrFail();
        $this->admin->forceFill(['must_change_password' => false])->save();
        $this->teacher->forceFill(['must_change_password' => false])->save();
        $this->unassignedTeacher->forceFill(['must_change_password' => false])->save();
    }

    public function test_admin_can_access_spmb_assign_teacher_and_manage_applicant(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admissions.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admissions/Index')
                ->where('can_manage_committee', true));

        $this->post(route('admissions.committee.assign'), ['user_id' => $this->teacher->id])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('admission_committee_members', [
            'school_id' => $this->admin->school_id,
            'user_id' => $this->teacher->id,
            'assigned_by' => $this->admin->id,
        ]);

        $this->post(route('admissions.applicants.store'), [
            'name' => 'Calon Siswa Baru',
            'nisn' => '0098765432',
            'gender' => 'P',
            'parent_name' => 'Orang Tua Calon',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $applicant = AdmissionApplicant::firstOrFail();
        $this->actingAs($this->teacher)
            ->put(route('admissions.applicants.update', $applicant), [
                'status' => 'documents_verified',
                'notes' => 'Berkas sudah diperiksa.',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('admission_applicants', [
            'id' => $applicant->id,
            'school_id' => $this->teacher->school_id,
            'status' => 'documents_verified',
            'notes' => 'Berkas sudah diperiksa.',
        ]);
    }

    public function test_unassigned_teacher_cannot_open_spmb_or_add_applicant(): void
    {
        $this->actingAs($this->unassignedTeacher)
            ->get(route('admissions.index'))
            ->assertForbidden();

        $this->post(route('admissions.applicants.store'), [
            'name' => 'Tidak Diizinkan',
            'gender' => 'L',
        ])->assertForbidden();

        $this->assertDatabaseCount('admission_applicants', 0);
    }

    public function test_admin_cannot_assign_a_non_teacher_as_committee_member(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admissions.committee.assign'), ['user_id' => $this->unassignedTeacher->id])
            ->assertNotFound();

        $this->assertDatabaseCount('admission_committee_members', 0);
    }

    public function test_admin_cannot_assign_teacher_from_another_school(): void
    {
        $otherSchool = School::create(['name' => 'Sekolah Lain', 'npsn' => '88888888', 'level' => 'SMP']);
        $teacherRole = $this->teacher->role;
        $otherTeacherUser = User::create([
            'school_id' => $otherSchool->id,
            'role_id' => $teacherRole->id,
            'name' => 'Guru Sekolah Lain',
            'username' => 'guru_lain',
            'email' => 'guru@sekolahlain.sch.id',
            'password' => 'password',
            'is_active' => true,
        ]);
        Teacher::create([
            'school_id' => $otherSchool->id,
            'user_id' => $otherTeacherUser->id,
            'name' => $otherTeacherUser->name,
            'is_active' => true,
        ]);

        $this->actingAs($this->admin)
            ->post(route('admissions.committee.assign'), ['user_id' => $otherTeacherUser->id])
            ->assertNotFound();

        $this->assertDatabaseCount('admission_committee_members', 0);
    }

    public function test_teacher_with_inactive_teacher_profile_loses_spmb_access(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admissions.committee.assign'), ['user_id' => $this->teacher->id])
            ->assertRedirect();
        Teacher::query()->where('user_id', $this->teacher->id)->update(['is_active' => false]);

        $this->actingAs($this->teacher)
            ->get(route('admissions.index'))
            ->assertForbidden();
    }

    public function test_teacher_cannot_read_another_school_admission_applicant(): void
    {
        $this->actingAs($this->admin)->post(route('admissions.committee.assign'), ['user_id' => $this->teacher->id]);
        $otherSchool = School::create(['name' => 'Sekolah Lain', 'npsn' => '99999999', 'level' => 'SMP']);
        $applicant = AdmissionApplicant::create([
            'school_id' => $otherSchool->id,
            'registration_no' => 'SPMB-2026-OTHER',
            'name' => 'Pendaftar Lintas Sekolah',
            'gender' => 'L',
            'created_by' => $this->admin->id,
        ]);

        $this->actingAs($this->teacher)
            ->put(route('admissions.applicants.update', $applicant), ['status' => 'accepted'])
            ->assertForbidden();
    }
}
