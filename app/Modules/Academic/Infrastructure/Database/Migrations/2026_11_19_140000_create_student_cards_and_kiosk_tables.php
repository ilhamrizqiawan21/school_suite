<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_cards', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('card_uid', 80)->nullable();
            $table->string('qr_token', 80)->unique();
            $table->string('status', 20)->default('active'); // active, revoked
            $table->timestamp('issued_at');
            $table->timestamp('revoked_at')->nullable();
            $table->string('revocation_reason', 255)->nullable();
            $table->foreignId('issued_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('revoked_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['school_id', 'card_uid'], 'uniq_student_cards_school_uid');
            $table->index(['school_id', 'student_id', 'status'], 'idx_student_cards_school_student_status');
        });

        Schema::create('attendance_kiosk_devices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('location', 150)->nullable();
            $table->string('device_token_hash', 255)->nullable();
            $table->boolean('gate_enabled')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_seen_at')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['school_id', 'is_active'], 'idx_kiosk_devices_school_active');
        });

        Schema::create('attendance_kiosk_scans', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->unsignedBigInteger('kiosk_device_id')->nullable();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('student_card_id')->nullable()->constrained('student_cards')->nullOnDelete();
            $table->string('scan_method', 20)->default('rfid'); // rfid, qr, manual
            $table->string('scan_result', 30)->default('recorded'); // recorded, duplicate, rejected
            $table->string('client_event_id', 80)->nullable();
            $table->timestamp('scanned_at');
            $table->foreignId('recorded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['school_id', 'client_event_id'], 'uniq_kiosk_scans_school_client_event');
            $table->index(['school_id', 'student_id', 'scanned_at'], 'idx_kiosk_scans_school_student_time');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_kiosk_scans');
        Schema::dropIfExists('attendance_kiosk_devices');
        Schema::dropIfExists('student_cards');
    }
};
