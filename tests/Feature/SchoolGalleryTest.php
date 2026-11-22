<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Core\Domain\RoleEnum;
use App\Modules\Core\Infrastructure\Models\Role;
use App\Modules\Core\Infrastructure\Models\School;
use App\Modules\Core\Infrastructure\Models\SchoolGalleryAlbum;
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

    public function test_media_orphan_scan_is_read_only_and_excludes_referenced_and_other_school_files(): void
    {
        Storage::fake('local');
        $disk = Storage::disk('local');
        $orphan = 'branding/'.$this->school->id.'/unused.png';
        $referenced = 'branding/'.$this->school->id.'/logo.png';
        $otherSchool = 'branding/'.($this->school->id + 1).'/other.png';
        foreach ([$orphan, $referenced, $otherSchool] as $path) {
            $disk->put($path, 'image');
            touch($disk->path($path), now()->subDays(2)->timestamp);
        }
        $this->school->update(['logo_path' => $referenced]);

        $this->artisan('school:media-orphans', ['school' => $this->school->id])
            ->expectsOutput($orphan)
            ->doesntExpectOutput($referenced)
            ->doesntExpectOutput($otherSchool)
            ->assertSuccessful();
        $disk->assertExists([$orphan, $referenced, $otherSchool]);
    }

    public function test_operator_can_upload_one_gallery_group_with_four_photos(): void
    {
        Storage::fake('local');
        $photoContent = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=', true);

        $this->actingAs($this->operator)->post('/pengaturan/galeri', [
            'photos' => [
                UploadedFile::fake()->createWithContent('kegiatan-1.png', $photoContent),
                UploadedFile::fake()->createWithContent('kegiatan-2.png', $photoContent),
                UploadedFile::fake()->createWithContent('kegiatan-3.png', $photoContent),
                UploadedFile::fake()->createWithContent('kegiatan-4.png', $photoContent),
            ],
            'title' => 'Pekan kreativitas siswa',
            'caption' => 'Momen karya dan kolaborasi siswa.',
            'is_published' => true,
        ])->assertRedirect('/pengaturan/galeri')->assertSessionHasNoErrors();

        $album = SchoolGalleryAlbum::query()->where('title', 'Pekan kreativitas siswa')->with('photos')->firstOrFail();
        $this->assertCount(4, $album->photos);
        Storage::disk('local')->assertExists($album->photos->first()->path);

        $this->actingAs($this->operator)->get('/pengaturan/galeri')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Core/Gallery/Index')
            ->has('albums', 1)
            ->where('albums.0.title', 'Pekan kreativitas siswa')
            ->has('albums.0.photos', 4));
    }

    public function test_gallery_rejects_more_than_four_photos(): void
    {
        Storage::fake('local');
        $photoContent = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=', true);
        $photos = array_map(
            fn (int $index) => UploadedFile::fake()->createWithContent("foto-$index.png", $photoContent),
            range(1, 5),
        );

        $this->actingAs($this->operator)->post('/pengaturan/galeri', [
            'photos' => $photos,
            'title' => 'Terlalu banyak foto',
            'is_published' => true,
        ])->assertSessionHasErrors('photos');

        $this->assertDatabaseCount('school_gallery_albums', 0);
    }

    public function test_gallery_rejects_a_group_over_five_mb_total(): void
    {
        Storage::fake('local');

        $this->actingAs($this->operator)->post('/pengaturan/galeri', [
            'photos' => [
                UploadedFile::fake()->create('foto-1.png', 3000, 'image/png'),
                UploadedFile::fake()->create('foto-2.png', 3000, 'image/png'),
            ],
            'title' => 'Ukuran terlalu besar',
            'is_published' => true,
        ])->assertSessionHasErrors(['photos' => 'Ukuran total semua foto maksimal 5 MB.']);

        $this->assertDatabaseCount('school_gallery_albums', 0);
    }

    public function test_only_published_gallery_groups_appear_on_public_pages(): void
    {
        Storage::fake('local');
        $published = $this->createAlbum('Lapangan sekolah', true, 2);
        $hidden = $this->createAlbum('Foto internal', false);

        $this->get('/galeri')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Public/Gallery/Index')
            ->has('albums', 1)
            ->where('albums.0.title', $published->title)
            ->has('albums.0.photos', 2));
        $this->get('/profil-sekolah')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Public/School/Profile')
            ->has('gallery', 1)
            ->where('gallery.0.title', $published->title));
        $this->get('/galeri/foto/'.$published->photos->first()->id)->assertOk();
        $this->get('/galeri/foto/'.$hidden->photos->first()->id)->assertNotFound();
    }

    public function test_gallery_management_is_scoped_and_role_protected(): void
    {
        Storage::fake('local');
        $album = $this->createAlbum('Foto sekolah lain', true);
        $otherSchool = School::create(['name' => 'Sekolah Lain', 'npsn' => '88990011', 'level' => 'SMA']);
        $album->update(['school_id' => $otherSchool->id]);

        $this->actingAs($this->teacher)->get('/pengaturan/galeri')->assertForbidden();
        $this->actingAs($this->admin)->put('/pengaturan/galeri/'.$album->id, [
            'title' => 'Tidak boleh lintas sekolah',
            'is_published' => true,
        ])->assertNotFound();
    }

    public function test_media_used_by_published_content_cannot_be_withdrawn_or_deleted(): void
    {
        Storage::fake('local');
        $album = $this->createAlbum('Foto kegiatan literasi', true);
        $photo = $album->photos->first();
        $this->school->update(['settings' => ['public_content' => ['published' => [
            'home.hero' => ['image_url' => route('core.gallery.photo', $photo->id)],
        ]]]]);
        $this->actingAs($this->admin)->put('/pengaturan/galeri/'.$album->id, [
            'title' => $album->title, 'is_published' => false,
        ])->assertSessionHasErrors('photos');
        $this->actingAs($this->admin)->delete('/pengaturan/galeri/'.$album->id)->assertSessionHasErrors('photos');
        $this->assertTrue($album->fresh()->is_published);
        Storage::disk('local')->assertExists($photo->path);
    }

    public function test_media_kept_only_in_publication_history_is_preserved_for_restore(): void
    {
        Storage::fake('local');
        $album = $this->createAlbum('Arsip dokumentasi literasi', true);
        $photo = $album->photos->first();
        $this->school->update(['settings' => ['public_content' => ['revisions' => [[
            'version' => 1, 'blocks' => ['home.hero' => ['image_url' => route('core.gallery.photo', $photo->id)]],
        ]]]]]);
        $this->actingAs($this->admin)->delete('/pengaturan/galeri/'.$album->id)->assertSessionHasErrors('photos');
        $this->assertNotNull($album->fresh());
        Storage::disk('local')->assertExists($photo->path);
    }

    public function test_unrelated_school_content_cannot_block_media_management(): void
    {
        Storage::fake('local');
        $album = $this->createAlbum('Dokumentasi lokal', true);
        School::create(['name' => 'Madrasah Sekolah Lain', 'npsn' => '88990022', 'level' => 'MA',
            'settings' => ['public_content' => ['published' => [
                'home.hero' => ['image_url' => route('core.gallery.photo', $album->photos->first()->id)],
            ]]]]);
        $this->actingAs($this->admin)->put('/pengaturan/galeri/'.$album->id, [
            'title' => $album->title, 'is_published' => false,
        ])->assertSessionHasNoErrors();
        $this->assertFalse($album->fresh()->is_published);
    }

    private function createAlbum(string $title, bool $isPublished, int $photoCount = 1): SchoolGalleryAlbum
    {
        $album = SchoolGalleryAlbum::create([
            'school_id' => $this->school->id,
            'title' => $title,
            'caption' => null,
            'is_published' => $isPublished,
            'sort_order' => 0,
        ]);
        for ($index = 0; $index < $photoCount; $index++) {
            $path = 'school-gallery/'.$this->school->id.'/'.$album->id.'/'.$index.'.jpg';
            Storage::disk('local')->put($path, 'fake image');
            $album->photos()->create([
                'school_id' => $this->school->id,
                'path' => $path,
                'original_name' => "foto-$index.jpg",
                'sort_order' => $index,
            ]);
        }

        return $album->load('photos');
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
