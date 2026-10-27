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
            $table->text('letter_body')->nullable();
            $table->string('signer_name', 180)->nullable();
            $table->string('signer_title', 180)->nullable();
            $table->string('attachment_note', 255)->nullable();
            $table->string('verification_token', 64)->nullable()->unique();
            $table->json('school_snapshot')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('correspondence_records', function (Blueprint $table): void {
            $table->dropUnique(['verification_token']);
            $table->dropColumn(['letter_body', 'signer_name', 'signer_title', 'attachment_note', 'verification_token', 'school_snapshot']);
        });
    }
};
