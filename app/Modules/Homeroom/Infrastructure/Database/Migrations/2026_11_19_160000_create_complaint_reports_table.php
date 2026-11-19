<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('complaint_reports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->unsignedBigInteger('service_ticket_id')->nullable()->index();
            $table->string('report_code', 40)->unique();
            $table->string('category', 40); // akademik, perundungan, fasilitas, staf, keselamatan, lainnya
            $table->boolean('is_anonymous')->default(false);
            $table->foreignId('reporter_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reporter_name', 150)->nullable();
            $table->string('reporter_contact', 100)->nullable();
            $table->string('reporter_role', 40)->nullable();
            $table->string('title', 200);
            $table->text('description');
            $table->date('incident_date')->nullable();
            $table->string('location', 180)->nullable();
            $table->json('evidence_document_ids')->nullable();
            $table->json('resolution_document_ids')->nullable();
            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 30)->default('submitted'); // submitted, in_review, needs_revision, completed, rejected
            $table->timestamp('sla_due_at')->nullable();
            $table->text('task_force_reply')->nullable();
            $table->text('resolution_summary')->nullable();
            $table->text('moderated_public_summary')->nullable();
            $table->boolean('publish_to_transparency')->default(false);
            $table->timestamp('resolved_at')->nullable();
            $table->string('idempotency_key', 80)->nullable();
            $table->timestamps();

            $table->unique(['school_id', 'idempotency_key'], 'uniq_complaints_school_idempotency');
            $table->index(['school_id', 'status', 'category'], 'idx_complaints_school_status_cat');
            $table->index(['school_id', 'publish_to_transparency'], 'idx_complaints_school_transparency');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complaint_reports');
    }
};
