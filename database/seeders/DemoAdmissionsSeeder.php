<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Academic\Infrastructure\Models\Student;
use App\Modules\Admissions\Infrastructure\Models\AdmissionApplicant;
use App\Modules\Admissions\Infrastructure\Models\AdmissionApplicantDocument;
use App\Modules\Admissions\Infrastructure\Models\AdmissionApplicantScore;
use App\Modules\Admissions\Infrastructure\Models\AdmissionDocumentRequirement;
use App\Modules\Admissions\Infrastructure\Models\AdmissionPeriod;
use App\Modules\Admissions\Infrastructure\Models\AdmissionReRegistration;
use App\Modules\Admissions\Infrastructure\Models\AdmissionSelectionCriterion;
use App\Modules\Admissions\Infrastructure\Models\AdmissionSelectionResult;
use App\Modules\Admissions\Infrastructure\Models\AdmissionTrack;
use App\Modules\Academic\Infrastructure\Models\Teacher;
use App\Modules\Core\Infrastructure\Models\School;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DemoAdmissionsSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->command?->warn('Data contoh SPMB hanya dapat dibuat di lingkungan local atau testing.');

            return;
        }

        $school = School::query()->first();
        $admin = $school
            ? User::query()->where('school_id', $school->id)->whereHas('role', fn ($query) => $query->where('name', 'admin'))->first()
            : null;

        if (! $school || ! $admin) {
            $this->command?->warn('Sekolah dan akun admin perlu tersedia sebelum data contoh SPMB dibuat.');

            return;
        }

        $now = now();
        $today = today();
        $period = AdmissionPeriod::query()->updateOrCreate(
            ['school_id' => $school->id, 'name' => '[DEMO] SPMB '.$now->year.'/'.($now->year + 1)],
            [
                'academic_year' => $now->year.'/'.($now->year + 1),
                'level' => $school->level,
                'status' => 'verification',
                'registration_start' => $today->copy()->subDays(18)->toDateString(),
                'registration_end' => $today->copy()->subDays(2)->toDateString(),
                'verification_start' => $today->copy()->subDay()->toDateString(),
                'verification_end' => $today->copy()->addDays(8)->toDateString(),
                'selection_start' => $today->copy()->addDays(9)->toDateString(),
                'selection_end' => $today->copy()->addDays(14)->toDateString(),
                'announcement_at' => $today->copy()->addDays(17)->toDateString(),
                're_registration_start' => $today->copy()->addDays(18)->toDateString(),
                're_registration_end' => $today->copy()->addDays(32)->toDateString(),
            ]
        );

        $trackDefinitions = [
            ['code' => 'DOMISILI', 'name' => 'Jalur Domisili', 'quota' => 20],
            ['code' => 'AFIRMASI', 'name' => 'Jalur Afirmasi', 'quota' => 8],
            ['code' => 'PRESTASI', 'name' => 'Jalur Prestasi', 'quota' => 10],
        ];
        $tracks = [];
        foreach ($trackDefinitions as $definition) {
            $track = AdmissionTrack::query()->updateOrCreate(
                ['period_id' => $period->id, 'code' => $definition['code']],
                ['name' => $definition['name'], 'quota' => $definition['quota'], 'is_active' => true]
            );
            $tracks[$definition['code']] = $track;

            foreach ([
                ['code' => 'KK', 'label' => 'Kartu Keluarga', 'sort_order' => 1],
                ['code' => 'IJAZAH', 'label' => 'Ijazah atau surat keterangan lulus', 'sort_order' => 2],
                ['code' => 'RAPOR', 'label' => 'Rapor semester akhir', 'sort_order' => 3],
            ] as $requirement) {
                AdmissionDocumentRequirement::query()->updateOrCreate(
                    ['track_id' => $track->id, 'code' => $requirement['code']],
                    ['label' => $requirement['label'], 'is_required' => true, 'sort_order' => $requirement['sort_order']]
                );
            }

            foreach ([
                ['code' => 'RAPOR', 'label' => 'Rata-rata nilai rapor', 'weight' => 70, 'max_score' => 100, 'sort_order' => 1],
                ['code' => 'PRESTASI', 'label' => 'Prestasi dan portofolio', 'weight' => 30, 'max_score' => 100, 'sort_order' => 2],
            ] as $criterion) {
                AdmissionSelectionCriterion::query()->updateOrCreate(
                    ['track_id' => $track->id, 'code' => $criterion['code']],
                    ['label' => $criterion['label'], 'weight' => $criterion['weight'], 'max_score' => $criterion['max_score'], 'sort_order' => $criterion['sort_order']]
                );
            }
        }

        $teacher = Teacher::query()->where('school_id', $school->id)->where('is_active', true)->with('user')->first();
        if ($teacher?->user) {
            DB::table('admission_committee_members')->updateOrInsert(
                ['school_id' => $school->id, 'user_id' => $teacher->user_id],
                ['assigned_by' => $admin->id, 'created_at' => $now, 'updated_at' => $now]
            );
        }

        $applicantDefinitions = [
            ['name' => 'Aisyah Rahma', 'track' => 'DOMISILI', 'status' => 'registered'],
            ['name' => 'Bagas Pratama', 'track' => 'DOMISILI', 'status' => 'needs_revision'],
            ['name' => 'Citra Maharani', 'track' => 'DOMISILI', 'status' => 'registered'],
            ['name' => 'Daffa Ramadhan', 'track' => 'DOMISILI', 'status' => 'documents_verified'],
            ['name' => 'Eka Putri', 'track' => 'DOMISILI', 'status' => 'accepted'],
            ['name' => 'Fajar Hidayat', 'track' => 'DOMISILI', 'status' => 're_registered'],
            ['name' => 'Gita Lestari', 'track' => 'AFIRMASI', 'status' => 'registered'],
            ['name' => 'Hafiz Maulana', 'track' => 'AFIRMASI', 'status' => 'needs_revision'],
            ['name' => 'Intan Permata', 'track' => 'AFIRMASI', 'status' => 'selection_review'],
            ['name' => 'Jihan Safitri', 'track' => 'PRESTASI', 'status' => 'accepted'],
            ['name' => 'Kevin Saputra', 'track' => 'PRESTASI', 'status' => 'waitlisted'],
            ['name' => 'Laras Wulandari', 'track' => 'PRESTASI', 'status' => 'documents_verified'],
            ['name' => 'Miko Firmansyah', 'track' => 'DOMISILI', 'status' => 'rejected'],
        ];

        foreach ($applicantDefinitions as $index => $definition) {
            $number = $index + 1;
            $track = $tracks[$definition['track']];
            $applicant = AdmissionApplicant::query()->updateOrCreate(
                ['school_id' => $school->id, 'registration_no' => sprintf('DEMO-SPMB-%d-%03d', $now->year, $number)],
                [
                    'period_id' => $period->id,
                    'track_id' => $track->id,
                    'name' => '[DEMO] '.$definition['name'],
                    'nisn' => null,
                    'gender' => $number % 2 === 0 ? 'L' : 'P',
                    'birth_date' => null,
                    'parent_name' => '[DEMO] Orang Tua '.$definition['name'],
                    'parent_phone' => '0000000000',
                    'previous_school' => '[DEMO] SMP Contoh Nusantara',
                    'status' => $definition['status'],
                    'notes' => 'Data contoh lokal untuk pratinjau alur SPMB.',
                    'created_by' => $admin->id,
                ]
            );

            $requirements = $track->requirements()->get();
            foreach ($requirements as $requirementIndex => $requirement) {
                $documentStatus = match ($definition['status']) {
                    'registered' => 'pending',
                    'needs_revision' => $requirementIndex === 0 ? 'needs_revision' : 'valid',
                    default => 'valid',
                };
                $path = sprintf('demo/admissions/%s-%s.pdf', $applicant->registration_no, $requirement->code);
                $pdf = $this->samplePdf($requirement->label);
                Storage::disk('local')->put($path, $pdf);

                AdmissionApplicantDocument::query()->updateOrCreate(
                    ['applicant_id' => $applicant->id, 'requirement_id' => $requirement->id],
                    [
                        'path' => $path,
                        'original_name' => '[DEMO] '.$requirement->label.'.pdf',
                        'mime_type' => 'application/pdf',
                        'size' => strlen($pdf),
                        'status' => $documentStatus,
                        'notes' => $documentStatus === 'needs_revision' ? 'Contoh catatan: unggah ulang dokumen yang lebih jelas.' : null,
                        'verified_by' => $documentStatus === 'valid' ? $admin->id : null,
                        'verified_at' => $documentStatus === 'valid' ? $now : null,
                    ]
                );
            }

            foreach ($track->criteria()->get() as $criterionIndex => $criterion) {
                $score = max(55, 96 - ($number * 2) - ($criterionIndex * 4));
                AdmissionApplicantScore::query()->updateOrCreate(
                    ['applicant_id' => $applicant->id, 'criterion_id' => $criterion->id],
                    ['score' => $score, 'notes' => '[DEMO] Nilai contoh.', 'scored_by' => $teacher?->user_id ?? $admin->id]
                );
            }

            $decision = match ($definition['status']) {
                'accepted', 're_registered' => 'accepted',
                'waitlisted' => 'waitlisted',
                'rejected' => 'rejected',
                default => null,
            };
            AdmissionSelectionResult::query()->updateOrCreate(
                ['applicant_id' => $applicant->id],
                [
                    'total_score' => max(58, 96 - ($number * 2)),
                    'rank' => $number,
                    'decision' => $decision,
                    'override_reason' => null,
                    'finalized_by' => $decision ? $admin->id : null,
                    'finalized_at' => $decision ? $now : null,
                ]
            );

            if ($definition['status'] === 'accepted') {
                AdmissionReRegistration::query()->updateOrCreate(
                    ['applicant_id' => $applicant->id],
                    ['status' => 'pending', 'student_id' => null, 'confirmed_by' => null, 'confirmed_at' => null, 'notes' => '[DEMO] Menunggu konfirmasi daftar ulang.']
                );
            } elseif ($definition['status'] === 're_registered') {
                $student = Student::query()->updateOrCreate(
                    ['school_id' => $school->id, 'nis' => sprintf('DEMO-%d-%03d', $now->year, $number)],
                    [
                        'nisn' => null,
                        'name' => $applicant->name,
                        'gender' => $applicant->gender,
                        'birth_place' => 'Nusantara',
                        'birth_date' => null,
                        'religion' => null,
                        'address' => '[DEMO] Alamat calon siswa.',
                        'parent_name' => $applicant->parent_name,
                        'parent_phone' => $applicant->parent_phone,
                        'status' => 'active',
                    ]
                );
                AdmissionReRegistration::query()->updateOrCreate(
                    ['applicant_id' => $applicant->id],
                    ['status' => 'confirmed', 'student_id' => $student->id, 'confirmed_by' => $admin->id, 'confirmed_at' => $now, 'notes' => '[DEMO] Daftar ulang selesai.']
                );
            }
        }

        $this->command?->info('Data contoh SPMB berhasil disiapkan untuk '.$school->name.'.');
    }

    private function samplePdf(string $documentName): string
    {
        $label = preg_replace('/[^A-Za-z0-9 -]/', '', $documentName) ?: 'Dokumen SPMB';
        $stream = "BT /F1 16 Tf 72 760 Td (DATA CONTOH SPMB) Tj 0 -30 Td /F1 11 Tf (".$label.") Tj 0 -22 Td (Dokumen sintetis untuk pratinjau lokal.) Tj ET";
        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
            '<< /Length '.strlen($stream)." >>\nstream\n".$stream."\nendstream",
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $index => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($index + 1)." 0 obj\n".$object."\nendobj\n";
        }
        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n0000000000 65535 f \n";
        foreach (array_slice($offsets, 1) as $offset) {
            $pdf .= sprintf('%010d 00000 n ', $offset)."\n";
        }
        $pdf .= "trailer\n<< /Size ".(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n".$xrefOffset."\n%%EOF";

        return $pdf;
    }
}
