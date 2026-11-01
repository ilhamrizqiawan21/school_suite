<?php

namespace Tests\Feature;

use App\Modules\Core\Infrastructure\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_guests_can_open_the_public_school_profile_from_application_root(): void
    {
        School::create([
            'name' => 'Sekolah Uji',
            'npsn' => '12345678',
            'level' => 'SMA',
        ]);

        $this->get('/')->assertOk();
    }
}
