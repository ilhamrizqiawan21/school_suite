<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $duplicate = DB::table('correspondence_records')
            ->select('school_id', 'letter_number')
            ->where('letter_type', 'outgoing')
            ->whereNotNull('letter_number')
            ->groupBy('school_id', 'letter_number')
            ->havingRaw('COUNT(*) > 1')
            ->first();

        if ($duplicate !== null) {
            throw new QueryException(
                DB::connection()->getName(),
                'Validate outgoing letter numbers before adding the school-scoped unique constraint.',
                [],
                new RuntimeException(sprintf(
                    'Duplicate outgoing letter number "%s" exists for school %s.',
                    $duplicate->letter_number,
                    $duplicate->school_id,
                )),
            );
        }

        Schema::table('correspondence_records', function (Blueprint $table): void {
            $table->timestamp('verification_expires_at')->nullable()->after('document_hash');
            $table->timestamp('verification_revoked_at')->nullable()->after('verification_expires_at');
            $table->string('outgoing_letter_number', 100)
                ->nullable()
                ->storedAs("CASE WHEN letter_type = 'outgoing' THEN letter_number ELSE NULL END")
                ->after('letter_number');
            $table->unique(['school_id', 'outgoing_letter_number'], 'correspondence_school_outgoing_number_unique');
        });

        DB::table('correspondence_records')
            ->whereNotNull('verification_token')
            ->whereNull('verification_expires_at')
            ->update([
                'verification_expires_at' => now()->addDays((int) config('correspondence.verification_ttl_days', 365)),
            ]);

        Schema::table('users', function (Blueprint $table): void {
            $table->unique(['school_id', 'id'], 'users_school_id_id_unique');
        });

        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            Schema::table('correspondence_records', function (Blueprint $table): void {
                $table->dropForeign('correspondence_records_created_by_foreign');
                $table->dropForeign('correspondence_records_approved_by_foreign');
                $table->foreign(['school_id', 'created_by'], 'correspondence_school_created_by_foreign')
                    ->references(['school_id', 'id'])->on('users')->restrictOnDelete();
                $table->foreign(['school_id', 'approved_by'], 'correspondence_school_approved_by_foreign')
                    ->references(['school_id', 'id'])->on('users')->restrictOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            DB::unprepared('DROP TRIGGER IF EXISTS correspondence_creator_school_insert');
            DB::unprepared('DROP TRIGGER IF EXISTS correspondence_creator_school_update');
            DB::unprepared('DROP TRIGGER IF EXISTS correspondence_approver_school_insert');
            DB::unprepared('DROP TRIGGER IF EXISTS correspondence_approver_school_update');
        } else {
            Schema::table('correspondence_records', function (Blueprint $table): void {
                $table->dropForeign('correspondence_school_created_by_foreign');
                $table->dropForeign('correspondence_school_approved_by_foreign');
                $table->foreign('created_by')->references('id')->on('users')->restrictOnDelete();
                $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
            });
        }

        Schema::table('correspondence_records', function (Blueprint $table): void {
            $table->dropUnique('correspondence_school_outgoing_number_unique');
            $table->dropColumn(['verification_expires_at', 'verification_revoked_at', 'outgoing_letter_number']);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique('users_school_id_id_unique');
        });
    }
};
