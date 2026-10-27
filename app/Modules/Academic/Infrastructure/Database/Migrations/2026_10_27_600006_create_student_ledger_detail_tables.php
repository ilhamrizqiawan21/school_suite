<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table): void {
            $table->unique(['id', 'school_id'], 'students_id_school_unique');
        });

        Schema::create('administration_student_ledger_details', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->unsignedBigInteger('student_id');
            $table->string('father_name', 100)->nullable();
            $table->string('father_occupation', 60)->nullable();
            $table->string('mother_name', 100)->nullable();
            $table->string('mother_occupation', 60)->nullable();
            $table->string('previous_school', 100)->nullable();
            $table->string('diploma_number', 50)->nullable();
            $table->date('diploma_date')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'school_id'], 'student_ledger_details_student_school_uq');
            $table->foreign('school_id', 'student_ledger_details_school_fk')
                ->references('id')->on('schools')->cascadeOnDelete();
            $table->foreign(['student_id', 'school_id'], 'student_ledger_details_student_school_fk')
                ->references(['id', 'school_id'])->on('students')->cascadeOnDelete();
        });

        Schema::create('administration_student_ledger_documents', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->unsignedBigInteger('student_id');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('document_type', 100);
            $table->string('file_path');
            $table->string('original_name', 255);
            $table->unsignedInteger('size_bytes');
            $table->timestamps();

            $table->index(['school_id', 'student_id', 'created_at'], 'student_ledger_docs_school_student_idx');
            $table->foreign('school_id', 'student_ledger_docs_school_fk')
                ->references('id')->on('schools')->cascadeOnDelete();
            $table->foreign(['student_id', 'school_id'], 'student_ledger_docs_student_school_fk')
                ->references(['id', 'school_id'])->on('students')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('administration_student_ledger_documents');
        Schema::dropIfExists('administration_student_ledger_details');

        Schema::table('students', function (Blueprint $table): void {
            $table->dropUnique('students_id_school_unique');
        });
    }
};
