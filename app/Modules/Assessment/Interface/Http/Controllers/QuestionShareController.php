<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Interface\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Assessment\Infrastructure\Models\ExamQuestion;
use App\Modules\Assessment\Infrastructure\Models\QuestionCategory;
use App\Modules\Assessment\Infrastructure\Models\QuestionPack;
use App\Modules\Assessment\Infrastructure\Models\QuestionShare;
use App\Modules\Assessment\Infrastructure\Models\QuestionShareEvent;
use App\Modules\Assessment\Infrastructure\Models\QuestionTag;
use App\Shared\Audit\AuditLogger;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class QuestionShareController extends Controller
{
    private const MANAGER_ROLES = ['teacher', 'homeroom'];

    public function __construct(private readonly AuditLogger $audit) {}

    public function index(Request $request): Response
    {
        $this->authorizeManager($request);
        $schoolId = (int) $request->user()->school_id;
        $sent = QuestionShare::query()->where('school_id', $schoolId)->where('sender_id', $request->user()->id)
            ->with(['recipient:id,name,username', 'events.actor:id,name'])->latest()->paginate(10, ['*'], 'sent_page');
        $received = QuestionShare::query()->where('school_id', $schoolId)->where('recipient_id', $request->user()->id)
            ->with(['sender:id,name,username', 'events.actor:id,name'])->latest()->paginate(10, ['*'], 'received_page');
        $users = User::query()->where('school_id', $schoolId)->where('is_active', true)->where('id', '!=', $request->user()->id)
            ->whereHas('role', fn ($query) => $query->whereIn('name', self::MANAGER_ROLES))
            ->orderBy('name')->get(['id', 'name', 'username']);

        $resourceIds = collect([$sent->getCollection(), $received->getCollection()])->flatten()
            ->groupBy('resource_type')->map(fn ($shares) => $shares->pluck('resource_id')->unique()->values());
        $sharedQuestionNames = ExamQuestion::query()->where('school_id', $schoolId)
            ->whereIn('id', $resourceIds->get('question', collect()))
            ->pluck('prompt', 'id')->map(fn ($prompt) => mb_strimwidth(strip_tags((string) $prompt), 0, 100, '…'));
        $sharedPackNames = QuestionPack::query()->where('school_id', $schoolId)
            ->whereIn('id', $resourceIds->get('pack', collect()))->pluck('name', 'id');
        $resourceName = function (QuestionShare $share) use ($sharedQuestionNames, $sharedPackNames): string {
            $name = $share->resource_type === 'question'
                ? $sharedQuestionNames->get($share->resource_id)
                : $sharedPackNames->get($share->resource_id);

            return $name ?: 'Sumber sudah dihapus';
        };
        foreach ([$sent, $received] as $paginator) {
            $paginator->getCollection()->transform(function (QuestionShare $share) use ($resourceName): QuestionShare {
                $share->setAttribute('resource_name', $resourceName($share));

                return $share;
            });
        }

        $resourceOptions = [
            'questions' => ExamQuestion::query()->leftJoin('subjects', 'subjects.id', '=', 'exam_questions.subject_id')
                ->where('exam_questions.school_id', $schoolId)->where('exam_questions.owner_id', $request->user()->id)
                ->where('exam_questions.is_active', true)
                ->select(['exam_questions.id', 'exam_questions.prompt', 'exam_questions.type', 'exam_questions.jenjang', 'exam_questions.curriculum', 'subjects.name as subject_name', 'subjects.code as subject_code'])
                ->latest('exam_questions.updated_at')->get()
                ->map(fn (ExamQuestion $question) => [
                    'id' => $question->id, 'name' => mb_strimwidth(strip_tags((string) $question->prompt), 0, 110, '…'),
                    'type' => $question->type, 'jenjang' => $question->jenjang, 'curriculum' => $question->curriculum,
                    'subject_name' => $question->subject_name, 'subject_code' => $question->subject_code,
                ])->values(),
            'packs' => QuestionPack::query()->where('school_id', $schoolId)->where('created_by', $request->user()->id)
                ->where('status', '!=', 'archived')->withCount('items')->latest('updated_at')
                ->get(['id', 'name', 'jenjang', 'curriculum', 'status'])
                ->map(fn (QuestionPack $pack) => [
                    'id' => $pack->id, 'name' => $pack->name, 'jenjang' => $pack->jenjang,
                    'curriculum' => $pack->curriculum, 'status' => $pack->status, 'items_count' => $pack->items_count,
                ])->values(),
        ];

        return Inertia::render('Assessment/QuestionShares/Index', [
            'sent' => $sent,
            'received' => $received,
            'recipients' => $users,
            'resource_options' => $resourceOptions,
            'resource' => ['type' => $request->query('type'), 'id' => $request->query('id')],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeManager($request);
        $data = $request->validate([
            'resource_type' => ['required', Rule::in(['question', 'pack'])],
            'resource_id' => ['required', 'integer', 'min:1'],
            'recipient_id' => ['required', 'integer', Rule::exists('users', 'id')->where('school_id', $request->user()->school_id)->where('is_active', true)],
            'permission' => ['required', Rule::in(['view', 'copy', 'edit'])],
            'request_note' => ['nullable', 'string', 'max:2000'],
        ]);
        $user = $request->user();
        $resource = $this->resolveResource((int) $user->school_id, $data['resource_type'], (int) $data['resource_id']);
        if (! $resource) {
            throw ValidationException::withMessages(['resource_id' => 'Sumber soal atau paket tidak ditemukan di sekolah ini.']);
        }
        $this->authorizeResourceOwner($user, $resource);
        if (($resource instanceof ExamQuestion && ! $resource->is_active) || ($resource instanceof QuestionPack && $resource->status === 'archived')) {
            throw ValidationException::withMessages(['resource_id' => 'Sumber yang sudah diarsipkan tidak dapat dibagikan.']);
        }
        $recipient = User::query()->where('school_id', $user->school_id)->where('is_active', true)->findOrFail($data['recipient_id']);
        if (! $recipient->hasRole(self::MANAGER_ROLES) || (int) $recipient->id === (int) $user->id) {
            throw ValidationException::withMessages(['recipient_id' => 'Penerima harus guru atau wali kelas lain di sekolah yang sama.']);
        }

        try {
            $share = DB::transaction(function () use ($user, $data, $recipient): QuestionShare {
                $share = QuestionShare::create([
                    'school_id' => $user->school_id,
                    'sender_id' => $user->id,
                    'recipient_id' => $recipient->id,
                    'resource_type' => $data['resource_type'],
                    'resource_id' => $data['resource_id'],
                    'permission' => $data['permission'],
                    'status' => 'pending',
                    'active_key' => 'active',
                    'request_note' => $data['request_note'] ?? null,
                ]);
                $this->recordEvent($share, $user, 'requested', null, $data['permission'], null, 'pending', $data['request_note'] ?? null);

                return $share;
            });
        } catch (QueryException $exception) {
            $databaseMessage = strtolower($exception->getMessage());
            if (! str_contains($databaseMessage, 'unique') && ! str_contains($databaseMessage, 'duplicate entry')) {
                throw $exception;
            }

            return back()->withErrors(['recipient_id' => 'Masih ada permintaan aktif untuk sumber ini kepada penerima tersebut.']);
        }

        $this->audit->log('assessment.question_share.requested', 'question_share', (string) $share->id, [
            'resource_type' => $share->resource_type, 'resource_id' => $share->resource_id,
            'recipient_id' => $recipient->id, 'permission' => $share->permission, 'status' => $share->status,
        ], actor: $user, schoolId: (int) $share->school_id);

        return redirect()->route('assessment.question-shares.index')->with('message', 'Permintaan berbagi dikirim. Akses baru aktif setelah penerima menyetujui.');
    }

    public function show(Request $request, QuestionShare $share): Response
    {
        $this->authorizeShareParticipant($request, $share);
        abort_unless($share->status === 'accepted' || (int) $share->sender_id === (int) $request->user()->id, 403);
        $resource = $this->resolveResource((int) $share->school_id, $share->resource_type, (int) $share->resource_id);
        abort_unless($resource, 404);
        if ((int) $request->user()->id === (int) $share->recipient_id) {
            abort_unless($share->status === 'accepted', 403);
        }

        $isRecipient = (int) $request->user()->id === (int) $share->recipient_id;

        return Inertia::render('Assessment/QuestionShares/Show', [
            'share' => $share->load(['sender:id,name', 'recipient:id,name', 'events.actor:id,name']),
            'resource' => $this->resourcePayload(
                $resource,
                ! $isRecipient || in_array($share->permission, ['copy', 'edit'], true),
            ),
            'can_copy' => $isRecipient && $share->permission === 'copy' && $share->status === 'accepted',
        ]);
    }

    public function decide(Request $request, QuestionShare $share, string $decision): RedirectResponse
    {
        $this->authorizeShareParticipant($request, $share);
        abort_unless((int) $request->user()->id === (int) $share->recipient_id, 403);
        abort_unless(in_array($decision, ['accept', 'reject'], true), 404);
        if ($share->status !== 'pending') {
            throw ValidationException::withMessages(['share' => 'Permintaan ini sudah diputuskan.']);
        }
        $data = $request->validate(['decision_note' => ['nullable', 'string', 'max:2000']]);
        $nextStatus = $decision === 'accept' ? 'accepted' : 'rejected';

        DB::transaction(function () use ($request, $share, $nextStatus, $data): void {
            $locked = QuestionShare::query()->whereKey($share->id)->lockForUpdate()->firstOrFail();
            if ($locked->status !== 'pending') {
                throw ValidationException::withMessages(['share' => 'Permintaan ini sudah diputuskan oleh pengguna lain.']);
            }
            $locked->update([
                'status' => $nextStatus,
                'active_key' => $nextStatus === 'accepted' ? 'active' : null,
                'decision_note' => $data['decision_note'] ?? null,
                'decided_at' => now(),
            ]);
            $this->recordEvent($locked, $request->user(), $nextStatus, $locked->permission, $locked->permission, 'pending', $nextStatus, $data['decision_note'] ?? null);
        });

        $this->auditDecision($request, $share, $nextStatus, $data['decision_note'] ?? null);

        return back()->with('message', $nextStatus === 'accepted' ? 'Permintaan diterima.' : 'Permintaan ditolak.');
    }

    public function revoke(Request $request, QuestionShare $share): RedirectResponse
    {
        $this->authorizeShareParticipant($request, $share);
        abort_unless((int) $request->user()->id === (int) $share->sender_id, 403);
        if (! in_array($share->status, ['pending', 'accepted'], true)) {
            throw ValidationException::withMessages(['share' => 'Berbagi ini sudah tidak aktif.']);
        }

        DB::transaction(function () use ($request, $share): void {
            $locked = QuestionShare::query()->whereKey($share->id)->lockForUpdate()->firstOrFail();
            if (! in_array($locked->status, ['pending', 'accepted'], true)) {
                throw ValidationException::withMessages(['share' => 'Berbagi ini sudah tidak aktif.']);
            }
            $previous = $locked->status;
            $locked->update(['status' => 'revoked', 'active_key' => null, 'revoked_at' => now()]);
            $this->recordEvent($locked, $request->user(), 'revoked', $locked->permission, $locked->permission, $previous, 'revoked', null);
        });

        $this->auditDecision($request, $share, 'revoked', null);

        return back()->with('message', 'Akses berbagi dicabut. Penerima tidak lagi dapat membuka sumber melalui share ini.');
    }

    public function copy(Request $request, QuestionShare $share): RedirectResponse
    {
        $this->authorizeShareParticipant($request, $share);
        abort_unless((int) $request->user()->id === (int) $share->recipient_id, 403);
        abort_unless($share->status === 'accepted' && $share->active_key === 'active' && $share->permission === 'copy', 403);
        $source = $this->resolveResource((int) $share->school_id, $share->resource_type, (int) $share->resource_id);
        abort_unless($source, 404);

        if ($source instanceof ExamQuestion) {
            $version = $source->currentVersionRecord() ?? $source;
            $copy = DB::transaction(function () use ($request, $source, $version): ExamQuestion {
                $attributes = [
                    'school_id' => $source->school_id, 'owner_id' => $request->user()->id,
                    'subject_id' => $version->subject_id ?? $source->subject_id, 'teaching_assignment_id' => null,
                    'type' => $version->type, 'jenjang' => $version->jenjang, 'curriculum' => $version->curriculum,
                    'prompt' => $version->prompt, 'indicator_text' => $version->indicator_text,
                    'cognitive_level' => $version->cognitive_level, 'kko_term_id' => $version->kko_term_id,
                    'kko_text' => $version->kko_text, 'options' => $version->options,
                    'matching_pairs' => $version->matching_pairs, 'essay_rubric' => $version->essay_rubric,
                    'correct_answer' => $version->correct_answer, 'explanation' => $version->explanation,
                    'points' => $version->points, 'is_active' => true, 'current_version' => 1,
                ];
                $copy = ExamQuestion::create($attributes);
                $copyVersion = $copy->versions()->create([
                    'version_number' => 1, 'type' => $version->type, 'prompt' => $version->prompt,
                    'indicator_text' => $version->indicator_text, 'cognitive_level' => $version->cognitive_level,
                    'kko_term_id' => $version->kko_term_id, 'kko_text' => $version->kko_text,
                    'options' => $version->options, 'matching_pairs' => $version->matching_pairs,
                    'essay_rubric' => $version->essay_rubric, 'correct_answer' => $version->correct_answer,
                    'explanation' => $version->explanation, 'points' => $version->points,
                    'subject_id' => $version->subject_id ?? $source->subject_id,
                    'subject_code_snapshot' => $version->subject_code_snapshot ?? null,
                    'subject_name_snapshot' => $version->subject_name_snapshot ?? null,
                    'jenjang' => $version->jenjang, 'curriculum' => $version->curriculum,
                    'created_by' => $request->user()->id,
                ]);
                $categoryNames = $source->categories()->pluck('name')->all();
                $tagNames = $source->tags()->pluck('name')->all();
                $categoryIds = collect($categoryNames)->map(fn (string $name) => QuestionCategory::firstOrCreate(['school_id' => $source->school_id, 'name' => $name])->id);
                $tagIds = collect($tagNames)->map(fn (string $name) => QuestionTag::firstOrCreate(['school_id' => $source->school_id, 'name' => $name])->id);
                $copy->categories()->sync($categoryIds);
                $copy->tags()->sync($tagIds);
                $copyVersion->categories()->sync($categoryIds);
                $copyVersion->tags()->sync($tagIds);

                return $copy;
            });
            $this->audit->log('assessment.question_share.copied', 'exam_question', (string) $copy->id, ['share_id' => $share->id, 'source_id' => $source->id], actor: $request->user(), schoolId: (int) $share->school_id);

            return redirect()->route('assessment.questions.index')->with('message', 'Soal disalin ke bank Anda sebagai pemilik baru.');
        }

        /** @var QuestionPack $source */
        $source->load('items.question', 'items.questionVersion');
        if ($source->status === 'archived' || $source->items->isEmpty() || $source->items->contains(fn ($item) => ! $item->question || ! $item->questionVersion)) {
            throw ValidationException::withMessages(['share' => 'Paket tidak dapat disalin karena sudah diarsipkan, kosong, atau memiliki item tanpa versi soal.']);
        }
        $copy = DB::transaction(function () use ($source, $request): QuestionPack {
            $copy = QuestionPack::create([
                'school_id' => $source->school_id, 'created_by' => $request->user()->id,
                'name' => mb_strimwidth($source->name.' (salinan)', 0, 180), 'description' => $source->description,
                'jenjang' => $source->jenjang, 'curriculum' => $source->curriculum,
                'duration_minutes' => $source->duration_minutes, 'randomize_questions' => $source->randomize_questions,
                'randomize_options' => $source->randomize_options, 'status' => 'draft',
            ]);
            foreach ($source->items as $item) {
                $copy->items()->create(['exam_question_id' => $item->exam_question_id, 'question_version_id' => $item->question_version_id, 'sequence' => $item->sequence, 'points' => $item->points]);
            }

            return $copy;
        });
        $this->audit->log('assessment.question_share.copied', 'question_pack', (string) $copy->id, ['share_id' => $share->id, 'source_id' => $source->id], actor: $request->user(), schoolId: (int) $share->school_id);

        return redirect()->route('assessment.question-packs.index')->with('message', 'Paket disalin sebagai draf baru milik Anda.');
    }

    public static function permits(User $user, string $type, int $id, string $permission): bool
    {
        return QuestionShare::query()->where('school_id', $user->school_id)->where('recipient_id', $user->id)
            ->where('resource_type', $type)->where('resource_id', $id)->where('status', 'accepted')
            ->where('permission', $permission)->where('active_key', 'active')->exists();
    }

    private function resolveResource(int $schoolId, string $type, int $id): ExamQuestion|QuestionPack|null
    {
        return match ($type) {
            'question' => ExamQuestion::query()->where('school_id', $schoolId)->find($id),
            'pack' => QuestionPack::query()->where('school_id', $schoolId)->find($id),
            default => null,
        };
    }

    private function authorizeManager(Request $request): void
    {
        abort_unless($request->user()->hasRole(self::MANAGER_ROLES) && $request->user()->is_active && $request->user()->school_id, 403);
    }

    private function authorizeResourceOwner(User $user, ExamQuestion|QuestionPack $resource): void
    {
        abort_unless((int) $resource->school_id === (int) $user->school_id, 404);
        $ownerId = $resource instanceof ExamQuestion ? $resource->owner_id : $resource->created_by;
        if ($user->hasRole(['teacher', 'homeroom'])) {
            abort_unless((int) $ownerId === (int) $user->id, 403);
        }
    }

    private function authorizeShareParticipant(Request $request, QuestionShare $share): void
    {
        $this->authorizeManager($request);
        abort_unless((int) $share->school_id === (int) $request->user()->school_id, 404);
        abort_unless(in_array((int) $request->user()->id, [(int) $share->sender_id, (int) $share->recipient_id], true), 403);
    }

    private function resourcePayload(ExamQuestion|QuestionPack $resource, bool $includeAnswerKey = true): array
    {
        if ($resource instanceof ExamQuestion) {
            $version = $resource->currentVersionRecord() ?? $resource;

            $content = [
                'type' => $version->type, 'prompt' => $version->prompt, 'options' => $version->options,
                'matching_pairs' => $version->matching_pairs, 'essay_rubric' => $version->essay_rubric,
                'points' => $version->points, 'jenjang' => $version->jenjang, 'curriculum' => $version->curriculum,
            ];
            if ($includeAnswerKey) {
                $content['correct_answer'] = $version->correct_answer;
                $content['explanation'] = $version->explanation;
            }

            return ['type' => 'question', 'id' => $resource->id, 'name' => mb_strimwidth(strip_tags((string) $version->prompt), 0, 120), 'content' => $content];
        }
        $resource->load('items.questionVersion');

        return ['type' => 'pack', 'id' => $resource->id, 'name' => $resource->name, 'content' => [
            'description' => $resource->description, 'jenjang' => $resource->jenjang,
            'curriculum' => $resource->curriculum, 'status' => $resource->status,
            'items' => $resource->items->map(fn ($item) => [
                'sequence' => $item->sequence, 'points' => $item->points,
                'type' => $item->questionVersion?->type, 'prompt' => $item->questionVersion?->prompt,
            ]),
        ]];
    }

    private function recordEvent(QuestionShare $share, User $actor, string $type, ?string $oldPermission, ?string $newPermission, ?string $oldStatus, ?string $newStatus, ?string $note): void
    {
        QuestionShareEvent::create([
            'question_share_id' => $share->id, 'actor_id' => $actor->id, 'event_type' => $type,
            'old_permission' => $oldPermission, 'new_permission' => $newPermission,
            'old_status' => $oldStatus, 'new_status' => $newStatus, 'note' => $note, 'created_at' => now(),
        ]);
    }

    private function auditDecision(Request $request, QuestionShare $share, string $status, ?string $note): void
    {
        $this->audit->log('assessment.question_share.'.$status, 'question_share', (string) $share->id,
            ['permission' => $share->permission, 'status' => $status, 'decision_note' => $note],
            ['permission' => $share->permission, 'status' => $share->status], actor: $request->user(), schoolId: (int) $share->school_id);
    }
}
