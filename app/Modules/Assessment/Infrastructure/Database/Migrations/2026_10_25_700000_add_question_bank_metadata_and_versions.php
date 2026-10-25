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
        Schema::table('exam_questions', function (Blueprint $table) {
            $table->foreignId('subject_id')->nullable()->after('school_id')->constrained('subjects')->nullOnDelete();
            $table->string('jenjang', 10)->nullable()->after('type');
            $table->string('curriculum', 20)->nullable()->after('jenjang');
            $table->text('indicator_text')->nullable()->after('prompt');
            $table->string('cognitive_level', 5)->nullable()->after('indicator_text');
            $table->text('kko_text')->nullable()->after('cognitive_level');
            $table->unsignedInteger('current_version')->default(1)->after('is_active');
        });

        Schema::create('exam_question_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_question_id')->constrained('exam_questions')->cascadeOnDelete();
            $table->unsignedInteger('version_number');
            $table->string('type', 20);
            $table->text('prompt');
            $table->text('indicator_text')->nullable();
            $table->string('cognitive_level', 5)->nullable();
            $table->text('kko_text')->nullable();
            $table->json('options')->nullable();
            $table->text('correct_answer')->nullable();
            $table->text('explanation')->nullable();
            $table->decimal('points', 6, 2)->default(1);
            $table->foreignId('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
            $table->string('subject_code_snapshot', 30)->nullable();
            $table->string('subject_name_snapshot', 100)->nullable();
            $table->string('jenjang', 10)->nullable();
            $table->string('curriculum', 20)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['exam_question_id', 'version_number'], 'uq_question_version_number');
            $table->index(['subject_id', 'cognitive_level'], 'idx_question_version_subject_level');
        });

        Schema::table('exam_question_items', function (Blueprint $table) {
            $table->foreignId('question_version_id')->nullable()->after('exam_question_id')
                ->constrained('exam_question_versions')->nullOnDelete();
        });

        Schema::table('exam_answers', function (Blueprint $table) {
            $table->foreignId('question_version_id')->nullable()->after('exam_question_id')
                ->constrained('exam_question_versions')->nullOnDelete();
        });

        $questions = DB::table('exam_questions')
            ->leftJoin('subjects', 'subjects.id', '=', 'exam_questions.subject_id')
            ->select([
                'exam_questions.*',
                'subjects.code as subject_code_snapshot',
                'subjects.name as subject_name_snapshot',
            ])
            ->orderBy('exam_questions.id')
            ->get();

        foreach ($questions as $question) {
            $versionId = DB::table('exam_question_versions')->insertGetId([
                'exam_question_id' => $question->id,
                'version_number' => (int) $question->current_version,
                'type' => $question->type,
                'prompt' => $question->prompt,
                'indicator_text' => $question->indicator_text,
                'cognitive_level' => $question->cognitive_level,
                'kko_text' => $question->kko_text,
                'options' => $question->options,
                'correct_answer' => $question->correct_answer,
                'explanation' => $question->explanation,
                'points' => $question->points,
                'subject_id' => $question->subject_id,
                'subject_code_snapshot' => $question->subject_code_snapshot,
                'subject_name_snapshot' => $question->subject_name_snapshot,
                'jenjang' => $question->jenjang,
                'curriculum' => $question->curriculum,
                'created_by' => $question->owner_id,
                'created_at' => $question->created_at,
                'updated_at' => $question->updated_at,
            ]);

            DB::table('exam_question_items')
                ->where('exam_question_id', $question->id)
                ->update(['question_version_id' => $versionId]);

            DB::table('exam_answers')
                ->where('exam_question_id', $question->id)
                ->whereIn('exam_attempt_id', function ($query) {
                    $query->select('exam_attempts.id')
                        ->from('exam_attempts')
                        ->join('exam_question_items', 'exam_question_items.exam_id', '=', 'exam_attempts.exam_id')
                        ->whereColumn('exam_question_items.exam_question_id', 'exam_answers.exam_question_id');
                })
                ->update(['question_version_id' => $versionId]);
        }
    }

    public function down(): void
    {
        Schema::table('exam_question_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('question_version_id');
        });

        Schema::table('exam_answers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('question_version_id');
        });

        Schema::dropIfExists('exam_question_versions');

        Schema::table('exam_questions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('subject_id');
            $table->dropColumn([
                'jenjang',
                'curriculum',
                'indicator_text',
                'cognitive_level',
                'kko_text',
                'current_version',
            ]);
        });
    }
};
