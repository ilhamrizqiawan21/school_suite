<?php

declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_questions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $t->foreignId('teaching_assignment_id')->nullable()->constrained('teaching_assignments')->nullOnDelete();
            $t->foreignId('owner_id')->constrained('users')->restrictOnDelete();
            $t->string('type', 20);
            $t->text('prompt');
            $t->json('options')->nullable();
            $t->text('correct_answer')->nullable();
            $t->text('explanation')->nullable();
            $t->decimal('points', 6, 2)->default(1);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
            $t->index(['school_id', 'owner_id', 'type']);
        });
        Schema::create('exams', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $t->foreignId('teaching_assignment_id')->constrained('teaching_assignments')->cascadeOnDelete();
            $t->foreignId('assessment_component_id')->nullable()->constrained('assessment_components')->nullOnDelete();
            $t->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $t->string('title');
            $t->text('instructions')->nullable();
            $t->timestamp('starts_at');
            $t->timestamp('ends_at');
            $t->unsignedSmallInteger('duration_minutes');
            $t->boolean('randomize_questions')->default(false);
            $t->boolean('randomize_options')->default(false);
            $t->unsignedTinyInteger('max_violations')->default(3);
            $t->string('status', 25)->default('draft');
            $t->timestamps();
            $t->index(['school_id', 'starts_at', 'status']);
        });
        Schema::create('exam_question_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('exam_id')->constrained('exams')->cascadeOnDelete();
            $t->foreignId('exam_question_id')->constrained('exam_questions')->restrictOnDelete();
            $t->unsignedSmallInteger('sequence');
            $t->decimal('points', 6, 2);
            $t->unique(['exam_id', 'exam_question_id']);
        });
        Schema::create('exam_attempts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('exam_id')->constrained('exams')->cascadeOnDelete();
            $t->foreignId('enrollment_id')->constrained('enrollments')->cascadeOnDelete();
            $t->string('status', 25)->default('in_progress');
            $t->timestamp('started_at');
            $t->timestamp('expires_at');
            $t->timestamp('last_heartbeat_at')->nullable();
            $t->timestamp('submitted_at')->nullable();
            $t->decimal('score', 7, 2)->nullable();
            $t->unsignedTinyInteger('violation_count')->default(0);
            $t->json('question_order');
            $t->unique(['exam_id', 'enrollment_id']);
            $t->index(['exam_id', 'status']);
        });
        Schema::create('exam_answers', function (Blueprint $t) {
            $t->id();
            $t->foreignId('exam_attempt_id')->constrained('exam_attempts')->cascadeOnDelete();
            $t->foreignId('exam_question_id')->constrained('exam_questions')->restrictOnDelete();
            $t->text('answer')->nullable();
            $t->boolean('is_correct')->nullable();
            $t->decimal('score', 6, 2)->nullable();
            $t->foreignId('graded_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('graded_at')->nullable();
            $t->timestamps();
            $t->unique(['exam_attempt_id', 'exam_question_id']);
        });
        Schema::create('exam_events', function (Blueprint $t) {
            $t->id();
            $t->foreignId('exam_attempt_id')->constrained('exam_attempts')->cascadeOnDelete();
            $t->string('type', 40);
            $t->json('payload')->nullable();
            $t->string('ip_address', 45)->nullable();
            $t->timestamp('occurred_at');
            $t->index(['exam_attempt_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_events');
        Schema::dropIfExists('exam_answers');
        Schema::dropIfExists('exam_attempts');
        Schema::dropIfExists('exam_question_items');
        Schema::dropIfExists('exams');
        Schema::dropIfExists('exam_questions');
    }
};
