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
            Schema::table('admission_applicants', function (Blueprint $table): void {
                $table->dropForeign('admission_applicants_created_by_foreign');
            });
        }

        Schema::table('admission_applicants', function (Blueprint $table): void {
            $table->unsignedBigInteger('created_by')->nullable()->change();
            $table->unsignedBigInteger('external_applicant_id')->nullable()->after('track_id')->index();
            $table->unsignedBigInteger('service_ticket_id')->nullable()->after('external_applicant_id')->index();
            $table->string('verification_secret_hash')->nullable()->after('service_ticket_id');
            $table->string('birth_place', 100)->nullable()->after('birth_date');
            $table->text('address')->nullable()->after('parent_phone');
            $table->text('revision_notes')->nullable()->after('notes');
            $table->string('idempotency_key', 80)->nullable()->after('revision_notes');

            $table->unique(['school_id', 'idempotency_key'], 'uniq_admissions_school_idempotency');
        });

        if (! $isSqlite) {
            Schema::table('admission_applicants', function (Blueprint $table): void {
                $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            });
        }

        Schema::table('admission_re_registrations', function (Blueprint $table): void {
            $table->timestamp('submitted_by_candidate_at')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('admission_re_registrations', function (Blueprint $table): void {
            $table->dropColumn('submitted_by_candidate_at');
        });

        $isSqlite = Schema::getConnection()->getDriverName() === 'sqlite';

        if (! $isSqlite) {
            Schema::table('admission_applicants', function (Blueprint $table): void {
                $table->dropForeign('admission_applicants_created_by_foreign');
            });
        }

        Schema::table('admission_applicants', function (Blueprint $table): void {
            $table->dropUnique('uniq_admissions_school_idempotency');
            $table->dropIndex(['external_applicant_id']);
            $table->dropIndex(['service_ticket_id']);
            $table->dropColumn([
                'external_applicant_id',
                'service_ticket_id',
                'verification_secret_hash',
                'birth_place',
                'address',
                'revision_notes',
                'idempotency_key',
            ]);
        });
    }
};
