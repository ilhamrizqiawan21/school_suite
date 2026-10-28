<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('correspondence_records', function (Blueprint $table): void {
            $table->string('signer_identifier_type', 10)->nullable()->after('signer_title');
            $table->string('signer_identifier', 80)->nullable()->after('signer_identifier_type');
        });
    }

    public function down(): void
    {
        Schema::table('correspondence_records', function (Blueprint $table): void {
            $table->dropColumn(['signer_identifier_type', 'signer_identifier']);
        });
    }
};
