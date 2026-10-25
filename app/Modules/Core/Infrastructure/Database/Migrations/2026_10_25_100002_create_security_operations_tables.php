<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'must_change_password')) {
            Schema::table('users', fn (Blueprint $table) => $table->boolean('must_change_password')->default(false)->after('is_active'));
        }
        if (! Schema::hasColumn('users', 'password_changed_at')) {
            Schema::table('users', fn (Blueprint $table) => $table->timestamp('password_changed_at')->nullable()->after('must_change_password'));
        }

        Schema::create('security_blocked_ips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->nullable()->constrained('schools')->cascadeOnDelete();
            $table->string('ip_address', 45)->unique();
            $table->string('reason', 500);
            $table->foreignId('blocked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['is_active', 'expires_at']);
        });

        if (! Schema::hasTable('login_attempt_logs')) {
            Schema::create('login_attempt_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('school_id')->nullable()->constrained('schools')->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('identity_hash', 64)->index();
                $table->string('status', 20)->index();
                $table->string('reason', 80)->nullable();
                $table->string('ip_address', 45)->nullable()->index();
                $table->text('user_agent')->nullable();
                $table->timestamp('created_at')->useCurrent()->index();
            });
        }

        if (! Schema::hasTable('system_error_events')) {
            Schema::create('system_error_events', function (Blueprint $table) {
                $table->id();
                $table->foreignId('school_id')->nullable()->constrained('schools')->nullOnDelete();
                $table->string('fingerprint', 64)->index();
                $table->string('exception_class');
                $table->string('request_method', 10)->nullable();
                $table->string('request_path', 500)->nullable();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->unsignedInteger('occurrences')->default(1);
                $table->timestamp('last_seen_at');
                $table->timestamp('resolved_at')->nullable();
                $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->index(['resolved_at', 'last_seen_at']);
                $table->index(['school_id', 'last_seen_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('system_error_events');
        Schema::dropIfExists('login_attempt_logs');
        Schema::dropIfExists('security_blocked_ips');
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'must_change_password')) {
                $table->dropColumn('must_change_password');
            }
            if (Schema::hasColumn('users', 'password_changed_at')) {
                $table->dropColumn('password_changed_at');
            }
        });
    }
};
