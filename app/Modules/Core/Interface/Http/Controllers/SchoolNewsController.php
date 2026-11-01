<?php

declare(strict_types=1);

namespace App\Modules\Core\Interface\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Infrastructure\Models\School;
use App\Modules\Core\Infrastructure\Models\SchoolNews;
use App\Modules\Core\Infrastructure\Models\SchoolNewsPhoto;
use App\Shared\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SchoolNewsController extends Controller
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function index(Request $request): Response
    {
        $this->authorizeManager($request);

        $news = SchoolNews::query()
            ->with(['author:id,name', 'photos'])
            ->where('school_id', $request->user()->school_id)
            ->latest('updated_at')
            ->get()
            ->map(fn (SchoolNews $item): array => $this->present($item, true))
            ->values();

        return Inertia::render('Core/News/Index', [
            'news' => $news,
            'can_manage' => true,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeManager($request);
        $validated = $this->validated($request);
        $status = $validated['status'];

        $photos = $validated['photos'] ?? [];
        unset($validated['status'], $validated['photos']);
        $news = SchoolNews::create([
            ...$validated,
            'school_id' => $request->user()->school_id,
            'author_id' => $request->user()->id,
            'slug' => $this->uniqueSlug((string) $validated['title'], (int) $request->user()->school_id),
            'status' => $status,
            'published_at' => $status === 'published' ? ($validated['published_at'] ?? now()) : null,
        ]);
        $this->storePhotos($photos, $news);

        $this->auditLogger->log('core.school_news.created', 'SchoolNews', (string) $news->id, [
            'title' => $news->title,
            'status' => $news->status,
        ], actor: $request->user(), schoolId: (int) $news->school_id);

        return redirect()->route('core.news.index')->with('message', 'Berita sekolah berhasil disimpan.');
    }

    public function update(Request $request, int $news): RedirectResponse
    {
        $this->authorizeManager($request);
        $item = SchoolNews::query()
            ->where('school_id', $request->user()->school_id)
            ->findOrFail($news);
        $validated = $this->validated($request);
        $status = $validated['status'];
        $oldValues = $item->only(['title', 'excerpt', 'body', 'status', 'published_at']);

        $photos = $validated['photos'] ?? [];
        unset($validated['status'], $validated['photos']);
        if ($item->photos()->count() + count($photos) > 5) {
            throw ValidationException::withMessages(['photos' => 'Maksimal 5 foto per berita. Hapus foto lama terlebih dahulu.']);
        }
        $item->update([
            ...$validated,
            'status' => $status,
            'published_at' => $status === 'published' ? ($validated['published_at'] ?? $item->published_at ?? now()) : null,
        ]);
        $this->storePhotos($photos, $item);

        $this->auditLogger->log('core.school_news.updated', 'SchoolNews', (string) $item->id, [
            'title' => $item->title,
            'status' => $item->status,
        ], oldValues: $oldValues, actor: $request->user(), schoolId: (int) $item->school_id);

        return redirect()->route('core.news.index')->with('message', 'Berita sekolah berhasil diperbarui.');
    }

    public function destroy(Request $request, int $news): RedirectResponse
    {
        $this->authorizeManager($request);
        $item = SchoolNews::query()
            ->where('school_id', $request->user()->school_id)
            ->findOrFail($news);
        $item->load('photos');
        foreach ($item->photos as $photo) {
            Storage::disk('local')->delete($photo->path);
        }
        $item->delete();

        $this->auditLogger->log('core.school_news.deleted', 'SchoolNews', (string) $news, [
            'title' => $item->title,
        ], actor: $request->user(), schoolId: (int) $request->user()->school_id);

        return redirect()->route('core.news.index')->with('message', 'Berita sekolah berhasil dihapus.');
    }

    public function destroyPhoto(Request $request, int $news, int $photo): RedirectResponse
    {
        $this->authorizeManager($request);
        $item = SchoolNews::query()->where('school_id', $request->user()->school_id)->findOrFail($news);
        $image = $item->photos()->findOrFail($photo);
        Storage::disk('local')->delete($image->path);
        $image->delete();

        $this->auditLogger->log('core.school_news.photo_deleted', 'SchoolNewsPhoto', (string) $photo, [
            'news_id' => $item->id,
        ], actor: $request->user(), schoolId: (int) $item->school_id);

        return redirect()->route('core.news.index')->with('message', 'Foto berita berhasil dihapus.');
    }

    public function publicIndex(): Response
    {
        $school = School::query()->firstOrFail();
        $news = $this->publishedQuery($school)->with('photos')->get()->map(fn (SchoolNews $item): array => $this->present($item))->values();

        return Inertia::render('Public/News/Index', ['school' => $school->only(['name', 'about', 'logo_path']), 'news' => $news]);
    }

    public function publicShow(string $slug): Response
    {
        $school = School::query()->firstOrFail();
        $item = $this->publishedQuery($school)->with('photos')->where('slug', $slug)->firstOrFail();

        return Inertia::render('Public/News/Show', [
            'school' => $school->only(['name', 'about', 'logo_path']),
            'news' => $this->present($item, true),
        ]);
    }

    public function photo(string $slug, int $photo): StreamedResponse
    {
        $school = School::query()->firstOrFail();
        $item = $this->publishedQuery($school)->where('slug', $slug)->firstOrFail();
        $image = $item->photos()->findOrFail($photo);
        abort_unless(Storage::disk('local')->exists($image->path), 404);

        return Storage::disk('local')->response($image->path, null, ['Cache-Control' => 'public, max-age=3600']);
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'body' => ['required', 'string', 'max:20000'],
            'status' => ['required', Rule::in(['draft', 'published'])],
            'published_at' => ['nullable', 'date'],
            'photos' => ['nullable', 'array', 'max:5'],
            'photos.*' => [File::image()->types(['jpg', 'jpeg', 'png', 'webp'])->max(2048)],
        ]);
    }

    private function authorizeManager(Request $request): void
    {
        abort_unless($request->user()?->hasRole(['admin', 'operator']), 403);
    }

    private function uniqueSlug(string $title, int $schoolId, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'berita-sekolah';
        $slug = $base;
        $suffix = 2;
        while (SchoolNews::query()->where('school_id', $schoolId)->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    /** @param array<int, mixed> $photos */
    private function storePhotos(array $photos, SchoolNews $item): void
    {
        $nextOrder = (int) $item->photos()->max('sort_order') + 1;
        foreach ($photos as $photo) {
            if (! $photo instanceof UploadedFile) {
                continue;
            }
            $item->photos()->create([
                'path' => $photo->store('school-news/'.$item->school_id.'/'.$item->id, 'local'),
                'original_name' => $photo->getClientOriginalName(),
                'sort_order' => $nextOrder++,
            ]);
        }
    }

    private function publishedQuery(School $school)
    {
        return SchoolNews::query()
            ->where('school_id', $school->id)
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->latest('published_at');
    }

    /** @return array<string, mixed> */
    private function present(SchoolNews $item, bool $full = false): array
    {
        $data = [
            'id' => $item->id,
            'title' => $item->title,
            'slug' => $item->slug,
            'excerpt' => $item->excerpt,
            'status' => $item->status,
            'published_at' => $item->published_at?->toIso8601String(),
            'photos' => $item->relationLoaded('photos') ? $item->photos->map(fn (SchoolNewsPhoto $photo): array => [
                'id' => $photo->id,
                'name' => $photo->original_name,
                'caption' => $photo->caption,
                'url' => route('core.news.photo', [$item->slug, $photo->id]),
            ])->values()->all() : [],
            'author_name' => $item->author?->name,
        ];
        if ($full) {
            $data['body'] = $item->body;
        }

        return $data;
    }
}
