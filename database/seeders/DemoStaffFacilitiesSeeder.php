<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Administration\Application\Services\FacilitiesService;
use App\Modules\Administration\Application\Services\StaffTaskService;
use App\Modules\Administration\Infrastructure\Models\FacilityAsset;
use App\Modules\Administration\Infrastructure\Models\StaffTask;
use App\Modules\Core\Infrastructure\Models\School;
use Illuminate\Database\Seeder;

class DemoStaffFacilitiesSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }
        $school = School::query()->where('npsn', '20109988')->first();
        $actor = $school ? User::query()->where('school_id', $school->id)->where('email', 'admin@pilot.sch.id')->first() : null;
        if (! $actor) {
            return;
        }
        $principalAssignee = User::query()->where('school_id', $school->id)->where('email', 'kepsek@pilot.sch.id')->where('is_active', true)->first();
        $teachers = User::query()->where('school_id', $school->id)->where('is_active', true)
            ->get()->filter(fn (User $user) => $user->hasRole(['teacher', 'homeroom', 'librarian']))->values();
        $primaryAssignee = $teachers->firstWhere('email', 'dewi@pilot.sch.id') ?? $teachers->first();
        $secondaryAssignee = $teachers->first(fn (User $user) => $user->id !== $primaryAssignee?->id) ?? $primaryAssignee;

        $service = app(StaffTaskService::class);
        $demoTasks = [
            [
                'title' => '[DEMO] Periksa kelengkapan arsip kelas',
                'description' => 'Data contoh untuk mencoba penugasan, progres, dan riwayat pekerjaan.',
                'category' => 'Administrasi',
                'priority' => 'normal',
                'due_at' => now()->addDays(3),
                'assignee' => $secondaryAssignee,
                'progress' => 0,
                'status' => 'assigned',
            ],
            [
                'title' => '[DEMO] Siapkan ruang pertemuan',
                'description' => 'Data contoh untuk mencoba penugasan, progres, dan riwayat pekerjaan.',
                'category' => 'Sarana Prasarana',
                'priority' => 'normal',
                'due_at' => now()->addDays(3),
                'assignee' => $secondaryAssignee,
                'progress' => 0,
                'status' => 'assigned',
            ],
            [
                'title' => '[DEMO] Rekap nilai formatif IPA kelas VII-A',
                'description' => 'Kumpulkan rekap nilai formatif harian dan sinkronkan dengan dokumen penilaian semester ganjil.',
                'category' => 'Kurikulum',
                'priority' => 'high',
                'due_at' => now()->addDays(3),
                'assignee' => $primaryAssignee,
                'progress' => 0,
                'status' => 'assigned',
            ],
            [
                'title' => '[DEMO] Verifikasi alat praktikum laboratorium IPA',
                'description' => 'Periksa kelayakan mikroskop, tabung reaksi, serta catatan peminjaman alat minggu ini.',
                'category' => 'Laboratorium',
                'priority' => 'urgent',
                'due_at' => now()->addDay(),
                'assignee' => $primaryAssignee,
                'progress' => 60,
                'status' => 'in_progress',
            ],
            [
                'title' => '[DEMO] Unggah berita acara supervisi akademik',
                'description' => 'Lengkapi tanda tangan dan unggah dokumen hasil supervisi pembelajaran kelas.',
                'category' => 'Administrasi',
                'priority' => 'high',
                'due_at' => now()->subDays(2),
                'assignee' => $primaryAssignee,
                'progress' => 40,
                'status' => 'in_progress',
            ],
            [
                'title' => '[DEMO] Penyerahan daftar hadir rapat dewan guru',
                'description' => 'Rekap daftar hadir dan notulensi singkat rapat koordinasi awal semester.',
                'category' => 'Tata Usaha',
                'priority' => 'normal',
                'due_at' => now()->subDays(4),
                'assignee' => $primaryAssignee,
                'progress' => 100,
                'status' => 'completed',
            ],
            [
                'title' => '[DEMO] Inventarisasi buku pegangan guru semester ganjil',
                'description' => 'Catat jumlah buku paket IPA yang didistribusikan ke ruang guru.',
                'category' => 'Perpustakaan',
                'priority' => 'low',
                'due_at' => now()->subDays(6),
                'assignee' => $primaryAssignee,
                'progress' => 100,
                'status' => 'completed',
            ],
            [
                'title' => '[DEMO] Tinjau pengesahan surat keluar semester ganjil',
                'description' => 'Periksa draf surat undangan komite sekolah dan beri persetujuan pada modul persuratan.',
                'category' => 'Pimpinan',
                'priority' => 'urgent',
                'due_at' => now()->addDays(2),
                'assignee' => $principalAssignee,
                'progress' => 0,
                'status' => 'assigned',
            ],
            [
                'title' => '[DEMO] Evaluasi capaian kehadiran siswa bulan berjalan',
                'description' => 'Tinjau grafik kehadiran siswa per jenjang serta tindak lanjut kelas dengan tingkat izin/alpha tinggi.',
                'category' => 'Akademik',
                'priority' => 'high',
                'due_at' => now()->addDays(4),
                'assignee' => $principalAssignee,
                'progress' => 50,
                'status' => 'in_progress',
            ],
            [
                'title' => '[DEMO] Penetapan kalender rapat evaluasi tengah semester',
                'description' => 'Finalisasi jadwal rapat dewan guru untuk penelaahan kesiapan STS dan rapor sementara.',
                'category' => 'Pimpinan',
                'priority' => 'normal',
                'due_at' => now()->subDays(3),
                'assignee' => $principalAssignee,
                'progress' => 100,
                'status' => 'completed',
            ],
        ];

        foreach ($demoTasks as $item) {
            if ($item['assignee'] && ! StaffTask::query()->where('school_id', $school->id)->where('title', $item['title'])->exists()) {
                $task = $service->create($school->id, $actor, [
                    'title' => $item['title'],
                    'description' => $item['description'],
                    'category' => $item['category'],
                    'priority' => $item['priority'],
                    'due_at' => $item['due_at'],
                    'assignee_id' => $item['assignee']?->id,
                ]);
                if ($item['assignee'] && $item['status'] !== 'assigned') {
                    $service->updateProgress($task, $item['assignee'], $item['progress'], $item['status']);
                }
            }
        }
        foreach (['DEMO-INV-001' => 'Proyektor demo', 'DEMO-INV-002' => 'Laptop demo'] as $code => $name) {
            if (! FacilityAsset::query()->where('school_id', $school->id)->where('inventory_code', $code)->exists()) {
                app(FacilitiesService::class)->createAsset($actor, [
                    'inventory_code' => $code, 'name' => $name, 'category' => 'Elektronik',
                    'location' => 'Ruang tata usaha', 'condition' => 'good', 'status' => 'available', 'notes' => '[DEMO] Data latihan inventaris.',
                ]);
            }
        }
    }
}
