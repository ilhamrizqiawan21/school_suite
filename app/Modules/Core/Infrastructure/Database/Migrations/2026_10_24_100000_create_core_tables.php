<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Schools table
        Schema::create('schools', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('npsn', 20)->unique();
            $table->string('level', 20)->default('SMA'); // SD, SMP, SMA, SMK, MI, MTs, MA
            $table->text('address')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('logo_path')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        // 2. Roles table
        Schema::create('roles', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 50)->unique(); // admin, operator, principal, teacher, homeroom, counselor, librarian, student, parent
            $table->string('label', 100);
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });

        // 3. Permissions table
        Schema::create('permissions', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 100)->unique(); // e.g. core.users.view, core.users.manage
            $table->string('label', 150);
            $table->string('module', 50); // Core, Academic, Homeroom, Assessment, etc.
            $table->timestamps();
        });

        // 4. Role - Permission pivot table
        Schema::create('role_permissions', function (Blueprint $table): void {
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
            $table->primary(['role_id', 'permission_id']);
        });

        // 5. Extend users table with school, role, and status
        Schema::table('users', function (Blueprint $table): void {
            $table->foreignId('school_id')->nullable()->after('id')->constrained('schools')->nullOnDelete();
            $table->foreignId('role_id')->nullable()->after('school_id')->constrained('roles')->nullOnDelete();
            $table->string('username', 50)->nullable()->unique()->after('email');
            $table->string('phone', 30)->nullable()->after('password');
            $table->boolean('is_active')->default(true)->after('phone');
        });

        // 6. Audit Events table
        Schema::create('audit_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->nullable()->constrained('schools')->nullOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event_type', 100)->index(); // auth.login, core.user.created, etc.
            $table->string('target_type', 100)->nullable()->index(); // User, School, etc.
            $table->string('target_id', 100)->nullable()->index();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_events');

        Schema::table('users', function (Blueprint $table): void {
            $table->dropForeign(['school_id']);
            $table->dropForeign(['role_id']);
            $table->dropUnique(['username']);
            $table->dropColumn(['school_id', 'role_id', 'username', 'phone', 'is_active']);
        });

        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('schools');
    }
};
