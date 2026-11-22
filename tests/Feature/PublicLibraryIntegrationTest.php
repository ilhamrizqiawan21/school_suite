<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Academic\Infrastructure\Models\Student;
use App\Modules\Core\Application\Services\RolePermissionRegistryService;
use App\Modules\Core\Domain\RoleEnum;
use App\Modules\Core\Infrastructure\Models\School;
use App\Modules\Library\Infrastructure\Models\LibraryCollection;
use App\Modules\Library\Infrastructure\Models\LibraryLoan;
use App\Modules\Library\Infrastructure\Models\LibraryVisit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PublicLibraryIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private function fixture(): array
    {
        $roles = app(RolePermissionRegistryService::class)->syncSystemRolesAndPermissions();
        $school = School::query()->create(['name' => 'Madrasah Literasi', 'npsn' => '12340001', 'level' => 'MA']);
        $user = User::query()->create(['school_id' => $school->id, 'role_id' => $roles['student']->id,
            'name' => 'Nadia Literasi', 'username' => 'nadia', 'email' => 'nadia@example.test',
            'password' => Hash::make('library-test-password'), 'is_active' => true]);
        $student = Student::query()->create(['school_id' => $school->id, 'user_id' => $user->id,
            'name' => $user->name, 'nis' => '24001', 'nisn' => '0012345678', 'gender' => 'P', 'status' => 'active']);
        $book = LibraryCollection::query()->create(['school_id' => $school->id, 'code' => 'FIS-01',
            'title' => 'Fisika untuk Penelitian Siswa', 'author' => 'Tim Literasi', 'category' => 'buku_teks',
            'format' => 'physical', 'total_copies' => 1, 'available_copies' => 1, 'access_level' => 'public', 'is_active' => true]);

        return [$school, $user, $student, $book, $roles];
    }

    public function test_public_cards_and_detail_use_the_same_live_collection(): void
    {
        [, , , $book] = $this->fixture();
        $this->get('/perpustakaan')->assertOk()->assertSee($book->title)->assertDontSee('Ensiklopedia Sains dan Teknologi Dunia');
        $this->getJson('/perpustakaan/aksi?pg=detail_buku&id='.$book->id)->assertOk()
            ->assertJsonPath('judul', $book->title)->assertJsonPath('id_buku', $book->id)
            ->assertJsonPath('data.judul', $book->title)->assertJsonPath('jumlah_tersedia', 1);
        $book->update(['access_level' => 'staff_only']);
        $this->get('/perpustakaan')->assertDontSee($book->title);
        $this->getJson('/perpustakaan/aksi?pg=detail_buku&id='.$book->id)->assertForbidden();
    }

    public function test_library_filters_legacy_categories_and_spotlight_use_real_collections(): void
    {
        [$school, , , $book] = $this->fixture();
        $fiction = LibraryCollection::query()->create(['school_id' => $school->id, 'code' => 'FIK-01',
            'title' => 'Cerita Perjalanan Sungai', 'category' => 'fiksi', 'format' => 'physical',
            'total_copies' => 1, 'available_copies' => 1, 'access_level' => 'public', 'is_active' => true]);
        $school->update(['settings' => ['public_content' => ['published' => [
            'library.spotlight' => ['collection_id' => $book->id],
        ]]]]);
        $this->get('/perpustakaan?kat=4')->assertOk()->assertSee($fiction->title)
            ->assertViewHas('collections', fn ($items) => count($items) === 1 && $items[0]['id'] === $fiction->id)
            ->assertViewHas('featuredCollection', fn ($item) => $item['id'] === $book->id)
            ->assertDontSee('DI SUDUT HYDE PARK')->assertDontSee('bookingBuku(36', false);
        $this->get('/perpustakaan?kategori=tidak-valid')->assertOk()->assertViewHas('collections', []);
        $book->update(['is_active' => false]);
        $this->get('/perpustakaan')->assertOk()->assertViewHas('featuredCollection', null);
    }

    public function test_member_login_uses_academic_password_and_logout_requires_post(): void
    {
        [, $user, $student] = $this->fixture();
        $this->postJson('/perpustakaan/aksi?pg=login_siswa', ['username' => $student->nisn, 'password' => $student->nisn])->assertUnprocessable();
        $this->assertGuest();
        $this->postJson('/perpustakaan/aksi?pg=login_siswa', ['username' => $student->nisn, 'password' => 'library-test-password'])
            ->assertOk()->assertJsonPath('status', 'success');
        $this->assertAuthenticatedAs($user);
        $this->getJson('/perpustakaan/aksi?pg=logout_siswa')->assertStatus(405);
        $this->assertAuthenticatedAs($user);
        $this->postJson('/perpustakaan/aksi?pg=logout_siswa')->assertOk();
        $this->assertGuest();
    }

    public function test_reservation_retry_is_idempotent_and_staff_handover_does_not_decrement_twice(): void
    {
        [$school, $user, $student, $book, $roles] = $this->fixture();
        $this->postJson('/perpustakaan/aksi?pg=booking_pinjam', ['id_buku' => $book->id])->assertUnauthorized();
        $this->actingAs($user)->postJson('/perpustakaan/aksi?pg=booking_pinjam', ['id_buku' => $book->id])->assertOk();
        $this->postJson('/perpustakaan/aksi?pg=booking_pinjam', ['id_buku' => $book->id])->assertOk();
        $this->assertDatabaseCount('library_loans', 1);
        $this->assertSame(0, $book->fresh()->available_copies);
        $loan = LibraryLoan::query()->firstOrFail();
        $this->assertSame('reserved', $loan->status);
        $this->post('/perpustakaan/kelola/pinjam/'.$loan->id.'/konfirmasi')->assertForbidden();
        $librarian = User::query()->create(['school_id' => $school->id, 'role_id' => $roles['librarian']->id,
            'name' => 'Pustakawan', 'username' => 'librarian', 'email' => 'librarian@example.test', 'password' => Hash::make('test-password'), 'is_active' => true]);
        $this->actingAs($librarian)->post('/perpustakaan/kelola/pinjam/'.$loan->id.'/konfirmasi')->assertRedirect();
        $this->assertSame('borrowed', $loan->fresh()->status);
        $this->assertSame(0, $book->fresh()->available_copies);
        $this->post('/perpustakaan/kelola/pinjam/'.$loan->id.'/kembali')->assertRedirect();
        $this->post('/perpustakaan/kelola/pinjam/'.$loan->id.'/kembali')->assertRedirect();
        $this->assertSame(1, $book->fresh()->available_copies);
    }

    public function test_actual_public_form_field_names_reach_visit_and_procurement_services(): void
    {
        [, $user, $student] = $this->fixture();
        $this->postJson('/perpustakaan/aksi?pg=buku_tamu', ['tipe_tamu' => 'siswa', 'nisn_or_nis' => $student->nisn, 'keperluan' => 'Membaca'])
            ->assertOk();
        $this->assertDatabaseHas('library_visits', ['student_id' => $student->id, 'purpose' => 'Membaca']);
        $this->actingAs($user)->postJson('/perpustakaan/aksi?pg=usulkan_buku',
            ['judul_buku' => 'Atlas Ekosistem Kalimantan', 'penulis' => 'Tim Riset', 'alasan' => 'Referensi riset ekologi siswa'])
            ->assertOk();
        $this->assertDatabaseHas('library_procurement_requests', ['title' => 'Atlas Ekosistem Kalimantan', 'requester_user_id' => $user->id]);
        $this->getJson('/perpustakaan/aksi?pg=statistik_kunjungan')->assertOk()
            ->assertJsonPath('kpi.today', 1)->assertJsonPath('tipe.data.0', 1)->assertJsonMissingPath('top_visitors');
    }

    public function test_visit_accepts_nis_and_rejects_ambiguous_or_inactive_student_numbers(): void
    {
        [$school, , $student] = $this->fixture();
        $this->postJson('/perpustakaan/aksi?pg=buku_tamu', ['tipe_tamu' => 'siswa', 'nisn_or_nis' => $student->nis, 'keperluan' => 'Membaca'])->assertOk();
        $this->assertDatabaseHas('library_visits', ['student_id' => $student->id]);
        Student::query()->create(['school_id' => $school->id, 'name' => 'Siswa Nomor Beririsan',
            'nis' => '24002', 'nisn' => $student->nis, 'gender' => 'L', 'status' => 'active']);
        $this->postJson('/perpustakaan/aksi?pg=buku_tamu', ['tipe_tamu' => 'siswa', 'nisn_or_nis' => $student->nis])->assertUnprocessable();
        $student->update(['status' => 'transferred']);
        $this->postJson('/perpustakaan/aksi?pg=buku_tamu', ['tipe_tamu' => 'siswa', 'nisn_or_nis' => $student->nisn])->assertUnprocessable();
        $this->assertDatabaseCount('library_visits', 1);
    }

    public function test_daily_visit_totals_follow_school_timezone_without_counting_the_previous_school_day(): void
    {
        [$school] = $this->fixture();
        $school->update(['timezone' => 'Asia/Makassar']);
        $this->travelTo(Carbon::parse('2026-11-22T16:30:00Z'));
        foreach (['2026-11-22T12:00:00Z', '2026-11-22T16:15:00Z'] as $time) {
            LibraryVisit::query()->create([
                'school_id' => $school->id, 'visitor_name' => 'Pengunjung', 'visitor_role' => 'guest',
                'visited_at' => Carbon::parse($time)->timezone(config('app.timezone')),
            ]);
        }
        $this->get('/perpustakaan')->assertOk()->assertViewHas('stats', fn ($stats) => $stats['visits_today'] === 1 && $stats['guest_visits_today'] === 1);
        $this->getJson('/perpustakaan/aksi?pg=statistik_kunjungan')->assertOk()->assertJsonPath('kpi.today', 1)
            ->assertJsonMissingPath('kpi.pending_procurements');
        $this->travelBack();
    }

    public function test_clearance_rejects_an_unrelated_student_identifier(): void
    {
        [, $user] = $this->fixture();
        $this->actingAs($user)->postJson('/perpustakaan/aksi?pg=cek_bebas_pustaka', ['nisn_or_nis' => '9999999999'])->assertForbidden();
        $this->getJson('/perpustakaan/aksi?pg=cek_bebas_pustaka')->assertStatus(405);
        $this->assertDatabaseCount('library_clearance_certificates', 0);
    }

    #[DataProvider('roles')]
    public function test_every_role_dashboard_renders_without_invalid_tables(string $role): void
    {
        [$school, , , , $roles] = $this->fixture();
        $user = User::query()->create(['school_id' => $school->id, 'role_id' => $roles[$role]->id,
            'name' => 'Petugas '.$role, 'username' => 'role_'.$role, 'email' => $role.'@example.test',
            'password' => Hash::make('test-password'), 'is_active' => true]);
        $this->actingAs($user)->get('/dashboard')->assertOk();
    }

    public static function roles(): array
    {
        return array_map(fn (RoleEnum $role) => [$role->value], RoleEnum::cases());
    }
}
