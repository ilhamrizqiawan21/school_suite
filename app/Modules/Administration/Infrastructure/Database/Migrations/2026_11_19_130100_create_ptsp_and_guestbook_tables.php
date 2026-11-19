<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ptsp_services', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('code', 40);
            $table->string('name', 150);
            $table->string('category', 60)->default('persuratan');
            $table->text('description')->nullable();
            $table->json('requirements')->nullable();
            $table->json('required_documents')->nullable();
            $table->string('estimated_duration', 80)->default('2 Hari Kerja');
            $table->string('service_hours', 120)->nullable();
            $table->string('delivery_mode', 30)->default('both'); // digital, physical, both
            $table->boolean('requires_verification_secret')->default(true);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_published')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['school_id', 'code'], 'uniq_ptsp_service_school_code');
            $table->index(['school_id', 'is_published', 'is_active'], 'idx_ptsp_service_school_pub_active');
        });

        Schema::create('ptsp_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('ptsp_service_id')->constrained('ptsp_services')->restrictOnDelete();
            $table->unsignedBigInteger('service_ticket_id')->nullable()->index();
            $table->unsignedBigInteger('applicant_user_id')->nullable()->index();
            $table->unsignedBigInteger('external_applicant_id')->nullable()->index();
            $table->string('draft_token', 64)->nullable()->unique();
            $table->json('service_snapshot');
            $table->string('applicant_type', 40)->default('general');
            $table->string('applicant_name', 150);
            $table->string('applicant_phone', 30)->nullable();
            $table->string('applicant_email', 150)->nullable();
            $table->string('applicant_organization', 180)->nullable();
            $table->string('purpose', 255);
            $table->text('notes')->nullable();
            $table->json('attachment_document_ids')->nullable();
            $table->string('status', 40)->default('draft');
            $table->text('revision_note')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->string('disposition_to', 150)->nullable();
            $table->text('disposition_note')->nullable();
            $table->unsignedBigInteger('result_document_id')->nullable();
            $table->string('result_delivery_mode', 30)->nullable();
            $table->string('physical_pickup_name', 150)->nullable();
            $table->timestamp('physical_pickup_at')->nullable();
            $table->text('physical_pickup_notes')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'status'], 'idx_ptsp_req_school_status');
            $table->index(['school_id', 'created_at'], 'idx_ptsp_req_school_created');
        });

        Schema::create('guestbook_visits', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('visit_code', 40)->unique();
            $table->string('visitor_name', 150);
            $table->string('visitor_organization', 180)->nullable();
            $table->string('visitor_phone', 30)->nullable();
            $table->string('visitor_email', 150)->nullable();
            $table->string('category', 40)->default('tamu_dinas');
            $table->string('purpose', 255);
            $table->unsignedBigInteger('host_teacher_id')->nullable();
            $table->string('host_name', 150)->nullable();
            $table->string('host_unit', 120)->nullable();
            $table->string('status', 30)->default('checked_in');
            $table->timestamp('checked_in_at');
            $table->timestamp('checked_out_at')->nullable();
            $table->unsignedBigInteger('recorded_by')->nullable();
            $table->unsignedBigInteger('checked_out_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'checked_in_at'], 'idx_guestbook_school_checkin');
            $table->index(['school_id', 'status'], 'idx_guestbook_school_status');
        });

        $isSqlite = Schema::getConnection()->getDriverName() === 'sqlite';

        if (! $isSqlite) {
            Schema::table('correspondence_records', function (Blueprint $table): void {
                $table->dropForeign('correspondence_school_created_by_foreign');
            });
        }

        Schema::table('correspondence_records', function (Blueprint $table): void {
            $table->unsignedBigInteger('created_by')->nullable()->change();
            $table->unsignedBigInteger('service_ticket_id')->nullable()->after('school_id')->index();
            $table->string('sender_contact_name', 150)->nullable()->after('sender');
            $table->string('sender_contact_position', 150)->nullable()->after('sender_contact_name');
            $table->string('sender_contact_phone', 30)->nullable()->after('sender_contact_position');
            $table->string('sender_contact_email', 150)->nullable()->after('sender_contact_phone');
            $table->json('external_supporting_document_ids')->nullable()->after('attachment_name');
        });

        if (! $isSqlite) {
            Schema::table('correspondence_records', function (Blueprint $table): void {
                $table->foreign(['school_id', 'created_by'], 'correspondence_school_created_by_foreign')
                    ->references(['school_id', 'id'])->on('users')->restrictOnDelete();
            });
        } else {
            // Altering correspondence_records on SQLite rebuilds the table and drops triggers.
            // Re-create the provenance triggers from 2026_10_28_700001_enforce_correspondence_provenance.
            DB::unprepared(<<<'SQL'
                CREATE TRIGGER IF NOT EXISTS correspondence_creator_school_insert
                BEFORE INSERT ON correspondence_records
                WHEN NEW.created_by IS NOT NULL AND NOT EXISTS (
                    SELECT 1 FROM users WHERE users.id = NEW.created_by AND users.school_id = NEW.school_id
                )
                BEGIN
                    SELECT RAISE(ABORT, 'Correspondence creator must belong to the record school');
                END;
            SQL);
            DB::unprepared(<<<'SQL'
                CREATE TRIGGER IF NOT EXISTS correspondence_creator_school_update
                BEFORE UPDATE OF school_id, created_by ON correspondence_records
                WHEN NEW.created_by IS NOT NULL AND NOT EXISTS (
                    SELECT 1 FROM users WHERE users.id = NEW.created_by AND users.school_id = NEW.school_id
                )
                BEGIN
                    SELECT RAISE(ABORT, 'Correspondence creator must belong to the record school');
                END;
            SQL);
            DB::unprepared(<<<'SQL'
                CREATE TRIGGER IF NOT EXISTS correspondence_approver_school_insert
                BEFORE INSERT ON correspondence_records
                WHEN NEW.approved_by IS NOT NULL AND NOT EXISTS (
                    SELECT 1 FROM users WHERE users.id = NEW.approved_by AND users.school_id = NEW.school_id
                )
                BEGIN
                    SELECT RAISE(ABORT, 'Correspondence approver must belong to the record school');
                END;
            SQL);
            DB::unprepared(<<<'SQL'
                CREATE TRIGGER IF NOT EXISTS correspondence_approver_school_update
                BEFORE UPDATE OF school_id, approved_by ON correspondence_records
                WHEN NEW.approved_by IS NOT NULL AND NOT EXISTS (
                    SELECT 1 FROM users WHERE users.id = NEW.approved_by AND users.school_id = NEW.school_id
                )
                BEGIN
                    SELECT RAISE(ABORT, 'Correspondence approver must belong to the record school');
                END;
            SQL);
        }
    }

    public function down(): void
    {
        Schema::table('correspondence_records', function (Blueprint $table): void {
            $table->dropIndex('correspondence_records_service_ticket_id_index');
        });

        Schema::table('correspondence_records', function (Blueprint $table): void {
            $table->dropColumn([
                'service_ticket_id',
                'sender_contact_name',
                'sender_contact_position',
                'sender_contact_phone',
                'sender_contact_email',
                'external_supporting_document_ids',
            ]);
        });

        Schema::dropIfExists('guestbook_visits');
        Schema::dropIfExists('ptsp_requests');
        Schema::dropIfExists('ptsp_services');
    }
};
