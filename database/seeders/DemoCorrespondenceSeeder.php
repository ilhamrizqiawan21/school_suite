<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Administration\Application\Services\CorrespondenceService;
use App\Modules\Administration\Infrastructure\Models\CorrespondenceRecord;
use App\Modules\Core\Infrastructure\Models\School;
use Illuminate\Database\Seeder;

class DemoCorrespondenceSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $school = School::query()->where('npsn', '20109988')->first();
        $actor = $school
            ? User::query()->where('school_id', $school->id)->where('email', 'admin@pilot.sch.id')->first()
            : null;
        if (! $actor) {
            return;
        }

        $principalId = User::query()
            ->where('school_id', $school->id)
            ->whereHas('role', fn ($query) => $query->where('name', 'principal'))
            ->value('id');
        $service = app(CorrespondenceService::class);

        $examples = [
            ['type' => 'incoming', 'number' => 'DEMO-IN/001', 'subject' => 'Undangan rapat komite sekolah', 'sender' => 'Komite Sekolah', 'status' => 'received', 'days' => 1],
            ['type' => 'incoming', 'number' => 'DEMO-IN/002', 'subject' => 'Permohonan data kegiatan siswa', 'sender' => 'Dinas Pendidikan Kota', 'status' => 'disposition', 'days' => 3, 'disposition' => 'Mohon ditindaklanjuti oleh Wakil Kepala Sekolah bidang kesiswaan.'],
            ['type' => 'incoming', 'number' => 'DEMO-IN/003', 'subject' => 'Pemberitahuan jadwal supervisi', 'sender' => 'Pengawas Pembina', 'status' => 'done', 'days' => 6, 'disposition' => 'Jadwal sudah diteruskan kepada tim akademik.'],
            ['type' => 'incoming', 'number' => 'DEMO-IN/004', 'subject' => 'Surat edaran kalender pendidikan', 'sender' => 'Dinas Pendidikan Provinsi', 'status' => 'archived', 'days' => 12, 'disposition' => 'Sudah dibaca dan disimpan pada arsip semester.'],
            ['type' => 'outgoing', 'number' => 'DEMO-OUT/001', 'subject' => 'Undangan rapat wali kelas', 'recipient' => 'Seluruh wali kelas', 'status' => 'draft', 'days' => 0, 'approval_required' => false],
            ['type' => 'outgoing', 'number' => 'DEMO-OUT/002', 'subject' => 'Permohonan narasumber kegiatan sekolah', 'recipient' => 'SMP Negeri 2 Percontohan', 'status' => 'pending_approval', 'days' => 2, 'approval_required' => true],
            ['type' => 'outgoing', 'number' => 'DEMO-OUT/003', 'subject' => 'Pemberitahuan program literasi', 'recipient' => 'Orang tua dan wali siswa', 'status' => 'approved', 'days' => 4, 'approval_required' => true, 'approved_by' => true],
            ['type' => 'outgoing', 'number' => 'DEMO-OUT/004', 'subject' => 'Surat tugas panitia kegiatan', 'recipient' => 'Panitia kegiatan sekolah', 'status' => 'awaiting_signature', 'days' => 7, 'approval_required' => false],
            ['type' => 'outgoing', 'number' => 'DEMO-OUT/005', 'subject' => 'Konfirmasi kerja sama sekolah', 'recipient' => 'Mitra sekolah', 'status' => 'issued', 'days' => 10, 'approval_required' => false],
            ['type' => 'outgoing', 'number' => 'DEMO-OUT/006', 'subject' => 'Laporan pelaksanaan kegiatan semester', 'recipient' => 'Dinas Pendidikan Kota', 'status' => 'archived', 'days' => 18, 'approval_required' => false],
        ];

        foreach ($examples as $example) {
            if (CorrespondenceRecord::query()
                ->where('school_id', $school->id)
                ->where('letter_number', $example['number'])
                ->exists()) {
                continue;
            }

            $date = today()->subDays($example['days'])->toDateString();
            $isIncoming = $example['type'] === 'incoming';
            $record = $service->create($actor, [
                'letter_type' => $example['type'],
                'letter_template' => 'official_school',
                'letter_number' => $example['number'],
                'letter_date' => $date,
                'received_at' => $isIncoming ? $date : null,
                'sender' => $isIncoming ? $example['sender'] : null,
                'recipient' => $isIncoming ? null : $example['recipient'],
                'subject' => '[DEMO] '.$example['subject'],
                'summary' => '[DEMO] Data contoh untuk memeriksa alur persuratan.',
                'disposition' => $example['disposition'] ?? null,
                'letter_body' => $isIncoming ? null : "Dengan hormat,\n\nSurat ini adalah data dummy untuk memeriksa alur surat keluar sekolah.\n\nDemikian disampaikan. Terima kasih.",
                'signer_name' => $isIncoming ? null : ($school->principal_name ?: 'Kepala Sekolah Demo'),
                'signer_title' => $isIncoming ? null : 'Kepala Sekolah',
                'approval_required' => $example['approval_required'] ?? false,
                'signature_method' => 'manual',
            ]);

            $updates = ['status' => $example['status']];
            if ($example['status'] === 'archived') {
                $updates['archived_at'] = now()->subDays(max(1, $example['days'] - 1));
            }
            if (($example['approved_by'] ?? false) && $principalId) {
                $updates['approval_status'] = 'approved';
                $updates['approved_by'] = $principalId;
                $updates['approved_at'] = now()->subDays(1);
                $updates['approval_note'] = '[DEMO] Disetujui untuk pemeriksaan alur.';
            }
            if ($example['status'] === 'pending_approval') {
                $updates['approval_status'] = 'pending';
            }

            $record->forceFill($updates)->save();
        }
    }
}
