<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Core\Infrastructure\Models\School;
use App\Modules\Core\Infrastructure\Models\SchoolGalleryAlbum;
use App\Modules\Core\Infrastructure\Models\SchoolGalleryPhoto;
use App\Modules\Core\Infrastructure\Models\SchoolNews;
use App\Modules\Core\Infrastructure\Models\SchoolNewsPhoto;
use App\Modules\Core\Infrastructure\Models\SchoolOrganizationMember;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class DemoLandingPageSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->command?->warn('Data contoh landing page hanya dapat dibuat di lingkungan local atau testing.');

            return;
        }

        $school = School::query()->first();
        $admin = $school
            ? User::query()
                ->where('school_id', $school->id)
                ->whereHas('role', fn ($query) => $query->where('name', 'admin'))
                ->first()
            : null;

        if (! $school || ! $admin) {
            $this->command?->warn('Sekolah dan akun admin perlu tersedia sebelum data contoh landing page dibuat.');

            return;
        }

        $this->seedSchoolProfile($school);
        $assetPaths = $this->copyLandingAssets();
        $this->seedGallery($school, $assetPaths);
        $this->seedNews($school, $admin, $assetPaths);
        $this->seedOrganization($school);
    }

    private function seedSchoolProfile(School $school): void
    {
        $settings = is_array($school->settings) ? $school->settings : [];
        $publicTheme = is_array(data_get($settings, 'public_theme'))
            ? data_get($settings, 'public_theme')
            : [];

        $settings['theme_primary'] = '#277450';
        $settings['public_theme'] = array_merge($publicTheme, [
            'preset' => 'institutional',
            'hero_variant' => 'collage',
            'hero_title' => 'Tempat belajar yang memberi ruang untuk',
            'hero_accent' => 'tumbuh bersama.',
            'hero_primary_label' => 'Kenali sekolah',
            'hero_secondary_label' => 'Baca kabar terbaru',
            'show_explore' => true,
            'show_news' => true,
            'show_gallery' => true,
        ]);

        $school->forceFill([
            'legal_name' => 'SMP Negeri 1 Percontohan Nusantara',
            'school_status' => 'Negeri',
            'accreditation' => 'Unggul',
            'principal_name' => 'Drs. H. Mulyadi, M.Pd.',
            'website' => 'https://smpn1percontohan.sch.id',
            'vision' => 'Menjadi sekolah yang menumbuhkan pelajar berkarakter, bernalar kritis, berdaya cipta, dan siap berkontribusi bagi lingkungan.',
            'mission' => "Menyelenggarakan pembelajaran yang berpihak pada murid.\nMenguatkan karakter, literasi, numerasi, dan kecakapan abad ke-21.\nMembangun kolaborasi sehat antara sekolah, keluarga, dan masyarakat.\nMenciptakan lingkungan belajar yang aman, hijau, inklusif, dan menyenangkan.",
            'about' => 'SMP Negeri 1 Percontohan Nusantara adalah ruang belajar publik yang memadukan tradisi akademik, kepedulian sosial, dan keberanian untuk mencoba. Setiap hari, siswa belajar memahami dunia sekaligus menemukan cara mereka sendiri untuk memberi arti.',
            'principal_message' => 'Selamat datang di rumah belajar kami. Sekolah bukan sekadar tempat mengejar nilai, melainkan tempat anak-anak bertemu dengan gagasan, sahabat, dan kesempatan untuk tumbuh menjadi versi terbaik dirinya. Mari berjalan bersama dengan rasa ingin tahu, kepedulian, dan kegembiraan.',
            'settings' => $settings,
        ])->save();
    }

    /** @return array<string, string> */
    private function copyLandingAssets(): array
    {
        $assets = [
            'campus' => ['source' => public_path('images/landing/campus-hero.png'), 'path' => 'demo/landing/campus-hero.png'],
            'library' => ['source' => public_path('images/landing/library.png'), 'path' => 'demo/landing/library.png'],
            'science' => ['source' => public_path('images/landing/science-lab.png'), 'path' => 'demo/landing/science-lab.png'],
            'activity' => ['source' => public_path('images/landing/activity-room.png'), 'path' => 'demo/landing/activity-room.png'],
            'trees' => ['source' => public_path('images/landing/tree-planting.png'), 'path' => 'demo/landing/tree-planting.png'],
        ];

        foreach ($assets as $asset) {
            if (File::exists($asset['source'])) {
                Storage::disk('local')->put($asset['path'], File::get($asset['source']));
            }
        }

        return array_map(static fn (array $asset): string => $asset['path'], $assets);
    }

    /** @param array<string, string> $assetPaths */
    private function seedGallery(School $school, array $assetPaths): void
    {
        $albums = [
            ['title' => 'Kampus utama', 'caption' => 'Ruang tumbuh yang terbuka, teduh, dan ramah bagi seluruh warga sekolah.', 'asset' => 'campus', 'sort_order' => 1],
            ['title' => 'Perpustakaan hijau', 'caption' => 'Membaca, berdiskusi, dan menemukan ide baru dalam suasana yang nyaman.', 'asset' => 'library', 'sort_order' => 2],
            ['title' => 'Laboratorium sains', 'caption' => 'Belajar melalui pengamatan, percobaan, dan rasa ingin tahu.', 'asset' => 'science', 'sort_order' => 3],
            ['title' => 'Komunitas & lingkungan', 'caption' => 'Kegiatan kolaboratif yang merawat kebersamaan dan bumi.', 'asset' => 'activity', 'sort_order' => 4],
        ];

        foreach ($albums as $albumDefinition) {
            $album = SchoolGalleryAlbum::query()->updateOrCreate(
                ['school_id' => $school->id, 'title' => $albumDefinition['title']],
                [
                    'caption' => $albumDefinition['caption'],
                    'is_published' => true,
                    'sort_order' => $albumDefinition['sort_order'],
                ]
            );

            $path = $assetPaths[$albumDefinition['asset']];
            SchoolGalleryPhoto::query()->updateOrCreate(
                ['school_id' => $school->id, 'path' => $path],
                [
                    'school_gallery_album_id' => $album->id,
                    'original_name' => basename($path),
                    'title' => $albumDefinition['title'],
                    'caption' => $albumDefinition['caption'],
                    'is_published' => true,
                    'sort_order' => 1,
                ]
            );

            if ($albumDefinition['asset'] === 'activity') {
                SchoolGalleryPhoto::query()->updateOrCreate(
                    ['school_id' => $school->id, 'path' => $assetPaths['trees']],
                    [
                        'school_gallery_album_id' => $album->id,
                        'original_name' => basename($assetPaths['trees']),
                        'title' => 'Gerakan satu kelas satu pohon',
                        'caption' => 'Belajar merawat lingkungan dimulai dari kebiasaan kecil yang dilakukan bersama.',
                        'is_published' => true,
                        'sort_order' => 2,
                    ]
                );
            }
        }
    }

    /** @param array<string, string> $assetPaths */
    private function seedNews(School $school, User $admin, array $assetPaths): void
    {
        $newsItems = [
            [
                'slug' => 'siswa-raih-juara-festival-sains-kota',
                'title' => 'Siswa Raih Juara Festival Sains Kota',
                'excerpt' => 'Tim riset muda membawa pulang penghargaan melalui proyek penyaring air sederhana berbasis material lokal.',
                'body' => 'Tim riset muda SMP Negeri 1 Percontohan Nusantara meraih juara pertama pada Festival Sains Kota. Proyek mereka mengembangkan penyaring air sederhana dari material yang mudah ditemukan di sekitar rumah.\n\nProsesnya dimulai dari pengamatan kualitas air, wawancara warga, hingga pengujian berulang di laboratorium sekolah. Prestasi ini menjadi pengingat bahwa pertanyaan sederhana dapat berkembang menjadi karya yang bermanfaat ketika dikerjakan dengan tekun dan kolaboratif.',
                'asset' => 'science',
                'caption' => 'Eksperimen dan presentasi proyek sains siswa.',
                'days_ago' => 2,
            ],
            [
                'slug' => 'perpustakaan-hijau-jadi-ruang-tumbuh-baru',
                'title' => 'Perpustakaan Hijau Jadi Ruang Tumbuh Baru',
                'excerpt' => 'Sudut baca baru menghadirkan ruang yang lebih hangat untuk membaca, berdiskusi, dan berbagi karya.',
                'body' => 'Perpustakaan sekolah kini memiliki sudut baca baru yang dirancang bersama oleh siswa, guru, dan orang tua. Rak buku ditata lebih terbuka, area diskusi dibuat fleksibel, dan koleksi bacaan pilihan ditempatkan lebih dekat dengan ruang kelas.\n\nSetiap Jumat, komunitas literasi mengadakan sesi berbagi buku dan cerita. Semua warga sekolah dipersilakan datang, membaca, dan meninggalkan rekomendasi bacaan untuk teman berikutnya.',
                'asset' => 'library',
                'caption' => 'Ruang baca dan diskusi komunitas literasi.',
                'days_ago' => 7,
            ],
            [
                'slug' => 'gerakan-satu-kelas-satu-pohon',
                'title' => 'Gerakan Satu Kelas Satu Pohon',
                'excerpt' => 'Kelas-kelas merawat pohon adopsi sebagai bagian dari pembelajaran lingkungan dan kebiasaan hidup berkelanjutan.',
                'body' => 'Gerakan Satu Kelas Satu Pohon dimulai sebagai cara sederhana untuk menghadirkan pembelajaran lingkungan ke dalam keseharian. Setiap kelas memilih satu pohon untuk dirawat, mencatat pertumbuhannya, dan menyusun jurnal kecil tentang perubahan yang mereka amati.\n\nKegiatan ini menghubungkan sains, numerasi, bahasa, dan kerja sama dalam satu pengalaman belajar yang nyata. Pada akhir semester, siswa akan mempresentasikan cerita perjalanan pohon mereka kepada seluruh komunitas sekolah.',
                'asset' => 'trees',
                'caption' => 'Siswa menanam dan merawat pohon di lingkungan sekolah.',
                'days_ago' => 14,
            ],
        ];

        foreach ($newsItems as $item) {
            $news = SchoolNews::query()->updateOrCreate(
                ['school_id' => $school->id, 'slug' => $item['slug']],
                [
                    'author_id' => $admin->id,
                    'title' => $item['title'],
                    'excerpt' => $item['excerpt'],
                    'body' => $item['body'],
                    'status' => 'published',
                    'published_at' => now()->subDays($item['days_ago'])->setTime(8, 0),
                ]
            );

            $path = $assetPaths[$item['asset']];
            SchoolNewsPhoto::query()->updateOrCreate(
                ['school_news_id' => $news->id, 'path' => $path],
                [
                    'original_name' => basename($path),
                    'caption' => $item['caption'],
                    'sort_order' => 1,
                ]
            );
        }
    }

    private function seedOrganization(School $school): void
    {
        $principal = SchoolOrganizationMember::query()->updateOrCreate(
            ['school_id' => $school->id, 'name' => 'Drs. H. Mulyadi, M.Pd.'],
            [
                'parent_id' => null,
                'position' => 'Kepala Sekolah',
                'department' => 'Pimpinan',
                'sort_order' => 1,
                'is_published' => true,
            ]
        );

        $members = [
            ['name' => 'Rina Wijaya, S.Pd.', 'position' => 'Wakil Kepala Sekolah · Kesiswaan', 'department' => 'Pimpinan', 'sort_order' => 1],
            ['name' => 'Siti Aminah, S.Pd.', 'position' => 'Wakil Kepala Sekolah · Kurikulum', 'department' => 'Pimpinan', 'sort_order' => 2],
            ['name' => 'Dewi Lestari, S.Si.', 'position' => 'Koordinator Laboratorium', 'department' => 'Pengembangan Pembelajaran', 'sort_order' => 3],
            ['name' => 'Fajar Nugraha, S.Kom.', 'position' => 'Koordinator Teknologi Pembelajaran', 'department' => 'Pengembangan Pembelajaran', 'sort_order' => 4],
            ['name' => 'Maya Kartika, S.Pd.', 'position' => 'Koordinator Literasi dan Perpustakaan', 'department' => 'Pengembangan Pembelajaran', 'sort_order' => 5],
        ];

        foreach ($members as $member) {
            SchoolOrganizationMember::query()->updateOrCreate(
                ['school_id' => $school->id, 'name' => $member['name']],
                [
                    'parent_id' => $principal->id,
                    'position' => $member['position'],
                    'department' => $member['department'],
                    'sort_order' => $member['sort_order'],
                    'is_published' => true,
                ]
            );
        }
    }
}
