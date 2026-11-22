<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('public_ikm_surveys', function (Blueprint $table): void {
            $table->json('instrument_snapshot')->nullable();
        });
    }

    public function down(): void
    {
        // Keep historical survey snapshots when application code is rolled back.
    }
};
