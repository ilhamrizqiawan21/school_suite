<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_proposals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('service_ticket_id')->nullable()->constrained('service_tickets')->nullOnDelete();
            $table->string('proposal_mode', 24); // teacher_work | independent
            $table->string('proposal_code', 40);
            $table->string('title', 200);
            $table->string('activity_type', 48);
            $table->string('organizer_name', 150);
            $table->string('organizer_unit', 120)->nullable();
            $table->json('committee_members')->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('location', 180)->nullable();
            $table->text('purpose');
            $table->json('budget_items');
            $table->unsignedBigInteger('total_requested_amount')->default(0);
            $table->unsignedSmallInteger('revision_number')->default(1);
            $table->json('revision_history')->nullable();
            $table->foreignId('attachment_document_id')->nullable()->constrained('service_documents')->nullOnDelete();
            $table->string('status', 32)->default('submitted'); // draft | submitted | in_review | needs_revision | approved | rejected | lpj_submitted | closed
            $table->text('review_note')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('submitted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->unsignedBigInteger('lpj_realization_amount')->nullable();
            $table->text('lpj_summary')->nullable();
            $table->json('lpj_document_ids')->nullable();
            $table->timestamp('lpj_submitted_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->string('idempotency_key', 96)->nullable();
            $table->timestamps();

            $table->unique(['school_id', 'proposal_code']);
            $table->unique(['school_id', 'idempotency_key']);
            $table->index(['school_id', 'proposal_mode', 'status']);
            $table->index(['school_id', 'submitted_by_user_id']);
        });

        Schema::create('integration_connections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('system_key', 32); // emis | rdm | slims | prayer | map | chat | email | whatsapp | kiosk_hardware
            $table->string('category', 32); // academic_sync | external_channel
            $table->string('mechanism', 32)->default('file_exchange'); // link | file_exchange | api_sync | provider
            $table->boolean('is_enabled')->default(false);
            $table->string('endpoint_url', 255)->nullable();
            $table->json('config_json')->nullable();
            $table->text('secret_encrypted')->nullable();
            $table->string('secret_last4', 8)->nullable();
            $table->string('last_health_status', 24)->default('unconfigured'); // healthy | degraded | offline | unconfigured
            $table->timestamp('last_checked_at')->nullable();
            $table->text('last_error')->nullable();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['school_id', 'system_key']);
            $table->index(['school_id', 'category']);
        });

        Schema::create('integration_sync_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('system_key', 32);
            $table->string('direction', 24); // import | export | sync
            $table->string('batch_code', 40);
            $table->string('status', 24)->default('completed'); // preview | partial_failed | completed | failed
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('success_rows')->default(0);
            $table->unsignedInteger('failed_rows')->default(0);
            $table->json('failed_details_json')->nullable();
            $table->json('payload_snapshot_json')->nullable();
            $table->string('idempotency_key', 96)->nullable();
            $table->foreignId('triggered_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('retried_from_log_id')->nullable()->constrained('integration_sync_logs')->nullOnDelete();
            $table->timestamps();

            $table->unique(['school_id', 'batch_code']);
            $table->unique(['school_id', 'idempotency_key']);
            $table->index(['school_id', 'system_key', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_sync_logs');
        Schema::dropIfExists('integration_connections');
        Schema::dropIfExists('activity_proposals');
    }
};
