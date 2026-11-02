<?php

declare(strict_types=1);

namespace App\Modules\Core\Interface\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Infrastructure\Models\School;
use App\Modules\Core\Infrastructure\Models\SchoolGalleryAlbum;
use App\Modules\Core\Infrastructure\Models\SchoolGalleryPhoto;
use App\Shared\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SchoolGalleryController extends Controller
{
    private const MAX_PHOTOS_PER_ALBUM = 4;

    private const MAX_TOTAL_BYTES = 5 * 1024 * 1024;

    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function index(Request $request): Response
    {
        $this->authorizeManager($request);

        $albums = SchoolGalleryAlbum::query()
            ->with('photos')
            ->where('school_id', $request->user()->school_id)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (SchoolGalleryAlbum $album): array => $this->presentAlbum($album, true))
            ->values();

        return Inertia::render('Core/Gallery/Index', [
            'albums' => $albums,
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

    public function publicIndex(): Response
    {
        $school = School::query()->firstOrFail();
        $albums = $this->publishedQuery($school)
            ->limit(30)
            ->get()
            ->map(fn (SchoolGalleryAlbum $album): array => $this->presentAlbum($album))
            ->values();

        return Inertia::render('Public/Gallery/Index', [
            'school' => [
                'name' => $school->name,
                'logo_url' => $school->logo_path ? route('branding.school', [$school, 'logo']) : null,
                'theme' => $school->publicTheme(),
            ],
            'albums' => $albums,
        ]);
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
