<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('online_class_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('term_id')->constrained('terms')->cascadeOnDelete();
            $table->foreignId('class_group_id')->constrained('class_groups')->cascadeOnDelete();
            $table->foreignId('teaching_assignment_id')->nullable()->constrained('teaching_assignments')->nullOnDelete();
            $table->string('title');
            $table->string('provider', 40)->default('other');
            $table->text('meeting_url');
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable();
            $table->string('status', 20)->default('scheduled');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['school_id', 'starts_at']);
        });

        Schema::create('online_attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('online_class_session_id')->constrained('online_class_sessions')->cascadeOnDelete();
            $table->foreignId('enrollment_id')->constrained('enrollments')->cascadeOnDelete();
            $table->string('status', 20)->default('present');
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('left_at')->nullable();
            $table->string('notes', 500)->nullable();
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['online_class_session_id', 'enrollment_id'], 'online_attendance_unique');
        });

        Schema::create('student_lifecycle_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->restrictOnDelete();
            $table->foreignId('source_enrollment_id')->nullable()->constrained('enrollments')->nullOnDelete();
            $table->foreignId('target_enrollment_id')->nullable()->constrained('enrollments')->nullOnDelete();
            $table->string('action', 20);
            $table->date('effective_date');
            $table->string('notes', 500)->nullable();
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['school_id', 'effective_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_lifecycle_events');
        Schema::dropIfExists('online_attendance_records');
        Schema::dropIfExists('online_class_sessions');
    }
};
