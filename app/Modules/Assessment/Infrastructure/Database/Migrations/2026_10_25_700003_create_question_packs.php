<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_packs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('name', 180);
            $table->text('description')->nullable();
            $table->string('jenjang', 10);
            $table->string('curriculum', 20);
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->boolean('randomize_questions')->default(false);
            $table->boolean('randomize_options')->default(false);
            $table->string('status', 20)->default('draft');
            $table->timestamps();
            $table->index(['school_id', 'status', 'created_by'], 'idx_question_packs_scope_status');
        });

        Schema::create('question_pack_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_pack_id')->constrained('question_packs')->cascadeOnDelete();
            $table->foreignId('exam_question_id')->constrained('exam_questions')->restrictOnDelete();
            $table->foreignId('question_version_id')->constrained('exam_question_versions')->restrictOnDelete();
            $table->unsignedSmallInteger('sequence');
            $table->decimal('points', 6, 2);
            $table->timestamps();
            $table->unique(['question_pack_id', 'exam_question_id'], 'uq_question_pack_question');
            $table->unique(['question_pack_id', 'sequence'], 'uq_question_pack_sequence');
            $table->index(['question_version_id', 'question_pack_id'], 'idx_pack_items_version');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_pack_items');
        Schema::dropIfExists('question_packs');
    }
};
