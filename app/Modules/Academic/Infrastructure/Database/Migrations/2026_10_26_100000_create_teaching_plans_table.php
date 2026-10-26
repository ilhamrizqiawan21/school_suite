<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teaching_plans', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
            $table->foreignId('term_id')->constrained('terms')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->unsignedTinyInteger('grade_level');
            $table->string('curriculum', 30)->default('merdeka');
            $table->json('data');
            $table->timestamps();

            $table->unique(['teacher_id', 'term_id', 'subject_id', 'grade_level'], 'uniq_teacher_term_subject_grade_plan');
            $table->index(['school_id', 'term_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teaching_plans');
    }
};
