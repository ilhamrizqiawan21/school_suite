<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_shares', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('recipient_id')->constrained('users')->restrictOnDelete();
            $table->string('resource_type', 20);
            $table->unsignedBigInteger('resource_id');
            $table->string('permission', 10);
            $table->string('status', 12)->default('pending');
            // Nullable on closed requests; unique index prevents concurrent active duplicates.
            $table->string('active_key', 8)->nullable();
            $table->text('request_note')->nullable();
            $table->text('decision_note')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
            $table->unique(['school_id', 'resource_type', 'resource_id', 'recipient_id', 'active_key'], 'uq_question_shares_active');
            $table->index(['school_id', 'recipient_id', 'status'], 'idx_question_shares_inbox');
            $table->index(['school_id', 'sender_id', 'status'], 'idx_question_shares_outbox');
            $table->index(['resource_type', 'resource_id'], 'idx_question_shares_resource');
        });

        Schema::create('question_share_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('question_share_id')->constrained('question_shares')->cascadeOnDelete();
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->string('event_type', 20);
            $table->string('old_permission', 10)->nullable();
            $table->string('new_permission', 10)->nullable();
            $table->string('old_status', 12)->nullable();
            $table->string('new_status', 12)->nullable();
            $table->text('note')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['question_share_id', 'created_at'], 'idx_question_share_events_history');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_share_events');
        Schema::dropIfExists('question_shares');
    }
};
