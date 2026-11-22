<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_dispensations', function (Blueprint $table): void {
            $table->string('contact_relationship', 30)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('attendance_dispensations', function (Blueprint $table): void {
            $table->dropColumn('contact_relationship');
        });
    }
};
