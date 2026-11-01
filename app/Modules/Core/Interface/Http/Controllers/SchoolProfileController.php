<?php

declare(strict_types=1);

namespace App\Modules\Core\Interface\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Infrastructure\Models\School;
use App\Modules\Core\Infrastructure\Models\SchoolGalleryPhoto;
use App\Modules\Core\Infrastructure\Models\SchoolNews;
use Inertia\Inertia;
use Inertia\Response;

class SchoolProfileController extends Controller
{
    public function index(): Response
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
        $gallery = SchoolGalleryPhoto::query()
            ->where('school_id', $school->id)
            ->where('is_published', true)
            ->orderBy('sort_order')
            ->orderByDesc('created_at')
            ->limit(6)
            ->get()
            ->map(fn (SchoolGalleryPhoto $photo): array => [
                'id' => $photo->id,
                'title' => $photo->title,
                'caption' => $photo->caption,
                'url' => route('core.gallery.photo', [$photo->id]),
            ])
            ->values();

        return Inertia::render('Public/School/Profile', [
            'school' => [
                ...$school->only([
                    'name', 'legal_name', 'level', 'school_status', 'accreditation',
                    'address', 'phone', 'email', 'principal_name', 'website',
                    'about', 'principal_message', 'vision', 'mission',
                ]),
                'logo_url' => $school->logo_path ? route('branding.school', [$school, 'logo']) : null,
            ],
            'news' => $news,
            'gallery' => $gallery,
        ]);
    }
}
