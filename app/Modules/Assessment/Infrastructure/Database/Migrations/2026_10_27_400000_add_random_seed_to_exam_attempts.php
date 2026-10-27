<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_attempts', function (Blueprint $table): void {
            $table->unsignedBigInteger('random_seed')->nullable()->after('question_order');
            $table->index(['exam_id', 'random_seed']);
        });
    }

    public function down(): void
    {
        Schema::table('exam_attempts', function (Blueprint $table): void {
            $table->dropIndex(['exam_id', 'random_seed']);
            $table->dropColumn('random_seed');
        });
    }
};
