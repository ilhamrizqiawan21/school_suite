<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Core\Domain\RoleEnum;
use App\Modules\Core\Infrastructure\Models\Role;
use App\Modules\Core\Infrastructure\Models\School;
use App\Modules\Core\Infrastructure\Models\SchoolGalleryPhoto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SchoolGalleryTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    private User $operator;

    private User $teacher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create(['name' => 'SMA Portofolio Nusantara', 'npsn' => '77889900', 'level' => 'SMA']);
        $adminRole = Role::create(['name' => RoleEnum::ADMIN->value, 'label' => RoleEnum::ADMIN->label(), 'is_system' => true]);
        $operatorRole = Role::create(['name' => RoleEnum::OPERATOR->value, 'label' => RoleEnum::OPERATOR->label(), 'is_system' => true]);
        $teacherRole = Role::create(['name' => RoleEnum::TEACHER->value, 'label' => RoleEnum::TEACHER->label(), 'is_system' => true]);

        $this->admin = $this->makeUser($adminRole, 'admin');
        $this->operator = $this->makeUser($operatorRole, 'operator');
        $this->teacher = $this->makeUser($teacherRole, 'teacher');
    }

    public function test_operator_can_upload_and_manage_a_gallery_photo(): void
    {
        Storage::fake('local');
        $photoContent = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=', true);

        $this->actingAs($this->operator)->post('/pengaturan/galeri', [
            'photo' => UploadedFile::fake()->createWithContent('laboratorium.png', $photoContent),
            'title' => 'Laboratorium sains',
            'caption' => 'Ruang belajar untuk eksperimen siswa.',
            'is_published' => true,
        ])->assertRedirect('/pengaturan/galeri')->assertSessionHasNoErrors();

        $photo = SchoolGalleryPhoto::query()->where('title', 'Laboratorium sains')->firstOrFail();
        $this->assertTrue($photo->is_published);
        Storage::disk('local')->assertExists($photo->path);

        $this->actingAs($this->operator)->get('/pengaturan/galeri')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Core/Gallery/Index')
            ->has('photos', 1)
            ->where('photos.0.title', 'Laboratorium sains'));
    }

    public function test_only_published_gallery_photos_appear_on_public_pages(): void
    {
        Storage::fake('local');
        $published = $this->createPhoto('Lapangan sekolah', true);
        $hidden = $this->createPhoto('Foto internal', false);

        $this->get('/galeri')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Public/Gallery/Index')
            ->has('photos', 1)
            ->where('photos.0.title', $published->title));
        $this->get('/profil-sekolah')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Public/School/Profile')
            ->has('gallery', 1)
            ->where('gallery.0.title', $published->title));
        $this->get('/galeri/foto/'.$published->id)->assertOk();
        $this->get('/galeri/foto/'.$hidden->id)->assertNotFound();
    }

    public function test_gallery_management_is_scoped_and_role_protected(): void
    {
        Storage::fake('local');
        $photo = $this->createPhoto('Foto sekolah lain', true);
        $otherSchool = School::create(['name' => 'Sekolah Lain', 'npsn' => '88990011', 'level' => 'SMA']);
        $photo->update(['school_id' => $otherSchool->id]);

        $this->actingAs($this->teacher)->get('/pengaturan/galeri')->assertForbidden();
        $this->actingAs($this->admin)->put('/pengaturan/galeri/'.$photo->id, [
            'title' => 'Tidak boleh lintas sekolah',
            'caption' => '',
            'is_published' => true,
        ])->assertNotFound();
    }

    private function createPhoto(string $title, bool $isPublished): SchoolGalleryPhoto
    {
        $path = 'school-gallery/'.$this->school->id.'/'.str()->random(12).'.jpg';
        Storage::disk('local')->put($path, 'fake image');

        return SchoolGalleryPhoto::create([
            'school_id' => $this->school->id,
            'path' => $path,
            'original_name' => str($title)->slug().'.jpg',
            'title' => $title,
            'caption' => null,
            'is_published' => $isPublished,
            'sort_order' => 0,
        ]);
    }

    private function makeUser(Role $role, string $name): User
    {
        return User::create([
            'school_id' => $this->school->id,
            'role_id' => $role->id,
            'name' => ucfirst($name),
            'username' => $name,
            'email' => $name.'@portofolio.test',
            'password' => 'secret123',
            'is_active' => true,
        ]);
    }
}
