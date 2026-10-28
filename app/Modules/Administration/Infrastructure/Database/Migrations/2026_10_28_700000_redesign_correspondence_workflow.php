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
        Schema::create('correspondence_signatories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('name', 180);
            $table->string('title', 180);
            $table->string('employee_number', 80)->nullable();
            $table->string('rank', 120)->nullable();
            $table->json('template_scopes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['school_id', 'is_active'], 'corr_signatories_school_active_idx');
            $table->unique(['school_id', 'id'], 'correspondence_signatories_school_id_id_unique');
            $table->foreign(['school_id', 'user_id'], 'correspondence_signatories_school_user_foreign')
                ->references(['school_id', 'id'])->on('users')->restrictOnDelete();
        });

        Schema::table('correspondence_records', function (Blueprint $table): void {
            // Kept as a plain nullable key so SQLite does not rebuild this table and drop
            // the existing school-provenance triggers. The service validates school scope.
            $table->unsignedBigInteger('signatory_id')->nullable()->after('signer_title');
            $table->text('recipient_address')->nullable()->after('recipient');
            $table->string('opening_greeting', 500)->nullable()->after('recipient_address');
            $table->text('closing_text')->nullable()->after('letter_body');
            $table->text('carbon_copy')->nullable()->after('closing_text');
            $table->json('signer_snapshot')->nullable()->after('school_snapshot');
            $table->boolean('is_legacy')->default(false)->after('signer_snapshot');
            $table->unsignedSmallInteger('letter_year')->nullable()->after('letter_date');
        });

        DB::table('correspondence_records')->whereNotNull('letter_date')->orderBy('id')->each(function (object $record): void {
            DB::table('correspondence_records')->where('id', $record->id)->update([
                'letter_year' => (int) substr((string) $record->letter_date, 0, 4),
            ]);
        });
        Schema::table('correspondence_records', function (Blueprint $table): void {
            $table->dropUnique('correspondence_school_outgoing_number_unique');
            $table->unique(['school_id', 'letter_year', 'outgoing_letter_number'], 'correspondence_school_year_number_unique');
        });

        Schema::create('correspondence_document_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools', 'id', 'corr_doc_versions_school_fk')->cascadeOnDelete();
            $table->foreignId('correspondence_record_id')->constrained('correspondence_records', 'id', 'corr_doc_versions_record_fk')->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->string('kind', 32);
            $table->string('storage_path');
            $table->string('original_name', 255);
            $table->string('mime_type', 100)->default('application/pdf');
            $table->unsignedBigInteger('size_bytes');
            $table->string('sha256', 64);
            $table->string('signature_method', 30)->nullable();
            $table->timestamp('finalized_at')->nullable();
            $table->foreignId('created_by')->constrained('users', 'id', 'corr_doc_versions_creator_fk')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['correspondence_record_id', 'version'], 'corr_doc_versions_record_version_unique');
            $table->index(['school_id', 'correspondence_record_id', 'kind'], 'corr_doc_versions_school_record_kind_idx');
        });

        Schema::create('correspondence_signature_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools', 'id', 'corr_sig_requests_school_fk')->cascadeOnDelete();
            $table->foreignId('correspondence_record_id')->constrained('correspondence_records', 'id', 'corr_sig_requests_record_fk')->cascadeOnDelete();
            $table->foreignId('document_version_id')->constrained('correspondence_document_versions', 'id', 'corr_sig_requests_version_fk')->restrictOnDelete();
            $table->foreignId('signatory_id')->nullable()->constrained('correspondence_signatories', 'id', 'corr_sig_requests_signatory_fk')->restrictOnDelete();
            $table->string('provider', 30);
            $table->uuid('idempotency_key')->unique('corr_sig_requests_idempotency_unique');
            $table->string('provider_request_id', 190)->nullable()->unique('corr_sig_requests_provider_unique');
            $table->string('status', 30)->default('pending');
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->string('error_code', 100)->nullable();
            $table->json('certificate_metadata')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->timestamp('last_attempted_at')->nullable();
            $table->timestamps();
            $table->index(['school_id', 'status', 'created_at'], 'corr_sig_requests_school_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('correspondence_signature_requests');
        Schema::dropIfExists('correspondence_document_versions');
        Schema::table('correspondence_records', function (Blueprint $table): void {
            $table->dropUnique('correspondence_school_year_number_unique');
            $table->unique(['school_id', 'outgoing_letter_number'], 'correspondence_school_outgoing_number_unique');
            $table->dropColumn(['signatory_id', 'recipient_address', 'opening_greeting', 'closing_text', 'carbon_copy', 'signer_snapshot', 'is_legacy', 'letter_year']);
        });
        Schema::dropIfExists('correspondence_signatories');
    }
};
