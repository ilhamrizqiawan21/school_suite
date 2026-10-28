<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teachers', function (Blueprint $table): void {
            $table->date('birth_date')->nullable()->after('gender');
            $table->index(['school_id', 'birth_date'], 'teachers_school_birth_date_idx');
        });
    }

    public function down(): void
    {
        Schema::table('teachers', function (Blueprint $table): void {
            $table->dropIndex('teachers_school_birth_date_idx');
            $table->dropColumn('birth_date');
        });
    }
};
