<?php

declare(strict_types=1);

namespace App\Modules\Core\Interface\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Application\Services\PublicPortalService;
use App\Modules\Core\Application\Services\SchoolScopeService;
use App\Modules\Core\Infrastructure\Models\School;
use App\Modules\Core\Infrastructure\Models\SchoolGalleryAlbum;
use App\Modules\Core\Infrastructure\Models\SchoolGalleryPhoto;
use App\Shared\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SchoolGalleryController extends Controller
{
    private const MAX_PHOTOS_PER_ALBUM = 4;

    private const MAX_TOTAL_BYTES = 5 * 1024 * 1024;

    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly PublicPortalService $portalService,
        private readonly SchoolScopeService $schoolScope,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorizeManager($request);
        $school = School::query()->findOrFail($request->user()->school_id);

        $albums = SchoolGalleryAlbum::query()
            ->with('photos')
            ->where('school_id', $school->id)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (SchoolGalleryAlbum $album): array => $this->presentAlbum($album, true))
            ->values();

        return Inertia::render('Core/Gallery/Index', [
            'albums' => $albums,
            'videos' => $this->portalService->publicVideosForSchool($school, false),
            'can_manage' => true,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeManager($request);
        $validated = $this->validated($request, true);
        $photos = $validated['photos'];
        unset($validated['photos']);

        $album = DB::transaction(function () use ($request, $validated, $photos): SchoolGalleryAlbum {
            $album = SchoolGalleryAlbum::create([
                ...$validated,
                'school_id' => $request->user()->school_id,
                'sort_order' => ((int) SchoolGalleryAlbum::query()
                    ->where('school_id', $request->user()->school_id)
                    ->max('sort_order')) + 1,
            ]);
            $this->storePhotos($album, $photos, (int) $request->user()->school_id);

            return $album;
        });

        $this->auditLogger->log('core.school_gallery.created', 'SchoolGalleryAlbum', (string) $album->id, [
            'title' => $album->title,
            'photo_count' => count($photos),
            'is_published' => $album->is_published,
        ], actor: $request->user(), schoolId: (int) $album->school_id);

        return redirect()->route('core.gallery.index')->with('message', 'Grup foto galeri berhasil ditambahkan.');
    }

    public function update(Request $request, int $album): RedirectResponse
    {
        $this->authorizeManager($request);
        $item = SchoolGalleryAlbum::query()
            ->with('photos')
            ->where('school_id', $request->user()->school_id)
            ->findOrFail($album);
        $validated = $this->validated($request, false);
        $photos = $validated['photos'] ?? [];
        unset($validated['photos']);

        $oldPaths = $item->photos->pluck('path')->all();
        DB::transaction(function () use ($item, $validated, $photos, $request): void {
            $item->update($validated);
            if ($photos !== []) {
                $item->photos()->delete();
                $this->storePhotos($item, $photos, (int) $request->user()->school_id);
            }
        });
        if ($photos !== []) {
            foreach ($oldPaths as $path) {
                Storage::disk('local')->delete($path);
            }
        }

        $this->auditLogger->log('core.school_gallery.updated', 'SchoolGalleryAlbum', (string) $item->id, [
            'title' => $item->title,
            'photo_count' => $photos === [] ? $item->photos()->count() : count($photos),
            'is_published' => $item->is_published,
        ], actor: $request->user(), schoolId: (int) $item->school_id);

        return redirect()->route('core.gallery.index')->with('message', 'Grup foto galeri berhasil diperbarui.');
    }

    public function destroy(Request $request, int $album): RedirectResponse
    {
        $this->authorizeManager($request);
        $item = SchoolGalleryAlbum::query()
            ->with('photos')
            ->where('school_id', $request->user()->school_id)
            ->findOrFail($album);
        foreach ($item->photos as $photo) {
            Storage::disk('local')->delete($photo->path);
        }
        $item->delete();

        $this->auditLogger->log('core.school_gallery.deleted', 'SchoolGalleryAlbum', (string) $album, [
            'title' => $item->title,
            'photo_count' => $item->photos->count(),
        ], actor: $request->user(), schoolId: (int) $request->user()->school_id);

        return redirect()->route('core.gallery.index')->with('message', 'Grup foto galeri berhasil dihapus.');
    }

    public function publicIndex(Request $request): Response|HttpResponse
    {
        $school = School::query()->orderBy('id')->firstOrFail();
        $albums = $this->publishedQuery($school)
            ->limit(30)
            ->get()
            ->map(fn (SchoolGalleryAlbum $album): array => $this->presentAlbum($album))
            ->values();

        if ($this->schoolScope->usesMadrasahPublicTheme((string) $school->npsn)) {
            if ($request->header('X-Inertia')) {
                return Inertia::location($request->fullUrl());
            }

            $shell = $this->portalService->madrasahShellData($school);

            return $this->portalService->applyPreviewResponseHeaders(
                response()->view('public.madrasah.gallery', [
                    ...$shell,
                    'albums' => $albums->all(),
                ]),
                (bool) ($shell['isDraftPreview'] ?? false),
            );
        }

        return Inertia::render('Public/Gallery/Index', [
            'school' => [
                'name' => $school->name,
                'logo_url' => $school->logo_path ? route('branding.school', [$school, 'logo']) : null,
                'theme' => $school->publicTheme(),
            ],
            'albums' => $albums,
        ]);
    }

    public function publicVideos(Request $request): HttpResponse
    {
        $school = School::query()->orderBy('id')->firstOrFail();
        abort_unless($this->schoolScope->usesMadrasahPublicTheme((string) $school->npsn), 404);

        if ($request->header('X-Inertia')) {
            return Inertia::location($request->fullUrl());
        }

        $shell = $this->portalService->madrasahShellData($school);

        return $this->portalService->applyPreviewResponseHeaders(
            response()->view('public.madrasah.videos', [
                ...$shell,
                'videos' => $this->portalService->publicVideosForSchool($school, true),
            ]),
            (bool) ($shell['isDraftPreview'] ?? false),
        );
    }

    public function storeVideo(Request $request): RedirectResponse
    {
        $this->authorizeManager($request);
        $school = School::query()->findOrFail($request->user()->school_id);
        $validated = $this->validatedVideo($request);

        $videos = $this->portalService->publicVideosForSchool($school, false);
        $id = Str::slug($validated['title']) ?: 'video';
        $existingIds = array_column($videos, 'id');
        $candidate = $id;
        $suffix = 2;
        while (in_array($candidate, $existingIds, true)) {
            $candidate = $id.'-'.$suffix++;
        }

        if ($validated['is_featured']) {
            foreach ($videos as &$v) {
                $v['is_featured'] = false;
            }
            unset($v);
        }

        $categoryLabels = [
            'Profil' => 'Profil Madrasah',
            'Kegiatan' => 'Kegiatan Santri & Siswa',
            'Prestasi' => 'Prestasi & Riset Sains',
            'Keagamaan' => 'Halaqah & Keagamaan',
        ];

        $newVideo = [
            'id' => $candidate,
            'title' => trim($validated['title']),
            'category' => $validated['category'],
            'category_label' => $categoryLabels[$validated['category']] ?? $validated['category'],
            'date' => trim((string) ($validated['date'] ?? now()->format('d M Y'))),
            'description' => trim((string) ($validated['description'] ?? '')),
            'poster' => trim($validated['poster']),
            'src' => trim($validated['src']),
            'is_featured' => (bool) $validated['is_featured'],
            'is_published' => (bool) $validated['is_published'],
            'is_demo' => false,
            'sort_order' => count($videos) + 1,
        ];
        $videos[] = $newVideo;

        $settings = is_array($school->settings) ? $school->settings : [];
        $settings['public_videos'] = array_values($videos);
        $school->update(['settings' => $settings]);

        $this->auditLogger->log('core.school_video.created', 'SchoolVideo', $candidate, [
            'title' => $newVideo['title'],
            'category' => $newVideo['category'],
            'is_published' => $newVideo['is_published'],
        ], actor: $request->user(), schoolId: (int) $school->id);

        return redirect()->route('core.gallery.index')->with('message', 'Video galeri berhasil ditambahkan.');
    }

    public function updateVideo(Request $request, string $videoId): RedirectResponse
    {
        $this->authorizeManager($request);
        $school = School::query()->findOrFail($request->user()->school_id);
        $validated = $this->validatedVideo($request);

        $videos = $this->portalService->publicVideosForSchool($school, false);
        $foundIndex = null;
        foreach ($videos as $index => $item) {
            if (($item['id'] ?? '') === $videoId) {
                $foundIndex = $index;
                break;
            }
        }
        abort_if($foundIndex === null, 404);

        if ($validated['is_featured']) {
            foreach ($videos as &$v) {
                $v['is_featured'] = false;
            }
            unset($v);
        }

        $categoryLabels = [
            'Profil' => 'Profil Madrasah',
            'Kegiatan' => 'Kegiatan Santri & Siswa',
            'Prestasi' => 'Prestasi & Riset Sains',
            'Keagamaan' => 'Halaqah & Keagamaan',
        ];

        $videos[$foundIndex] = [
            ...$videos[$foundIndex],
            'title' => trim($validated['title']),
            'category' => $validated['category'],
            'category_label' => $categoryLabels[$validated['category']] ?? $validated['category'],
            'date' => trim((string) ($validated['date'] ?? $videos[$foundIndex]['date'] ?? now()->format('d M Y'))),
            'description' => trim((string) ($validated['description'] ?? '')),
            'poster' => trim($validated['poster']),
            'src' => trim($validated['src']),
            'is_featured' => (bool) $validated['is_featured'],
            'is_published' => (bool) $validated['is_published'],
            'is_demo' => false,
        ];

        $settings = is_array($school->settings) ? $school->settings : [];
        $settings['public_videos'] = array_values($videos);
        $school->update(['settings' => $settings]);

        $this->auditLogger->log('core.school_video.updated', 'SchoolVideo', $videoId, [
            'title' => $videos[$foundIndex]['title'],
            'is_published' => $videos[$foundIndex]['is_published'],
        ], actor: $request->user(), schoolId: (int) $school->id);

        return redirect()->route('core.gallery.index')->with('message', 'Video galeri berhasil diperbarui.');
    }

    public function destroyVideo(Request $request, string $videoId): RedirectResponse
    {
        $this->authorizeManager($request);
        $school = School::query()->findOrFail($request->user()->school_id);
        $videos = $this->portalService->publicVideosForSchool($school, false);

        $remaining = array_values(array_filter(
            $videos,
            static fn (array $item): bool => ($item['id'] ?? '') !== $videoId,
        ));
        abort_if(count($remaining) === count($videos), 404);

        $settings = is_array($school->settings) ? $school->settings : [];
        $settings['public_videos'] = $remaining;
        $school->update(['settings' => $settings]);

        $this->auditLogger->log('core.school_video.deleted', 'SchoolVideo', $videoId, [], actor: $request->user(), schoolId: (int) $school->id);

        return redirect()->route('core.gallery.index')->with('message', 'Video galeri berhasil dihapus.');
    }

    /** @return array<string, mixed> */
    private function validatedVideo(Request $request): array
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'category' => ['required', 'string', Rule::in(['Profil', 'Kegiatan', 'Prestasi', 'Keagamaan'])],
            'date' => ['nullable', 'string', 'max:40'],
            'description' => ['nullable', 'string', 'max:500'],
            'poster' => ['required', 'string', 'max:500'],
            'src' => ['required', 'string', 'max:500'],
            'is_featured' => ['sometimes', 'boolean'],
            'is_published' => ['required', 'boolean'],
        ]);

        $errors = [];
        foreach (['poster', 'src'] as $field) {
            $value = trim((string) $validated[$field]);
            $isLocalPath = str_starts_with($value, '/') && ! str_starts_with($value, '//') && ! str_contains($value, '..');
            $isAllowedUrl = filter_var($value, FILTER_VALIDATE_URL) !== false && preg_match('/^https?:\/\//i', $value) === 1;
            if (! $isLocalPath && ! $isAllowedUrl) {
                $errors[$field] = 'Gunakan path media lokal yang valid (diawali /) atau URL HTTPS yang sah.';
            }
        }
        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        $validated['is_featured'] = (bool) ($validated['is_featured'] ?? false);

        return $validated;
    }

    public function photo(int $photo): StreamedResponse
    {
        $school = School::query()->firstOrFail();
        $item = SchoolGalleryPhoto::query()
            ->whereHas('album', fn ($query) => $query
                ->where('school_id', $school->id)
                ->where('is_published', true))
            ->findOrFail($photo);

        return $this->imageResponse($item, ['Cache-Control' => 'public, max-age=3600']);
    }

    public function adminPhoto(Request $request, int $photo): StreamedResponse
    {
        $this->authorizeManager($request);
        $item = SchoolGalleryPhoto::query()
            ->whereHas('album', fn ($query) => $query->where('school_id', $request->user()->school_id))
            ->findOrFail($photo);

        return $this->imageResponse($item, ['Cache-Control' => 'private, max-age=300']);
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, bool $requiredPhotos): array
    {
        $rules = [
            'title' => ['required', 'string', 'max:120'],
            'caption' => ['nullable', 'string', 'max:255'],
            'is_published' => ['required', 'boolean'],
            'photos' => [$requiredPhotos ? 'required' : 'nullable', 'array', 'max:'.self::MAX_PHOTOS_PER_ALBUM],
            'photos.*' => [File::image()->types(['jpg', 'jpeg', 'png', 'webp'])->max(5120)],
        ];
        $validated = $request->validate($rules);
        $photos = array_values(array_filter($validated['photos'] ?? [], fn ($photo): bool => $photo instanceof UploadedFile));

        if ($requiredPhotos && $photos === []) {
            throw ValidationException::withMessages(['photos' => 'Pilih minimal satu foto.']);
        }
        if (array_sum(array_map(fn (UploadedFile $photo): int => (int) ($photo->getSize() ?: 0), $photos)) > self::MAX_TOTAL_BYTES) {
            throw ValidationException::withMessages(['photos' => 'Ukuran total semua foto maksimal 5 MB.']);
        }

        $validated['photos'] = $photos;

        return $validated;
    }

    /** @param list<UploadedFile> $photos */
    private function storePhotos(SchoolGalleryAlbum $album, array $photos, int $schoolId): void
    {
        foreach ($photos as $index => $photo) {
            $album->photos()->create([
                'school_id' => $schoolId,
                'path' => $photo->store('school-gallery/'.$schoolId.'/'.$album->id, 'local'),
                'original_name' => $photo->getClientOriginalName(),
                'sort_order' => $index,
            ]);
        }
    }

    private function authorizeManager(Request $request): void
    {
        abort_unless($request->user()?->hasRole(['admin', 'operator']), 403);
    }

    private function publishedQuery(School $school)
    {
        return SchoolGalleryAlbum::query()
            ->with('photos')
            ->where('school_id', $school->id)
            ->where('is_published', true)
            ->orderBy('sort_order')
            ->orderByDesc('created_at');
    }

    /** @param array<string, string> $headers */
    private function imageResponse(SchoolGalleryPhoto $photo, array $headers): StreamedResponse
    {
        abort_unless(Storage::disk('local')->exists($photo->path), 404);

        return Storage::disk('local')->response($photo->path, $photo->original_name, $headers);
    }

    /** @return array<string, mixed> */
    private function presentAlbum(SchoolGalleryAlbum $album, bool $admin = false): array
    {
        return [
            'id' => $album->id,
            'title' => $album->title,
            'caption' => $album->caption,
            'is_published' => $album->is_published,
            'photos' => $album->photos->map(fn (SchoolGalleryPhoto $photo): array => [
                'id' => $photo->id,
                'name' => $photo->original_name,
                'url' => route($admin ? 'core.gallery.admin-photo' : 'core.gallery.photo', [$photo->id])
                    .'?v='.($photo->updated_at?->timestamp ?? $photo->id),
            ])->values()->all(),
        ];
    }
}
