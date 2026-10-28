<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            return;
        }

        DB::unprepared(<<<'SQL'
            CREATE TRIGGER correspondence_creator_school_insert
            BEFORE INSERT ON correspondence_records
            WHEN NEW.created_by IS NOT NULL AND NOT EXISTS (
                SELECT 1 FROM users WHERE users.id = NEW.created_by AND users.school_id = NEW.school_id
            )
            BEGIN
                SELECT RAISE(ABORT, 'Correspondence creator must belong to the record school');
            END;
        SQL);
        DB::unprepared(<<<'SQL'
            CREATE TRIGGER correspondence_creator_school_update
            BEFORE UPDATE OF school_id, created_by ON correspondence_records
            WHEN NEW.created_by IS NOT NULL AND NOT EXISTS (
                SELECT 1 FROM users WHERE users.id = NEW.created_by AND users.school_id = NEW.school_id
            )
            BEGIN
                SELECT RAISE(ABORT, 'Correspondence creator must belong to the record school');
            END;
        SQL);
        DB::unprepared(<<<'SQL'
            CREATE TRIGGER correspondence_approver_school_insert
            BEFORE INSERT ON correspondence_records
            WHEN NEW.approved_by IS NOT NULL AND NOT EXISTS (
                SELECT 1 FROM users WHERE users.id = NEW.approved_by AND users.school_id = NEW.school_id
            )
            BEGIN
                SELECT RAISE(ABORT, 'Correspondence approver must belong to the record school');
            END;
        SQL);
        DB::unprepared(<<<'SQL'
            CREATE TRIGGER correspondence_approver_school_update
            BEFORE UPDATE OF school_id, approved_by ON correspondence_records
            WHEN NEW.approved_by IS NOT NULL AND NOT EXISTS (
                SELECT 1 FROM users WHERE users.id = NEW.approved_by AND users.school_id = NEW.school_id
            )
            BEGIN
                SELECT RAISE(ABORT, 'Correspondence approver must belong to the record school');
            END;
        SQL);
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            return;
        }

        DB::unprepared('DROP TRIGGER IF EXISTS correspondence_creator_school_insert');
        DB::unprepared('DROP TRIGGER IF EXISTS correspondence_creator_school_update');
        DB::unprepared('DROP TRIGGER IF EXISTS correspondence_approver_school_insert');
        DB::unprepared('DROP TRIGGER IF EXISTS correspondence_approver_school_update');
    }
};
