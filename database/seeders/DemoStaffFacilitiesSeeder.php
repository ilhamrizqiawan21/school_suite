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
        $assignee = User::query()->where('school_id', $school->id)->where('is_active', true)
            ->get()->first(fn (User $user) => $user->hasRole(['teacher', 'homeroom', 'librarian']));
        foreach (['[DEMO] Periksa kelengkapan arsip kelas', '[DEMO] Siapkan ruang pertemuan'] as $title) {
            if (! StaffTask::query()->where('school_id', $school->id)->where('title', $title)->exists()) {
                app(StaffTaskService::class)->create($school->id, $actor, [
                    'title' => $title, 'description' => 'Data contoh untuk mencoba penugasan, progres, dan riwayat pekerjaan.',
                    'category' => 'Administrasi', 'priority' => 'normal', 'due_at' => now()->addDays(3), 'assignee_id' => $assignee?->id,
                ]);
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
