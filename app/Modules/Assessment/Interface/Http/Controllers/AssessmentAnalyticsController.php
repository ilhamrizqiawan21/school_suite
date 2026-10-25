<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Interface\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Assessment\Application\Services\AssessmentAnalyticsService;
use App\Shared\Audit\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AssessmentAnalyticsController extends Controller
{
    private const ROLES = ['admin', 'operator', 'principal', 'teacher', 'homeroom'];

    public function __construct(
        private readonly AssessmentAnalyticsService $analytics,
        private readonly AuditLogger $audit,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorizeAccess($request);

        return Inertia::render('Assessment/Analytics/Index', [
            ...$this->analytics->report($request->user(), $this->filters($request)),
            'can_manage_question_bank' => $request->user()->hasRole(['teacher', 'homeroom']),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorizeAccess($request);
        $report = $this->analytics->report($request->user(), $this->filters($request));
        $this->audit->log('assessment.analytics.exported', 'assessment_analytics', isset($report['exam_results']['exam']->id) ? (string) $report['exam_results']['exam']->id : null, [
            'filters' => $report['filters'], 'bank_question_count' => $report['bank']['total'],
            'results_available' => $report['exam_results']['available'],
        ], actor: $request->user(), schoolId: (int) $request->user()->school_id);

        $book = new Spreadsheet;
        $bankSheet = $book->getActiveSheet();
        $bankSheet->setTitle('Bank Soal');
        $bankRows = [['Metrik', 'Kelompok', 'Jumlah'], ['Total soal aktif', 'Semua', $report['bank']['total']]];
        foreach ([
            'Tipe soal' => $report['bank']['by_type'], 'Mata pelajaran' => $report['bank']['by_subject'],
            'Kategori' => $report['bank']['by_category'], 'Kurikulum' => $report['bank']['by_curriculum'],
            'Level kognitif' => $report['bank']['by_cognitive_level'],
        ] as $dimension => $rows) {
            foreach ($rows as $row) {
                $bankRows[] = [$dimension, $row['label'], $row['count']];
            }
        }
        $this->writeTextRows($bankSheet, $bankRows);

        $examSheet = $book->createSheet();
        $examSheet->setTitle('Hasil Ujian');
        $examRows = [['Metrik', 'Kelompok', 'Jumlah'], ['Hasil tersedia', $report['exam_results']['available'] ? 'Ya' : 'Belum diterbitkan', '']];
        if ($report['exam_results']['exam']) {
            $examRows[] = ['Ujian', $report['exam_results']['exam']->title, ''];
            $examRows[] = ['Total attempt', 'Semua status', $report['exam_results']['total_attempts']];
            if ($report['exam_results']['available']) {
                $examRows[] = ['Attempt dinilai', 'Graded/submitted', $report['exam_results']['graded_attempts']];
                $examRows[] = ['Rata-rata nilai', 'Skala 0–100', $report['exam_results']['average_score'] ?? ''];
                foreach ($report['exam_results']['score_distribution'] as $band) {
                    $examRows[] = ['Distribusi nilai '.$band['label'], 'Jumlah / persentase', $band['count'].' / '.($band['percentage'] ?? '—').'%'];
                }
            }
        }
        $this->writeTextRows($examSheet, $examRows);

        $itemsSheet = $book->createSheet();
        $itemsSheet->setTitle('Analisis Butir');
        $itemRows = [['ID soal', 'Tipe', 'Butir soal', 'Attempt dinilai', 'Terjawab', 'Benar', 'Benar (%)', 'Rata-rata poin', 'Rata-rata (%)']];
        if ($report['exam_results']['available']) {
            foreach ($report['exam_results']['items'] as $item) {
                $itemRows[] = [
                    $item['question_id'], $item['type'], $item['prompt'], $item['attempts'], $item['answered'],
                    $item['correct_count'] ?? 'N/A', $item['correct_rate'] ?? 'N/A',
                    $item['average_points'] ?? 'N/A', $item['average_percent'] ?? 'N/A',
                ];
            }
        }
        $this->writeTextRows($itemsSheet, $itemRows);

        return response()->streamDownload(function () use ($book): void {
            (new Xlsx($book))->save('php://output');
            $book->disconnectWorksheets();
        }, 'analisis-bank-soal-dan-ujian.xlsx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    /** @return array<string, mixed> */
    private function filters(Request $request): array
    {
        $schoolId = (int) $request->user()->school_id;

        return $request->validate([
            'subject_id' => ['nullable', 'integer', Rule::exists('subjects', 'id')->where('school_id', $schoolId)],
            'category_id' => ['nullable', 'integer', Rule::exists('question_categories', 'id')->where('school_id', $schoolId)],
            'type' => ['nullable', Rule::in(['multiple_choice', 'essay', 'true_false', 'matching'])],
            'jenjang' => ['nullable', Rule::in(['SD', 'SMP', 'SMA'])],
            'curriculum' => ['nullable', Rule::in(['merdeka', 'kbc', 'both'])],
            'cognitive_level' => ['nullable', Rule::in(['L1', 'L2', 'L3'])],
            'exam_id' => ['nullable', 'integer', 'min:1'],
        ]);
    }

    private function authorizeAccess(Request $request): void
    {
        abort_unless($request->user()->hasRole(self::ROLES), 403);
        abort_unless($request->user()->is_active && $request->user()->school_id, 403);
    }

    /** @param list<array<int, mixed>> $rows */
    private function writeTextRows(Worksheet $sheet, array $rows): void
    {
        foreach ($rows as $rowIndex => $row) {
            foreach (array_values($row) as $columnIndex => $value) {
                $sheet->setCellValueExplicit([$columnIndex + 1, $rowIndex + 1], (string) ($value ?? ''), DataType::TYPE_STRING);
            }
        }
        foreach ($sheet->getColumnIterator() as $column) {
            $sheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
        }
    }
}
