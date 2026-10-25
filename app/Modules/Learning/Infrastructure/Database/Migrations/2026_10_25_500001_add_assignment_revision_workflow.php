<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('learning_assignments', function (Blueprint $table) {
            $table->unsignedTinyInteger('revision_limit')->default(1)->after('allow_late');
        });
        Schema::table('assignment_submissions', function (Blueprint $table) {
            $table->string('review_status', 30)->default('pending')->after('status');
            $table->unsignedTinyInteger('revision_count')->default(0)->after('review_status');
        });
        Schema::create('assignment_submission_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_submission_id')->constrained('assignment_submissions')->cascadeOnDelete();
            $table->unsignedTinyInteger('revision_number');
            $table->text('content')->nullable();
            $table->json('file_names')->nullable();
            $table->timestamp('submitted_at');
            $table->timestamps();
            $table->unique(['assignment_submission_id', 'revision_number'], 'uniq_submission_revision');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignment_submission_revisions');
        Schema::table('assignment_submissions', fn (Blueprint $table) => $table->dropColumn(['review_status', 'revision_count']));
        Schema::table('learning_assignments', fn (Blueprint $table) => $table->dropColumn('revision_limit'));
    }
};
