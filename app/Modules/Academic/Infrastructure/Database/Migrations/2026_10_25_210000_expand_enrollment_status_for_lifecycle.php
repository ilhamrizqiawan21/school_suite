<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE enrollments MODIFY status ENUM('active', 'transferred', 'withdrawn', 'promoted', 'retained', 'graduated') NOT NULL DEFAULT 'active'");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("UPDATE enrollments SET status = 'withdrawn' WHERE status IN ('promoted', 'retained', 'graduated')");
        DB::statement("ALTER TABLE enrollments MODIFY status ENUM('active', 'transferred', 'withdrawn') NOT NULL DEFAULT 'active'");
    }
};
