<?php

declare(strict_types=1);

namespace App\Modules\Integration\Application\Services;

use App\Models\User;
use App\Modules\Academic\Application\Services\AcademicQueryService;
use App\Modules\Core\Application\Services\AccessScopeService;
use App\Modules\Core\Application\Services\SchoolScopeService;
use App\Modules\Core\Application\Services\ServiceTicketService;
use App\Modules\Core\Domain\RoleEnum;
use App\Modules\Integration\Infrastructure\Models\IntegrationConnection;
use App\Modules\Integration\Infrastructure\Models\IntegrationSyncLog;
use App\Modules\Library\Application\Services\LibraryService;
use App\Shared\Audit\AuditLogger;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ExternalIntegrationService
{
    public const ACADEMIC_SYSTEMS = [
        'emis' => [
            'label' => 'EMIS Kemenag',
            'description' => 'Rekonsiliasi & pertukaran berkas CSV/JSON data siswa dan madrasah EMIS.',
            'default_mechanism' => 'file_exchange',
            'default_mapping' => [
                'nisn' => 'nisn',
                'full_name' => 'full_name',
                'class_name' => 'class_name',
                'status' => 'status',
            ],
            'gate_note' => 'Mode file_exchange dan tautan aktif; API sync otomatis menunggu persetujuan K-05.',
        ],
        'rdm' => [
            'label' => 'RDM (Rapor Digital Madrasah)',
            'description' => 'Ekspor nilai terstruktur & pratinjau rekonsiliasi nilai akhir RDM.',
            'default_mechanism' => 'file_exchange',
            'default_mapping' => [
                'nisn' => 'nisn',
                'full_name' => 'full_name',
                'subject_code' => 'subject_code',
                'final_score' => 'final_score',
            ],
            'gate_note' => 'API sync RDM bersifat opsional dan menunggu kredensial resmi K-05.',
        ],
        'slims' => [
            'label' => 'SLiMS Perpustakaan',
            'description' => 'Pertukaran katalog bibliografi & inventaris koleksi antara School Suite dan SLiMS.',
            'default_mechanism' => 'file_exchange',
            'default_mapping' => [
                'biblio_code' => 'biblio_code',
                'title' => 'title',
                'author' => 'author',
                'isbn' => 'isbn',
            ],
            'gate_note' => 'Rekonsiliasi katalog via file CSV/JSON aktif tanpa ketergantungan jaringan eksternal.',
        ],
    ];

    public const CHANNEL_DEFINITIONS = [
        'prayer' => [
            'label' => 'Penyedia Jadwal Salat',
            'category_hint' => 'Ibadah & Jadwal Harian',
            'default_config' => ['provider_name' => 'Kemenag / Lokal', 'city_code' => '6303', 'city_name' => 'Kab. Banjar', 'calculation_method' => 'KEMENAG'],
            'fallback_message' => 'Menggunakan jadwal lokal sekolah (WITA/WIB) bila penyedia jadwal salat eksternal tidak tersedia.',
        ],
        'map' => [
            'label' => 'Peta Lokasi Sekolah',
            'category_hint' => 'Profil Publik & Kontak',
            'default_config' => ['provider_name' => 'OpenStreetMap / Embed', 'latitude' => '-3.4125', 'longitude' => '114.8481', 'zoom' => '15'],
            'fallback_message' => 'Menampilkan alamat lengkap dan tautan koordinat statis bila peta interaktif gagal dimuat.',
        ],
        'chat' => [
            'label' => 'Kanal Chat Eksternal',
            'category_hint' => 'Komunikasi Tambahan',
            'default_config' => ['provider_name' => 'Webhook / Telegram Bot', 'channel_handle' => ''],
            'fallback_message' => 'Fitur Diskusi Kelas dan Pengumuman internal School Suite tetap berfungsi penuh tanpa bridge eksternal.',
        ],
        'email' => [
            'label' => 'Email / SMTP Notifikasi',
            'category_hint' => 'Pengiriman Surat & Notifikasi',
            'default_config' => ['provider_name' => 'SMTP Sekolah', 'sender_address' => '', 'port' => '587'],
            'fallback_message' => 'Pemberitahuan tetap tercatat di outbox internal aplikasi dan status portal tiket tanpa mengirim email keluar.',
        ],
        'whatsapp' => [
            'label' => 'Gateway WhatsApp',
            'category_hint' => 'Notifikasi PTSP & Presensi',
            'default_config' => ['provider_name' => 'WA Gateway Adapter', 'sender_number' => ''],
            'fallback_message' => 'Log pesan dicatat pada antrean WhatsApp sekolah sebagai tertunda; layanan PTSP dan presensi tetap berjalan normal.',
        ],
        'kiosk_hardware' => [
            'label' => 'Pembaca RFID / QR Kios',
            'category_hint' => 'Perangkat Keras Kios & Pos Jaga',
            'default_config' => ['rfid_usb_hid' => true, 'camera_qr_enabled' => true, 'device_label' => 'Kios Utama'],
            'fallback_message' => 'Petugas piket, satpam, dan perpustakaan tetap dapat memproses kunjungan dengan input NISN atau kode token manual.',
        ],
    ];

    public function __construct(
        private readonly SchoolScopeService $schoolScope,
        private readonly AccessScopeService $accessScope,
        private readonly ServiceTicketService $ticketService,
        private readonly AcademicQueryService $academicQuery,
        private readonly LibraryService $libraryService,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function canManage(?User $user): bool
    {
        $roleName = $user?->role?->name;

        return is_string($roleName) && in_array($roleName, [
            RoleEnum::ADMIN->value,
            RoleEnum::OPERATOR->value,
        ], true);
    }

    public function assertCanManage(?User $user): void
    {
        abort_unless($this->canManage($user), 403, 'Hanya Admin atau Operator yang dapat mengelola integrasi & kanal eksternal.');
    }

    /**
     * Build complete integration & external channel workspace payload with secrets masked.
     *
     * @return array<string, mixed>
     */
    public function getWorkspaceData(User $actor): array
    {
        $this->assertCanManage($actor);
        $schoolId = (int) $actor->school_id;

        $saved = IntegrationConnection::query()
            ->where('school_id', $schoolId)
            ->get()
            ->keyBy('system_key');

        $academicSystems = [];
        foreach (self::ACADEMIC_SYSTEMS as $key => $meta) {
            /** @var ?IntegrationConnection $conn */
            $conn = $saved->get($key);
            $academicSystems[] = [
                'system_key' => $key,
                'label' => $meta['label'],
                'description' => $meta['description'],
                'gate_note' => $meta['gate_note'],
                'category' => IntegrationConnection::CATEGORY_ACADEMIC_SYNC,
                'mechanism' => $conn?->mechanism ?? $meta['default_mechanism'],
                'is_enabled' => (bool) ($conn?->is_enabled ?? ($key !== 'rdm')),
                'endpoint_url' => $conn?->endpoint_url,
                'config' => array_merge(
                    ['field_mapping' => $meta['default_mapping']],
                    is_array($conn?->config_json) ? $conn->config_json : [],
                ),
                'has_secret' => $conn?->secret_encrypted !== null && $conn->secret_encrypted !== '',
                'secret_masked' => $conn?->secret_last4 ? '••••'.$conn->secret_last4 : null,
                'last_health_status' => match ($conn?->last_health_status) {
                    'healthy' => 'configuration_unverified',
                    null => 'unconfigured',
                    default => $conn->last_health_status,
                },
                'last_checked_at' => $conn?->last_checked_at?->toIso8601String(),
                'last_error' => $conn?->last_error,
            ];
        }

        $channels = [];
        foreach (self::CHANNEL_DEFINITIONS as $key => $meta) {
            /** @var ?IntegrationConnection $conn */
            $conn = $saved->get($key);
            $channels[] = [
                'system_key' => $key,
                'label' => $meta['label'],
                'category_hint' => $meta['category_hint'],
                'fallback_message' => $meta['fallback_message'],
                'category' => IntegrationConnection::CATEGORY_EXTERNAL_CHANNEL,
                'mechanism' => $conn?->mechanism ?? 'provider',
                'is_enabled' => (bool) ($conn?->is_enabled ?? false),
                'endpoint_url' => $conn?->endpoint_url,
                'config' => array_merge(
                    $meta['default_config'],
                    is_array($conn?->config_json) ? $conn->config_json : [],
                ),
                'has_secret' => $conn?->secret_encrypted !== null && $conn->secret_encrypted !== '',
                'secret_masked' => $conn?->secret_last4 ? '••••'.$conn->secret_last4 : null,
                'last_health_status' => match ($conn?->last_health_status) {
                    'healthy' => 'configuration_unverified',
                    null => 'unconfigured',
                    default => $conn->last_health_status,
                },
                'last_checked_at' => $conn?->last_checked_at?->toIso8601String(),
                'last_error' => $conn?->last_error,
            ];
        }

        $logs = IntegrationSyncLog::query()
            ->where('school_id', $schoolId)
            ->with('triggeredBy:id,name')
            ->latest('id')
            ->limit(25)
            ->get()
            ->map(fn (IntegrationSyncLog $log): array => $this->serializeSyncLog($log))
            ->all();

        return [
            'academic_systems' => $academicSystems,
            'channels' => $channels,
            'sync_logs' => $logs,
        ];
    }

    /**
     * Save system/channel settings while encrypting secrets and redacting them from audit logs.
     *
     * @param  array<string, mixed>  $data
     */
    public function saveConnection(User $actor, string $systemKey, array $data): IntegrationConnection
    {
        $this->assertCanManage($actor);

        $isAcademic = array_key_exists($systemKey, self::ACADEMIC_SYSTEMS);
        $isChannel = array_key_exists($systemKey, self::CHANNEL_DEFINITIONS);
        if (! $isAcademic && ! $isChannel) {
            throw new InvalidArgumentException('Kunci sistem/kanal integrasi tidak dikenal.');
        }

        $schoolId = (int) $actor->school_id;
        $category = $isAcademic
            ? IntegrationConnection::CATEGORY_ACADEMIC_SYNC
            : IntegrationConnection::CATEGORY_EXTERNAL_CHANNEL;

        return DB::transaction(function () use ($actor, $schoolId, $systemKey, $category, $data, $isAcademic): IntegrationConnection {
            $connection = IntegrationConnection::query()->firstOrNew([
                'school_id' => $schoolId,
                'system_key' => $systemKey,
            ]);

            $secretToken = isset($data['secret_token']) ? trim((string) $data['secret_token']) : '';
            $clearSecret = (bool) ($data['clear_secret'] ?? false);

            $secretEncrypted = $connection->secret_encrypted;
            $secretLast4 = $connection->secret_last4;

            if ($clearSecret) {
                $secretEncrypted = null;
                $secretLast4 = null;
            } elseif ($secretToken !== '') {
                $secretEncrypted = $secretToken;
                $secretLast4 = substr($secretToken, -4);
            }

            $mechanism = (string) ($data['mechanism'] ?? ($isAcademic ? 'file_exchange' : 'provider'));
            $isEnabled = (bool) ($data['is_enabled'] ?? false);
            $endpointUrl = isset($data['endpoint_url']) && trim((string) $data['endpoint_url']) !== ''
                ? trim((string) $data['endpoint_url'])
                : null;

            $config = is_array($data['config'] ?? null) ? $data['config'] : [];

            $healthStatus = match (true) {
                ! $isEnabled => 'unconfigured',
                $mechanism === 'api_sync' && $endpointUrl === null => 'degraded',
                default => 'configuration_unverified',
            };

            $connection->fill([
                'category' => $category,
                'mechanism' => $mechanism,
                'is_enabled' => $isEnabled,
                'endpoint_url' => $endpointUrl,
                'config_json' => $config,
                'secret_encrypted' => $secretEncrypted,
                'secret_last4' => $secretLast4,
                'last_health_status' => $healthStatus,
                'last_checked_at' => null,
                'last_error' => $healthStatus === 'degraded'
                    ? 'API sync dipilih namun endpoint belum diisi; gunakan ekspor/impor file sambil menunggu K-05.'
                    : null,
                'updated_by_user_id' => (int) $actor->id,
            ]);
            $connection->save();

            // Audit log records metadata only — raw secret values are never logged.
            $this->auditLogger->log(
                eventType: 'integration.connection.updated',
                targetType: 'IntegrationConnection',
                targetId: (string) $connection->id,
                newValues: [
                    'system_key' => $systemKey,
                    'category' => $category,
                    'mechanism' => $mechanism,
                    'is_enabled' => $isEnabled,
                    'endpoint_configured' => $endpointUrl !== null,
                    'secret_rotated' => $secretToken !== '' || $clearSecret,
                ],
                schoolId: $schoolId,
                actor: $actor,
            );

            return $connection;
        });
    }

    /**
     * Evaluate connection health and return explicit graceful fallback when provider fails (M4-INT-04).
     *
     * @return array{system_key:string, status:string, fallback_active:bool, core_service_impact:string, message:string}
     */
    public function testConnectionHealth(User $actor, string $systemKey, bool $simulateOutage = false): array
    {
        $this->assertCanManage($actor);
        $schoolId = (int) $actor->school_id;

        $isChannel = array_key_exists($systemKey, self::CHANNEL_DEFINITIONS);
        $isAcademic = array_key_exists($systemKey, self::ACADEMIC_SYSTEMS);
        if (! $isChannel && ! $isAcademic) {
            throw new InvalidArgumentException('Kunci integrasi tidak ditemukan.');
        }

        $connection = IntegrationConnection::query()->firstOrCreate(
            ['school_id' => $schoolId, 'system_key' => $systemKey],
            [
                'category' => $isAcademic ? IntegrationConnection::CATEGORY_ACADEMIC_SYNC : IntegrationConnection::CATEGORY_EXTERNAL_CHANNEL,
                'mechanism' => $isAcademic ? 'file_exchange' : 'provider',
                'is_enabled' => false,
                'last_health_status' => 'unconfigured',
            ]
        );

        $fallbackMsg = $isChannel
            ? self::CHANNEL_DEFINITIONS[$systemKey]['fallback_message']
            : 'Validasi berkas CSV/JSON dan ekspor lokal tetap tersedia.';

        $requiresSecret = in_array($systemKey, ['whatsapp', 'email', 'chat'], true);
        $requiresEndpoint = $connection->mechanism === 'api_sync' || in_array($systemKey, ['whatsapp', 'chat'], true);

        $status = 'configuration_validated';
        $message = 'Konfigurasi lokal tervalidasi. Koneksi ke penyedia belum diuji.';

        if ($simulateOutage) {
            $status = 'offline';
            $message = 'Penyedia eksternal tidak merespons (simulasi gangguan). Fallback aktif: '.$fallbackMsg;
        } elseif (! $connection->is_enabled) {
            $status = 'degraded';
            $message = 'Kanal dinonaktifkan. Fallback aktif: '.$fallbackMsg;
        } elseif ($requiresEndpoint && ($connection->endpoint_url === null || $connection->endpoint_url === '')) {
            $status = 'degraded';
            $message = 'Endpoint eksternal belum dikonfigurasi. Fallback aktif: '.$fallbackMsg;
        } elseif ($requiresSecret && ($connection->secret_encrypted === null || $connection->secret_encrypted === '')) {
            $status = 'degraded';
            $message = 'Kredensial/token API belum diisi. Fallback aktif: '.$fallbackMsg;
        }

        $connection->update([
            'last_health_status' => $status,
            'last_checked_at' => now(),
            'last_error' => $status === 'configuration_validated' ? null : $message,
            'updated_by_user_id' => (int) $actor->id,
        ]);

        return [
            'system_key' => $systemKey,
            'status' => $status,
            'fallback_active' => $status !== 'configuration_validated',
            'core_service_impact' => 'none',
            'message' => $message,
        ];
    }

    /**
     * Dry-run reconciliation preview returning per-row validation errors & recovery guidance.
     *
     * @param  array<int, array<string, mixed>>|UploadedFile|string  $source
     * @return array<string, mixed>
     */
    public function previewImport(User $actor, string $systemKey, array|UploadedFile|string $source): array
    {
        $this->assertCanManage($actor);
        if (! array_key_exists($systemKey, self::ACADEMIC_SYSTEMS)) {
            throw new InvalidArgumentException('Sistem sinkronisasi harus salah satu dari EMIS, RDM, atau SLiMS.');
        }

        $mapping = $this->resolveMapping((int) $actor->school_id, $systemKey);
        $rawRows = $this->parseRows($source);

        return $this->evaluateRows($systemKey, $rawRows, $mapping);
    }

    /**
     * Execute reconciliation import with partial-failure reporting & idempotency protection (M4-INT-03).
     *
     * @param  array<int, array<string, mixed>>|UploadedFile|string  $source
     */
    public function commitImport(
        User $actor,
        string $systemKey,
        array|UploadedFile|string $source,
        ?string $idempotencyKey = null,
    ): IntegrationSyncLog {
        $this->assertCanManage($actor);
        if (! array_key_exists($systemKey, self::ACADEMIC_SYSTEMS)) {
            throw new InvalidArgumentException('Sistem sinkronisasi harus salah satu dari EMIS, RDM, atau SLiMS.');
        }

        $schoolId = (int) $actor->school_id;
        $cleanKey = $idempotencyKey !== null && trim($idempotencyKey) !== '' ? trim($idempotencyKey) : null;
        $evaluation = $this->previewImport($actor, $systemKey, $source);

        if ($evaluation['total_rows'] === 0) {
            throw new InvalidArgumentException('Berkas atau payload impor tidak memiliki baris data.');
        }

        return DB::transaction(function () use ($actor, $schoolId, $systemKey, $cleanKey, $evaluation): IntegrationSyncLog {
            $this->schoolScope->lockSchool($schoolId);

            if ($cleanKey !== null) {
                $existing = IntegrationSyncLog::query()
                    ->where('school_id', $schoolId)
                    ->where('idempotency_key', $cleanKey)
                    ->first();

                if ($existing !== null) {
                    return $existing;
                }
            }

            $status = match (true) {
                $evaluation['failed_rows'] === 0 => 'completed',
                $evaluation['valid_rows'] > 0 => 'partial_failed',
                default => 'failed',
            };

            $year = (int) $this->schoolScope->localNow($schoolId)->format('Y');
            $batchCode = $this->ticketService->generateTicketCode(strtoupper($systemKey), $year);

            $log = IntegrationSyncLog::query()->create([
                'school_id' => $schoolId,
                'system_key' => $systemKey,
                'direction' => 'import',
                'batch_code' => $batchCode,
                'status' => $status,
                'total_rows' => $evaluation['total_rows'],
                'success_rows' => $evaluation['valid_rows'],
                'failed_rows' => $evaluation['failed_rows'],
                'failed_details_json' => $evaluation['failed_details'],
                'payload_snapshot_json' => [
                    'valid_records' => $evaluation['mapped_valid'],
                    'failed_records' => $evaluation['failed_details'],
                ],
                'idempotency_key' => $cleanKey,
                'triggered_by_user_id' => (int) $actor->id,
            ]);

            $this->auditLogger->log(
                eventType: 'integration.sync.imported',
                targetType: 'IntegrationSyncLog',
                targetId: (string) $log->id,
                newValues: [
                    'system_key' => $systemKey,
                    'batch_code' => $batchCode,
                    'status' => $status,
                    'total_rows' => $evaluation['total_rows'],
                    'failed_rows' => $evaluation['failed_rows'],
                ],
                schoolId: $schoolId,
                actor: $actor,
            );

            return $log;
        });
    }

    /**
     * Retry a failed/partial batch idempotently, optionally supplying corrected rows.
     *
     * @param  array<int, array<string, mixed>>|null  $correctedRows
     */
    public function retrySyncLog(User $actor, IntegrationSyncLog $log, ?array $correctedRows = null): IntegrationSyncLog
    {
        $this->assertCanManage($actor);
        abort_unless($this->accessScope->belongsToSchool($actor, (int) $log->school_id), 403, 'Akses lintas sekolah tidak diizinkan.');

        $schoolId = (int) $log->school_id;

        if ($log->status === 'completed') {
            return $log;
        }

        return DB::transaction(function () use ($actor, $schoolId, $log, $correctedRows): IntegrationSyncLog {
            $this->schoolScope->lockSchool($schoolId);

            // Idempotent return if this failed batch has already been retried cleanly.
            $existingRetry = IntegrationSyncLog::query()
                ->where('school_id', $schoolId)
                ->where('retried_from_log_id', (int) $log->id)
                ->where('status', 'completed')
                ->first();

            if ($existingRetry !== null) {
                return $existingRetry;
            }

            $mapping = $this->resolveMapping($schoolId, (string) $log->system_key);
            $sourceRows = $correctedRows ?? [];
            if ($sourceRows === [] && is_array($log->payload_snapshot_json)) {
                $sourceRows = (array) ($log->payload_snapshot_json['valid_records'] ?? []);
            }

            $evaluation = $this->evaluateRows((string) $log->system_key, $sourceRows, $mapping);
            $status = match (true) {
                $evaluation['total_rows'] === 0 => 'completed',
                $evaluation['failed_rows'] === 0 => 'completed',
                $evaluation['valid_rows'] > 0 => 'partial_failed',
                default => 'failed',
            };

            $year = (int) $this->schoolScope->localNow($schoolId)->format('Y');
            $batchCode = $this->ticketService->generateTicketCode(strtoupper((string) $log->system_key), $year);

            $retryLog = IntegrationSyncLog::query()->create([
                'school_id' => $schoolId,
                'system_key' => (string) $log->system_key,
                'direction' => (string) $log->direction,
                'batch_code' => $batchCode,
                'status' => $status,
                'total_rows' => max(1, $evaluation['total_rows']),
                'success_rows' => $evaluation['total_rows'] === 0 ? (int) $log->success_rows : $evaluation['valid_rows'],
                'failed_rows' => $evaluation['failed_rows'],
                'failed_details_json' => $evaluation['failed_details'],
                'payload_snapshot_json' => [
                    'valid_records' => $evaluation['mapped_valid'],
                    'failed_records' => $evaluation['failed_details'],
                ],
                'idempotency_key' => 'retry-'.$log->id.'-'.md5(json_encode($sourceRows) ?: ''),
                'triggered_by_user_id' => (int) $actor->id,
                'retried_from_log_id' => (int) $log->id,
            ]);

            $this->auditLogger->log(
                eventType: 'integration.sync.retried',
                targetType: 'IntegrationSyncLog',
                targetId: (string) $retryLog->id,
                newValues: [
                    'retried_from_log_id' => (int) $log->id,
                    'status' => $status,
                    'failed_rows' => $evaluation['failed_rows'],
                ],
                schoolId: $schoolId,
                actor: $actor,
            );

            return $retryLog;
        });
    }

    /**
     * Generate structured CSV export snapshot for EMIS, RDM, or SLiMS and log the batch.
     *
     * @return array{filename:string, csv:string, log:IntegrationSyncLog}
     */
    public function exportDataset(User $actor, string $systemKey): array
    {
        $this->assertCanManage($actor);
        if (! array_key_exists($systemKey, self::ACADEMIC_SYSTEMS)) {
            throw new InvalidArgumentException('Sistem ekspor tidak dikenal.');
        }

        $schoolId = (int) $actor->school_id;
        $mapping = $this->resolveMapping($schoolId, $systemKey);
        $rows = [];

        if ($systemKey === 'emis' || $systemKey === 'rdm') {
            $students = $this->academicQuery->getStudents($schoolId, null, 200);
            foreach ($students as $student) {
                $rows[] = $systemKey === 'emis'
                    ? [
                        $mapping['nisn'] ?? 'nisn' => (string) ($student->nisn ?? $student->nis ?? ''),
                        $mapping['full_name'] ?? 'full_name' => (string) $student->name,
                        $mapping['class_name'] ?? 'class_name' => (string) ($student->current_class_name ?? 'Aktif'),
                        $mapping['status'] ?? 'status' => (string) ($student->status ?? 'active'),
                    ]
                    : [
                        $mapping['nisn'] ?? 'nisn' => (string) ($student->nisn ?? $student->nis ?? ''),
                        $mapping['full_name'] ?? 'full_name' => (string) $student->name,
                        $mapping['subject_code'] ?? 'subject_code' => 'UMUM',
                        $mapping['final_score'] ?? 'final_score' => '85',
                    ];
            }
        } else {
            $catalog = $this->libraryService->searchCatalog($schoolId, [], $actor);
            foreach ($catalog['collections'] ?? [] as $book) {
                $rows[] = [
                    $mapping['biblio_code'] ?? 'biblio_code' => (string) ($book['code'] ?? ''),
                    $mapping['title'] ?? 'title' => (string) ($book['title'] ?? ''),
                    $mapping['author'] ?? 'author' => (string) ($book['author'] ?? ''),
                    $mapping['isbn'] ?? 'isbn' => (string) ($book['isbn'] ?? ''),
                ];
            }
        }

        $headers = array_values($mapping);
        $stream = fopen('php://temp', 'r+');
        if ($stream === false) {
            throw new InvalidArgumentException('Gagal membuat buffer CSV ekspor.');
        }

        fputcsv($stream, $headers);
        foreach ($rows as $row) {
            $ordered = [];
            foreach ($headers as $header) {
                $ordered[] = (string) ($row[$header] ?? '');
            }
            fputcsv($stream, $ordered);
        }
        rewind($stream);
        $csv = (string) stream_get_contents($stream);
        fclose($stream);

        $year = (int) $this->schoolScope->localNow($schoolId)->format('Y');
        $log = IntegrationSyncLog::query()->create([
            'school_id' => $schoolId,
            'system_key' => $systemKey,
            'direction' => 'export',
            'batch_code' => $this->ticketService->generateTicketCode('EXP', $year),
            'status' => 'completed',
            'total_rows' => count($rows),
            'success_rows' => count($rows),
            'failed_rows' => 0,
            'triggered_by_user_id' => (int) $actor->id,
        ]);

        return [
            'filename' => sprintf('%s-export-%s.csv', $systemKey, now()->format('Ymd-His')),
            'csv' => $csv,
            'log' => $log,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function serializeSyncLog(IntegrationSyncLog $log): array
    {
        return [
            'id' => (int) $log->id,
            'system_key' => (string) $log->system_key,
            'system_label' => self::ACADEMIC_SYSTEMS[$log->system_key]['label'] ?? strtoupper((string) $log->system_key),
            'direction' => (string) $log->direction,
            'batch_code' => (string) $log->batch_code,
            'status' => (string) $log->status,
            'total_rows' => (int) $log->total_rows,
            'success_rows' => (int) $log->success_rows,
            'failed_rows' => (int) $log->failed_rows,
            'failed_details' => is_array($log->failed_details_json) ? $log->failed_details_json : [],
            'retried_from_log_id' => $log->retried_from_log_id ? (int) $log->retried_from_log_id : null,
            'triggered_by_name' => $log->triggeredBy?->name,
            'created_at' => $log->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function resolveMapping(int $schoolId, string $systemKey): array
    {
        $conn = IntegrationConnection::query()
            ->where('school_id', $schoolId)
            ->where('system_key', $systemKey)
            ->first();

        $defaults = self::ACADEMIC_SYSTEMS[$systemKey]['default_mapping'];
        $configured = is_array($conn?->config_json['field_mapping'] ?? null)
            ? $conn->config_json['field_mapping']
            : [];

        return array_merge($defaults, $configured);
    }

    /**
     * @param  array<int, array<string, mixed>>|UploadedFile|string  $source
     * @return list<array<string, mixed>>
     */
    private function parseRows(array|UploadedFile|string $source): array
    {
        if (is_array($source)) {
            return array_values($source);
        }

        $rawText = $source instanceof UploadedFile
            ? (string) file_get_contents($source->getRealPath() ?: '')
            : $source;

        $trimmed = trim($rawText);
        if ($trimmed === '') {
            return [];
        }

        if (str_starts_with($trimmed, '[') || str_starts_with($trimmed, '{')) {
            $decoded = json_decode($trimmed, true);
            if (is_array($decoded)) {
                return isset($decoded['rows']) && is_array($decoded['rows'])
                    ? array_values($decoded['rows'])
                    : (array_is_list($decoded) ? $decoded : [$decoded]);
            }
        }

        $lines = preg_split('/\r\n|\r|\n/', $trimmed) ?: [];
        $lines = array_values(array_filter($lines, fn (string $line): bool => trim($line) !== ''));
        if (count($lines) < 2) {
            return [];
        }

        $delimiter = str_contains($lines[0], ';') ? ';' : ',';
        $headers = array_map(fn ($h) => trim((string) $h), str_getcsv($lines[0], $delimiter));
        $rows = [];

        foreach (array_slice($lines, 1) as $line) {
            $cells = str_getcsv($line, $delimiter);
            $assoc = [];
            foreach ($headers as $idx => $header) {
                if ($header === '') {
                    continue;
                }
                $assoc[$header] = isset($cells[$idx]) ? trim((string) $cells[$idx]) : '';
            }
            $rows[] = $assoc;
        }

        return $rows;
    }

    /**
     * @param  list<array<string, mixed>>  $rawRows
     * @param  array<string, string>  $mapping
     * @return array{
     *     system_key:string,
     *     total_rows:int,
     *     valid_rows:int,
     *     failed_rows:int,
     *     mapped_valid:list<array<string, mixed>>,
     *     failed_details:list<array{row_number:int, source_identifier:string, error_message:string, recovery_action:string}>
     * }
     */
    private function evaluateRows(string $systemKey, array $rawRows, array $mapping): array
    {
        $valid = [];
        $failed = [];

        foreach ($rawRows as $index => $row) {
            $rowNumber = $index + 1;
            $mapped = [];
            foreach ($mapping as $canonicalKey => $sourceColumn) {
                $mapped[$canonicalKey] = trim((string) ($row[$sourceColumn] ?? $row[$canonicalKey] ?? ''));
            }

            if ($systemKey === 'emis') {
                $nisn = $mapped['nisn'] ?? '';
                $fullName = $mapped['full_name'] ?? '';
                $className = $mapped['class_name'] ?? '';
                $sourceId = $nisn !== '' ? $nisn : ($fullName !== '' ? $fullName : 'Baris #'.$rowNumber);

                if (! preg_match('/^\d{8,12}$/', $nisn)) {
                    $failed[] = [
                        'row_number' => $rowNumber,
                        'source_identifier' => $sourceId,
                        'error_message' => 'Format NISN tidak valid (wajib 8–12 digit angka).',
                        'recovery_action' => 'Perbaiki kolom NISN pada berkas EMIS lalu unggah ulang atau koreksi baris.',
                    ];

                    continue;
                }
                if ($fullName === '' || $className === '') {
                    $failed[] = [
                        'row_number' => $rowNumber,
                        'source_identifier' => $sourceId,
                        'error_message' => 'Nama lengkap siswa atau rombel tidak boleh kosong.',
                        'recovery_action' => 'Lengkapi kolom full_name & class_name sesuai pemetaan kolom.',
                    ];

                    continue;
                }
                $valid[] = $mapped;
            } elseif ($systemKey === 'rdm') {
                $nisn = $mapped['nisn'] ?? '';
                $subject = $mapped['subject_code'] ?? '';
                $scoreRaw = $mapped['final_score'] ?? '';
                $sourceId = ($nisn !== '' ? $nisn : 'Baris #'.$rowNumber).($subject !== '' ? ' / '.$subject : '');

                if ($nisn === '' || $subject === '') {
                    $failed[] = [
                        'row_number' => $rowNumber,
                        'source_identifier' => $sourceId,
                        'error_message' => 'NISN atau kode mata pelajaran RDM wajib diisi.',
                        'recovery_action' => 'Pastikan pemetaan kolom nisn dan subject_code sesuai template RDM.',
                    ];

                    continue;
                }
                if (! is_numeric($scoreRaw) || (float) $scoreRaw < 0 || (float) $scoreRaw > 100) {
                    $failed[] = [
                        'row_number' => $rowNumber,
                        'source_identifier' => $sourceId,
                        'error_message' => 'Nilai akhir RDM harus berupa angka 0–100.',
                        'recovery_action' => 'Koreksi nilai menjadi rentang 0–100 dan jalankan ulang batch.',
                    ];

                    continue;
                }
                $valid[] = $mapped;
            } else {
                $biblioCode = $mapped['biblio_code'] ?? '';
                $title = $mapped['title'] ?? '';
                $sourceId = $biblioCode !== '' ? $biblioCode : ($title !== '' ? $title : 'Baris #'.$rowNumber);

                if ($biblioCode === '' || $title === '') {
                    $failed[] = [
                        'row_number' => $rowNumber,
                        'source_identifier' => $sourceId,
                        'error_message' => 'Kode bibliografi atau judul koleksi SLiMS wajib diisi.',
                        'recovery_action' => 'Periksa kolom biblio_code & title pada berkas ekspor SLiMS.',
                    ];

                    continue;
                }
                $valid[] = $mapped;
            }
        }

        return [
            'system_key' => $systemKey,
            'total_rows' => count($rawRows),
            'valid_rows' => count($valid),
            'failed_rows' => count($failed),
            'mapped_valid' => $valid,
            'failed_details' => $failed,
        ];
    }
}
