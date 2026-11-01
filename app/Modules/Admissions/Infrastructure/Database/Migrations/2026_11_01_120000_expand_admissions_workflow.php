<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admission_periods', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('academic_year', 20);
            $table->string('level', 10)->nullable();
            $table->string('status', 30)->default('draft');
            $table->string('guideline_path')->nullable();
            $table->date('registration_start')->nullable();
            $table->date('registration_end')->nullable();
            $table->date('verification_start')->nullable();
            $table->date('verification_end')->nullable();
            $table->date('selection_start')->nullable();
            $table->date('selection_end')->nullable();
            $table->date('announcement_at')->nullable();
            $table->date('re_registration_start')->nullable();
            $table->date('re_registration_end')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'status']);
            $table->index(['school_id', 'academic_year', 'level']);
        });

        Schema::create('admission_tracks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('period_id')->constrained('admission_periods')->cascadeOnDelete();
            $table->string('code', 30);
            $table->string('name', 100);
            $table->unsignedInteger('quota')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['period_id', 'code']);
        });

        Schema::create('admission_document_requirements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('track_id')->constrained('admission_tracks')->cascadeOnDelete();
            $table->string('code', 40);
            $table->string('label', 150);
            $table->boolean('is_required')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['track_id', 'code']);
        });

        Schema::create('admission_selection_criteria', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('track_id')->constrained('admission_tracks')->cascadeOnDelete();
            $table->string('code', 40);
            $table->string('label', 150);
            $table->decimal('weight', 8, 2)->default(1);
            $table->decimal('max_score', 8, 2)->default(100);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['track_id', 'code']);
        });

        Schema::table('admission_applicants', function (Blueprint $table): void {
            $table->foreignId('period_id')->nullable()->after('school_id')->constrained('admission_periods')->nullOnDelete();
            $table->foreignId('track_id')->nullable()->after('period_id')->constrained('admission_tracks')->nullOnDelete();
            $table->index(['school_id', 'period_id', 'status']);
            $table->index(['school_id', 'period_id', 'nisn']);
        });

        Schema::create('admission_applicant_documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('applicant_id')->constrained('admission_applicants')->cascadeOnDelete();
            $table->foreignId('requirement_id')->nullable()->constrained('admission_document_requirements')->nullOnDelete();
            $table->string('path');
            $table->string('original_name', 255);
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->string('status', 30)->default('pending');
            $table->text('notes')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->index(['applicant_id', 'status']);
        });

        Schema::create('admission_applicant_scores', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('applicant_id')->constrained('admission_applicants')->cascadeOnDelete();
            $table->foreignId('criterion_id')->constrained('admission_selection_criteria')->cascadeOnDelete();
            $table->decimal('score', 8, 2)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('scored_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['applicant_id', 'criterion_id']);
        });

        Schema::create('admission_selection_results', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('applicant_id')->unique()->constrained('admission_applicants')->cascadeOnDelete();
            $table->decimal('total_score', 10, 2)->default(0);
            $table->unsignedInteger('rank')->nullable();
            $table->string('decision', 30)->nullable();
            $table->text('override_reason')->nullable();
            $table->foreignId('finalized_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('finalized_at')->nullable();
            $table->timestamps();

            $table->index(['decision', 'rank']);
        });

        Schema::create('admission_re_registrations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('applicant_id')->unique()->constrained('admission_applicants')->cascadeOnDelete();
            $table->string('status', 30)->default('pending');
            $table->text('notes')->nullable();
            $table->foreignId('student_id')->nullable()->constrained('students')->nullOnDelete();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admission_re_registrations');
        Schema::dropIfExists('admission_selection_results');
        Schema::dropIfExists('admission_applicant_scores');
        Schema::dropIfExists('admission_applicant_documents');
        Schema::table('admission_applicants', function (Blueprint $table): void {
            $table->dropForeign(['track_id']);
            $table->dropForeign(['period_id']);
            $table->dropIndex(['school_id', 'period_id', 'status']);
            $table->dropIndex(['school_id', 'period_id', 'nisn']);
            $table->dropColumn(['period_id', 'track_id']);
        });
        Schema::dropIfExists('admission_selection_criteria');
        Schema::dropIfExists('admission_document_requirements');
        Schema::dropIfExists('admission_tracks');
        Schema::dropIfExists('admission_periods');
    }
};
