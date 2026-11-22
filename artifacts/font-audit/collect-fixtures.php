<?php

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Tests\TestCase;

require '/home/ilhamzp/Projects/school_suite/vendor/autoload.php';
final class FontAuditFixtures extends TestCase
{
    public function collect(): void
    {
        $this->setUp();
        try {
            if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
                throw new RuntimeException('Audit requires isolated in-memory SQLite.');
            }
            $this->artisan('migrate', ['--force' => true])->assertExitCode(0);
            $this->seed(DatabaseSeeder::class);
            $pages = [];
            foreach (['/', '/login', '/profil-sekolah', '/berita', '/ppdb'] as $url) {
                $this->capture($pages, 'public'.$url, $url);
            }
            $admin = User::where('username', 'admin_pilot')->firstOrFail();
            $admin->forceFill(['must_change_password' => false])->save();
            $this->actingAs($admin);
            foreach (['/dashboard', '/akademik/siswa', '/akademik/guru', '/akademik/jadwal', '/pengaturan/pengguna', '/pembelajaran', '/ppdb/admin', '/penilaian/bank-soal'] as $url) {
                $this->capture($pages, 'admin'.$url, $url);
            }
            foreach (['teacher', 'student'] as $role) {
                $user = User::whereHas('role', fn ($q) => $q->where('name', $role))->firstOrFail();
                $user->forceFill(['must_change_password' => false])->save();
                $this->actingAs($user);
                foreach (($role === 'teacher' ? ['/dashboard', '/pembelajaran', '/penilaian/bank-soal'] : ['/dashboard', '/siswa/tugas']) as $url) {
                    $this->capture($pages, $role.$url, $url);
                }
            }
            file_put_contents('/home/ilhamzp/Projects/school_suite/artifacts/font-audit/page-fixtures.json', json_encode($pages, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            echo json_encode(array_map(fn ($v) => ['status' => $v['status'], 'component' => $v['page']['component'] ?? null], $pages), JSON_PRETTY_PRINT),"\n";
        } finally { /* Process-local in-memory database is discarded on exit. */
        }
    }

    private function capture(array &$pages, string $key, string $url): void
    {
        $response = $this->get($url);
        $pages[$key] = ['url' => $url, 'status' => $response->getStatusCode()];
        if ($response->getStatusCode() === 200) {
            $pages[$key]['page'] = $response->viewData('page');
        }
    }
}
(new FontAuditFixtures('collect'))->collect();
