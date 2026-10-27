<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('administration_staff_tasks')) {
            Schema::create('administration_staff_tasks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
                $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
                $table->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('title', 180);
                $table->text('description')->nullable();
                $table->string('category', 60)->nullable();
                $table->string('priority', 20)->default('normal');
                $table->string('status', 24)->default('assigned');
                $table->unsignedTinyInteger('progress')->default(0);
                $table->string('attachment_path')->nullable();
                $table->string('attachment_name')->nullable();
                $table->timestamp('due_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();

                $table->index(['school_id', 'status', 'due_at']);
                $table->index(['school_id', 'assignee_id', 'status']);
                $table->unique(['id', 'school_id']);
            });
        }

        if (! Schema::hasTable('administration_staff_task_activities')) {
            Schema::create('administration_staff_task_activities', function (Blueprint $table) {
                $table->id();
                $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
                $table->unsignedBigInteger('task_id');
                $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('action', 40);
                $table->json('changes')->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->index(
                    ['school_id', 'task_id', 'created_at'],
                    'admin_staff_task_activity_task_created_idx',
                );
                $table->foreign(['task_id', 'school_id'], 'admin_staff_task_activity_task_school_fk')
                    ->references(['id', 'school_id'])
                    ->on('administration_staff_tasks')
                    ->cascadeOnDelete();
            });
        }

        // MySQL may leave the table behind when a later ALTER TABLE fails during migration.
        if (! Schema::hasIndex('administration_staff_task_activities', 'admin_staff_task_activity_task_created_idx')) {
            Schema::table('administration_staff_task_activities', function (Blueprint $table) {
                $table->index(
                    ['school_id', 'task_id', 'created_at'],
                    'admin_staff_task_activity_task_created_idx',
                );
            });
        }

        if (! Schema::hasForeignKey('administration_staff_task_activities', 'admin_staff_task_activity_task_school_fk')) {
            Schema::table('administration_staff_task_activities', function (Blueprint $table) {
                $table->foreign(['task_id', 'school_id'], 'admin_staff_task_activity_task_school_fk')
                    ->references(['id', 'school_id'])
                    ->on('administration_staff_tasks')
                    ->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('administration_staff_task_activities');
        Schema::dropIfExists('administration_staff_tasks');
    }
};
