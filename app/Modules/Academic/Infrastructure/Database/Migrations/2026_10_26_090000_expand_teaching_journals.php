<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teaching_journals', function (Blueprint $table): void {
            $table->string('theme', 255)->nullable()->after('topic');
            $table->unsignedTinyInteger('period_count')->default(2)->after('theme');
            $table->text('opening_activity')->nullable()->after('learning_objectives');
            $table->text('main_activity')->nullable()->after('opening_activity');
            $table->text('closing_activity')->nullable()->after('main_activity');
            $table->text('follow_up')->nullable()->after('notes');
            $table->text('attendance_notes')->nullable()->after('follow_up');
            $table->text('observations')->nullable()->after('attendance_notes');
            $table->text('reflection')->nullable()->after('observations');
        });
    }

    public function down(): void
    {
        Schema::table('teaching_journals', function (Blueprint $table): void {
            $table->dropColumn([
                'theme',
                'period_count',
                'opening_activity',
                'main_activity',
                'closing_activity',
                'follow_up',
                'attendance_notes',
                'observations',
                'reflection',
            ]);
        });
    }
};
