<?php

declare(strict_types=1);

namespace App\Modules\Core\Interface\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Application\Services\SchoolOrganizationTreeService;
use App\Modules\Core\Infrastructure\Models\School;
use App\Modules\Core\Infrastructure\Models\SchoolOrganizationMember;
use App\Shared\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SchoolOrganizationController extends Controller
{
    public function __construct(
        private readonly SchoolOrganizationTreeService $tree,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorizeManager($request);

        return Inertia::render('Core/Organization/Index', [
            'members' => $this->presentMembers((int) $request->user()->school_id, true),
        ]);
    }

    public function publicIndex(): Response
    {
        $school = School::query()->firstOrFail();

        return Inertia::render('Public/Organization/Index', [
            'school' => [
                'name' => $school->name,
                'logo_url' => $school->publicBrandingUrl('logo'),
                'theme' => $school->publicTheme(),
            ],
            'organization' => $this->presentMembers((int) $school->id, false),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeManager($request);

        return DB::transaction(function () use ($request): RedirectResponse {
            School::query()->whereKey($request->user()->school_id)->lockForUpdate()->firstOrFail();
            $schoolId = (int) $request->user()->school_id;
            $validated = $this->validated($request, $schoolId);
            $photo = $validated['photo'] ?? null;
            unset($validated['photo']);
            $member = SchoolOrganizationMember::create([
                ...$validated,
                'school_id' => $schoolId,
            ]);
            if ($photo instanceof UploadedFile) {
                $member->update(['photo_path' => $this->storePhoto($photo, $schoolId, (int) $member->id)]);
            }

            $this->auditLogger->log('core.school_organization.created', 'SchoolOrganizationMember', (string) $member->id, [
                'name' => $member->name,
                'position' => $member->position,
            ], actor: $request->user(), schoolId: $schoolId);

            return redirect()->route('core.organization.index')->with('message', 'Anggota struktur organisasi berhasil ditambahkan.');
        });
    }

    public function update(Request $request, int $member): RedirectResponse
    {
        $this->authorizeManager($request);

        return DB::transaction(function () use ($request, $member): RedirectResponse {
            School::query()->whereKey($request->user()->school_id)->lockForUpdate()->firstOrFail();
            $schoolId = (int) $request->user()->school_id;
            $item = SchoolOrganizationMember::query()->where('school_id', $schoolId)->findOrFail($member);
            $validated = $this->validated($request, $schoolId);
            $photo = $validated['photo'] ?? null;
            unset($validated['photo']);

            if (($validated['parent_id'] ?? null) === $item->id || $this->isDescendant((int) ($validated['parent_id'] ?? 0), $item)) {
                return back()->withErrors(['parent_id' => 'Atasan tidak boleh berada di bawah anggota ini.']);
            }

            if ($photo instanceof UploadedFile || ($item->is_published && ! $validated['is_published'])) {
                $this->assertPhotoNotReferenced($item, $photo instanceof UploadedFile);
            }
            $oldPhotoPath = $item->photo_path;
            $item->update($validated);
            if ($photo instanceof UploadedFile) {
                $item->update(['photo_path' => $this->storePhoto($photo, $schoolId, (int) $item->id)]);
                if ($oldPhotoPath) {
                    DB::afterCommit(static fn () => Storage::disk('local')->delete($oldPhotoPath));
                }
            }
            $this->auditLogger->log('core.school_organization.updated', 'SchoolOrganizationMember', (string) $item->id, [
                'name' => $item->name,
                'position' => $item->position,
            ], actor: $request->user(), schoolId: $schoolId);

            return redirect()->route('core.organization.index')->with('message', 'Anggota struktur organisasi berhasil diperbarui.');
        });
    }

    public function destroy(Request $request, int $member): RedirectResponse
    {
        $this->authorizeManager($request);

        return DB::transaction(function () use ($request, $member): RedirectResponse {
            School::query()->whereKey($request->user()->school_id)->lockForUpdate()->firstOrFail();
            $schoolId = (int) $request->user()->school_id;
            $item = SchoolOrganizationMember::query()->where('school_id', $schoolId)->findOrFail($member);
            $this->assertPhotoNotReferenced($item);
            if ($item->photo_path) {
                $oldPath = $item->photo_path;
                DB::afterCommit(static fn () => Storage::disk('local')->delete($oldPath));
            }
            $item->delete();

            $this->auditLogger->log('core.school_organization.deleted', 'SchoolOrganizationMember', (string) $member, [
                'name' => $item->name,
                'position' => $item->position,
            ], actor: $request->user(), schoolId: $schoolId);

            return redirect()->route('core.organization.index')->with('message', 'Anggota struktur organisasi berhasil dihapus.');
        });
    }

    public function photo(int $member): StreamedResponse
    {
        $school = School::query()->firstOrFail();
        $item = SchoolOrganizationMember::query()
            ->where('school_id', $school->id)
            ->where('is_published', true)
            ->findOrFail($member);

        return $this->imageResponse($item, ['Cache-Control' => 'public, no-cache, must-revalidate']);
    }

    public function adminPhoto(Request $request, int $member): StreamedResponse
    {
        $this->authorizeManager($request);
        $item = SchoolOrganizationMember::query()
            ->where('school_id', $request->user()->school_id)
            ->findOrFail($member);

        return $this->imageResponse($item, ['Cache-Control' => 'private, max-age=300']);
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, int $schoolId): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'position' => ['required', 'string', 'max:120'],
            'department' => ['nullable', 'string', 'max:120'],
            'photo' => ['nullable', File::image()->types(['jpg', 'jpeg', 'png', 'webp'])->max(2048)],
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('school_organization_members', 'id')->where(fn ($query) => $query->where('school_id', $schoolId)),
            ],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'is_published' => ['required', 'boolean'],
        ]) + ['sort_order' => (int) $request->input('sort_order', 0)];
    }

    private function isDescendant(int $candidateId, SchoolOrganizationMember $item): bool
    {
        if ($candidateId === 0) {
            return false;
        }

        $children = SchoolOrganizationMember::query()->where('parent_id', $item->id)->pluck('id');
        if ($children->contains($candidateId)) {
            return true;
        }

        return $children->contains(fn ($childId): bool => $this->isDescendant($candidateId, $item->newQuery()->findOrFail($childId)));
    }

    private function authorizeManager(Request $request): void
    {
        abort_unless($request->user()?->hasRole(['admin', 'operator']), 403);
    }

    /** @return list<array<string, mixed>> */
    private function presentMembers(int $schoolId, bool $admin): array
    {
        return array_map(function (array $member) use ($admin): array {
            $photoPath = $member['photo_path'] ?? null;
            unset($member['photo_path']);
            $member['photo_url'] = $photoPath
                ? route($admin ? 'core.organization.admin-photo' : 'core.organization.photo', [$member['id']])
                : null;

            return $member;
        }, $this->tree->rowsForSchool($schoolId, ! $admin));
    }

    private function assertPhotoNotReferenced(SchoolOrganizationMember $member, bool $includeHistory = true): void
    {
        $school = School::query()->findOrFail($member->school_id);
        $registry = $school->publicContentRegistry();
        if (! $includeHistory) {
            unset($registry['revisions']);
        }
        $path = (string) parse_url(route('core.organization.photo', $member->id), PHP_URL_PATH);
        if (preg_match('~'.preg_quote($path, '~').'(?=[?\x23"]|$)~', json_encode($registry, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR))) {
            throw ValidationException::withMessages(['photo' => 'Foto masih dipakai oleh konten, draf, jadwal, atau riwayat publikasi. Ganti rujukannya sebelum menghapus atau mengganti foto.']);
        }
    }

    private function storePhoto(UploadedFile $photo, int $schoolId, int $memberId): string
    {
        $path = $photo->store('school-organization/'.$schoolId.'/'.$memberId, 'local');
        if (! is_string($path) || $path === '') {
            throw ValidationException::withMessages(['photo' => 'Foto gagal disimpan. Foto lama tetap dipertahankan.']);
        }
        DB::afterRollBack(static fn () => Storage::disk('local')->delete($path));

        return $path;
    }

    /** @param array<string, string> $headers */
    private function imageResponse(SchoolOrganizationMember $member, array $headers): StreamedResponse
    {
        abort_unless($member->photo_path && Storage::disk('local')->exists($member->photo_path), 404);

        return Storage::disk('local')->response($member->photo_path, basename($member->photo_path), $headers);
    }
}
