<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('library_collections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('code', 60);
            $table->string('title', 200);
            $table->string('author', 150)->nullable();
            $table->string('publisher', 150)->nullable();
            $table->unsignedSmallInteger('publish_year')->nullable();
            $table->string('category', 50)->default('buku_teks'); // buku_teks, referensi, fiksi, jurnal, agama, ebook, lainnya
            $table->string('format', 20)->default('physical'); // physical, ebook, hybrid
            $table->unsignedInteger('total_copies')->default(1);
            $table->unsignedInteger('available_copies')->default(1);
            $table->string('shelf_location', 80)->nullable();
            $table->text('description')->nullable();
            $table->string('ebook_url', 500)->nullable();
            $table->unsignedBigInteger('ebook_document_id')->nullable();
            $table->string('access_level', 30)->default('public'); // public, members_only, staff_only
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['school_id', 'code'], 'uniq_lib_collections_school_code');
            $table->index(['school_id', 'category', 'is_active'], 'idx_lib_collections_cat_active');
        });

        Schema::create('library_visits', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->unsignedBigInteger('student_id')->nullable()->index();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('visitor_name', 150);
            $table->string('visitor_identifier', 60)->nullable();
            $table->string('visitor_role', 30)->default('student'); // student, teacher, guest
            $table->string('check_in_method', 20)->default('manual'); // qr, manual
            $table->string('purpose', 180)->nullable();
            $table->timestamp('visited_at');
            $table->timestamps();

            $table->index(['school_id', 'visited_at'], 'idx_lib_visits_school_time');
        });

        Schema::create('library_loans', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('collection_id')->constrained('library_collections')->cascadeOnDelete();
            $table->unsignedBigInteger('student_id')->nullable()->index();
            $table->foreignId('borrower_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('borrower_name', 150);
            $table->string('borrower_identifier', 60)->nullable();
            $table->string('loan_code', 40)->unique();
            $table->date('borrowed_at');
            $table->date('due_at');
            $table->date('returned_at')->nullable();
            $table->string('status', 30)->default('borrowed'); // borrowed, returned, overdue, lost
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['school_id', 'student_id', 'status'], 'idx_lib_loans_school_student_status');
        });

        Schema::create('library_procurement_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('requester_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('requester_name', 150);
            $table->string('title', 200);
            $table->string('author', 150)->nullable();
            $table->string('publisher', 150)->nullable();
            $table->string('isbn', 40)->nullable();
            $table->text('reason');
            $table->unsignedSmallInteger('estimated_copies')->default(1);
            $table->string('status', 30)->default('submitted'); // submitted, approved, rejected, acquired
            $table->text('decision_note')->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'status'], 'idx_lib_proc_school_status');
        });

        Schema::create('library_clearance_certificates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->unsignedBigInteger('student_id')->index();
            $table->unsignedBigInteger('service_ticket_id')->nullable()->index();
            $table->string('clearance_code', 40)->unique();
            $table->string('status', 30)->default('pending'); // cleared, blocked_active_loan
            $table->unsignedInteger('active_loan_count')->default(0);
            $table->unsignedBigInteger('clearance_document_id')->nullable();
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('issued_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'student_id'], 'idx_lib_clearance_school_student');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('library_clearance_certificates');
        Schema::dropIfExists('library_procurement_requests');
        Schema::dropIfExists('library_loans');
        Schema::dropIfExists('library_visits');
        Schema::dropIfExists('library_collections');
    }
};
