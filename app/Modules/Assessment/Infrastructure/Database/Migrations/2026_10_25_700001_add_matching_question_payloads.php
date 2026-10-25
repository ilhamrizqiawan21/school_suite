<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_questions', function (Blueprint $table) {
            $table->json('matching_pairs')->nullable();
            $table->text('essay_rubric')->nullable();
        });

        Schema::table('exam_question_versions', function (Blueprint $table) {
            $table->json('matching_pairs')->nullable();
            $table->text('essay_rubric')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('exam_question_versions', function (Blueprint $table) {
            $table->dropColumn(['matching_pairs', 'essay_rubric']);
        });

        Schema::table('exam_questions', function (Blueprint $table) {
            $table->dropColumn(['matching_pairs', 'essay_rubric']);
        });
    }
};
