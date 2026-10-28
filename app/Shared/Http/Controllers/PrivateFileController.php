<?php

declare(strict_types=1);

namespace App\Shared\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Shared\Files\PrivateStorageService;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PrivateFileController extends Controller
{
    public function __construct(
        protected PrivateStorageService $storageService
    ) {}

    /**
     * Download berkas privat yang diverifikasi tanda tangan digital URL-nya.
     */
    public function download(Request $request): BinaryFileResponse
    {
        if (! $request->hasValidSignature()) {
            abort(403, 'Tautan unduhan tidak sah atau telah kedaluwarsa.');
        }

        $path = (string) $request->query('path');

        try {
            if (! $this->storageService->exists($path)) {
                abort(404, 'Berkas privat tidak ditemukan.');
            }

            $absolutePath = $this->storageService->path($path);
        } catch (InvalidArgumentException) {
            abort(404, 'Berkas privat tidak ditemukan.');
        }

        $response = response()->download($absolutePath);
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        return $response;
    }
}
