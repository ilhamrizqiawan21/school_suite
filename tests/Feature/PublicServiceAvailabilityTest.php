<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Core\Infrastructure\Models\Role;
use App\Modules\Core\Infrastructure\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PublicServiceAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('submissionRoutes')]
    public function test_closed_service_blocks_new_requests_at_the_server(string $code, string $uri): void
    {
        School::query()->create(['name' => 'Madrasah Layanan', 'npsn' => '12340002', 'level' => 'MA',
            'settings' => ['service_catalog' => [$code => ['available' => false, 'maintenance_message' => 'Pemutakhiran layanan hingga jadwal berikutnya.']]]]);
        if ($code === 'proposal-mandiri') {
            $school = School::query()->firstOrFail();
            $role = Role::query()->create(['name' => 'teacher', 'label' => 'Guru', 'is_system' => true]);
            $this->actingAs(User::query()->create(['school_id' => $school->id, 'role_id' => $role->id, 'name' => 'Guru Riset', 'email' => 'guru@example.test', 'password' => 'test-password', 'is_active' => true]));
        }
        $this->postJson($uri, [])->assertStatus(503)->assertJsonPath('message', 'Pemutakhiran layanan hingga jadwal berikutnya.');
    }

    public static function submissionRoutes(): array
    {
        return [
            ['ptsp', '/layanan/ptsp/ajukan'], ['ptsp', '/layanan/ptsp/draf'],
            ['surat-eksternal', '/layanan/surat-dinas'], ['buku-tamu', '/layanan/buku-tamu'],
            ['izin-siswa', '/layanan/izin-siswa'], ['mutasi-siswa', '/layanan/mutasi'],
            ['legalisir', '/layanan/legalisir'], ['siap-lapor', '/layanan/lapor'],
            ['tracer-alumni', '/layanan/tracer-alumni'], ['ppdb', '/ppdb/daftar'],
            ['proposal-mandiri', '/layanan/proposal'], ['perpustakaan', '/perpustakaan/aksi?pg=booking_pinjam'],
            ['perpustakaan', '/perpustakaan/aksi?pg=usulkan_buku'], ['zona-integritas', '/zona-integritas/ikm'],
        ];
    }

    public function test_maintenance_keeps_tracking_accounts_and_existing_request_revision_available(): void
    {
        School::query()->create(['name' => 'Madrasah Layanan', 'npsn' => '12340002', 'level' => 'MA',
            'settings' => ['service_catalog' => ['ptsp' => ['available' => false], 'mutasi-siswa' => ['available' => false]]]]);
        $this->get('/layanan/ptsp')->assertOk();
        $this->get('/lacak')->assertOk();
        $this->postJson('/layanan/ptsp/akun/masuk')->assertUnprocessable();
        $this->postJson('/layanan/mutasi/999999/perbaiki')->assertNotFound();
        $this->assertDatabaseCount('service_tickets', 0);
    }
}
