<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('correspondence_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('letter_type', 20);
            $table->string('agenda_number', 40)->nullable();
            $table->string('letter_number', 100)->nullable();
            $table->date('letter_date')->nullable();
            $table->date('received_at')->nullable();
            $table->string('sender', 180)->nullable();
            $table->string('recipient', 180)->nullable();
            $table->string('subject', 200);
            $table->text('summary')->nullable();
            $table->text('disposition')->nullable();
            $table->string('status', 24);
            $table->string('attachment_path')->nullable();
            $table->string('attachment_name', 255)->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();

            $table->unique(['school_id', 'agenda_number']);
            $table->index(['school_id', 'letter_type', 'status', 'created_at'], 'correspondence_school_type_status_created_idx');
            $table->index(['school_id', 'letter_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('correspondence_records');
    }
};
