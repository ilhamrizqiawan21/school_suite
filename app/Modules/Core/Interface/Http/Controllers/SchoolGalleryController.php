<?php

declare(strict_types=1);

namespace App\Modules\Core\Interface\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Infrastructure\Models\School;
use App\Modules\Core\Infrastructure\Models\SchoolGalleryPhoto;
use App\Shared\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\File;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SchoolGalleryController extends Controller
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function index(Request $request): Response
    {
        $this->authorizeManager($request);

        $photos = SchoolGalleryPhoto::query()
            ->where('school_id', $request->user()->school_id)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (SchoolGalleryPhoto $photo): array => $this->present($photo, true))
            ->values();

        return Inertia::render('Core/Gallery/Index', [
            'photos' => $photos,
            'can_manage' => true,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeManager($request);
        $validated = $this->validated($request, true);
        $photoFile = $validated['photo'];
        unset($validated['photo']);

        $photo = SchoolGalleryPhoto::create([
            ...$validated,
            'school_id' => $request->user()->school_id,
            'path' => $photoFile->store('school-gallery/'.$request->user()->school_id, 'local'),
            'original_name' => $photoFile->getClientOriginalName(),
            'sort_order' => ((int) SchoolGalleryPhoto::query()
                ->where('school_id', $request->user()->school_id)
                ->max('sort_order')) + 1,
        ]);

        $this->auditLogger->log('core.school_gallery.created', 'SchoolGalleryPhoto', (string) $photo->id, [
            'title' => $photo->title,
            'is_published' => $photo->is_published,
        ], actor: $request->user(), schoolId: (int) $photo->school_id);

        return redirect()->route('core.gallery.index')->with('message', 'Foto galeri berhasil ditambahkan.');
    }

    public function update(Request $request, int $photo): RedirectResponse
    {
        $this->authorizeManager($request);
        $item = SchoolGalleryPhoto::query()
            ->where('school_id', $request->user()->school_id)
            ->findOrFail($photo);
        $validated = $this->validated($request, false);
        $photoFile = $validated['photo'] ?? null;
        unset($validated['photo']);

        $oldPath = $item->path;
        if ($photoFile instanceof UploadedFile) {
            $validated['path'] = $photoFile->store('school-gallery/'.$request->user()->school_id, 'local');
            $validated['original_name'] = $photoFile->getClientOriginalName();
        }
        $item->update($validated);
        if ($photoFile instanceof UploadedFile) {
            Storage::disk('local')->delete($oldPath);
        }

        $this->auditLogger->log('core.school_gallery.updated', 'SchoolGalleryPhoto', (string) $item->id, [
            'title' => $item->title,
            'is_published' => $item->is_published,
        ], actor: $request->user(), schoolId: (int) $item->school_id);

        return redirect()->route('core.gallery.index')->with('message', 'Foto galeri berhasil diperbarui.');
    }

    public function destroy(Request $request, int $photo): RedirectResponse
    {
        $this->authorizeManager($request);
        $item = SchoolGalleryPhoto::query()
            ->where('school_id', $request->user()->school_id)
            ->findOrFail($photo);
        Storage::disk('local')->delete($item->path);
        $item->delete();

        $this->auditLogger->log('core.school_gallery.deleted', 'SchoolGalleryPhoto', (string) $photo, [
            'title' => $item->title,
        ], actor: $request->user(), schoolId: (int) $request->user()->school_id);

        return redirect()->route('core.gallery.index')->with('message', 'Foto galeri berhasil dihapus.');
    }

    public function publicIndex(): Response
    {
        $school = School::query()->firstOrFail();
        $photos = $this->publishedQuery($school)
            ->limit(30)
            ->get()
            ->map(fn (SchoolGalleryPhoto $photo): array => $this->present($photo))
            ->values();

        return Inertia::render('Public/Gallery/Index', [
            'school' => [
                'name' => $school->name,
                'logo_url' => $school->logo_path ? route('branding.school', [$school, 'logo']) : null,
            ],
            'photos' => $photos,
        ]);
    }

    public function photo(int $photo): StreamedResponse
    {
        $school = School::query()->firstOrFail();
        $item = $this->publishedQuery($school)->findOrFail($photo);

        return $this->imageResponse($item, ['Cache-Control' => 'public, max-age=3600']);
    }

    public function adminPhoto(Request $request, int $photo): StreamedResponse
    {
        $this->authorizeManager($request);
        $item = SchoolGalleryPhoto::query()
            ->where('school_id', $request->user()->school_id)
            ->findOrFail($photo);

        return $this->imageResponse($item, ['Cache-Control' => 'private, max-age=300']);
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, bool $requiredPhoto): array
    {
        $rules = [
            'title' => ['nullable', 'string', 'max:120'],
            'caption' => ['nullable', 'string', 'max:255'],
            'is_published' => ['required', 'boolean'],
            'photo' => [File::image()->types(['jpg', 'jpeg', 'png', 'webp'])->max(4096)],
        ];
        $rules['photo'][] = $requiredPhoto ? 'required' : 'nullable';

        return $request->validate($rules);
    }

    private function authorizeManager(Request $request): void
    {
        abort_unless($request->user()?->hasRole(['admin', 'operator']), 403);
    }

    private function publishedQuery(School $school)
    {
        return SchoolGalleryPhoto::query()
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
    private function present(SchoolGalleryPhoto $photo, bool $admin = false): array
    {
        return [
            'id' => $photo->id,
            'title' => $photo->title,
            'caption' => $photo->caption,
            'name' => $photo->original_name,
            'is_published' => $photo->is_published,
            'url' => route($admin ? 'core.gallery.admin-photo' : 'core.gallery.photo', [$photo->id]),
        ];
    }
}
