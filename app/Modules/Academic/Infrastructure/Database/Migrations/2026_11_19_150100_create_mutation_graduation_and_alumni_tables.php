<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            Schema::table('enrollments', function (Blueprint $table): void {
                $table->string('status', 30)->default('active')->change();
            });
        }

        Schema::create('student_mutations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('term_id')->nullable()->constrained('terms')->nullOnDelete();
            $table->unsignedBigInteger('service_ticket_id')->nullable()->index();
            $table->string('direction', 20); // incoming, outgoing
            $table->foreignId('student_id')->nullable()->constrained('students')->nullOnDelete();
            $table->foreignId('target_class_group_id')->nullable()->constrained('class_groups')->nullOnDelete();
            $table->foreignId('submitted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('mutation_code', 40)->unique();
            $table->string('student_name', 150);
            $table->string('nis', 30)->nullable();
            $table->string('nisn', 30)->nullable();
            $table->string('gender', 5)->default('L');
            $table->date('birth_date')->nullable();
            $table->string('origin_school', 180)->nullable();
            $table->string('destination_school', 180)->nullable();
            $table->text('reason');
            $table->string('applicant_name', 150)->nullable();
            $table->string('applicant_phone', 30)->nullable();
            $table->json('attachment_document_ids')->nullable();
            $table->unsignedBigInteger('official_letter_document_id')->nullable();
            $table->string('status', 30)->default('submitted'); // submitted, in_review, needs_revision, approved, rejected, cancelled
            $table->text('decision_note')->nullable();
            $table->text('revision_note')->nullable();
            $table->text('revocation_reason')->nullable();
            $table->foreignId('decided_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->string('idempotency_key', 80)->nullable();
            $table->timestamps();

            $table->unique(['school_id', 'idempotency_key'], 'uniq_mutations_school_idempotency');
            $table->index(['school_id', 'direction', 'status'], 'idx_mutations_school_dir_status');
        });

        Schema::create('graduation_batches', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->string('title', 150);
            $table->string('skl_prefix', 40)->default('SKL');
            $table->timestamp('release_at')->nullable();
            $table->boolean('is_published')->default(false);
            $table->string('signatory_name', 150)->nullable();
            $table->string('signatory_title', 150)->nullable();
            $table->foreignId('published_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'academic_year_id', 'is_published'], 'idx_grad_batch_school_year_pub');
        });

        Schema::create('graduation_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('batch_id')->constrained('graduation_batches')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('enrollment_id')->nullable()->constrained('enrollments')->nullOnDelete();
            $table->string('decision', 30)->default('graduated'); // graduated, not_graduated
            $table->string('skl_number', 80)->nullable();
            $table->decimal('average_score', 6, 2)->nullable();
            $table->unsignedBigInteger('skl_document_id')->nullable();
            $table->string('access_pin_hash')->nullable();
            $table->unsignedSmallInteger('reissue_count')->default(0);
            $table->text('reissue_reason')->nullable();
            $table->timestamp('original_collected_at')->nullable();
            $table->string('original_collected_by', 150)->nullable();
            $table->string('collector_relation', 60)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['batch_id', 'student_id'], 'uniq_grad_record_batch_student');
            $table->index(['school_id', 'student_id'], 'idx_grad_record_school_student');
        });

        Schema::create('alumni_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('student_id')->nullable()->constrained('students')->nullOnDelete();
            $table->unsignedBigInteger('external_applicant_id')->nullable()->index();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('nis', 30)->nullable();
            $table->string('nisn', 30)->nullable();
            $table->string('full_name', 150);
            $table->unsignedSmallInteger('graduation_year');
            $table->string('major_or_class', 100)->nullable();
            $table->string('current_status', 40)->default('studying'); // studying, working, entrepreneur, job_seeking, other
            $table->string('institution_or_company', 180)->nullable();
            $table->string('position_or_program', 150)->nullable();
            $table->text('career_notes')->nullable();
            $table->text('scholarship_info')->nullable();
            $table->text('testimonial')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email', 150)->nullable();
            $table->unsignedBigInteger('proof_document_id')->nullable();
            $table->boolean('opt_in_directory')->default(false);
            $table->boolean('opt_in_contact')->default(false);
            $table->boolean('opt_in_career')->default(false);
            $table->boolean('opt_in_testimonial')->default(false);
            $table->string('moderation_status', 30)->default('pending'); // pending, approved, rejected
            $table->text('moderation_note')->nullable();
            $table->foreignId('moderated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('moderated_at')->nullable();
            $table->string('ekta_number', 60)->nullable();
            $table->unsignedBigInteger('ekta_document_id')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'graduation_year', 'moderation_status'], 'idx_alumni_school_year_mod');
            $table->index(['school_id', 'nisn'], 'idx_alumni_school_nisn');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alumni_profiles');
        Schema::dropIfExists('graduation_records');
        Schema::dropIfExists('graduation_batches');
        Schema::dropIfExists('student_mutations');
    }
};
