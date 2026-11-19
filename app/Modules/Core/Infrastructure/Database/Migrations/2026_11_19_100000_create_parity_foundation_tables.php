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
        Schema::table('schools', function (Blueprint $table): void {
            $table->string('timezone', 64)->default('Asia/Jakarta')->after('website');
        });

        Schema::create('external_applicants', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('applicant_type', 40); // guardian, student, alumni, external_agency, candidate, general
            $table->string('full_name', 150);
            $table->string('identifier_last4', 8)->nullable();
            $table->string('identifier_hash', 128)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('organization_name', 180)->nullable();
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'applicant_type']);
            $table->index(['school_id', 'user_id']);
        });

        Schema::create('service_tickets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('service_type', 40); // ptsp, surat_eksternal, izin, mutasi, legalisir, ppdb, aduan
            $table->string('ticket_code', 40)->unique();
            $table->string('verification_secret_hash')->nullable();
            $table->foreignId('applicant_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('external_applicant_id')->nullable()->constrained('external_applicants')->nullOnDelete();
            $table->unsignedBigInteger('subject_student_id')->nullable()->index();
            $table->json('applicant_snapshot');
            $table->string('public_status', 40)->default('submitted');
            $table->string('internal_status', 50)->default('submitted');
            $table->string('public_summary', 255);
            $table->boolean('is_anonymous')->default(false);
            $table->string('idempotency_key', 80)->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->unique(['school_id', 'service_type', 'idempotency_key'], 'uniq_ticket_school_service_idempotency');
            $table->index(['school_id', 'service_type', 'public_status'], 'idx_ticket_school_service_status');
        });

        Schema::create('service_ticket_transitions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('service_ticket_id')->constrained('service_tickets')->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_role', 50)->nullable();
            $table->string('from_status', 50)->nullable();
            $table->string('to_status', 50);
            $table->string('public_status', 40);
            $table->text('reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();

            $table->index(['school_id', 'service_ticket_id'], 'idx_transition_school_ticket');
        });

        Schema::create('service_documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('service_ticket_id')->nullable()->constrained('service_tickets')->nullOnDelete();
            $table->string('document_kind', 40); // attachment, receipt, letter, skl, legalization, power_of_attorney, sptjm, evidence
            $table->string('template_key', 60)->nullable();
            $table->unsignedInteger('template_version')->default(1);
            $table->string('document_number', 80)->nullable();
            $table->string('verification_token', 64)->nullable()->unique();
            $table->string('storage_path', 255);
            $table->string('original_name', 255);
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size_bytes');
            $table->string('sha256', 64);
            $table->string('signatory_name', 150)->nullable();
            $table->string('signatory_title', 150)->nullable();
            $table->string('signature_mode', 30)->default('none'); // none, image, tte, manual_wet
            $table->string('status', 30)->default('draft'); // draft, issued, revoked
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->string('revocation_reason', 255)->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['school_id', 'document_kind', 'document_number'], 'uniq_doc_school_kind_number');
            $table->index(['school_id', 'service_ticket_id', 'status'], 'idx_doc_school_ticket_status');
        });

        Schema::create('notification_outboxes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('event_key', 80);
            $table->string('channel', 30); // in_app, email, whatsapp
            $table->string('recipient_type', 30); // user, external_applicant
            $table->unsignedBigInteger('recipient_id')->nullable();
            $table->string('template_key', 80);
            $table->json('payload');
            $table->string('idempotency_key', 80);
            $table->string('status', 30)->default('queued'); // queued, sent, failed, dead_letter
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->unsignedTinyInteger('max_attempts')->default(3);
            $table->string('last_error', 255)->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('next_retry_at')->nullable();
            $table->timestamps();

            $table->unique(['school_id', 'channel', 'idempotency_key'], 'uniq_outbox_school_channel_idempotency');
            $table->index(['school_id', 'status', 'next_retry_at'], 'idx_outbox_school_status_retry');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_outboxes');
        Schema::dropIfExists('service_documents');
        Schema::dropIfExists('service_ticket_transitions');
        Schema::dropIfExists('service_tickets');
        Schema::dropIfExists('external_applicants');

        Schema::table('schools', function (Blueprint $table): void {
            $table->dropColumn('timezone');
        });
    }
};
