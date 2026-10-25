<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Interface\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Assessment\Infrastructure\Models\ExamQuestion;
use App\Modules\Assessment\Infrastructure\Models\ExamQuestionVersion;
use App\Modules\Assessment\Infrastructure\Models\QuestionPack;
use App\Modules\Assessment\Infrastructure\Models\QuestionPackItem;
use App\Shared\Audit\AuditLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class QuestionPackController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function index(Request $request): Response
    {
        $this->authorizeManager($request);
        $user = $request->user();
        $schoolId = (int) $user->school_id;
        $filters = $request->only(['search', 'jenjang', 'curriculum', 'status']);

        $packs = QuestionPack::query()
            ->where('school_id', $schoolId)
            ->when($user->hasRole(['teacher', 'homeroom']), fn (Builder $query) => $query->where(function (Builder $scope) use ($user): void {
                $scope->where('created_by', $user->id)->orWhereExists(function ($shares) use ($user): void {
                    $shares->selectRaw('1')->from('question_shares')->whereColumn('question_shares.resource_id', 'question_packs.id')
                        ->where('question_shares.resource_type', 'pack')->where('question_shares.school_id', $user->school_id)
                        ->where('question_shares.recipient_id', $user->id)->where('question_shares.status', 'accepted')
                        ->where('question_shares.permission', 'edit')->where('question_shares.active_key', 'active');
                });
            }))
            ->when(($filters['status'] ?? 'all') !== 'all', fn (Builder $query) => $query->where('status', $filters['status']))
            ->when($request->filled('jenjang'), fn (Builder $query) => $query->where('jenjang', $filters['jenjang']))
            ->when($request->filled('curriculum'), fn (Builder $query) => $query->where('curriculum', $filters['curriculum']))
            ->when($request->filled('search'), fn (Builder $query) => $query->where(function (Builder $search) use ($filters) {
                $term = '%'.trim((string) $filters['search']).'%';
                $search->where('name', 'like', $term)->orWhere('description', 'like', $term);
            }))
            ->with([
                'creator:id,name',
                'items.question:id,owner_id,is_active',
                'items.questionVersion:id,exam_question_id,version_number,type,prompt,points,jenjang,curriculum',
            ])
            ->withCount('items')
            ->withSum('items', 'points')
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $questionOptions = ExamQuestion::query()
            ->leftJoin('subjects', 'subjects.id', '=', 'exam_questions.subject_id')
            ->where('exam_questions.school_id', $schoolId)
            ->where('exam_questions.is_active', true)
            ->when($user->hasRole(['teacher', 'homeroom']), fn (Builder $query) => $query->where(function (Builder $scope) use ($user): void {
                $scope->where('exam_questions.owner_id', $user->id)->orWhereExists(function ($shares) use ($user): void {
                    $shares->selectRaw('1')->from('question_shares')->whereColumn('question_shares.resource_id', 'exam_questions.id')
                        ->where('question_shares.resource_type', 'question')->where('question_shares.school_id', $user->school_id)
                        ->where('question_shares.recipient_id', $user->id)->where('question_shares.status', 'accepted')
                        ->where('question_shares.permission', 'copy')->where('question_shares.active_key', 'active');
                });
            }))
            ->select([
                'exam_questions.id', 'exam_questions.type', 'exam_questions.prompt', 'exam_questions.points',
                'exam_questions.jenjang', 'exam_questions.curriculum', 'exam_questions.current_version',
                'subjects.name as subject_name', 'subjects.code as subject_code',
            ])
            ->orderByDesc('exam_questions.updated_at')
            ->get();

        return Inertia::render('Assessment/QuestionPacks/Index', [
            'packs' => $packs,
            'filters' => [...$filters, 'status' => $filters['status'] ?? 'all'],
            'question_options' => $questionOptions,
            'capabilities' => ['manage_all' => $user->hasRole(['admin', 'operator'])],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeManager($request);
        $data = $this->validatedData($request);

        $pack = DB::transaction(function () use ($data, $request) {
            $questions = $this->resolveQuestions($request, $data['items']);
            $pack = QuestionPack::create($this->packAttributes($data, $request));
            $this->replaceItems($pack, $data['items'], $questions, collect());

            return $pack;
        });

        $this->audit->log('assessment.question_pack.created', 'question_pack', (string) $pack->id, [
            'items_count' => count($data['items']),
            'status' => $pack->status,
        ], actor: $request->user(), schoolId: (int) $pack->school_id);

        return back()->with('message', 'Paket soal berhasil dibuat.');
    }

    public function update(Request $request, QuestionPack $pack): RedirectResponse
    {
        $this->authorizePack($request, $pack, 'edit');
        abort_unless($pack->status !== 'archived', 422, 'Pulihkan paket sebelum mengubahnya.');
        $data = $this->validatedData($request);

        DB::transaction(function () use ($data, $pack, $request) {
            $locked = QuestionPack::query()->whereKey($pack->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->status !== 'archived', 422, 'Paket telah diarsipkan.');
            $oldItems = $locked->items()->get()->keyBy('exam_question_id');
            $questions = $this->resolveQuestions($request, $data['items'], $locked);
            $locked->fill($this->packAttributes($data, $request, (int) $locked->created_by));
            $locked->save();
            $this->replaceItems($locked, $data['items'], $questions, $oldItems);
        });

        $this->audit->log('assessment.question_pack.updated', 'question_pack', (string) $pack->id, [
            'items_count' => count($data['items']),
            'status' => $data['status'],
        ], actor: $request->user(), schoolId: (int) $pack->school_id);

        return back()->with('message', 'Paket soal dan urutan item berhasil diperbarui.');
    }

    public function archive(Request $request, QuestionPack $pack): RedirectResponse
    {
        $this->authorizePack($request, $pack);
        $pack->update(['status' => 'archived']);
        $this->audit->log('assessment.question_pack.archived', 'question_pack', (string) $pack->id, [], actor: $request->user(), schoolId: (int) $pack->school_id);

        return back()->with('message', 'Paket soal diarsipkan.');
    }

    public function restore(Request $request, QuestionPack $pack): RedirectResponse
    {
        $this->authorizePack($request, $pack);
        $pack->update(['status' => 'draft']);
        $this->audit->log('assessment.question_pack.restored', 'question_pack', (string) $pack->id, ['status' => 'draft'], actor: $request->user(), schoolId: (int) $pack->school_id);

        return back()->with('message', 'Paket dipulihkan sebagai draf.');
    }

    public function duplicate(Request $request, QuestionPack $pack): RedirectResponse
    {
        $this->authorizePack($request, $pack, 'copy');
        $pack->load('items');
        $copy = DB::transaction(function () use ($pack, $request) {
            $copy = QuestionPack::create([
                'school_id' => $pack->school_id,
                'created_by' => $request->user()->id,
                'name' => mb_strimwidth($pack->name.' (salinan)', 0, 180),
                'description' => $pack->description,
                'jenjang' => $pack->jenjang,
                'curriculum' => $pack->curriculum,
                'duration_minutes' => $pack->duration_minutes,
                'randomize_questions' => $pack->randomize_questions,
                'randomize_options' => $pack->randomize_options,
                'status' => 'draft',
            ]);

            foreach ($pack->items as $item) {
                $copy->items()->create([
                    'exam_question_id' => $item->exam_question_id,
                    'question_version_id' => $item->question_version_id,
                    'sequence' => $item->sequence,
                    'points' => $item->points,
                ]);
            }

            return $copy;
        });

        $this->audit->log('assessment.question_pack.duplicated', 'question_pack', (string) $copy->id, ['source_id' => $pack->id], actor: $request->user(), schoolId: (int) $copy->school_id);

        return back()->with('message', 'Paket diduplikasi sebagai draf baru.');
    }

    /** @return array<string, mixed> */
    private function validatedData(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:5000'],
            'jenjang' => ['required', Rule::in(['SD', 'SMP', 'SMA'])],
            'curriculum' => ['required', Rule::in(['merdeka', 'kbc', 'both'])],
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:480'],
            'randomize_questions' => ['required', 'boolean'],
            'randomize_options' => ['required', 'boolean'],
            'status' => ['required', Rule::in(['draft', 'active'])],
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.question_id' => ['required', 'integer', 'distinct'],
            'items.*.points' => ['required', 'numeric', 'min:0.1', 'max:100'],
        ]);

        return $data;
    }

    /** @param list<array{question_id:int|string,points:int|float|string}> $items
     * @return Collection<int, ExamQuestion>
     */
    private function resolveQuestions(Request $request, array $items, ?QuestionPack $pack = null)
    {
        $user = $request->user();
        $ids = collect($items)->pluck('question_id')->map(fn ($id) => (int) $id)->all();
        $existing = $pack?->items()->get()->keyBy('exam_question_id') ?? collect();
        $questions = ExamQuestion::query()
            ->where('school_id', $user->school_id)
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        abort_unless($questions->count() === count(array_unique($ids)), 422, 'Paket berisi soal yang tidak tersedia di sekolah ini.');
        foreach ($questions as $question) {
            $wasInPack = $existing->has($question->id);
            abort_unless($question->is_active || $wasInPack, 422, 'Soal arsip tidak dapat ditambahkan ke paket.');
            if ($user->hasRole(['teacher', 'homeroom'])) {
                abort_unless((int) $question->owner_id === (int) $user->id || $wasInPack
                    || QuestionShareController::permits($user, 'question', (int) $question->id, 'copy'), 403, 'Anda hanya dapat menambahkan soal milik sendiri atau yang boleh disalin.');
            }
        }

        return $questions;
    }

    /** @param array<string, mixed> $data */
    private function packAttributes(array $data, Request $request, ?int $createdBy = null): array
    {
        return [
            'school_id' => $request->user()->school_id,
            'created_by' => $createdBy ?? $request->user()->id,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'jenjang' => $data['jenjang'],
            'curriculum' => $data['curriculum'],
            'duration_minutes' => $data['duration_minutes'] ?? null,
            'randomize_questions' => $data['randomize_questions'],
            'randomize_options' => $data['randomize_options'],
            'status' => $data['status'],
        ];
    }

    /** @param list<array{question_id:int|string,points:int|float|string}> $items
     * @param  Collection<int, ExamQuestion>  $questions
     * @param  Collection<int|string, QuestionPackItem>  $oldItems
     */
    private function replaceItems(QuestionPack $pack, array $items, $questions, $oldItems): void
    {
        $versions = ExamQuestionVersion::query()
            ->whereIn('exam_question_id', $questions->pluck('id'))
            ->get()
            ->keyBy(fn (ExamQuestionVersion $version) => $version->exam_question_id.':'.$version->version_number);

        $pack->items()->delete();
        foreach ($items as $index => $item) {
            $questionId = (int) $item['question_id'];
            $question = $questions->get($questionId);
            $existing = $oldItems->get($questionId);
            if (! $question->is_active && $existing) {
                $versionId = $existing->question_version_id;
            } else {
                abort_unless($question->jenjang === null || $question->jenjang === $pack->jenjang, 422, 'Jenjang soal harus sesuai dengan paket.');
                abort_unless(
                    $question->curriculum === null
                        || $pack->curriculum === 'both'
                        || in_array($question->curriculum, [$pack->curriculum, 'both'], true),
                    422,
                    'Kurikulum soal harus sesuai dengan paket.'
                );
                $version = $versions->get($question->id.':'.$question->current_version);
                abort_unless($version, 422, 'Versi soal terbaru belum tersedia.');
                $versionId = $version->id;
            }

            $pack->items()->create([
                'exam_question_id' => $questionId,
                'question_version_id' => $versionId,
                'sequence' => $index + 1,
                'points' => $item['points'],
            ]);
        }
    }

    private function authorizeManager(Request $request): void
    {
        abort_unless($request->user()->hasRole(['admin', 'operator', 'teacher', 'homeroom']), 403);
        abort_unless($request->user()->is_active && $request->user()->school_id, 403);
    }

    private function authorizePack(Request $request, QuestionPack $pack, ?string $sharePermission = null): void
    {
        $this->authorizeManager($request);
        abort_unless((int) $pack->school_id === (int) $request->user()->school_id, 404);
        if ($request->user()->hasRole(['teacher', 'homeroom'])) {
            abort_unless((int) $pack->created_by === (int) $request->user()->id
                || ($sharePermission !== null && QuestionShareController::permits($request->user(), 'pack', (int) $pack->id, $sharePermission)), 403);
        }
    }
}
