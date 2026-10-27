<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teachers', function (Blueprint $table): void {
            $table->unique(['id', 'school_id'], 'teachers_id_school_unique');
        });

        Schema::create('teacher_attendance_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->unsignedBigInteger('teacher_id');
            $table->date('date');
            $table->string('status', 20)->default('present');
            $table->string('notes', 255)->nullable();
            $table->foreignId('recorded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['school_id', 'teacher_id', 'date'], 'teacher_att_school_teacher_date_uq');
            $table->unique(['id', 'school_id'], 'teacher_att_id_school_uq');
            $table->index(['school_id', 'date', 'status'], 'teacher_att_school_date_status_idx');
            $table->foreign(['teacher_id', 'school_id'], 'teacher_att_teacher_school_fk')
                ->references(['id', 'school_id'])->on('teachers')->cascadeOnDelete();
        });

        Schema::create('teacher_leave_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->unsignedBigInteger('teacher_id');
            $table->string('type', 20);
            $table->date('start_date');
            $table->date('end_date');
            $table->string('reason', 500);
            $table->string('status', 20)->default('pending');
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('review_note', 255)->nullable();
            $table->timestamps();

            $table->index(['school_id', 'status', 'start_date'], 'teacher_leave_school_status_start_idx');
            $table->index(['school_id', 'teacher_id', 'status'], 'teacher_leave_teacher_status_idx');
            $table->foreign(['teacher_id', 'school_id'], 'teacher_leave_teacher_school_fk')
                ->references(['id', 'school_id'])->on('teachers')->cascadeOnDelete();
        });

        Schema::table('schedules', function (Blueprint $table): void {
            $table->unique(['id', 'school_id'], 'schedules_id_school_unique');
        });

        Schema::create('teacher_substitute_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->unsignedBigInteger('attendance_record_id');
            $table->unsignedBigInteger('schedule_id');
            $table->unsignedBigInteger('substitute_teacher_id');
            $table->foreignId('assigned_by_user_id')->constrained('users')->restrictOnDelete();
            $table->date('date');
            $table->string('notes', 255)->nullable();
            $table->timestamps();

            $table->unique(['school_id', 'schedule_id', 'date'], 'teacher_sub_slot_date_uq');
            $table->index(['school_id', 'substitute_teacher_id', 'date'], 'teacher_sub_teacher_date_idx');
            $table->foreign(['attendance_record_id', 'school_id'], 'teacher_sub_att_school_fk')
                ->references(['id', 'school_id'])->on('teacher_attendance_records')->cascadeOnDelete();
            $table->foreign(['schedule_id', 'school_id'], 'teacher_sub_schedule_school_fk')
                ->references(['id', 'school_id'])->on('schedules')->cascadeOnDelete();
            $table->foreign(['substitute_teacher_id', 'school_id'], 'teacher_sub_teacher_school_fk')
                ->references(['id', 'school_id'])->on('teachers')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_substitute_assignments');
        Schema::dropIfExists('teacher_leave_requests');
        Schema::dropIfExists('teacher_attendance_records');
        Schema::table('schedules', function (Blueprint $table): void {
            $table->dropUnique('schedules_id_school_unique');
        });
        Schema::table('teachers', function (Blueprint $table): void {
            $table->dropUnique('teachers_id_school_unique');
        });
    }
};
