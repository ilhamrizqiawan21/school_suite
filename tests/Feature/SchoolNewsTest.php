<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Core\Domain\RoleEnum;
use App\Modules\Core\Infrastructure\Models\Role;
use App\Modules\Core\Infrastructure\Models\School;
use App\Modules\Core\Infrastructure\Models\SchoolNews;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SchoolNewsTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    private User $operator;

    private User $teacher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create(['name' => 'SMA Berita Nusantara', 'npsn' => '77889900', 'level' => 'SMA']);
        $adminRole = Role::create(['name' => RoleEnum::ADMIN->value, 'label' => RoleEnum::ADMIN->label(), 'is_system' => true]);
        $operatorRole = Role::create(['name' => RoleEnum::OPERATOR->value, 'label' => RoleEnum::OPERATOR->label(), 'is_system' => true]);
        $teacherRole = Role::create(['name' => RoleEnum::TEACHER->value, 'label' => RoleEnum::TEACHER->label(), 'is_system' => true]);

        $this->admin = $this->makeUser($adminRole, 'admin');
        $this->operator = $this->makeUser($operatorRole, 'operator');
        $this->teacher = $this->makeUser($teacherRole, 'teacher');
    }

    public function test_admin_can_create_a_draft_news_item(): void
    {
        $this->actingAs($this->admin)->post('/pengaturan/berita', [
            'title' => 'Prestasi Sekolah di Tingkat Provinsi',
            'excerpt' => 'Siswa sekolah meraih prestasi membanggakan.',
            'body' => 'Berita lengkap tentang prestasi siswa dan pendampingan guru.',
            'status' => 'draft',
        ])->assertRedirect('/pengaturan/berita')->assertSessionHasNoErrors();

        $this->assertDatabaseHas('school_news', [
            'school_id' => $this->school->id,
            'title' => 'Prestasi Sekolah di Tingkat Provinsi',
            'status' => 'draft',
            'published_at' => null,
        ]);
    }

    public function test_operator_can_manage_news_but_teacher_cannot(): void
    {
        $this->actingAs($this->operator)->get('/pengaturan/berita')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Core/News/Index')
            ->where('can_manage', true));

        $this->actingAs($this->operator)->post('/pengaturan/berita', [
            'title' => 'Kegiatan Literasi Sekolah',
            'body' => 'Kegiatan literasi berjalan dengan baik.',
            'status' => 'published',
        ])->assertRedirect('/pengaturan/berita');

        $this->actingAs($this->teacher)->get('/pengaturan/berita')->assertForbidden();
        $this->actingAs($this->teacher)->post('/pengaturan/berita', [
            'title' => 'Tidak boleh',
            'body' => 'Guru biasa tidak mengelola berita publik.',
            'status' => 'published',
        ])->assertForbidden();
    }

    public function test_news_accepts_multiple_photos_within_the_five_photo_limit(): void
    {
        Storage::fake('local');
        $photoContent = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=', true);

        $this->actingAs($this->operator)->post('/pengaturan/berita', [
            'title' => 'Dokumentasi Kegiatan Sekolah',
            'body' => 'Dokumentasi kegiatan sekolah dengan beberapa foto.',
            'status' => 'published',
            'photos' => [
                UploadedFile::fake()->createWithContent('kegiatan-1.png', $photoContent),
                UploadedFile::fake()->createWithContent('kegiatan-2.png', $photoContent),
                UploadedFile::fake()->createWithContent('kegiatan-3.png', $photoContent),
            ],
        ])->assertRedirect('/pengaturan/berita')->assertSessionHasNoErrors();

        $news = SchoolNews::query()->where('title', 'Dokumentasi Kegiatan Sekolah')->firstOrFail();
        $this->assertCount(3, $news->photos);
        Storage::disk('local')->assertExists($news->photos->first()->path);
    }

    public function test_news_saves_captions_for_uploaded_photos(): void
    {
        Storage::fake('local');
        $photoContent = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=', true);

        $this->actingAs($this->operator)->post('/pengaturan/berita', [
            'title' => 'Dokumentasi Upacara Sekolah',
            'body' => 'Dokumentasi upacara bendera sekolah.',
            'status' => 'draft',
            'photos' => [UploadedFile::fake()->createWithContent('upacara.png', $photoContent)],
            'photo_captions' => ['Upacara bendera memperingati hari pendidikan.'],
        ])->assertRedirect('/pengaturan/berita')->assertSessionHasNoErrors();

        $news = SchoolNews::query()->where('title', 'Dokumentasi Upacara Sekolah')->firstOrFail();
        $this->assertSame('Upacara bendera memperingati hari pendidikan.', $news->photos->first()->caption);
    }

    public function test_public_pages_show_published_news_but_hide_drafts(): void
    {
        $published = SchoolNews::create([
            'school_id' => $this->school->id,
            'author_id' => $this->admin->id,
            'title' => 'Hari Pendidikan Nasional',
            'slug' => 'hari-pendidikan-nasional',
            'body' => 'Sekolah memperingati Hari Pendidikan Nasional.',
            'status' => 'published',
            'published_at' => now(),
        ]);
        SchoolNews::create([
            'school_id' => $this->school->id,
            'author_id' => $this->admin->id,
            'title' => 'Rancangan Berita Internal',
            'slug' => 'rancangan-berita-internal',
            'body' => 'Draft ini tidak boleh terlihat oleh publik.',
            'status' => 'draft',
        ]);

        $this->get('/berita')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Public/News/Index')
            ->has('news', 1)
            ->where('news.0.title', $published->title));
        $this->get('/berita/'.$published->slug)->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Public/News/Show')
            ->where('news.title', $published->title));
        $this->get('/berita/rancangan-berita-internal')->assertNotFound();
    }

    public function test_public_school_profile_displays_profile_content_and_latest_published_news(): void
    {
        $this->school->update([
            'legal_name' => 'Yayasan Nusantara Pendidikan',
            'school_status' => 'Swasta',
            'accreditation' => 'A',
            'address' => 'Jl. Pendidikan No. 1, Kota Nusantara',
            'phone' => '021-1234567',
            'email' => 'info@berita.test',
            'principal_name' => 'Dr. Kepala Sekolah',
            'website' => 'https://berita.test',
            'about' => 'Sekolah yang menumbuhkan prestasi dan karakter.',
            'principal_message' => 'Selamat datang di sekolah kami.',
            'vision' => 'Menjadi sekolah yang unggul dan berkarakter.',
            'mission' => "Melayani peserta didik.\nMenguatkan kolaborasi.",
        ]);
        $published = SchoolNews::create([
            'school_id' => $this->school->id,
            'author_id' => $this->admin->id,
            'title' => 'Kegiatan Sekolah Terbaru',
            'slug' => 'kegiatan-sekolah-terbaru',
            'excerpt' => 'Kabar terbaru sekolah.',
            'body' => 'Isi berita terbaru sekolah.',
            'status' => 'published',
            'published_at' => now(),
        ]);
        SchoolNews::create([
            'school_id' => $this->school->id,
            'author_id' => $this->admin->id,
            'title' => 'Berita Draf',
            'slug' => 'berita-draf',
            'body' => 'Tidak boleh tampil di halaman publik.',
            'status' => 'draft',
        ]);

        $this->get('/profil-sekolah')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Public/School/Profile')
            ->where('school.name', $this->school->name)
            ->where('school.about', 'Sekolah yang menumbuhkan prestasi dan karakter.')
            ->where('school.principal_message', 'Selamat datang di sekolah kami.')
            ->where('school.vision', 'Menjadi sekolah yang unggul dan berkarakter.')
            ->where('news.0.title', $published->title)
            ->has('news', 1));
    }

    public function test_public_information_menu_has_dedicated_pages(): void
    {
        $this->get('/akademik-fasilitas')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Public/School/Programs')
            ->where('school.name', $this->school->name));

        $this->get('/kesiswaan-prestasi')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Public/School/StudentLife')
            ->where('school.name', $this->school->name));

        $this->get('/kontak')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Public/School/Contact')
            ->where('school.name', $this->school->name));
    }

    public function test_news_is_scoped_to_the_current_school_for_management(): void
    {
        $otherSchool = School::create(['name' => 'Sekolah Lain', 'npsn' => '88990011', 'level' => 'SMA']);
        $foreignNews = SchoolNews::create([
            'school_id' => $otherSchool->id,
            'author_id' => $this->admin->id,
            'title' => 'Berita Sekolah Lain',
            'slug' => 'berita-sekolah-lain',
            'body' => 'Tidak boleh diubah lintas sekolah.',
            'status' => 'draft',
        ]);

        $this->actingAs($this->admin)->put('/pengaturan/berita/'.$foreignNews->id, [
            'title' => 'Percobaan lintas sekolah',
            'body' => 'Tidak boleh tersimpan.',
            'status' => 'draft',
        ])->assertNotFound();
    }

    private function makeUser(Role $role, string $name): User
    {
        return User::create([
            'school_id' => $this->school->id,
            'role_id' => $role->id,
            'name' => ucfirst($name),
            'username' => $name,
            'email' => $name.'@berita.test',
            'password' => 'secret123',
            'is_active' => true,
        ]);
    }
}
