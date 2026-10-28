<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Shared\Files\PrivateStorageService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class PrivateStorageTest extends TestCase
{
    private PrivateStorageService $storageService;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->storageService = new PrivateStorageService;
    }

    public function test_put_and_get_file_in_private_storage(): void
    {
        $path = 'documents/students/kartu-pelajar-1001.pdf';
        $content = '%PDF-1.4 sample content';

        $saved = $this->storageService->put($path, $content);
        $this->assertTrue($saved);
        $this->assertTrue($this->storageService->exists($path));
        $this->assertSame($content, $this->storageService->get($path));

        $this->storageService->delete($path);
        $this->assertFalse($this->storageService->exists($path));
    }

    public function test_unauthorized_direct_access_without_signature_returns_403(): void
    {
        $response = $this->get('/berkas-privat/unduh?path=documents/sample.pdf');
        $response->assertForbidden();
    }

    public function test_access_with_valid_signed_url_returns_file_download(): void
    {
        $path = 'documents/reports/rapor-semester-1.pdf';
        $content = 'DOKUMEN_RAPOR_RESMI_TERENKRIPSI';
        $this->storageService->put($path, $content);

        $signedUrl = $this->storageService->generateTemporaryUrl($path, now()->addMinutes(15));

        $response = $this->get($signedUrl);

        $response->assertOk();
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertSame($content, $response->streamedContent());
    }

    public function test_signed_url_with_traversal_path_is_rejected_without_server_error(): void
    {
        $signedUrl = URL::temporarySignedRoute(
            'private.file.download',
            now()->addMinutes(15),
            ['path' => '../secret.pdf'],
        );

        $this->get($signedUrl)->assertNotFound();
    }

    public function test_expired_signed_url_returns_403(): void
    {
        $path = 'documents/reports/rapor-expired.pdf';
        $this->storageService->put($path, 'CONTENT');

        // Buat url yang sudah kedaluwarsa 5 menit lalu
        $signedUrl = $this->storageService->generateTemporaryUrl($path, now()->subMinutes(5));

        $response = $this->get($signedUrl);
        $response->assertForbidden();
    }

    public function test_non_existent_file_with_valid_signature_returns_404(): void
    {
        $path = 'documents/does-not-exist.pdf';
        $signedUrl = $this->storageService->generateTemporaryUrl($path, now()->addMinutes(15));

        $response = $this->get($signedUrl);
        $response->assertNotFound();
    }

    public function test_path_traversal_segments_are_rejected_instead_of_stripped(): void
    {
        foreach (['../../etc/shadow', 'documents/../secret.pdf', 'documents\\..\\secret.pdf'] as $maliciousPath) {
            $this->assertThrows(
                fn () => $this->storageService->put($maliciousPath, 'dummy'),
                \InvalidArgumentException::class,
            );
        }

        $this->assertFalse($this->storageService->exists('etc/shadow'));
    }

    public function test_dot_segments_are_canonicalized_without_allowing_parent_traversal(): void
    {
        $this->storageService->put('./documents/./sample.pdf', 'CONTENT');

        $this->assertTrue($this->storageService->exists('documents/sample.pdf'));
    }
}
