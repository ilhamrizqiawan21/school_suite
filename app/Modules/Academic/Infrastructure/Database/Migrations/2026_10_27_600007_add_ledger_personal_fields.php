<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('administration_student_ledger_details', function (Blueprint $table): void {
            $table->string('nickname', 100)->nullable();
            $table->string('nik', 16)->nullable();
            $table->string('blood_type', 2)->nullable();
            $table->string('address_rt', 5)->nullable();
            $table->string('address_rw', 5)->nullable();
            $table->string('village', 100)->nullable();
            $table->string('district', 100)->nullable();
            $table->string('sports_hobby', 100)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('administration_student_ledger_details', function (Blueprint $table): void {
            $table->dropColumn([
                'nickname', 'nik', 'blood_type', 'address_rt', 'address_rw', 'village', 'district', 'sports_hobby',
            ]);
        });
    }
};
