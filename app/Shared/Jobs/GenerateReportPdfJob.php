<?php

declare(strict_types=1);

namespace App\Shared\Jobs;

use App\Modules\Assessment\Infrastructure\Models\ReportSnapshot;
use App\Shared\Files\PrivateStorageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenerateReportPdfJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Jumlah percobaan maksimal sebelum job dinyatakan gagal.
     */
    public int $tries = 3;

    /**
     * Waktu maksimum job berjalan dalam detik.
     */
    public int $timeout = 120;

    public function __construct(
        public int $reportSnapshotId,
        public string $destinationDirectory = 'reports/generated'
    ) {}

    /**
     * Eksekusi job penyusunan berkas rapor di latar belakang.
     */
    public function handle(PrivateStorageService $storageService): void
    {
        $snapshot = ReportSnapshot::find($this->reportSnapshotId);

        if (! $snapshot) {
            Log::warning("[GenerateReportPdfJob] ReportSnapshot #{$this->reportSnapshotId} tidak ditemukan. Job dihentikan.");

            return;
        }

        // Simulasi penyusunan PDF berbasis JSON snapshot
        $filename = "{$this->destinationDirectory}/rapor-snapshot-{$snapshot->id}-{$snapshot->checksum}.pdf";

        $pdfContent = "%PDF-1.4\n% School Suite Digital Report Card\n"
            ."Report ID: {$snapshot->id}\n"
            ."Checksum: {$snapshot->checksum}\n"
            ."Scheme Version: {$snapshot->scheme_version}\n"
            ."Status: {$snapshot->status->value}\n"
            .'Generated At: '.now()->toIso8601String()."\n"
            .'%%EOF';

        $storageService->put($filename, $pdfContent);

        Log::info("[GenerateReportPdfJob] Berhasil menghasilkan berkas rapor untuk snapshot #{$snapshot->id} di {$filename}.");
    }

    /**
     * Tangani kegagalan permanen job setelah melampaui batas percobaan.
     */
    public function failed(?Throwable $exception): void
    {
        Log::error("[GenerateReportPdfJob] Gagal menyusun PDF untuk ReportSnapshot #{$this->reportSnapshotId}", [
            'error' => $exception?->getMessage(),
            'trace' => $exception?->getTraceAsString(),
        ]);
    }
}
