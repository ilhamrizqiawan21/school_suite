<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $isSqlite = Schema::getConnection()->getDriverName() === 'sqlite';

        if (! $isSqlite) {
            Schema::table('attendance_dispensations', function (Blueprint $table): void {
                $table->dropForeign('attendance_dispensations_submitted_by_user_id_foreign');
            });
        }

        Schema::table('attendance_dispensations', function (Blueprint $table): void {
            $table->unsignedBigInteger('submitted_by_user_id')->nullable()->change();
            $table->string('status', 30)->default('pending')->change();
            $table->unsignedBigInteger('service_ticket_id')->nullable()->after('term_id')->index();
            $table->unsignedBigInteger('attachment_document_id')->nullable()->after('attachment_path');
            $table->string('contact_name', 150)->nullable()->after('reason');
            $table->string('contact_phone', 30)->nullable()->after('contact_name');
            $table->text('signature_data')->nullable()->after('contact_phone');
            $table->text('decision_note')->nullable()->after('approved_by_user_id');
            $table->timestamp('decided_at')->nullable()->after('decision_note');
            $table->text('correction_reason')->nullable()->after('decided_at');
            $table->string('idempotency_key', 80)->nullable()->after('notes');

            $table->unique(['school_id', 'idempotency_key'], 'uniq_dispensations_school_idempotency');
        });

        if (! $isSqlite) {
            Schema::table('attendance_dispensations', function (Blueprint $table): void {
                $table->foreign('submitted_by_user_id')->references('id')->on('users')->nullOnDelete();
            });
        }

        Schema::create('student_permit_tokens', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('purpose', 60)->default('permit_submission');
            $table->string('token_hash', 255);
            $table->string('token_prefix', 24)->index();
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->unsignedBigInteger('used_by_dispensation_id')->nullable();
            $table->foreignId('issued_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['school_id', 'student_id', 'used_at'], 'idx_permit_tokens_school_student_used');
        });

        Schema::create('gate_passes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('term_id')->constrained('terms')->cascadeOnDelete();
            $table->string('pass_code', 40)->unique();
            $table->string('exit_type', 30)->default('temporary_out'); // temporary_out, early_home, school_duty
            $table->string('reason', 255);
            $table->string('destination', 180)->nullable();
            $table->timestamp('valid_from');
            $table->timestamp('valid_until');
            $table->string('status', 30)->default('active'); // active, out, returned, expired, cancelled
            $table->foreignId('issued_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('exit_recorded_at')->nullable();
            $table->foreignId('exit_recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('return_recorded_at')->nullable();
            $table->foreignId('return_recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason', 255)->nullable();
            $table->text('handover_notes')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'status', 'valid_until'], 'idx_gate_passes_school_status_valid');
            $table->index(['school_id', 'student_id'], 'idx_gate_passes_school_student');
        });

        Schema::table('student_incidents', function (Blueprint $table): void {
            $table->string('incident_time', 10)->nullable()->after('incident_date');
            $table->string('location', 150)->nullable()->after('incident_time');
            $table->json('evidence_document_ids')->nullable()->after('follow_up_action');
            $table->boolean('is_bk_confidential')->default(false)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('student_incidents', function (Blueprint $table): void {
            $table->dropColumn(['incident_time', 'location', 'evidence_document_ids', 'is_bk_confidential']);
        });

        Schema::dropIfExists('gate_passes');
        Schema::dropIfExists('student_permit_tokens');

        Schema::table('attendance_dispensations', function (Blueprint $table): void {
            $table->dropUnique('uniq_dispensations_school_idempotency');
            $table->dropIndex('attendance_dispensations_service_ticket_id_index');
        });

        Schema::table('attendance_dispensations', function (Blueprint $table): void {
            $table->dropColumn([
                'service_ticket_id',
                'attachment_document_id',
                'contact_name',
                'contact_phone',
                'signature_data',
                'decision_note',
                'decided_at',
                'correction_reason',
                'idempotency_key',
            ]);
        });
    }
};
