<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('correspondence_records', function (Blueprint $table): void {
            $table->string('letter_template', 40)->default('official_school')->after('letter_type');
            $table->string('classification', 40)->nullable()->after('subject');
            $table->string('urgency', 20)->nullable()->after('classification');
            $table->boolean('approval_required')->default(false)->after('status');
            $table->string('approval_status', 20)->default('not_required')->after('approval_required');
            $table->foreignId('approved_by')->nullable()->after('approval_status')->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->text('approval_note')->nullable()->after('approved_at');
            $table->string('signature_method', 30)->default('manual')->after('approval_note');
            $table->string('document_hash', 64)->nullable()->after('verification_token');

            $table->index(['school_id', 'approval_status', 'created_at'], 'correspondence_school_approval_created_idx');
        });
    }

    public function down(): void
    {
        Schema::table('correspondence_records', function (Blueprint $table): void {
            $table->dropForeign(['approved_by']);
            $table->dropIndex('correspondence_school_approval_created_idx');
            $table->dropColumn([
                'letter_template', 'classification', 'urgency', 'approval_required', 'approval_status',
                'approved_by', 'approved_at', 'approval_note', 'signature_method', 'document_hash',
            ]);
        });
    }
};
