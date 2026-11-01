<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Core\Domain\RoleEnum;
use App\Modules\Core\Infrastructure\Models\Role;
use App\Modules\Core\Infrastructure\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SchoolOrganizationTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $operator;

    private User $teacher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create([
            'name' => 'SMA Struktur Nusantara',
            'npsn' => '99001122',
            'level' => 'SMA',
        ]);

        $operatorRole = Role::create(['name' => RoleEnum::OPERATOR->value, 'label' => RoleEnum::OPERATOR->label(), 'is_system' => true]);
        $teacherRole = Role::create(['name' => RoleEnum::TEACHER->value, 'label' => RoleEnum::TEACHER->label(), 'is_system' => true]);
        $this->operator = $this->makeUser($operatorRole, 'operator');
        $this->teacher = $this->makeUser($teacherRole, 'teacher');
    }

    public function test_operator_can_manage_hierarchical_school_organization(): void
    {
        Storage::fake('local');
        $photoContent = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=', true);

        $this->actingAs($this->operator)->post('/pengaturan/struktur-organisasi', [
            'name' => 'Drs. Budi Santoso',
            'position' => 'Kepala Sekolah',
            'department' => 'Pimpinan',
            'is_published' => true,
            'sort_order' => 0,
            'photo' => UploadedFile::fake()->createWithContent('budi.png', $photoContent),
        ])->assertRedirect('/pengaturan/struktur-organisasi')->assertSessionHasNoErrors();

        $principal = $this->school->organizationMembers()->firstOrFail();
        $principalId = (int) $principal->id;
        $this->assertNotNull($principal->photo_path);
        Storage::disk('local')->assertExists($principal->photo_path);
        $this->actingAs($this->operator)->get('/pengaturan/struktur-organisasi/'.$principal->id.'/file')->assertOk();
        $this->get('/struktur-organisasi/foto/'.$principal->id)->assertOk();
        $this->actingAs($this->operator)->post('/pengaturan/struktur-organisasi', [
            'name' => 'Siti Rahma, S.Pd.',
            'position' => 'Wakil Kepala Sekolah',
            'department' => 'Kurikulum',
            'parent_id' => $principalId,
            'is_published' => true,
            'sort_order' => 1,
        ])->assertRedirect('/pengaturan/struktur-organisasi')->assertSessionHasNoErrors();

        $this->actingAs($this->operator)->get('/pengaturan/struktur-organisasi')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Core/Organization/Index')
            ->has('members', 2)
            ->where('members.1.parent_id', $principalId)
        );

        $this->get('/profil-sekolah')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->has('organization', 2)
            ->where('organization.0.position', 'Kepala Sekolah')
            ->where('organization.1.depth', 1)
            ->where('organization.0.photo_url', fn ($url) => is_string($url) && $url !== '')
        );

        $this->get('/struktur-organisasi')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Public/Organization/Index')
            ->has('organization', 2)
            ->where('organization.0.position', 'Kepala Sekolah')
            ->where('organization.1.depth', 1)
        );
    }

    public function test_organization_is_school_scoped_and_hidden_members_are_not_public(): void
    {
        $otherSchool = School::create(['name' => 'Sekolah Lain', 'npsn' => '99001123', 'level' => 'SMA']);
        $otherMember = $otherSchool->organizationMembers()->create([
            'name' => 'Tidak boleh dipakai',
            'position' => 'Jabatan lain',
            'is_published' => true,
            'sort_order' => 0,
        ]);

        $this->actingAs($this->operator)->post('/pengaturan/struktur-organisasi', [
            'name' => 'Anggota ilegal',
            'position' => 'Jabatan',
            'parent_id' => $otherMember->id,
            'is_published' => true,
            'sort_order' => 0,
        ])->assertSessionHasErrors('parent_id');

        $this->school->organizationMembers()->create([
            'name' => 'Tim internal',
            'position' => 'Koordinator',
            'is_published' => false,
            'sort_order' => 0,
        ]);

        $this->get('/profil-sekolah')->assertOk()->assertInertia(fn (Assert $page) => $page->where('organization', []));
    }

    public function test_operator_can_update_and_delete_an_organization_member(): void
    {
        $member = $this->school->organizationMembers()->create([
            'name' => 'Nama Awal',
            'position' => 'Staf',
            'is_published' => true,
            'sort_order' => 0,
        ]);

        $this->actingAs($this->operator)->put('/pengaturan/struktur-organisasi/'.$member->id, [
            'name' => 'Nama Diperbarui',
            'position' => 'Koordinator',
            'department' => 'Administrasi',
            'is_published' => false,
            'sort_order' => 2,
        ])->assertRedirect('/pengaturan/struktur-organisasi')->assertSessionHasNoErrors();

        $this->assertDatabaseHas('school_organization_members', [
            'id' => $member->id,
            'name' => 'Nama Diperbarui',
            'position' => 'Koordinator',
            'is_published' => false,
        ]);

        $this->actingAs($this->operator)->delete('/pengaturan/struktur-organisasi/'.$member->id)
            ->assertRedirect('/pengaturan/struktur-organisasi');
        $this->assertDatabaseMissing('school_organization_members', ['id' => $member->id]);
    }

    public function test_teacher_cannot_access_organization_management(): void
    {
        $this->actingAs($this->teacher)->get('/pengaturan/struktur-organisasi')->assertForbidden();
        $this->actingAs($this->teacher)->post('/pengaturan/struktur-organisasi', [
            'name' => 'Tidak boleh',
            'position' => 'Jabatan',
            'is_published' => true,
        ])->assertForbidden();
    }

    private function makeUser(Role $role, string $name): User
    {
        return User::create([
            'school_id' => $this->school->id,
            'role_id' => $role->id,
            'name' => ucfirst($name),
            'username' => $name,
            'email' => $name.'@struktur.test',
            'password' => 'secret123',
            'is_active' => true,
        ]);
    }
}
