<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('external_applicants', function (Blueprint $table): void {
            $table->string('password_hash')->nullable()->after('email');
            $table->string('recovery_token_hash')->nullable()->after('password_hash');
            $table->timestamp('recovery_expires_at')->nullable()->after('recovery_token_hash');
            $table->timestamp('last_login_at')->nullable()->after('verified_at');

            $table->index(['school_id', 'email'], 'idx_ext_applicant_school_email');
        });
    }

    public function down(): void
    {
        Schema::table('external_applicants', function (Blueprint $table): void {
            $table->dropIndex('idx_ext_applicant_school_email');
            $table->dropColumn([
                'password_hash',
                'recovery_token_hash',
                'recovery_expires_at',
                'last_login_at',
            ]);
        });
    }
};
