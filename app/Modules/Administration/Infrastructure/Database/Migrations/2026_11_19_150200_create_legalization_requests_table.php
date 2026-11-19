<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legalization_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->unsignedBigInteger('service_ticket_id')->nullable()->index();
            $table->unsignedBigInteger('external_applicant_id')->nullable()->index();
            $table->foreignId('applicant_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('legalization_code', 40)->unique();
            $table->string('applicant_name', 150);
            $table->string('applicant_nis', 30)->nullable();
            $table->string('applicant_nisn', 30)->nullable();
            $table->unsignedSmallInteger('graduation_year');
            $table->string('phone', 30);
            $table->string('email', 150)->nullable();
            $table->string('document_type', 40)->default('ijazah'); // ijazah, skl, transkrip, rapor
            $table->string('certificate_number', 80);
            $table->unsignedTinyInteger('copy_count')->default(2);
            $table->string('purpose', 255);
            $table->string('delivery_method', 30)->default('pickup_direct'); // pickup_direct, pickup_proxy
            $table->string('proxy_name', 150)->nullable();
            $table->string('proxy_nik_last4', 8)->nullable();
            $table->string('proxy_relation', 60)->nullable();
            $table->string('proxy_phone', 30)->nullable();
            $table->text('signature_data')->nullable();
            $table->json('attachment_document_ids')->nullable();
            $table->unsignedBigInteger('power_of_attorney_document_id')->nullable();
            $table->unsignedBigInteger('sptjm_document_id')->nullable();
            $table->unsignedBigInteger('legalization_document_id')->nullable();
            $table->string('status', 30)->default('submitted'); // submitted, in_review, needs_revision, verified_original, approved, rejected, completed
            $table->timestamp('original_verified_at')->nullable();
            $table->foreignId('original_verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('original_verification_note')->nullable();
            $table->text('revision_note')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('picked_up_at')->nullable();
            $table->string('picked_up_by_name', 150)->nullable();
            $table->string('pickup_relation', 60)->nullable();
            $table->text('pickup_notes')->nullable();
            $table->string('idempotency_key', 80)->nullable();
            $table->timestamps();

            $table->unique(['school_id', 'idempotency_key'], 'uniq_legalization_school_idempotency');
            $table->index(['school_id', 'status'], 'idx_legalization_school_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legalization_requests');
    }
};
