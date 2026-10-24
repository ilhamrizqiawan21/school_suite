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
        // 1. Catatan Observasi & Perkembangan Siswa (Student Notes)
        Schema::create('student_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('term_id')->constrained('terms')->cascadeOnDelete();
            $table->foreignId('class_group_id')->nullable()->constrained('class_groups')->nullOnDelete();
            $table->foreignId('author_id')->constrained('users')->cascadeOnDelete();
            $table->string('title', 200);
            $table->text('content');
            $table->enum('category', ['academic', 'behavior', 'counseling', 'general'])->default('general');
            $table->enum('access_level', ['public', 'internal', 'confidential'])->default('internal');
            $table->timestamps();

            $table->index(['school_id', 'student_id', 'access_level'], 'idx_notes_student_access');
            $table->index(['term_id', 'class_group_id'], 'idx_notes_term_class');
        });

        // 2. Insiden & Pelanggaran Tata Tertib (Student Incidents)
        Schema::create('student_incidents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('term_id')->constrained('terms')->cascadeOnDelete();
            $table->foreignId('class_group_id')->nullable()->constrained('class_groups')->nullOnDelete();
            $table->foreignId('recorded_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->date('incident_date');
            $table->string('title', 200);
            $table->text('description');
            $table->string('category', 100);
            $table->unsignedSmallInteger('penalty_points')->default(0);
            $table->text('follow_up_action')->nullable();
            $table->enum('status', ['reported', 'investigating', 'resolved'])->default('resolved');
            $table->timestamps();

            $table->index(['school_id', 'student_id'], 'idx_incidents_student');
            $table->index(['term_id', 'class_group_id'], 'idx_incidents_term_class');
        });

        // 3. Prestasi Siswa (Student Achievements)
        Schema::create('student_achievements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('term_id')->constrained('terms')->cascadeOnDelete();
            $table->string('title', 200);
            $table->enum('achievement_type', ['academic', 'sports', 'arts', 'religious', 'other'])->default('academic');
            $table->enum('level', ['school', 'district', 'city', 'province', 'national', 'international'])->default('city');
            $table->string('rank', 50); // "Juara 1", "Medali Emas", dll.
            $table->date('event_date');
            $table->string('organizer', 150)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'student_id'], 'idx_achievements_student');
            $table->index(['term_id', 'achievement_type'], 'idx_achievements_term_type');
        });

        // 4. Tindak Lanjut Bantuan & Home Visit (Assistance Records)
        Schema::create('assistance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('term_id')->constrained('terms')->cascadeOnDelete();
            $table->foreignId('facilitator_id')->constrained('users')->cascadeOnDelete();
            $table->date('date');
            $table->enum('type', ['home_visit', 'counseling', 'parent_conference', 'financial_aid'])->default('counseling');
            $table->text('summary');
            $table->text('agreed_solution')->nullable();
            $table->enum('access_level', ['internal', 'confidential'])->default('confidential');
            $table->enum('status', ['planned', 'ongoing', 'completed'])->default('completed');
            $table->timestamps();

            $table->index(['school_id', 'student_id', 'access_level'], 'idx_assist_student_access');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assistance_records');
        Schema::dropIfExists('student_achievements');
        Schema::dropIfExists('student_incidents');
        Schema::dropIfExists('student_notes');
    }
};
