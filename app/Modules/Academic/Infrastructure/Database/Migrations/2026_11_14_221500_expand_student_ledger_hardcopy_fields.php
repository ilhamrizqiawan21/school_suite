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
            $table->string('blood_type', 15)->nullable()->change();

            // A. Keterangan Pribadi Siswa
            $table->string('kk_number', 16)->nullable();
            $table->string('citizenship', 30)->nullable();
            $table->unsignedTinyInteger('child_order')->nullable();
            $table->unsignedTinyInteger('siblings_count')->nullable();
            $table->string('family_status', 40)->nullable();
            $table->string('daily_language', 60)->nullable();

            // B. Keterangan Tempat Tinggal & Kesehatan
            $table->string('city', 100)->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->string('living_with', 60)->nullable();
            $table->decimal('distance_km', 5, 2)->nullable();
            $table->unsignedSmallInteger('height_cm')->nullable();
            $table->unsignedSmallInteger('weight_kg')->nullable();
            $table->string('health_history', 255)->nullable();
            $table->string('arts_hobby', 100)->nullable();

            // C. Keterangan Orang Tua / Wali
            $table->string('father_nik', 16)->nullable();
            $table->string('father_education', 40)->nullable();
            $table->string('father_income', 60)->nullable();
            $table->string('father_phone', 30)->nullable();
            $table->string('mother_nik', 16)->nullable();
            $table->string('mother_education', 40)->nullable();
            $table->string('mother_income', 60)->nullable();
            $table->string('mother_phone', 30)->nullable();
            $table->string('guardian_relation', 60)->nullable();
            $table->string('guardian_occupation', 60)->nullable();
            $table->string('guardian_address', 255)->nullable();

            // D. Keterangan Pendidikan Sebelumnya & Penerimaan
            $table->date('accepted_date')->nullable();
            $table->string('accepted_grade', 30)->nullable();
            $table->string('admission_type', 40)->nullable();
            $table->string('study_duration_years', 30)->nullable();

            // E. Keterangan Meninggalkan Sekolah / Tamat Belajar
            $table->date('graduation_date')->nullable();
            $table->string('graduation_diploma_number', 60)->nullable();
            $table->string('continuing_to', 120)->nullable();
            $table->string('leaving_reason', 255)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('administration_student_ledger_details', function (Blueprint $table): void {
            $table->dropColumn([
                'kk_number',
                'citizenship',
                'child_order',
                'siblings_count',
                'family_status',
                'daily_language',
                'city',
                'postal_code',
                'living_with',
                'distance_km',
                'height_cm',
                'weight_kg',
                'health_history',
                'arts_hobby',
                'father_nik',
                'father_education',
                'father_income',
                'father_phone',
                'mother_nik',
                'mother_education',
                'mother_income',
                'mother_phone',
                'guardian_relation',
                'guardian_occupation',
                'guardian_address',
                'accepted_date',
                'accepted_grade',
                'admission_type',
                'study_duration_years',
                'graduation_date',
                'graduation_diploma_number',
                'continuing_to',
                'leaving_reason',
            ]);
        });
    }
};
