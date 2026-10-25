<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_chat_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('teaching_assignment_id')->constrained('teaching_assignments')->cascadeOnDelete();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['school_id', 'teaching_assignment_id']);
        });
        Schema::create('class_chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_chat_room_id')->constrained('class_chat_rooms')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->foreignId('moderated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('moderated_at')->nullable();
            $table->string('moderation_reason', 500)->nullable();
            $table->timestamps();
            $table->index(['class_chat_room_id', 'created_at']);
        });
        Schema::create('whatsapp_message_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('recipient_name');
            $table->string('phone', 20);
            $table->string('template_key', 80);
            $table->text('message');
            $table->string('status', 20)->default('prepared');
            $table->foreignId('prepared_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('prepared_at');
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->index(['school_id', 'status', 'prepared_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_message_logs');
        Schema::dropIfExists('class_chat_messages');
        Schema::dropIfExists('class_chat_rooms');
    }
};
