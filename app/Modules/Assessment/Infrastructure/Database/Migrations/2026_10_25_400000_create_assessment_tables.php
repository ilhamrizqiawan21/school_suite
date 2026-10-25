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
        // 1. Skema Penilaian Berversi (Assessment Schemes)
        Schema::create('assessment_schemes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('code', 50);
            $table->unsignedSmallInteger('version')->default(1);
            $table->boolean('is_active')->default(true);
            $table->enum('rounding_rule', ['half_up', 'floor', 'ceil'])->default('half_up');
            $table->unsignedTinyInteger('decimal_places')->default(2);
            $table->decimal('passing_grade_default', 5, 2)->default(75.00);
            $table->timestamps();

            $table->index(['school_id', 'is_active'], 'idx_schemes_school_active');
            $table->unique(['school_id', 'code', 'version'], 'uniq_scheme_code_version');
        });

        // 2. Komponen Penilaian (Assessment Components)
        Schema::create('assessment_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scheme_id')->constrained('assessment_schemes')->cascadeOnDelete();
            $table->foreignId('teaching_assignment_id')->nullable()->constrained('teaching_assignments')->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('code', 50);
            $table->decimal('weight_percentage', 5, 2); // e.g. 40.00 = 40%
            $table->decimal('passing_grade', 5, 2)->default(75.00); // KKM / KKTP
            $table->decimal('max_score', 5, 2)->default(100.00);
            $table->unsignedSmallInteger('sequence')->default(1);
            $table->timestamps();

            $table->index(['scheme_id', 'sequence'], 'idx_components_scheme_seq');
        });

        // 3. Buku Nilai Siswa (Scores per Enrollment & Component)
        Schema::create('scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('enrollment_id')->constrained('enrollments')->cascadeOnDelete();
            $table->foreignId('assessment_component_id')->constrained('assessment_components')->cascadeOnDelete();
            $table->decimal('raw_score', 5, 2)->nullable();
            $table->decimal('remedial_score', 5, 2)->nullable();
            $table->decimal('final_score', 5, 2)->nullable();
            $table->string('notes', 255)->nullable();
            $table->foreignId('recorded_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['enrollment_id', 'assessment_component_id'], 'uniq_enrollment_component');
            $table->index(['school_id', 'assessment_component_id'], 'idx_scores_school_component');
        });

        // 4. Catatan Riwayat Remedial (Remedial Records)
        Schema::create('remedial_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('score_id')->constrained('scores')->cascadeOnDelete();
            $table->decimal('initial_score', 5, 2);
            $table->decimal('remedial_score', 5, 2);
            $table->decimal('passing_grade', 5, 2);
            $table->decimal('adjusted_score', 5, 2); // min(remedial, passing_grade)
            $table->date('conducted_at');
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['score_id', 'conducted_at'], 'idx_remedial_score_date');
        });

        // 5. Snapshot Rapor Terbit Immutable (Report Snapshots - SS-D-005)
        Schema::create('report_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('enrollment_id')->constrained('enrollments')->cascadeOnDelete();
            $table->foreignId('term_id')->constrained('terms')->cascadeOnDelete();
            $table->foreignId('class_group_id')->constrained('class_groups')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->enum('status', ['draft', 'reviewed', 'published'])->default('draft');
            $table->json('scheme_snapshot');
            $table->json('grades_snapshot');
            $table->json('attendance_snapshot');
            $table->json('extracurricular_snapshot')->nullable();
            $table->json('notes_snapshot');
            $table->string('checksum', 64);
            $table->timestamp('published_at')->nullable();
            $table->foreignId('published_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['enrollment_id', 'term_id'], 'uniq_report_enrollment_term');
            $table->index(['school_id', 'term_id', 'status'], 'idx_reports_school_term_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_snapshots');
        Schema::dropIfExists('remedial_records');
        Schema::dropIfExists('scores');
        Schema::dropIfExists('assessment_components');
        Schema::dropIfExists('assessment_schemes');
    }
};
