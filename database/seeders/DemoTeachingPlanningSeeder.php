<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Academic\Infrastructure\Models\Subject;
use App\Modules\Academic\Infrastructure\Models\Teacher;
use App\Modules\Academic\Infrastructure\Models\TeachingAssignment;
use App\Modules\Academic\Infrastructure\Models\TeachingDocument;
use App\Modules\Academic\Infrastructure\Models\TeachingPlan;
use App\Modules\Academic\Infrastructure\Models\Term;
use Illuminate\Database\Seeder;

class DemoTeachingPlanningSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->where('email', 'dewi@pilot.sch.id')->first();
        if (! $user) {
            $this->command?->warn('Akun demo Dewi tidak ditemukan; data perangkat pembelajaran dilewati.');

            return;
        }

        $teacher = Teacher::query()->where('school_id', $user->school_id)->where('user_id', $user->id)->first();
        $term = Term::query()->whereHas('academicYear', fn ($query) => $query->where('school_id', $user->school_id))
            ->where('is_active', true)->first();
        $subject = Subject::query()->where('school_id', $user->school_id)->where('code', 'IPA-01')->first();

        if (! $teacher || ! $term || ! $subject) {
            $this->command?->warn('Guru IPA atau periode aktif belum tersedia; data perangkat pembelajaran dilewati.');

            return;
        }

        $examples = [
            7 => ['curriculum' => 'merdeka', 'theme' => 'Interaksi makhluk hidup dengan lingkungannya', 'first' => 'Hakikat Sains dan Pengukuran', 'second' => 'Zat dan Perubahannya'],
            8 => ['curriculum' => 'kbc', 'theme' => 'Menjaga keseimbangan alam sebagai wujud cinta lingkungan', 'first' => 'Struktur dan Fungsi Tubuh', 'second' => 'Usaha, Energi, dan Pesawat Sederhana'],
            9 => ['curriculum' => 'k13', 'theme' => 'Teknologi dan keberlanjutan kehidupan', 'first' => 'Sistem Reproduksi Manusia', 'second' => 'Listrik dan Kemagnetan'],
        ];

        foreach ($examples as $grade => $example) {
            $assigned = TeachingAssignment::query()->where('term_id', $term->id)
                ->where('teacher_id', $teacher->id)->where('subject_id', $subject->id)
                ->whereHas('classGroup', fn ($query) => $query->where('grade_level', $grade))
                ->exists();
            if (! $assigned) {
                continue;
            }

            $chapters = [
                ['id' => "demo-{$grade}-1", 'title' => $example['first'], 'semester' => '1'],
                ['id' => "demo-{$grade}-2", 'title' => $example['second'], 'semester' => '2'],
            ];
            $units = [
                ['id' => "demo-{$grade}-u1", 'chapter_id' => $chapters[0]['id'], 'code' => "{$grade}.1.1", 'objective' => 'Menjelaskan konsep utama melalui pengamatan dan bukti.', 'material' => "Pengantar {$example['first']}", 'jp' => 6, 'kkm' => 75],
                ['id' => "demo-{$grade}-u2", 'chapter_id' => $chapters[0]['id'], 'code' => "{$grade}.1.2", 'objective' => 'Merancang dan melakukan penyelidikan sederhana secara aman.', 'material' => 'Keterampilan proses dan praktikum', 'jp' => 8, 'kkm' => 75],
                ['id' => "demo-{$grade}-u3", 'chapter_id' => $chapters[1]['id'], 'code' => "{$grade}.2.1", 'objective' => 'Menghubungkan konsep dengan fenomena di lingkungan sekitar.', 'material' => "Konsep inti {$example['second']}", 'jp' => 8, 'kkm' => 75],
                ['id' => "demo-{$grade}-u4", 'chapter_id' => $chapters[1]['id'], 'code' => "{$grade}.2.2", 'objective' => 'Menyajikan hasil analisis dan solusi berbasis data.', 'material' => 'Proyek dan komunikasi ilmiah', 'jp' => 10, 'kkm' => 75],
            ];

            $references = $example['curriculum'] === 'k13'
                ? [
                    ['key' => 'KI-1', 'name' => 'Sikap Spiritual (KI-1)', 'content' => 'Menghargai dan menghayati ajaran agama yang dianutnya.'],
                    ['key' => 'KI-2', 'name' => 'Sikap Sosial (KI-2)', 'content' => 'Menunjukkan perilaku jujur, disiplin, tanggung jawab, peduli, santun, dan percaya diri.'],
                    ['key' => 'KI-3', 'name' => 'Pengetahuan (KI-3)', 'content' => 'Memahami pengetahuan faktual, konseptual, dan prosedural berdasarkan rasa ingin tahunya tentang ilmu pengetahuan.'],
                    ['key' => 'KI-4', 'name' => 'Keterampilan (KI-4)', 'content' => 'Mengolah, menyaji, dan menalar dalam ranah konkret maupun abstrak secara mandiri.'],
                ]
                : [
                    ['key' => "CP-{$grade}-1", 'name' => 'CP Fase D · Pemahaman IPA', 'phase' => 'D', 'element' => 'Pemahaman IPA', 'content' => "Peserta didik memahami konsep {$example['theme']} melalui pengamatan, pemodelan, dan penalaran ilmiah; menggunakan konsep untuk menjelaskan fenomena serta mengambil keputusan yang bertanggung jawab."],
                    ['key' => "CP-{$grade}-2", 'name' => 'CP Fase D · Keterampilan Proses', 'phase' => 'D', 'element' => 'Keterampilan Proses', 'content' => 'Peserta didik mampu mengamati, mempertanyakan, memprediksi, merencanakan penyelidikan, mengolah dan menafsirkan data, mengevaluasi, serta mengomunikasikan hasil dengan memperhatikan keselamatan kerja.'],
                ];

            $data = [
                'references' => $references,
                'chapters' => $chapters,
                'units' => $units,
                'waktu' => ['jp_per_minggu' => 4, 'jpPerPertemuan' => 2, 'minggu_efektif' => ['1' => 18, '2' => 18]],
            ];

            foreach ($chapters as $chapterIndex => $chapter) {
                $chapterMeetingCount = (int) ceil(collect($units)
                    ->where('chapter_id', $chapter['id'])
                    ->sum('jp') / 2);
                $data['modul'][$chapter['id']] = [
                    'bagian' => [
                        'pengetahuanAwal' => 'Peserta didik telah mengenal contoh fenomena IPA di rumah dan sekolah.',
                        'minat' => 'Eksperimen sederhana, diskusi, dan pemecahan masalah kontekstual.',
                        'kebutuhanBelajar' => 'Panduan visual, pilihan cara menunjukkan pemahaman, dan pendampingan saat praktikum.',
                        'topikPancaCinta' => $example['theme'],
                        'materiInsersi' => 'Menumbuhkan rasa syukur, tanggung jawab, dan kepedulian terhadap kehidupan.',
                        'pengetahuanKonseptual' => 'Konsep dan hubungan sebab-akibat pada materi bab.',
                        'pengetahuanProsedural' => 'Langkah penyelidikan, penggunaan alat, dan pencatatan data.',
                        'relevansiKehidupan' => 'Mengaitkan konsep dengan kebiasaan dan lingkungan sekitar peserta didik.',
                        'tingkatKesulitan' => 'Bertahap dari pengamatan konkret menuju penjelasan dan penerapan konsep.',
                        'strukturMateri' => 'Konsep inti, penyelidikan, analisis bukti, dan penerapan.',
                        'integrasiNilai' => 'Jujur pada data, bekerja sama, dan menjaga keselamatan serta lingkungan.',
                        'profilPenalaran' => 'Menilai bukti dan menyusun kesimpulan yang logis.',
                        'profilKolaborasi' => 'Berbagi peran dan menyepakati hasil kerja kelompok.',
                        'lintasDisiplin' => 'Matematika untuk pengukuran dan Bahasa Indonesia untuk menyampaikan hasil.',
                        'indikator' => 'Menjelaskan konsep dengan bukti, melakukan penyelidikan, dan menyampaikan kesimpulan.',
                        'iklimBudaya' => 'Kelas aman untuk bertanya, mencoba, dan belajar dari hasil yang belum sesuai.',
                        'topikKontekstual' => $example['theme'],
                        'modelPembelajaran' => 'Inkuiri terbimbing dan project-based learning.',
                        'pendekatan' => 'Pembelajaran kontekstual, diferensiatif, dan berpusat pada peserta didik.',
                        'metodePembelajaran' => 'Eksperimen, diskusi kelompok, presentasi, dan refleksi.',
                        'diferensiasiKonten' => 'Teks ringkas, diagram, demonstrasi, dan video pembelajaran.',
                        'diferensiasiProses' => 'Kelompok fleksibel, pertanyaan bertingkat, dan lembar panduan praktikum.',
                        'diferensiasiProduk' => 'Laporan, poster ilmiah, presentasi, atau model sederhana.',
                        'lingkunganFisik' => 'Kelas dan laboratorium IPA dengan aturan keselamatan yang jelas.',
                        'budayaBelajar' => 'Rasa ingin tahu, gotong royong, dan komunikasi berbasis bukti.',
                        'pemanfaatanDigital' => 'Simulasi dan lembar kerja digital untuk mencatat serta membandingkan data.',
                        'asesmenDiagnostik' => 'Pertanyaan pemantik dan peta konsep sebelum pembelajaran.',
                        'asesmenFormatif' => 'Observasi proses, cek pemahaman, dan umpan balik pada lembar kerja.',
                        'asesmenSumatif' => 'Tes pemahaman konsep dan tugas proyek/laporan penyelidikan.',
                    ],
                    'langkah' => array_map(fn (int $meeting) => [
                        'topik' => $chapter['title'],
                        'pembahasan' => $units[$chapterIndex * 2 + min($meeting - 1, 1)]['material'],
                        'pendahuluan' => 'Salam, tujuan belajar, pertanyaan pemantik, dan pengaitan dengan pengalaman peserta didik.',
                        'inti' => 'Peserta didik mengamati fenomena, bekerja dalam kelompok, mengumpulkan bukti, lalu membahas hasil dengan bimbingan guru.',
                        'penutup' => 'Menyimpulkan konsep, mengisi refleksi singkat, dan menerima tindak lanjut belajar.',
                    ], range(1, max(1, $chapterMeetingCount))),
                ];
            }

            $plan = TeachingPlan::query()->firstOrCreate(
                ['teacher_id' => $teacher->id, 'term_id' => $term->id, 'subject_id' => $subject->id, 'grade_level' => $grade],
                ['school_id' => $user->school_id, 'curriculum' => $example['curriculum'], 'data' => $data]
            );

            $this->seedDocuments($plan, $user->school_id, $teacher->id, $term->id, $chapters, $units);
        }
    }

    private function seedDocuments(TeachingPlan $plan, int $schoolId, int $teacherId, int $termId, array $chapters, array $units): void
    {
        $intervals = [
            ['label' => 'Perlu bimbingan intensif', 'range' => '0–40%', 'description' => 'Remedial pada seluruh tujuan pembelajaran.'],
            ['label' => 'Mulai berkembang', 'range' => '41–65%', 'description' => 'Remedial pada tujuan yang belum dikuasai.'],
            ['label' => 'Cakap', 'range' => '66–85%', 'description' => 'Tujuan pembelajaran tercapai; penguatan sesuai kebutuhan.'],
            ['label' => 'Mahir', 'range' => '86–100%', 'description' => 'Tujuan tercapai; lanjutkan dengan kegiatan pengayaan.'],
        ];
        $allocation = ['effective_weeks' => ['ganjil' => 18, 'genap' => 18], 'jp_per_week' => 4, 'monthly_period' => ['ganjil' => 7, 'genap' => 1]];
        $items = [
            ['type' => 'alokasi_waktu', 'semester' => null, 'title' => 'Analisis Alokasi Waktu IPA Kelas '.$plan->grade_level, 'content' => $allocation],
            ['type' => 'prota', 'semester' => null, 'title' => 'Program Tahunan IPA Kelas '.$plan->grade_level, 'content' => ['chapters' => $chapters, 'units' => $units]],
        ];

        foreach (['ganjil' => '1', 'genap' => '2'] as $semester => $chapterSemester) {
            $semesterChapters = array_values(array_filter($chapters, fn (array $chapter) => (string) $chapter['semester'] === $chapterSemester));
            $chapterIds = array_column($semesterChapters, 'id');
            $semesterUnits = array_values(array_filter($units, fn (array $unit) => in_array($unit['chapter_id'], $chapterIds, true)));
            $items[] = ['type' => 'promes', 'semester' => $semester, 'title' => 'Program Semester '.ucfirst($semester).' IPA Kelas '.$plan->grade_level, 'content' => ['semester' => $semester, 'months' => [], 'chapters' => $semesterChapters, 'units' => $semesterUnits, 'distribution' => []]];
            $items[] = ['type' => 'kktp', 'semester' => $semester, 'title' => 'KKTP IPA Kelas '.$plan->grade_level.' · '.ucfirst($semester), 'content' => ['scale' => 'interval', 'intervals' => $intervals, 'units' => array_map(fn (array $unit) => ['id' => $unit['id'], 'title' => $unit['objective'], 'objective' => $unit['objective'], 'kkm' => 75], $semesterUnits)]];
        }

        foreach ($items as $item) {
            TeachingDocument::query()->firstOrCreate(
                ['school_id' => $schoolId, 'teacher_id' => $teacherId, 'term_id' => $termId, 'plan_id' => $plan->id, 'type' => $item['type'], 'semester' => $item['semester']],
                ['title' => $item['title'], 'content' => $item['content']]
            );
        }
    }
}
