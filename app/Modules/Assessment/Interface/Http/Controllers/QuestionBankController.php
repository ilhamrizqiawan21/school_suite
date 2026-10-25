<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Interface\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academic\Application\Services\AcademicQueryService;
use App\Modules\Assessment\Application\Services\QuestionSpreadsheetService;
use App\Modules\Assessment\Infrastructure\Models\ExamQuestion;
use App\Modules\Assessment\Infrastructure\Models\ExamQuestionVersion;
use App\Modules\Assessment\Infrastructure\Models\QuestionCategory;
use App\Modules\Assessment\Infrastructure\Models\QuestionKkoTerm;
use App\Modules\Assessment\Infrastructure\Models\QuestionTag;
use App\Shared\Audit\AuditLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class QuestionBankController extends Controller
{
    public function __construct(
        private readonly AcademicQueryService $academicQuery,
        private readonly AuditLogger $audit,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorizeManager($request);
        $user = $request->user();
        $schoolId = (int) $user->school_id;
        $filters = $request->only(['search', 'type', 'subject_id', 'jenjang', 'curriculum', 'cognitive_level', 'category_id', 'tag_id', 'status']);

        $query = ExamQuestion::query()
            ->leftJoin('subjects', 'subjects.id', '=', 'exam_questions.subject_id')
            ->where('exam_questions.school_id', $schoolId)
            ->when($user->hasRole(['teacher', 'homeroom']), fn (Builder $q) => $q->where(function (Builder $scope) use ($user): void {
                $scope->where('exam_questions.owner_id', $user->id)->orWhereExists(function ($shares) use ($user): void {
                    $shares->selectRaw('1')->from('question_shares')->whereColumn('question_shares.resource_id', 'exam_questions.id')
                        ->where('question_shares.resource_type', 'question')->where('question_shares.school_id', $user->school_id)
                        ->where('question_shares.recipient_id', $user->id)->where('question_shares.status', 'accepted')
                        ->where('question_shares.permission', 'edit')->where('question_shares.active_key', 'active');
                });
            }))
            ->when(($filters['status'] ?? 'active') === 'active', fn (Builder $q) => $q->where('exam_questions.is_active', true))
            ->when(($filters['status'] ?? 'active') === 'archived', fn (Builder $q) => $q->where('exam_questions.is_active', false))
            ->when(! in_array(($filters['status'] ?? 'active'), ['active', 'archived'], true), fn (Builder $q) => $q)
            ->when($request->filled('type'), fn (Builder $q) => $q->where('exam_questions.type', $filters['type']))
            ->when($request->filled('subject_id'), fn (Builder $q) => $q->where('exam_questions.subject_id', $filters['subject_id']))
            ->when($request->filled('jenjang'), fn (Builder $q) => $q->where('exam_questions.jenjang', $filters['jenjang']))
            ->when($request->filled('curriculum'), fn (Builder $q) => $q->where('exam_questions.curriculum', $filters['curriculum']))
            ->when($request->filled('cognitive_level'), fn (Builder $q) => $q->where('exam_questions.cognitive_level', $filters['cognitive_level']))
            ->when($request->filled('category_id'), fn (Builder $q) => $q->whereHas('categories', fn (Builder $category) => $category->whereKey($filters['category_id'])->where('question_categories.school_id', $schoolId)))
            ->when($request->filled('tag_id'), fn (Builder $q) => $q->whereHas('tags', fn (Builder $tag) => $tag->whereKey($filters['tag_id'])->where('question_tags.school_id', $schoolId)))
            ->when($request->filled('search'), fn (Builder $q) => $q->where(function (Builder $search) use ($filters) {
                $term = '%'.trim((string) $filters['search']).'%';
                $search->where('exam_questions.prompt', 'like', $term)
                    ->orWhere('exam_questions.indicator_text', 'like', $term);
            }))
            ->select('exam_questions.*', 'subjects.name as subject_name', 'subjects.code as subject_code')
            ->with(['categories:id,name', 'tags:id,name'])
            ->withCount('examItems')
            ->latest('exam_questions.updated_at');

        $questions = $query->paginate(15)->withQueryString();

        $term = $this->academicQuery->getActiveTerm($schoolId);
        $assignmentQuery = DB::table('teaching_assignments')
            ->join('class_groups', 'class_groups.id', '=', 'teaching_assignments.class_group_id')
            ->join('subjects', 'subjects.id', '=', 'teaching_assignments.subject_id')
            ->where('class_groups.school_id', $schoolId)
            ->when($term, fn ($q) => $q->where('teaching_assignments.term_id', $term->id))
            ->select('teaching_assignments.id', 'teaching_assignments.subject_id', 'subjects.name as subject_name', 'subjects.code as subject_code', 'class_groups.name as class_name')
            ->orderBy('subjects.name')->orderBy('class_groups.name');
        if ($user->hasRole(['teacher', 'homeroom'])) {
            $assignmentQuery->join('teachers', 'teachers.id', '=', 'teaching_assignments.teacher_id')->where('teachers.user_id', $user->id);
        }

        $questionStats = fn () => ExamQuestion::query()->where('school_id', $schoolId)->where('is_active', true)
            ->when($user->hasRole(['teacher', 'homeroom']), fn (Builder $q) => $q->where('owner_id', $user->id));

        return Inertia::render('Assessment/QuestionBank/Index', [
            'questions' => $questions,
            'filters' => [...$filters, 'status' => $filters['status'] ?? 'active'],
            'subjects' => DB::table('subjects')->where('school_id', $schoolId)->where('is_active', true)->orderBy('name')->get(['id', 'code', 'name']),
            'assignments' => $term ? $assignmentQuery->get() : [],
            'categories' => QuestionCategory::query()->where('school_id', $schoolId)->orderBy('name')->get(['id', 'name']),
            'tags' => QuestionTag::query()->where('school_id', $schoolId)->orderBy('name')->get(['id', 'name']),
            'kko_terms' => QuestionKkoTerm::query()->where('is_active', true)
                ->where(fn ($q) => $q->where(fn ($global) => $global->whereNull('school_id')->whereNull('subject_id'))
                    ->orWhere(fn ($local) => $local->where('school_id', $schoolId)->where(fn ($subject) => $subject->whereNull('subject_id')->orWhereIn('subject_id', function ($subjects) use ($schoolId) {
                        $subjects->select('id')->from('subjects')->where('school_id', $schoolId);
                    }))))
                ->orderBy('term')->get(['id', 'subject_id', 'term', 'cognitive_level']),
            'taxonomy_stats' => [
                'categories' => QuestionCategory::where('school_id', $schoolId)->count(),
                'tags' => QuestionTag::where('school_id', $schoolId)->count(),
                'jenjang' => $questionStats()->select('jenjang', DB::raw('count(*) as total'))->groupBy('jenjang')->pluck('total', 'jenjang'),
                'curriculum' => $questionStats()->select('curriculum', DB::raw('count(*) as total'))->groupBy('curriculum')->pluck('total', 'curriculum'),
                'cognitive_level' => $questionStats()->select('cognitive_level', DB::raw('count(*) as total'))->groupBy('cognitive_level')->pluck('total', 'cognitive_level'),
                'categories_distribution' => DB::table('question_category_links')->join('exam_questions', 'exam_questions.id', '=', 'question_category_links.exam_question_id')->join('question_categories', 'question_categories.id', '=', 'question_category_links.category_id')->where('exam_questions.school_id', $schoolId)->where('exam_questions.is_active', true)->when($user->hasRole(['teacher', 'homeroom']), fn ($q) => $q->where('exam_questions.owner_id', $user->id))->groupBy('question_categories.id', 'question_categories.name')->select('question_categories.name', DB::raw('count(*) as total'))->orderByDesc('total')->limit(5)->get(),
            ],
            'capabilities' => ['manage_all' => $user->hasRole(['admin', 'operator'])],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeManager($request);
        $data = $this->validatedData($request);
        $data = $this->assertMetadataOwnership($request, $data);

        $question = DB::transaction(function () use ($request, $data) {
            $question = ExamQuestion::create($this->rootAttributes($data, $request, 1));
            $this->createVersion($question, $data, $request);

            return $question;
        });

        $this->audit->log('assessment.question.created', 'exam_question', (string) $question->id, [
            'type' => $question->type,
            'version' => $question->current_version,
        ], actor: $request->user(), schoolId: (int) $question->school_id);

        return back()->with('message', 'Soal berhasil ditambahkan ke bank soal.');
    }

    public function update(Request $request, ExamQuestion $question): RedirectResponse
    {
        $this->authorizeQuestion($request, $question, 'edit');
        abort_unless($question->is_active, 422, 'Soal arsip tidak dapat diubah.');
        $data = $this->validatedData($request);
        $data = $this->assertMetadataOwnership($request, $data);

        DB::transaction(function () use ($request, $question, $data) {
            $locked = ExamQuestion::query()->whereKey($question->id)->lockForUpdate()->firstOrFail();
            $versionNumber = (int) $locked->current_version + 1;
            $attributes = $this->rootAttributes($data, $request, $versionNumber);
            $attributes['owner_id'] = $locked->owner_id;
            $locked->fill($attributes);
            $locked->save();
            $locked->categories()->sync($this->taxonomyIds(QuestionCategory::class, $data['category_names'] ?? [], (int) $locked->school_id));
            $locked->tags()->sync($this->taxonomyIds(QuestionTag::class, $data['tag_names'] ?? [], (int) $locked->school_id));
            $this->createVersion($locked, $data, $request, $versionNumber);
        });

        $this->audit->log('assessment.question.version_created', 'exam_question', (string) $question->id, [
            'version' => (int) $question->current_version + 1,
            'type' => $data['type'],
        ], actor: $request->user(), schoolId: (int) $question->school_id);

        return back()->with('message', 'Perubahan disimpan sebagai versi baru. Versi yang dipakai ujian sebelumnya tetap tersimpan.');
    }

    public function archive(Request $request, ExamQuestion $question): RedirectResponse
    {
        $this->authorizeQuestion($request, $question);
        $question->update(['is_active' => false]);
        $this->audit->log('assessment.question.archived', 'exam_question', (string) $question->id, [], actor: $request->user(), schoolId: (int) $question->school_id);

        return back()->with('message', 'Soal diarsipkan. Soal tetap tersedia pada ujian yang sudah dibuat.');
    }

    public function restore(Request $request, ExamQuestion $question): RedirectResponse
    {
        $this->authorizeQuestion($request, $question);
        $question->update(['is_active' => true]);
        $this->audit->log('assessment.question.restored', 'exam_question', (string) $question->id, [], actor: $request->user(), schoolId: (int) $question->school_id);

        return back()->with('message', 'Soal dikembalikan ke bank soal aktif.');
    }

    public function duplicate(Request $request, ExamQuestion $question): RedirectResponse
    {
        $this->authorizeQuestion($request, $question, 'copy');
        $source = $question->currentVersionRecord() ?? $question;
        $copy = DB::transaction(function () use ($request, $source, $question) {
            $data = [
                'subject_id' => $source->subject_id,
                'teaching_assignment_id' => $question->teaching_assignment_id,
                'type' => $source->type,
                'jenjang' => $source->jenjang,
                'curriculum' => $source->curriculum,
                'prompt' => $source->prompt,
                'indicator_text' => $source->indicator_text,
                'cognitive_level' => $source->cognitive_level,
                'kko_term_id' => $source->kko_term_id,
                'kko_text' => $source->kko_text,
                'options' => $source->options,
                'matching_pairs' => $source->matching_pairs,
                'essay_rubric' => $source->essay_rubric,
                'correct_answer' => $source->correct_answer,
                'explanation' => $source->explanation,
                'points' => $source->points,
                'category_names' => $question->categories()->pluck('name')->all(),
                'tag_names' => $question->tags()->pluck('name')->all(),
            ];
            $copy = ExamQuestion::create($this->rootAttributes($data, $request, 1));
            $this->createVersion($copy, $data, $request);

            return $copy;
        });

        $this->audit->log('assessment.question.duplicated', 'exam_question', (string) $copy->id, ['source_id' => $question->id], actor: $request->user(), schoolId: (int) $copy->school_id);

        return back()->with('message', 'Soal berhasil diduplikasi sebagai butir baru.');
    }

    public function spreadsheetTemplate(Request $request, QuestionSpreadsheetService $spreadsheet): StreamedResponse
    {
        $this->authorizeManager($request);
        $book = $spreadsheet->template();

        return response()->streamDownload(function () use ($book) {
            (new Xlsx($book))->save('php://output');
            $book->disconnectWorksheets();
        }, 'template-bank-soal.xlsx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    public function previewImport(Request $request, QuestionSpreadsheetService $spreadsheet): Response|RedirectResponse
    {
        $this->authorizeManager($request);
        $request->validate(['file' => ['required', 'file', 'mimes:xlsx', 'max:2048']]);

        try {
            $sourceRows = $spreadsheet->read($request->file('file'));
        } catch (Throwable $exception) {
            throw ValidationException::withMessages(['file' => $exception instanceof RuntimeException ? $exception->getMessage() : 'Berkas XLSX tidak dapat diproses.']);
        }

        $schoolId = (int) $request->user()->school_id;
        $previewRows = [];
        $validRows = [];
        foreach ($sourceRows as $source) {
            [$data, $errors] = $this->normalizeImportRow($source['values'], $schoolId);
            $previewRows[] = ['row' => $source['row'], 'valid' => $errors === [], 'errors' => $errors, 'prompt' => $data['prompt'] ?? '', 'type' => $data['type'] ?? '', 'correct_answer' => $data['correct_answer'] ?? ''];
            if ($errors === []) {
                $data['source_row'] = $source['row'];
                $validRows[] = $data;
            }
        }
        if (strlen(json_encode($validRows, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)) > 5 * 1024 * 1024) {
            throw ValidationException::withMessages(['file' => 'Ukuran data hasil validasi melewati batas 5 MB. Kurangi jumlah atau panjang isi soal.']);
        }

        $token = (string) Str::uuid();
        Cache::put($this->importCacheKey($token), [
            'user_id' => (int) $request->user()->id,
            'school_id' => $schoolId,
            'rows' => $validRows,
            'errors' => collect($previewRows)->contains(fn ($row) => ! $row['valid']),
        ], now()->addMinutes(15));

        return Inertia::render('Assessment/QuestionBank/ImportPreview', [
            'token' => $token,
            'rows' => $previewRows,
            'valid_count' => count($validRows),
            'invalid_count' => count($previewRows) - count($validRows),
        ]);
    }

    public function confirmImport(Request $request, string $token): RedirectResponse
    {
        $this->authorizeManager($request);
        abort_unless(Str::isUuid($token), 404);
        $cacheKey = $this->importCacheKey($token);
        $lock = Cache::lock($cacheKey.':confirm', 900);
        if (! $lock->get()) {
            return back()->withErrors(['file' => 'Impor ini sedang diproses. Coba lagi beberapa saat.']);
        }

        try {
            $stage = Cache::get($cacheKey);
            abort_unless(is_array($stage) && $stage['user_id'] === (int) $request->user()->id && $stage['school_id'] === (int) $request->user()->school_id, 404, 'Preview impor sudah kedaluwarsa atau tidak tersedia.');
            abort_if($stage['errors'] || $stage['rows'] === [], 422, 'Perbaiki semua baris yang bermasalah lalu unggah ulang sebelum impor.');

            $created = DB::transaction(function () use ($request, $stage) {
                $ids = [];
                foreach ($stage['rows'] as $row) {
                    unset($row['source_row']);
                    $data = $this->assertMetadataOwnership($request, $row);
                    $question = ExamQuestion::create($this->rootAttributes($data, $request, 1));
                    $this->createVersion($question, $data, $request);
                    $ids[] = $question->id;
                }

                return $ids;
            });

            Cache::forget($cacheKey);
            $this->audit->log('assessment.questions.imported', 'question_import', $token, ['count' => count($created)], actor: $request->user(), schoolId: (int) $request->user()->school_id);

            return redirect()->route('assessment.questions.index')->with('message', count($created).' soal berhasil diimpor.');
        } finally {
            $lock->release();
        }
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorizeManager($request);
        $user = $request->user();
        $schoolId = (int) $user->school_id;
        $status = (string) $request->query('status', 'active');
        $query = ExamQuestion::query()->where('school_id', $schoolId)
            ->when($user->hasRole(['teacher', 'homeroom']), fn (Builder $q) => $q->where('owner_id', $user->id))
            ->when($status === 'active', fn (Builder $q) => $q->where('is_active', true))
            ->when($status === 'archived', fn (Builder $q) => $q->where('is_active', false))
            ->when($request->filled('type'), fn (Builder $q) => $q->where('type', $request->query('type')))
            ->when($request->filled('subject_id'), fn (Builder $q) => $q->where('subject_id', $request->query('subject_id')))
            ->when($request->filled('jenjang'), fn (Builder $q) => $q->where('jenjang', $request->query('jenjang')))
            ->when($request->filled('curriculum'), fn (Builder $q) => $q->where('curriculum', $request->query('curriculum')))
            ->when($request->filled('cognitive_level'), fn (Builder $q) => $q->where('cognitive_level', $request->query('cognitive_level')))
            ->when($request->filled('category_id'), fn (Builder $q) => $q->whereHas('categories', fn (Builder $category) => $category->whereKey($request->query('category_id'))->where('question_categories.school_id', $schoolId)))
            ->when($request->filled('tag_id'), fn (Builder $q) => $q->whereHas('tags', fn (Builder $tag) => $tag->whereKey($request->query('tag_id'))->where('question_tags.school_id', $schoolId)))
            ->when($request->filled('search'), fn (Builder $q) => $q->where(fn (Builder $search) => $search->where('prompt', 'like', '%'.trim((string) $request->query('search')).'%')->orWhere('indicator_text', 'like', '%'.trim((string) $request->query('search')).'%')))
            ->with(['categories:id,name', 'tags:id,name'])
            ->orderBy('id')->limit(QuestionSpreadsheetService::MAX_EXPORT_ROWS + 1);
        $questions = $query->get();
        abort_if($questions->count() > QuestionSpreadsheetService::MAX_EXPORT_ROWS, 422, 'Hasil ekspor melebihi 2.000 soal. Persempit filter terlebih dahulu.');
        $subjectCodes = DB::table('subjects')->where('school_id', $schoolId)->whereIn('id', $questions->pluck('subject_id')->filter()->unique())->pluck('code', 'id');

        $book = app(QuestionSpreadsheetService::class)->template();
        $book->removeSheetByIndex(1);
        $sheet = $book->getActiveSheet();
        $sheet->setTitle('Data');
        foreach ($questions as $question) {
            $sheet->fromArray([
                $question->type, $question->jenjang, $question->curriculum,
                $subjectCodes[$question->subject_id] ?? '',
                $question->prompt, $question->indicator_text, $question->cognitive_level, $question->kko_text,
                $question->options === null ? '' : json_encode($question->options, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                $question->matching_pairs === null ? '' : json_encode($question->matching_pairs, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                $question->essay_rubric, $question->correct_answer, $question->explanation, $question->points,
                $question->categories->pluck('name')->implode(', '), $question->tags->pluck('name')->implode(', '),
            ], null, 'A'.($sheet->getHighestRow() + 1));
        }
        // Force text type for every exported cell so spreadsheet formulas remain inert.
        foreach ($sheet->getRowIterator(1, $sheet->getHighestRow()) as $row) {
            foreach ($row->getCellIterator('A', 'P') as $cell) {
                $cell->setValueExplicit((string) ($cell->getValue() ?? ''), DataType::TYPE_STRING);
            }
        }

        return response()->streamDownload(function () use ($book) {
            (new Xlsx($book))->save('php://output');
            $book->disconnectWorksheets();
        }, 'bank-soal.xlsx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    /** @param array<string, mixed> $row
     * @return array{0:array<string,mixed>,1:list<string>}
     */
    private function normalizeImportRow(array $row, int $schoolId): array
    {
        $options = $this->decodeJsonCell($row['options_json'] ?? null);
        $pairs = $this->decodeJsonCell($row['matching_pairs_json'] ?? null);
        $subjectCode = trim((string) ($row['subject_code'] ?? ''));
        $subjectId = $subjectCode === '' ? null : DB::table('subjects')->where('school_id', $schoolId)->whereRaw('LOWER(code) = ?', [mb_strtolower($subjectCode)])->value('id');
        $data = [
            'subject_id' => $subjectId,
            'type' => trim((string) ($row['type'] ?? '')),
            'jenjang' => strtoupper(trim((string) ($row['jenjang'] ?? ''))),
            'curriculum' => strtolower(trim((string) ($row['curriculum'] ?? ''))),
            'prompt' => (string) ($row['prompt'] ?? ''),
            'indicator_text' => $this->nullableText($row['indicator_text'] ?? null),
            'cognitive_level' => $this->nullableText($row['cognitive_level'] ?? null),
            'kko_text' => $this->nullableText($row['kko_text'] ?? null),
            'options' => $options,
            'matching_pairs' => $pairs,
            'essay_rubric' => $this->nullableText($row['essay_rubric'] ?? null),
            'correct_answer' => $this->nullableText($row['correct_answer'] ?? null),
            'explanation' => $this->nullableText($row['explanation'] ?? null),
            'points' => $row['points'],
            'category_names' => $this->splitNames($row['category_names'] ?? ''),
            'tag_names' => $this->splitNames($row['tag_names'] ?? ''),
        ];
        if ($subjectCode !== '' && $subjectId === null) {
            return [$data, ["Kode mata pelajaran '{$subjectCode}' tidak ditemukan pada sekolah ini."]];
        }

        if (! empty($data['kko_text'])) {
            $term = QuestionKkoTerm::query()->where('is_active', true)
                ->whereRaw('LOWER(term) = ?', [mb_strtolower((string) $data['kko_text'])])
                ->where(fn ($query) => $query->whereNull('school_id')->orWhere('school_id', $schoolId))
                ->where(fn ($query) => $query->whereNull('subject_id')->orWhere('subject_id', $subjectId))
                ->first();
            $data['kko_term_id'] = $term?->id;
            if ($term && empty($data['cognitive_level'])) {
                $data['cognitive_level'] = $term->cognitive_level;
            } elseif ($term && $term->cognitive_level !== $data['cognitive_level']) {
                return [$data, ['Level kognitif tidak sesuai dengan KKO yang dipilih.']];
            }
        } else {
            $data['kko_term_id'] = null;
        }

        $validator = validator($data, [
            'type' => ['required', Rule::in(['multiple_choice', 'essay', 'true_false', 'matching'])],
            'jenjang' => ['required', Rule::in(['SD', 'SMP', 'SMA'])],
            'curriculum' => ['required', Rule::in(['merdeka', 'kbc', 'both'])],
            'prompt' => ['required', 'string', 'max:10000'],
            'indicator_text' => ['nullable', 'string', 'max:5000'],
            'cognitive_level' => ['nullable', Rule::in(['L1', 'L2', 'L3'])],
            'kko_text' => ['nullable', 'string', 'max:180'],
            'kko_term_id' => ['nullable', 'integer'],
            'options' => ['nullable', 'array', 'min:2', 'max:8'],
            'options.*' => ['required', 'string', 'max:1000', 'distinct'],
            'matching_pairs' => ['nullable', 'array', 'min:2', 'max:10'],
            'matching_pairs.*.left' => ['required', 'string', 'max:1000', 'distinct'],
            'matching_pairs.*.right' => ['required', 'string', 'max:1000', 'distinct'],
            'essay_rubric' => ['nullable', 'string', 'max:5000'],
            'correct_answer' => ['nullable', 'string', 'max:2000'],
            'explanation' => ['nullable', 'string', 'max:5000'],
            'points' => ['required', 'numeric', 'min:0.1', 'max:100'],
            'category_names' => ['nullable', 'array', 'max:10'],
            'category_names.*' => ['required', 'string', 'max:100'],
            'tag_names' => ['nullable', 'array', 'max:20'],
            'tag_names.*' => ['required', 'string', 'max:60'],
        ]);
        $validator->after(function ($validator) use (&$data) {
            if ($data['type'] === 'multiple_choice') {
                if (count($data['options'] ?? []) < 2) {
                    $validator->errors()->add('options', 'Pilihan ganda membutuhkan options_json berupa array minimal dua opsi.');
                } elseif (! in_array($data['correct_answer'], $data['options'], true)) {
                    $validator->errors()->add('correct_answer', 'Kunci harus sama persis dengan salah satu opsi.');
                }
            } elseif ($data['type'] === 'true_false' && ! in_array($data['correct_answer'], ['true', 'false'], true)) {
                $validator->errors()->add('correct_answer', 'Kunci benar/salah harus berupa true atau false.');
            } elseif ($data['type'] === 'matching') {
                $pairsValid = collect($data['matching_pairs'] ?? [])->every(fn ($pair) => is_array($pair) && ! empty($pair['left']) && ! empty($pair['right']));
                if (count($data['matching_pairs'] ?? []) < 2 || ! $pairsValid) {
                    $validator->errors()->add('matching_pairs', 'Menjodohkan membutuhkan matching_pairs_json minimal dua pasangan.');
                } else {
                    $data['correct_answer'] = json_encode(collect($data['matching_pairs'])->mapWithKeys(fn ($pair) => [$pair['left'] => $pair['right']])->all(), JSON_THROW_ON_ERROR);
                }
            }
        });
        if ($validator->fails()) {
            return [$data, array_values($validator->errors()->all())];
        }

        if ($data['type'] !== 'multiple_choice') {
            $data['options'] = null;
        }
        if ($data['type'] !== 'matching') {
            $data['matching_pairs'] = null;
        }
        if ($data['type'] !== 'essay') {
            $data['essay_rubric'] = null;
        }
        if (! in_array($data['type'], ['multiple_choice', 'true_false', 'matching'], true)) {
            $data['correct_answer'] = null;
        }
        $data['category_names'] = collect($data['category_names'])->map(fn ($name) => trim((string) $name))->filter()->unique(fn ($name) => mb_strtolower($name))->values()->all();
        $data['tag_names'] = collect($data['tag_names'])->map(fn ($name) => trim(ltrim((string) $name, '#')))->filter()->unique(fn ($name) => mb_strtolower($name))->values()->all();

        return [$data, []];
    }

    private function decodeJsonCell(mixed $value): ?array
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }
        $decoded = json_decode($value, true);

        return json_last_error() === JSON_ERROR_NONE && is_array($decoded) ? $decoded : null;
    }

    /** @return list<string> */
    private function splitNames(mixed $value): array
    {
        return collect(explode(',', (string) $value))->map(fn ($name) => trim($name))->filter(fn ($name) => $name !== '')->values()->all();
    }

    private function nullableText(mixed $value): ?string
    {
        $text = trim((string) ($value ?? ''));

        return $text === '' ? null : $text;
    }

    private function importCacheKey(string $token): string
    {
        return 'assessment:question-import:'.$token;
    }

    /** @return array<string, mixed> */
    private function validatedData(Request $request): array
    {
        $data = $request->validate([
            'subject_id' => ['nullable', 'integer', 'exists:subjects,id'],
            'teaching_assignment_id' => ['nullable', 'integer', 'exists:teaching_assignments,id'],
            'type' => ['required', Rule::in(['multiple_choice', 'essay', 'true_false', 'matching'])],
            'jenjang' => ['required', Rule::in(['SD', 'SMP', 'SMA'])],
            'curriculum' => ['required', Rule::in(['merdeka', 'kbc', 'both'])],
            'prompt' => ['required', 'string', 'max:10000'],
            'indicator_text' => ['nullable', 'string', 'max:5000'],
            'cognitive_level' => ['nullable', Rule::in(['L1', 'L2', 'L3'])],
            'kko_term_id' => ['nullable', 'integer', 'exists:question_kko_terms,id'],
            'kko_text' => ['nullable', 'string', 'max:180'],
            'category_names' => ['nullable', 'array', 'max:10'],
            'category_names.*' => ['required', 'string', 'max:100'],
            'tag_names' => ['nullable', 'array', 'max:20'],
            'tag_names.*' => ['required', 'string', 'max:60'],
            'options' => ['nullable', 'array', 'min:2', 'max:8'],
            'options.*' => ['required', 'string', 'max:1000', 'distinct'],
            'matching_pairs' => ['nullable', 'array', 'min:2', 'max:10'],
            'matching_pairs.*.left' => ['required', 'string', 'max:1000', 'distinct'],
            'matching_pairs.*.right' => ['required', 'string', 'max:1000', 'distinct'],
            'essay_rubric' => ['nullable', 'string', 'max:5000'],
            'correct_answer' => ['nullable', 'string', 'max:2000'],
            'explanation' => ['nullable', 'string', 'max:5000'],
            'points' => ['required', 'numeric', 'min:0.1', 'max:100'],
        ]);

        if ($data['type'] === 'multiple_choice') {
            abort_unless(count($data['options'] ?? []) >= 2, 422, 'Pilihan ganda membutuhkan minimal dua opsi.');
            abort_unless(in_array($data['correct_answer'] ?? null, $data['options'], true), 422, 'Kunci jawaban harus sesuai salah satu opsi.');
        } elseif ($data['type'] === 'true_false') {
            abort_unless(in_array($data['correct_answer'] ?? null, ['true', 'false'], true), 422, 'Pilih kunci Benar atau Salah.');
        } elseif ($data['type'] === 'matching') {
            abort_unless(count($data['matching_pairs'] ?? []) >= 2, 422, 'Soal menjodohkan membutuhkan minimal dua pasangan.');
            $data['correct_answer'] = json_encode(collect($data['matching_pairs'])->mapWithKeys(fn ($pair) => [$pair['left'] => $pair['right']])->all(), JSON_THROW_ON_ERROR);
        } else {
            $data['correct_answer'] = null;
        }

        $data['options'] = $data['type'] === 'multiple_choice' ? array_values($data['options']) : null;
        $data['matching_pairs'] = $data['type'] === 'matching' ? array_values($data['matching_pairs']) : null;
        $data['essay_rubric'] = $data['type'] === 'essay' ? ($data['essay_rubric'] ?? null) : null;
        $data['category_names'] = collect($data['category_names'] ?? [])->map(fn ($name) => trim($name))->filter()->unique(fn ($name) => mb_strtolower($name))->values()->all();
        $data['tag_names'] = collect($data['tag_names'] ?? [])->map(fn ($name) => trim(ltrim($name, '#')))->filter()->unique(fn ($name) => mb_strtolower($name))->values()->all();

        return $data;
    }

    /** @param array<string, mixed> $data */
    private function assertMetadataOwnership(Request $request, array $data): array
    {
        $schoolId = (int) $request->user()->school_id;
        if (! empty($data['subject_id'])) {
            abort_unless(DB::table('subjects')->where('id', $data['subject_id'])->where('school_id', $schoolId)->exists(), 422, 'Mata pelajaran tidak berada di sekolah ini.');
        }
        if (! empty($data['teaching_assignment_id'])) {
            $assignment = DB::table('teaching_assignments')
                ->join('class_groups', 'class_groups.id', '=', 'teaching_assignments.class_group_id')
                ->where('teaching_assignments.id', $data['teaching_assignment_id'])
                ->where('class_groups.school_id', $schoolId)
                ->first(['teaching_assignments.subject_id']);
            abort_unless($assignment, 422, 'Penugasan mengajar tidak berada di sekolah ini.');
            abort_unless(empty($data['subject_id']) || (int) $data['subject_id'] === (int) $assignment->subject_id, 422, 'Mapel harus sesuai dengan penugasan mengajar.');

            $user = $request->user();
            if ($user->hasRole(['teacher', 'homeroom'])) {
                $visible = DB::table('teaching_assignments')
                    ->join('teachers', 'teachers.id', '=', 'teaching_assignments.teacher_id')
                    ->join('class_groups', 'class_groups.id', '=', 'teaching_assignments.class_group_id')
                    ->where('teaching_assignments.id', $data['teaching_assignment_id'])
                    ->where('teachers.user_id', $user->id)
                    ->where('class_groups.school_id', $schoolId)
                    ->exists();
                abort_unless($visible, 403);
            }
        }
        if (! empty($data['kko_term_id'])) {
            $term = QuestionKkoTerm::query()->whereKey($data['kko_term_id'])->where('is_active', true)
                ->where(fn ($q) => $q->whereNull('school_id')->orWhere('school_id', $schoolId))->first();
            abort_unless($term, 422, 'KKO tidak tersedia untuk sekolah ini.');
            abort_unless($term->subject_id === null || (! empty($data['subject_id']) && (int) $term->subject_id === (int) $data['subject_id']), 422, 'KKO tidak sesuai dengan mata pelajaran.');
            abort_unless(empty($data['cognitive_level']) || $term->cognitive_level === $data['cognitive_level'], 422, 'Level kognitif harus sesuai dengan KKO.');
            $data['cognitive_level'] = $term->cognitive_level;
            $data['kko_text'] = $term->term;
        }

        return $data;
    }

    /** @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function rootAttributes(array $data, Request $request, int $version): array
    {
        return [
            ...collect($data)->except(['category_names', 'tag_names'])->all(),
            'school_id' => $request->user()->school_id,
            'owner_id' => $request->user()->id,
            'is_active' => true,
            'current_version' => $version,
        ];
    }

    /** @param array<string, mixed> $data */
    private function createVersion(ExamQuestion $question, array $data, Request $request, int $version = 1): void
    {
        $subject = ! empty($data['subject_id'])
            ? DB::table('subjects')->where('id', $data['subject_id'])->first(['code', 'name'])
            : null;

        $categoryIds = $this->taxonomyIds(QuestionCategory::class, $data['category_names'] ?? [], (int) $question->school_id);
        $tagIds = $this->taxonomyIds(QuestionTag::class, $data['tag_names'] ?? [], (int) $question->school_id);
        $question->categories()->sync($categoryIds);
        $question->tags()->sync($tagIds);

        $questionVersion = ExamQuestionVersion::create([
            'exam_question_id' => $question->id,
            'version_number' => $version,
            'type' => $data['type'],
            'prompt' => $data['prompt'],
            'indicator_text' => $data['indicator_text'] ?? null,
            'cognitive_level' => $data['cognitive_level'] ?? null,
            'kko_term_id' => $data['kko_term_id'] ?? null,
            'kko_text' => $data['kko_text'] ?? null,
            'options' => $data['options'] ?? null,
            'matching_pairs' => $data['matching_pairs'] ?? null,
            'essay_rubric' => $data['essay_rubric'] ?? null,
            'correct_answer' => $data['correct_answer'] ?? null,
            'explanation' => $data['explanation'] ?? null,
            'points' => $data['points'],
            'subject_id' => $data['subject_id'] ?? null,
            'subject_code_snapshot' => $subject?->code,
            'subject_name_snapshot' => $subject?->name,
            'jenjang' => $data['jenjang'],
            'curriculum' => $data['curriculum'],
            'created_by' => $request->user()->id,
        ]);
        $questionVersion->categories()->sync($categoryIds);
        $questionVersion->tags()->sync($tagIds);
    }

    /** @param class-string<QuestionCategory|QuestionTag> $model
     * @param  list<string>  $names
     * @return list<int>
     */
    private function taxonomyIds(string $model, array $names, int $schoolId): array
    {
        return collect($names)
            ->map(fn (string $name) => $model::firstOrCreate(['school_id' => $schoolId, 'name' => $name])->id)
            ->all();
    }

    private function authorizeManager(Request $request): void
    {
        abort_unless($request->user()->hasRole(['teacher', 'homeroom']), 403);
        abort_unless($request->user()->is_active && $request->user()->school_id, 403);
    }

    private function authorizeQuestion(Request $request, ExamQuestion $question, ?string $sharePermission = null): void
    {
        $this->authorizeManager($request);
        abort_unless((int) $question->school_id === (int) $request->user()->school_id, 404);
        if ($request->user()->hasRole(['teacher', 'homeroom'])) {
            abort_unless((int) $question->owner_id === (int) $request->user()->id
                || ($sharePermission !== null && QuestionShareController::permits($request->user(), 'question', (int) $question->id, $sharePermission)), 403);
        }
    }
}
