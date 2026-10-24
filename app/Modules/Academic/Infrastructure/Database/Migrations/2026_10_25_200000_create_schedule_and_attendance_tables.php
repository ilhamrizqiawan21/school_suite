<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Jadwal Pelajaran (Schedules)
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('term_id')->constrained('terms')->cascadeOnDelete();
            $table->foreignId('class_group_id')->constrained('class_groups')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week'); // 1 = Senin, ..., 7 = Minggu
            $table->time('start_time');
            $table->time('end_time');
            $table->string('room', 50)->nullable();
            $table->enum('status', ['draft', 'published'])->default('draft');
            $table->timestamps();

            // Indeks pendukung deteksi benturan (conflict detection)
            $table->index(['term_id', 'teacher_id', 'day_of_week'], 'idx_sched_teacher_slot');
            $table->index(['term_id', 'room', 'day_of_week'], 'idx_sched_room_slot');
            $table->index(['term_id', 'class_group_id', 'day_of_week'], 'idx_sched_class_slot');
            $table->index(['term_id', 'status'], 'idx_sched_term_status');
        });

        // 2. Pertemuan / Sesi Pembelajaran Aktual (Lesson Sessions)
        Schema::create('lesson_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('schedule_id')->nullable()->constrained('schedules')->nullOnDelete();
            $table->foreignId('teaching_assignment_id')->nullable()->constrained('teaching_assignments')->nullOnDelete();
            $table->foreignId('term_id')->constrained('terms')->cascadeOnDelete();
            $table->foreignId('class_group_id')->constrained('class_groups')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedSmallInteger('meeting_number')->default(1);
            $table->enum('status', ['scheduled', 'completed', 'cancelled'])->default('scheduled');
            $table->timestamps();

            $table->index(['class_group_id', 'date'], 'idx_session_class_date');
            $table->index(['teacher_id', 'date'], 'idx_session_teacher_date');
        });

        // 3. Rekam Presensi (Attendance Records: Daily atau Per Pertemuan)
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('enrollment_id')->constrained('enrollments')->cascadeOnDelete();
            $table->foreignId('lesson_session_id')->nullable()->constrained('lesson_sessions')->cascadeOnDelete();
            $table->date('date');
            $table->enum('type', ['daily', 'session'])->default('session');
            $table->enum('status', ['present', 'sick', 'permitted', 'absent'])->default('present');
            $table->string('notes', 255)->nullable();
            $table->foreignId('recorded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // Constraint unik untuk presensi harian dan presensi sesi
            $table->index(['enrollment_id', 'date', 'type'], 'idx_att_enrollment_date_type');
            $table->index(['lesson_session_id', 'status'], 'idx_att_session_status');
        });

        // 4. Jurnal Pembelajaran Guru (Teaching Journals)
        Schema::create('teaching_journals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_session_id')->constrained('lesson_sessions')->cascadeOnDelete()->unique();
            $table->string('topic', 255);
            $table->text('learning_objectives')->nullable();
            $table->text('notes')->nullable();
            $table->json('attendance_summary')->nullable(); // Snapshot jumlah H/S/I/A
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teaching_journals');
        Schema::dropIfExists('attendance_records');
        Schema::dropIfExists('lesson_sessions');
        Schema::dropIfExists('schedules');
    }
};
