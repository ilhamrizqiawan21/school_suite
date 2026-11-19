<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('school_news', function (Blueprint $table): void {
            if (! Schema::hasColumn('school_news', 'category')) {
                $table->string('category', 50)->default('berita')->after('slug');
            }
            if (! Schema::hasColumn('school_news', 'video_url')) {
                $table->string('video_url', 500)->nullable()->after('body');
            }
            if (! Schema::hasColumn('school_news', 'source_url')) {
                $table->string('source_url', 500)->nullable()->after('video_url');
            }
            if (! Schema::hasColumn('school_news', 'is_archived')) {
                $table->boolean('is_archived')->default(false)->after('status');
            }
        });

        Schema::create('public_downloads', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('category', 40);
            $table->string('title', 180);
            $table->text('description')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->string('file_path', 255);
            $table->string('original_name', 255);
            $table->string('mime_type', 120);
            $table->unsignedBigInteger('size_bytes');
            $table->boolean('is_published')->default(true);
            $table->unsignedBigInteger('download_count')->default(0);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['school_id', 'is_published', 'category']);
        });

        Schema::create('public_ikm_surveys', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('respondent_name', 120)->nullable();
            $table->string('respondent_role', 40)->default('umum');
            $table->string('service_name', 120);
            $table->unsignedTinyInteger('rating');
            $table->text('comment')->nullable();
            $table->boolean('is_approved')->default(false);
            $table->string('submitted_ip_hash', 64)->nullable();
            $table->timestamps();

            $table->index(['school_id', 'is_approved']);
        });

        Schema::create('public_visit_stats', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->date('visit_date');
            $table->unsignedBigInteger('hits')->default(0);
            $table->timestamps();

            $table->unique(['school_id', 'visit_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('public_visit_stats');
        Schema::dropIfExists('public_ikm_surveys');
        Schema::dropIfExists('public_downloads');

        Schema::table('school_news', function (Blueprint $table): void {
            $columns = array_values(array_filter(
                ['category', 'video_url', 'source_url', 'is_archived'],
                static fn (string $column): bool => Schema::hasColumn('school_news', $column),
            ));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
