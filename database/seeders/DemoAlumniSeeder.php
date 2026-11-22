<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Academic\Application\Services\AlumniService;
use App\Modules\Academic\Infrastructure\Models\AlumniProfile;
use App\Modules\Core\Infrastructure\Models\School;
use App\Modules\Core\Infrastructure\Models\SchoolNews;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoAlumniSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->command?->warn('Data alumni demo hanya tersedia untuk lingkungan lokal dan pengujian.');

            return;
        }

        $school = School::query()->where('npsn', '20109988')->first();
        if (! $school) {
            $this->command?->warn('Sekolah pilot belum tersedia; data alumni demo dilewati.');

            return;
        }

        DB::transaction(function () use ($school): void {
            $this->seedProfiles($school);
            $this->seedOpportunities($school);
        });
    }

    private function seedProfiles(School $school): void
    {
        $firstNames = ['Ahmad', 'Muhammad', 'Rizky', 'Fajar', 'Dimas', 'Ilham', 'Farhan', 'Bagas', 'Rafi', 'Arif',
            'Yusuf', 'Reza', 'Naufal', 'Hafiz', 'Aditya', 'Aisyah', 'Siti', 'Nurul', 'Zahra', 'Nabila',
            'Putri', 'Dinda', 'Annisa', 'Salma', 'Fitri', 'Safira', 'Nadia', 'Intan', 'Aulia', 'Hana'];
        $lastNames = ['Pratama', 'Ramadhani', 'Maulana', 'Lestari', 'Permata', 'Fauzi'];
        $statuses = array_keys(AlumniService::STATUS_LABELS);
        $cities = ['Martapura', 'Banjarbaru', 'Banjarmasin', 'Balikpapan', 'Samarinda', 'Yogyakarta'];
        $majors = strtoupper((string) $school->level) === 'SMP' ? ['IX A', 'IX B', 'IX C', 'IX D'] : ['MIPA', 'IPS', 'Bahasa', 'MANPK'];
        $campuses = ['Universitas Cakrawala (Demo)', 'Institut Teknologi Nusantara (Demo)', 'Politeknik Banua (Demo)',
            'Universitas Pelita Ilmu (Demo)', 'Sekolah Tinggi Kesehatan Mandiri (Demo)', 'Institut Pendidikan Borneo (Demo)'];
        $programs = ['Teknik Informatika', 'Akuntansi', 'Teknik Sipil', 'Pendidikan Matematika', 'Keperawatan', 'Manajemen'];

        for ($index = 0; $index < 180; $index++) {
            $number = $index + 1;
            $status = $statuses[$index % count($statuses)];
            $variant = intdiv($index, count($statuses)) % count($cities);
            [$institution, $position] = match ($status) {
                'studying' => [$campuses[$variant], $programs[$variant]],
                'working' => ['PT Banua Karya Simulasi', ['Staf Administrasi', 'Analis Keuangan', 'Teknisi Jaringan', 'Pengembang Aplikasi', 'Staf Operasional', 'Desainer Produk'][$variant]],
                'civil_servant' => ['Instansi Pelayanan Publik (Demo)', ['Analis Kebijakan', 'Guru', 'Pranata Komputer', 'Staf Pelayanan', 'Tenaga Kesehatan', 'Pengelola Arsip'][$variant]],
                'freelance' => ['Studio Kreasi Alumni (Demo)', ['Desainer Grafis', 'Fotografer', 'Pengembang Web', 'Penulis', 'Editor Video', 'Penerjemah'][$variant]],
                'entrepreneur' => ['Usaha Mandiri Alumni (Demo)', ['Pemilik Kedai Kopi', 'Pengusaha Kuliner', 'Pemilik Toko Buku', 'Pengelola Toko Daring', 'Pemilik Percetakan', 'Pengusaha Kerajinan'][$variant]],
                'training' => ['Balai Pelatihan Keterampilan (Demo)', ['Pelatihan Desain', 'Pelatihan Akuntansi', 'Pelatihan Komputer', 'Kursus Bahasa', 'Pelatihan Tata Boga', 'Pelatihan Elektronika'][$variant]],
                'homemaker' => [null, 'Pengelolaan Rumah Tangga'],
                'retired' => [null, 'Purnatugas'],
                'job_seeking' => [null, 'Mencari Peluang Karir'],
                default => [null, 'Kegiatan Sosial & Komunitas'],
            };

            AlumniProfile::query()->firstOrCreate([
                'school_id' => $school->id, 'email' => sprintf('alumni-demo-%03d@example.test', $number),
            ], [
                'full_name' => $firstNames[$index % count($firstNames)].' '.$lastNames[intdiv($index, count($firstNames))],
                'graduation_year' => match ($status) {
                    'retired' => 1985 - $variant,
                    'homemaker' => 2005 + $variant,
                    'studying', 'training', 'job_seeking' => 2019 + $variant,
                    default => 2010 + ($index % 15),
                },
                'major_or_class' => $majors[intdiv($index, 3) % count($majors)],
                'current_status' => $status, 'institution_or_company' => $institution, 'position_or_program' => $position,
                'activity_location' => $cities[$variant], 'residence_city' => $cities[($variant + 1) % count($cities)],
                'career_notes' => '[DEMO] Profil fiktif untuk pratinjau portal alumni; bukan riwayat orang nyata.',
                'scholarship_info' => $status === 'studying' && $variant % 2 === 0 ? '[DEMO] Penerima bantuan pendidikan prestasi.' : null,
                'testimonial' => $index % 4 === 0 ? '[DEMO] Pengalaman belajar dan dukungan guru menjadi bekal saya melanjutkan pendidikan serta berkarya.' : null,
                'opt_in_directory' => true, 'opt_in_career' => true, 'opt_in_contact' => false,
                'opt_in_testimonial' => $index % 4 === 0, 'moderation_status' => 'approved',
                'moderation_note' => '[DEMO] Disetujui untuk pratinjau antarmuka lokal.', 'moderated_at' => now(),
            ]);
        }
    }

    private function seedOpportunities(School $school): void
    {
        $publications = [
            ['karir-alumni', 'Staf Administrasi — PT Banua Karya Simulasi', 'Latihan lowongan administrasi untuk alumni yang teliti dan menguasai aplikasi perkantoran.'],
            ['karir-alumni', 'Magang Pengembang Web — Studio Kreasi Alumni', 'Contoh kesempatan magang pengembangan situs dengan pendampingan tim teknologi.'],
            ['karir-alumni', 'Desainer Grafis — Studio Kreasi Alumni', 'Contoh peluang karir desain komunikasi visual untuk alumni dengan portofolio kreatif.'],
            ['karir-alumni', 'Asisten Akuntansi — Koperasi Alumni Simulasi', 'Contoh peluang kerja pembukuan, pencatatan transaksi, dan pelaporan keuangan.'],
            ['beasiswa-alumni', 'Beasiswa Prestasi Cakrawala', 'Contoh bantuan biaya pendidikan untuk alumni dengan prestasi akademik dan kegiatan organisasi.'],
            ['beasiswa-alumni', 'Beasiswa Teknologi Nusantara', 'Contoh dukungan pendidikan untuk calon mahasiswa bidang teknologi dan rekayasa.'],
            ['beasiswa-alumni', 'Beasiswa Pendidikan Banua', 'Contoh program dukungan kuliah untuk alumni yang berminat pada bidang pendidikan.'],
            ['beasiswa-alumni', 'Beasiswa Wirausaha Muda', 'Contoh bantuan pengembangan kompetensi bisnis dan pendidikan bagi alumni perintis usaha.'],
        ];

        foreach ($publications as $index => [$category, $title, $excerpt]) {
            SchoolNews::query()->firstOrCreate([
                'school_id' => $school->id, 'slug' => 'demo-alumni-publikasi-'.($index + 1),
            ], [
                'title' => '[DEMO] '.$title, 'category' => $category,
                'excerpt' => '[DEMO] '.$excerpt,
                'body' => "Data fiktif untuk pratinjau portal alumni. Ini bukan penawaran kerja atau beasiswa yang sebenarnya.\n\n".$excerpt,
                'status' => 'published', 'is_archived' => false, 'published_at' => now()->subDays($index + 1),
            ]);
        }
    }
}
