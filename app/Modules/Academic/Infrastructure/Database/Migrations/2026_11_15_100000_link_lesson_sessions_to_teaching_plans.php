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
        Schema::table('lesson_sessions', function (Blueprint $table): void {
            $table->foreignId('teaching_plan_id')->nullable()->after('teaching_assignment_id')->constrained('teaching_plans')->nullOnDelete();
            $table->string('teaching_unit_id', 64)->nullable()->after('teaching_plan_id');
            $table->index(['teaching_plan_id', 'teaching_unit_id'], 'lesson_sessions_plan_unit_idx');
        });

        DB::table('lesson_sessions')
            ->orderBy('id')
            ->chunkById(100, function ($sessions): void {
                foreach ($sessions as $session) {
                    $gradeLevel = DB::table('class_groups')->where('id', $session->class_group_id)->value('grade_level');
                    if ($gradeLevel === null) {
                        continue;
                    }

                    $planId = DB::table('teaching_plans')
                        ->where('school_id', $session->school_id)
                        ->where('teacher_id', $session->teacher_id)
                        ->where('term_id', $session->term_id)
                        ->where('subject_id', $session->subject_id)
                        ->where('grade_level', $gradeLevel)
                        ->value('id');

                    if ($planId !== null) {
                        DB::table('lesson_sessions')->where('id', $session->id)->update(['teaching_plan_id' => $planId]);
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::table('lesson_sessions', function (Blueprint $table): void {
            $table->dropIndex('lesson_sessions_plan_unit_idx');
            $table->dropConstrainedForeignId('teaching_plan_id');
            $table->dropColumn('teaching_unit_id');
        });
    }
};
