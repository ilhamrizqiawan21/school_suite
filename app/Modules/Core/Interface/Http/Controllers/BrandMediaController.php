<?php

declare(strict_types=1);

namespace App\Modules\Core\Interface\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Infrastructure\Models\School;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BrandMediaController extends Controller
{
    public function school(Request $request, School $school, string $kind): StreamedResponse
    {
        abort_unless(in_array($kind, ['logo', 'favicon'], true), 404);
        if ($request->user()) {
            abort_unless((int) $request->user()->school_id === (int) $school->id, 404);
        }
        $path = $kind === 'logo' ? $school->logo_path : $school->favicon_path;
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, null, ['Cache-Control' => 'private, max-age=3600']);
    }

    public function avatar(Request $request): StreamedResponse
    {
        $path = $request->user()->avatar_path;
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, null, ['Cache-Control' => 'private, max-age=3600']);
    }
}
