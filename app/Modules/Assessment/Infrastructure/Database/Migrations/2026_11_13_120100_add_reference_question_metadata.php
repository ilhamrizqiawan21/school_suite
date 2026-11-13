<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['exam_questions', 'exam_question_versions'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->string('source', 500)->nullable();
                $table->string('class_label', 120)->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach (['exam_questions', 'exam_question_versions'] as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->dropColumn(['source', 'class_label']));
        }
    }
};
