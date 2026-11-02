<?php

declare(strict_types=1);

namespace App\Modules\Core\Interface\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Application\Services\SchoolOrganizationTreeService;
use App\Modules\Core\Infrastructure\Models\School;
use App\Modules\Core\Infrastructure\Models\SchoolGalleryAlbum;
use App\Modules\Core\Infrastructure\Models\SchoolNews;
use Inertia\Inertia;
use Inertia\Response;

class SchoolProfileController extends Controller
{
    public function __construct(private readonly SchoolOrganizationTreeService $organizationTree) {}

    public function index(): Response
    {
        return Inertia::render('Public/School/Profile', $this->publicData());
    }

    public function home(): Response
    {
        $data = $this->publicData();

        return Inertia::render('Public/School/Home', [
            'school' => $data['school'],
            'news' => $data['news'],
            'gallery' => $data['gallery'],
        ]);
    }

    public function programs(): Response
    {
        $data = $this->publicData();

        return Inertia::render('Public/School/Programs', [
            'school' => $data['school'],
            'news' => $data['news'],
            'gallery' => $data['gallery'],
        ]);
    }

    public function studentLife(): Response
    {
        $data = $this->publicData();

        return Inertia::render('Public/School/StudentLife', [
            'school' => $data['school'],
            'news' => $data['news'],
            'gallery' => $data['gallery'],
        ]);
    }

    public function contact(): Response
    {
        return Inertia::render('Public/School/Contact', [
            'school' => $this->publicData()['school'],
        ]);
    }

    /** @return array<string, mixed> */
    private function publicData(): array
    {
        $school = School::query()->firstOrFail();
        $news = SchoolNews::query()
            ->with('photos')
            ->where('school_id', $school->id)
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->latest('published_at')
            ->limit(3)
            ->get()
            ->map(fn (SchoolNews $item): array => [
                'id' => $item->id,
                'title' => $item->title,
                'slug' => $item->slug,
                'excerpt' => $item->excerpt,
                'published_at' => $item->published_at?->toIso8601String(),
                'photos' => $item->photos->map(fn ($photo): array => [
                    'id' => $photo->id,
                    'name' => $photo->original_name,
                    'caption' => $photo->caption,
                    'url' => route('core.news.photo', [$item->slug, $photo->id]),
                ])->values()->all(),
            ])
            ->values();
        $gallery = SchoolGalleryAlbum::query()
            ->with('photos')
            ->where('school_id', $school->id)
            ->where('is_published', true)
            ->orderBy('sort_order')
            ->orderByDesc('created_at')
            ->limit(6)
            ->get()
            ->filter(fn (SchoolGalleryAlbum $album): bool => $album->photos->isNotEmpty())
            ->map(fn (SchoolGalleryAlbum $album): array => [
                'id' => $album->id,
                'title' => $album->title,
                'caption' => $album->caption,
                'url' => route('core.gallery.photo', [$album->photos->first()->id]),
            ])
            ->values();
        $organization = array_map(function (array $member): array {
            $photoPath = $member['photo_path'] ?? null;
            unset($member['photo_path']);
            $member['photo_url'] = $photoPath ? route('core.organization.photo', [$member['id']]) : null;

            return $member;
        }, $this->organizationTree->rowsForSchool((int) $school->id, true));

        return [
            'school' => [
                ...$school->only([
                    'name', 'legal_name', 'level', 'school_status', 'accreditation',
                    'address', 'phone', 'email', 'principal_name', 'website',
                    'about', 'principal_message', 'vision', 'mission',
                ]),
                'logo_url' => $school->logo_path ? route('branding.school', [$school, 'logo']) : null,
                'theme' => $school->publicTheme(),
            ],
            'news' => $news,
            'gallery' => $gallery,
            'organization' => $organization,
        ];
    }
}
