<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('alumni_profiles', function (Blueprint $table): void {
            $table->string('diploma_serial', 100)->nullable();
            $table->string('activity_location', 150)->nullable();
            $table->string('residence_city', 150)->nullable();
            $table->string('linkedin_url', 255)->nullable();
            $table->string('instagram_handle', 50)->nullable();
            $table->unsignedBigInteger('photo_document_id')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('alumni_profiles', function (Blueprint $table): void {
            $table->dropColumn(['diploma_serial', 'activity_location', 'residence_city', 'linkedin_url', 'instagram_handle', 'photo_document_id']);
        });
    }
};
