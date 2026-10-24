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
        // 1. Tahun Ajaran
        Schema::create('academic_years', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('name', 50); // contoh: "2025/2026"
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_active')->default(false);
            $table->timestamps();

            $table->unique(['school_id', 'name']);
        });

        // 2. Semester / Term
        Schema::create('terms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->string('name', 50); // contoh: "Semester Ganjil", "Semester Genap"
            $table->enum('semester_type', ['odd', 'even'])->default('odd');
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_active')->default(false);
            $table->timestamps();

            $table->unique(['academic_year_id', 'name']);
        });

        // 3. Mata Pelajaran (Subjects)
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('code', 30); // contoh: "MTK", "BIN"
            $table->string('name', 100);
            $table->string('category', 50)->nullable(); // contoh: "Wajib", "Pilihan", "Muatan Lokal"
            $table->unsignedSmallInteger('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['school_id', 'code']);
        });

        // 4. Guru (Teachers)
        Schema::create('teachers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('nip', 50)->nullable();
            $table->string('name', 150);
            $table->enum('gender', ['L', 'P'])->default('L');
            $table->string('phone', 30)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('employment_status', 50)->nullable(); // "PNS", "PPPK", "GTT", "Honor"
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['school_id', 'nip']);
        });

        // 5. Rombongan Belajar (Class Groups)
        Schema::create('class_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->unsignedTinyInteger('grade_level'); // 7, 8, 9 atau 10, 11, 12 atau 1-6
            $table->string('name', 50); // contoh: "7A", "X MIPA 1"
            $table->foreignId('homeroom_teacher_id')->nullable()->constrained('teachers')->nullOnDelete();
            $table->unsignedSmallInteger('capacity')->default(36);
            $table->timestamps();

            $table->unique(['academic_year_id', 'name']);
        });

        // 6. Siswa (Students) - Identitas orang murni tanpa kolom class_id langsung (SS-D-004)
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('nis', 30);
            $table->string('nisn', 30)->nullable();
            $table->string('name', 150);
            $table->enum('gender', ['L', 'P'])->default('L');
            $table->string('birth_place', 100)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('religion', 30)->nullable();
            $table->text('address')->nullable();
            $table->string('parent_phone', 30)->nullable();
            $table->string('parent_name', 150)->nullable();
            $table->enum('status', ['active', 'graduated', 'transferred', 'dropped_out'])->default('active');
            $table->timestamps();

            $table->unique(['school_id', 'nis']);
            $table->index(['school_id', 'nisn']);
            $table->index(['school_id', 'status']);
        });

        // 7. Enrollment (Penempatan Siswa di Kelas per Term / Semester) - SS-D-004
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('term_id')->constrained('terms')->cascadeOnDelete();
            $table->foreignId('class_group_id')->constrained('class_groups')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->unsignedSmallInteger('roll_number')->nullable();
            $table->enum('status', ['active', 'transferred', 'withdrawn'])->default('active');
            $table->timestamps();

            $table->unique(['term_id', 'student_id']);
            $table->index(['term_id', 'class_group_id']);
        });

        // 8. Penugasan Mengajar (Teaching Assignments per Term / Semester)
        Schema::create('teaching_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('term_id')->constrained('terms')->cascadeOnDelete();
            $table->foreignId('class_group_id')->constrained('class_groups')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
            $table->unsignedTinyInteger('hours_per_week')->default(2);
            $table->timestamps();

            $table->unique(['term_id', 'class_group_id', 'subject_id', 'teacher_id'], 'uniq_term_class_subj_teacher');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop dalam urutan terbalik untuk mencegah foreign key integrity violations
        Schema::dropIfExists('teaching_assignments');
        Schema::dropIfExists('enrollments');
        Schema::dropIfExists('students');
        Schema::dropIfExists('class_groups');
        Schema::dropIfExists('teachers');
        Schema::dropIfExists('subjects');
        Schema::dropIfExists('terms');
        Schema::dropIfExists('academic_years');
    }
};
