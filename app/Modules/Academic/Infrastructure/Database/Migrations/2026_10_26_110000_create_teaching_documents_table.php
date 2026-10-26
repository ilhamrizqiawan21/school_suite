<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teaching_documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
            $table->foreignId('term_id')->constrained('terms')->cascadeOnDelete();
            $table->string('type', 32);
            $table->string('title', 180);
            $table->string('semester', 16)->nullable();
            $table->json('content');
            $table->timestamps();
            $table->index(['teacher_id', 'term_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teaching_documents');
    }
};
