<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The school policy has one fixed threshold. Normalize older drafts and
        // published exams before the controller starts enforcing it.
        DB::table('exams')->update(['max_violations' => 3]);

        if (Schema::hasTable('exam_attempts')) {
            DB::table('exam_attempts')
                ->where('violation_count', '>=', 3)
                ->whereIn('status', ['in_progress', 'locked'])
                ->update(['status' => 'cheated', 'score' => 0, 'submitted_at' => now()]);
        }
    }

    public function down(): void
    {
        // The previous threshold was configurable, so it cannot be restored
        // safely without historical values. Existing records remain valid.
    }
};
