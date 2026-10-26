<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admission_committee_members', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('assigned_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['school_id', 'user_id']);
        });

        Schema::create('admission_applicants', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('registration_no', 32);
            $table->string('name', 150);
            $table->string('nisn', 20)->nullable();
            $table->enum('gender', ['L', 'P']);
            $table->date('birth_date')->nullable();
            $table->string('parent_name', 150)->nullable();
            $table->string('parent_phone', 30)->nullable();
            $table->string('previous_school', 180)->nullable();
            $table->string('status', 30)->default('registered');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['school_id', 'registration_no']);
            $table->index(['school_id', 'status']);
            $table->index(['school_id', 'nisn']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admission_applicants');
        Schema::dropIfExists('admission_committee_members');
    }
};
