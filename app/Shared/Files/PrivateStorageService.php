<?php

declare(strict_types=1);

namespace App\Shared\Files;

use DateTimeInterface;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use InvalidArgumentException;

class PrivateStorageService
{
    protected string $disk = 'local';

    /**
     * Simpan isi konten berkas ke penyimpanan privat.
     */
    public function put(string $path, string $contents): bool
    {
        $sanitizedPath = $this->sanitizePath($path);

        return Storage::disk($this->disk)->put($sanitizedPath, $contents);
    }

    /**
     * Ambil isi berkas dari penyimpanan privat.
     */
    public function get(string $path): ?string
    {
        $sanitizedPath = $this->sanitizePath($path);

        if (! Storage::disk($this->disk)->exists($sanitizedPath)) {
            return null;
        }

        return Storage::disk($this->disk)->get($sanitizedPath);
    }

    /**
     * Periksa apakah berkas ada di penyimpanan privat.
     */
    public function exists(string $path): bool
    {
        return Storage::disk($this->disk)->exists($this->sanitizePath($path));
    }

    /**
     * Hapus berkas dari penyimpanan privat.
     */
    public function delete(string $path): bool
    {
        return Storage::disk($this->disk)->delete($this->sanitizePath($path));
    }

    /**
     * Dapatkan path lokal absolut dari berkas privat.
     */
    public function path(string $path): string
    {
        return Storage::disk($this->disk)->path($this->sanitizePath($path));
    }

    /**
     * Menghasilkan Signed URL sementara yang kedaluwarsa dalam rentang waktu tertentu.
     *
     * @param  array<string, mixed>  $extraParams
     */
    public function generateTemporaryUrl(string $path, DateTimeInterface|int $expiration, array $extraParams = []): string
    {
        $sanitizedPath = $this->sanitizePath($path);

        $params = array_merge($extraParams, [
            'path' => $sanitizedPath,
        ]);

        return URL::temporarySignedRoute(
            'private.file.download',
            $expiration,
            $params
        );
    }

    /**
     * Cegah directory traversal attacks dan kembalikan path relatif kanonis.
     */
    protected function sanitizePath(string $path): string
    {
        $normalized = str_replace('\\', '/', $path);

        if (str_contains($normalized, "\0")) {
            throw new InvalidArgumentException('Path berkas privat tidak valid.');
        }

        $segments = explode('/', $normalized);

        if (in_array('..', $segments, true)) {
            throw new InvalidArgumentException('Path berkas privat tidak boleh memuat segmen parent traversal.');
        }

        $segments = array_values(array_filter(
            $segments,
            static fn (string $segment): bool => $segment !== '' && $segment !== '.',
        ));
        $clean = implode('/', $segments);

        if ($clean === '') {
            throw new InvalidArgumentException('Path berkas privat tidak boleh kosong.');
        }

        return $clean;
    }
}
