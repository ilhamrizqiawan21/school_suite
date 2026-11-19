<?php

declare(strict_types=1);

namespace App\Modules\Core\Domain;

/**
 * Data privacy classification, retention, and redaction policy per service (M4-FND-08).
 */
final class DataClassificationMap
{
    public const LEVEL_PUBLIC = 'public';

    public const LEVEL_PRIVATE = 'private';

    public const LEVEL_RESTRICTED = 'restricted';

    /**
     * Canonical classification matrix across parity services.
     *
     * @return array<string, array{
     *     public_fields: list<string>,
     *     private_fields: list<string>,
     *     restricted_fields: list<string>,
     *     retention_policy: string,
     *     owner_role: string
     * }>
     */
    public static function matrix(): array
    {
        return [
            'ptsp' => [
                'public_fields' => ['ticket_code', 'service_type', 'public_status', 'public_summary', 'updated_at'],
                'private_fields' => ['applicant_name', 'applicant_phone', 'applicant_email', 'notes', 'attachments', 'issued_documents'],
                'restricted_fields' => ['verification_secret_hash', 'nik', 'audit_ip'],
                'retention_policy' => 'Arsip aktif selama tahun ajaran berjalan + retensi arsip 5 tahun (menunggu pengesahan K-02/K-08).',
                'owner_role' => RoleEnum::PTSP->value,
            ],
            'surat_eksternal' => [
                'public_fields' => ['ticket_code', 'public_status', 'public_summary', 'agenda_number'],
                'private_fields' => ['agency_name', 'contact_person', 'letter_subject', 'primary_pdf', 'dispositions'],
                'restricted_fields' => ['verification_secret_hash', 'contact_phone'],
                'retention_policy' => 'Arsip persuratan permanen / mengikuti jadwal retensi arsip tata usaha (K-02).',
                'owner_role' => RoleEnum::OPERATOR->value,
            ],
            'izin_dan_piket' => [
                'public_fields' => ['ticket_code', 'public_status', 'valid_until'],
                'private_fields' => ['student_name', 'guardian_name', 'reason', 'evidence_attachment', 'exit_return_logs'],
                'restricted_fields' => ['dispensation_token_hash', 'guardian_phone'],
                'retention_policy' => 'Disimpan sepanjang masa studi siswa + 2 tahun untuk audit presensi (K-02).',
                'owner_role' => RoleEnum::HOMEROOM->value,
            ],
            'bk_disiplin' => [
                'public_fields' => [],
                'private_fields' => [],
                'restricted_fields' => ['student_id', 'incident_chronology', 'evidence_files', 'counseling_notes', 'follow_up_action'],
                'retention_policy' => 'Sangat terbatas hanya untuk Guru BK & peran berwenang; tidak pernah muncul di ekspor umum atau ringkasan publik.',
                'owner_role' => RoleEnum::COUNSELOR->value,
            ],
            'aduan_satgas' => [
                'public_fields' => ['moderated_aggregate_counts', 'moderated_public_summary'],
                'private_fields' => ['ticket_code', 'public_status', 'task_force_reply'],
                'restricted_fields' => ['reporter_identity', 'reporter_contact', 'chronology', 'evidence_files'],
                'retention_policy' => 'Identitas pelapor anonim tidak disimpan atau dipisahkan dari ringkasan publik; eviden hanya diakses Satgas.',
                'owner_role' => RoleEnum::TASK_FORCE->value,
            ],
            'alumni_dan_legalisir' => [
                'public_fields' => ['verified_document_status', 'alumni_opted_in_directory_fields'],
                'private_fields' => ['legalization_request', 'power_of_attorney', 'sptjm_pdf', 'graduation_certificate_number'],
                'restricted_fields' => ['nik', 'personal_phone', 'home_address', 'private_tracer_fields'],
                'retention_policy' => 'Hanya menampilkan atribut yang disetujui alumni (`opt-in`) setelah moderasi admin.',
                'owner_role' => RoleEnum::OPERATOR->value,
            ],
        ];
    }

    /**
     * Sensitive payload keys that must always be redacted from logs, notifications, and public responses.
     *
     * @return list<string>
     */
    public static function sensitiveKeys(): array
    {
        return [
            'password',
            'password_confirmation',
            'token',
            'remember_token',
            'secret',
            'verification_secret',
            'secret_hash',
            'api_key',
            'access_token',
            'refresh_token',
            'pin',
            'credit_card',
            'phone',
            'parent_phone',
            'guardian_phone',
            'whatsapp',
            'wa_number',
            'nik',
            'nis',
            'nisn',
            'address',
            'birth_date',
            'mother_name',
            'father_name',
            'kronologi',
            'chronology',
            'evidence',
            'complaint_body',
            'authorization',
        ];
    }
}
