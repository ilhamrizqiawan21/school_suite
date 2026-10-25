<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('name', 100);
            $table->timestamps();
            $table->unique(['school_id', 'name']);
        });

        Schema::create('question_tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('name', 60);
            $table->timestamps();
            $table->unique(['school_id', 'name']);
        });

        Schema::create('question_category_links', function (Blueprint $table) {
            $table->foreignId('exam_question_id')->constrained('exam_questions')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('question_categories')->cascadeOnDelete();
            $table->primary(['exam_question_id', 'category_id']);
        });
        Schema::create('question_tag_links', function (Blueprint $table) {
            $table->foreignId('exam_question_id')->constrained('exam_questions')->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained('question_tags')->cascadeOnDelete();
            $table->primary(['exam_question_id', 'tag_id']);
        });
        Schema::create('question_version_category_links', function (Blueprint $table) {
            $table->foreignId('question_version_id')->constrained('exam_question_versions')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('question_categories')->cascadeOnDelete();
            $table->primary(['question_version_id', 'category_id']);
        });
        Schema::create('question_version_tag_links', function (Blueprint $table) {
            $table->foreignId('question_version_id')->constrained('exam_question_versions')->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained('question_tags')->cascadeOnDelete();
            $table->primary(['question_version_id', 'tag_id']);
        });

        Schema::create('question_kko_terms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->nullable()->constrained('schools')->cascadeOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained('subjects')->cascadeOnDelete();
            $table->string('term', 100);
            $table->string('cognitive_level', 5);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['school_id', 'subject_id', 'cognitive_level', 'is_active'], 'idx_kko_scope_level');
        });

        Schema::table('exam_questions', function (Blueprint $table) {
            $table->foreignId('kko_term_id')->nullable()->after('cognitive_level')->constrained('question_kko_terms')->nullOnDelete();
        });
        Schema::table('exam_question_versions', function (Blueprint $table) {
            $table->foreignId('kko_term_id')->nullable()->after('cognitive_level')->constrained('question_kko_terms')->nullOnDelete();
        });

        $terms = [
            'L1' => ['mengingat', 'menyebutkan', 'mengidentifikasi', 'mengenali', 'menentukan', 'menjelaskan'],
            'L2' => ['mengelompokkan', 'menerapkan', 'menghitung', 'membandingkan', 'menggunakan', 'menafsirkan'],
            'L3' => ['menganalisis', 'menilai', 'mengevaluasi', 'merancang', 'mencipta', 'menyimpulkan'],
        ];
        $now = now();
        foreach ($terms as $level => $verbs) {
            foreach ($verbs as $verb) {
                $termId = DB::table('question_kko_terms')->insertGetId([
                    'school_id' => null,
                    'subject_id' => null,
                    'term' => $verb,
                    'cognitive_level' => $level,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                DB::table('exam_questions')->whereRaw('LOWER(kko_text) = ?', [$verb])->where('cognitive_level', $level)->update(['kko_term_id' => $termId]);
                DB::table('exam_question_versions')->whereRaw('LOWER(kko_text) = ?', [$verb])->where('cognitive_level', $level)->update(['kko_term_id' => $termId]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('exam_question_versions', fn (Blueprint $table) => $table->dropConstrainedForeignId('kko_term_id'));
        Schema::table('exam_questions', fn (Blueprint $table) => $table->dropConstrainedForeignId('kko_term_id'));
        Schema::dropIfExists('question_version_tag_links');
        Schema::dropIfExists('question_version_category_links');
        Schema::dropIfExists('question_tag_links');
        Schema::dropIfExists('question_category_links');
        Schema::dropIfExists('question_kko_terms');
        Schema::dropIfExists('question_tags');
        Schema::dropIfExists('question_categories');
    }
};
