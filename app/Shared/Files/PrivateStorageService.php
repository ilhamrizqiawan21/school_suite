<?php

declare(strict_types=1);

namespace App\Shared\Files;

use DateTimeInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use InvalidArgumentException;

class PrivateStorageService
{
    /** @var list<string> */
    public const DEFAULT_ALLOWED_MIMES = [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    /** @var array<string, list<string>> */
    private const MIME_EXTENSIONS = [
        'application/pdf' => ['pdf'],
        'image/jpeg' => ['jpg', 'jpeg'],
        'image/png' => ['png'],
        'image/webp' => ['webp'],
        'text/plain' => ['txt'],
        'text/csv' => ['csv'],
    ];

    protected string $disk = 'local';

    /**
     * Validate an uploaded file (size, real MIME, safe extension) and store it on the private disk.
     *
     * @param  list<string>  $allowedMimes
     * @return array{path: string, original_name: string, mime_type: string, size_bytes: int, sha256: string}
     */
    public function storeValidatedUpload(
        UploadedFile $file,
        string $directory,
        array $allowedMimes = self::DEFAULT_ALLOWED_MIMES,
        int $maxBytes = 5_242_880,
    ): array {
        if (! $file->isValid()) {
            throw new InvalidArgumentException('Unggahan berkas tidak valid.');
        }

        $sizeBytes = (int) ($file->getSize() ?? 0);
        if ($sizeBytes <= 0 || $sizeBytes > $maxBytes) {
            throw new InvalidArgumentException('Ukuran berkas melebihi batas atau kosong.');
        }

        $mimeType = (string) ($file->getMimeType() ?? '');
        if (! in_array($mimeType, $allowedMimes, true)) {
            throw new InvalidArgumentException('Jenis MIME berkas tidak diizinkan.');
        }

        $extension = strtolower((string) $file->getClientOriginalExtension());
        $allowedExtensions = self::MIME_EXTENSIONS[$mimeType] ?? [];
        if ($extension === '' || ! in_array($extension, $allowedExtensions, true)) {
            throw new InvalidArgumentException('Ekstensi berkas tidak cocok dengan jenis berkas.');
        }

        $contents = file_get_contents($file->getRealPath());
        if ($contents === false) {
            throw new InvalidArgumentException('Gagal membaca berkas unggahan.');
        }

        $cleanDirectory = $this->sanitizePath($directory);
        $storedName = Str::uuid()->toString().'.'.$extension;
        $storagePath = $cleanDirectory.'/'.$storedName;

        $this->put($storagePath, $contents);

        return [
            'path' => $storagePath,
            'original_name' => basename((string) $file->getClientOriginalName()),
            'mime_type' => $mimeType,
            'size_bytes' => $sizeBytes,
            'sha256' => hash('sha256', $contents),
        ];
    }

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
