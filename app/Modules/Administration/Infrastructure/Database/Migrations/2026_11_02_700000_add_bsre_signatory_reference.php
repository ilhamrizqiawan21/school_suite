<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('correspondence_signatories', function (Blueprint $table): void {
            $table->string('provider_reference', 190)->nullable()->after('user_id');
            $table->index(['school_id', 'provider_reference'], 'corr_signatories_provider_reference_idx');
        });
    }

    public function down(): void
    {
        Schema::table('correspondence_signatories', function (Blueprint $table): void {
            $table->dropIndex('corr_signatories_provider_reference_idx');
            $table->dropColumn('provider_reference');
        });
    }
};
