<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_attitudes', function (Blueprint $table): void {
            $table->json('spiritual_scores')->nullable()->after('description');
            $table->json('social_scores')->nullable()->after('spiritual_scores');
        });
    }

    public function down(): void
    {
        Schema::table('student_attitudes', function (Blueprint $table): void {
            $table->dropColumn(['spiritual_scores', 'social_scores']);
        });
    }
};
