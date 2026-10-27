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
        $actor = $school ? User::query()->where('school_id', $school->id)->where('email', 'admin@pilot.sch.id')->first() : null;
        if (! $actor) {
            return;
        }

        $service = app(CorrespondenceService::class);
        foreach ([
            ['number' => 'DEMO/001/TU', 'subject' => '[DEMO] Undangan rapat koordinasi', 'issue' => false],
            ['number' => 'DEMO/002/TU', 'subject' => '[DEMO] Pemberitahuan kegiatan sekolah', 'issue' => true],
        ] as $example) {
            if (CorrespondenceRecord::query()->where('school_id', $school->id)->where('letter_type', 'outgoing')->where('letter_number', $example['number'])->exists()) {
                continue;
            }

            $record = $service->create($actor, [
                'letter_type' => 'outgoing',
                'letter_number' => $example['number'],
                'letter_date' => today()->toDateString(),
                'recipient' => 'Warga sekolah',
                'subject' => $example['subject'],
                'summary' => 'Data contoh untuk mencoba proses persuratan.',
                'letter_body' => "Dengan hormat,\n\nSurat ini merupakan contoh untuk mencoba penyusunan dan pencatatan surat sekolah. Silakan menyesuaikan isi, tujuan, serta nomor surat sesuai kebutuhan sekolah.\n\nDemikian informasi ini disampaikan. Atas perhatian dan kerja samanya, kami mengucapkan terima kasih.",
                'signer_name' => $school->principal_name ?: 'Kepala Sekolah Demo',
                'signer_title' => 'Kepala Sekolah',
                'attachment_note' => null,
            ]);

            if ($example['issue']) {
                $service->updateStatus($actor, $record, 'issued', null);
            }
        }
    }
}
