<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Application\Services;

use App\Shared\Exports\SpreadsheetStyler;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use RuntimeException;

class QuestionSpreadsheetService
{
    public const HEADERS = [
        'type', 'jenjang', 'curriculum', 'subject_code', 'prompt', 'indicator_text', 'cognitive_level', 'kko_text',
        'options_json', 'matching_pairs_json', 'essay_rubric', 'correct_answer', 'explanation', 'points', 'category_names', 'tag_names', 'source', 'class_label',
    ];

    public const MAX_IMPORT_ROWS = 500;

    public const MAX_EXPORT_ROWS = 2000;

    public function template(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;
        $data = $spreadsheet->getActiveSheet();
        $data->setTitle('Data');
        $this->writeRow($data, 1, self::HEADERS);
        SpreadsheetStyler::styleWorksheet($data, 'A2');
        $data->getColumnDimension('E')->setAutoSize(false);
        $data->getColumnDimension('E')->setWidth(48);

        $examples = $spreadsheet->createSheet();
        $examples->setTitle('Contoh');
        $this->writeRow($examples, 1, self::HEADERS);
        $samples = [
            ['multiple_choice', 'SMP', 'merdeka', 'MAT-01', 'Berapakah 2 + 2?', 'Operasi bilangan', 'L2', 'menghitung', '["3","4","5"]', '', '', '4', 'Pilih hasil yang tepat.', 1, 'Bilangan', 'dasar'],
            ['true_false', 'SMP', 'merdeka', 'IPA-01', 'Air mendidih pada 100°C pada tekanan normal.', 'Memahami perubahan wujud', 'L1', 'menjelaskan', '', '', '', 'true', '', 1, 'Zat', 'suhu'],
            ['essay', 'SMP', 'merdeka', 'IND-01', 'Jelaskan gagasan utama paragraf tersebut.', 'Menganalisis bacaan', 'L3', 'menganalisis', '', '', 'Ketepatan isi dan alasan', '', '', 5, 'Membaca', 'esai'],
            ['matching', 'SMP', 'merdeka', 'IPS-01', 'Pasangkan istilah dengan maknanya.', 'Memahami istilah', 'L1', 'mengidentifikasi', '', '[{"left":"Produsen","right":"Menghasilkan barang"},{"left":"Konsumen","right":"Menggunakan barang"}]', '', '', '', 2, 'Ekonomi', 'pasangan'],
        ];
        foreach ($samples as $index => $sample) {
            $this->writeRow($examples, $index + 2, $sample);
        }
        SpreadsheetStyler::styleWorksheet($examples, 'A2');

        return $spreadsheet;
    }

    /** @return list<array{row:int,values:array<string,mixed>}> */
    public function read(UploadedFile $file): array
    {
        $raw = app(AssessmentWorkbookService::class)->read($file, 'Data');
        $headers = array_map(fn ($value) => strtolower(trim((string) $value)), array_shift($raw) ?? []);
        $native = in_array('teks soal', $headers, true);
        if (! $native && array_slice($headers, 0, 16) !== array_slice(self::HEADERS, 0, 16)) {
            throw new RuntimeException('Header tidak sesuai. Gunakan template School Suite atau bank soal Jurnal Guru.');
        }
        if (count($raw) > self::MAX_IMPORT_ROWS) {
            throw new RuntimeException('Maksimal 500 baris soal per impor.');
        }
        $rows = [];
        foreach ($raw as $index => $cells) {
            if (collect($cells)->every(fn ($cell) => trim((string) $cell) === '')) {
                continue;
            }
            $values = [];
            foreach ($headers as $i => $header) {
                $values[$header] = is_string($cells[$i] ?? null) ? trim($cells[$i]) : ($cells[$i] ?? null);
            }
            if ($native) {
                $options = collect(['a', 'b', 'c', 'd', 'e'])->mapWithKeys(fn ($letter) => [strtoupper($letter) => $values['opsi '.$letter] ?? ''])->filter(fn ($text) => $text !== '');
                $values = [
                    'type' => 'multiple_choice', 'prompt' => $values['teks soal'] ?? '',
                    'subject_name' => $values['mapel'] ?? '', 'class_label' => $values['kelas'] ?? '',
                    'indicator_text' => $values['tujuan'] ?? '', 'cognitive_level' => $values['level kognitif'] ?? '',
                    'options_json' => json_encode($options->values()->all()),
                    'correct_answer' => $options->get(strtoupper((string) ($values['jawaban pg'] ?? '')), ''),
                    'explanation' => $values['pembahasan'] ?? '', 'tag_names' => $values['tag'] ?? '',
                    'source' => $values['sumber'] ?? '', 'points' => 1,
                ];
            }
            $rows[] = ['row' => $index + 2, 'values' => $values];
        }
        if ($rows === []) {
            throw new RuntimeException('Tidak ada baris soal dalam berkas.');
        }

        return $rows;
    }

    /** @param iterable<array<int, scalar|null>> $rows */
    public function writeRows(Spreadsheet $spreadsheet, iterable $rows): void
    {
        $sheet = $spreadsheet->getActiveSheet();
        foreach ($rows as $row) {
            $this->writeRow($sheet, $sheet->getHighestRow() + 1, $row);
        }
        SpreadsheetStyler::styleWorksheet($sheet, 'A2');
        $sheet->getColumnDimension('E')->setAutoSize(false);
        $sheet->getColumnDimension('E')->setWidth(48);
    }

    private function writeRow(Worksheet $sheet, int $row, array $values): void
    {
        foreach (array_values($values) as $index => $value) {
            $sheet->setCellValueExplicit([$index + 1, $row], (string) ($value ?? ''), DataType::TYPE_STRING);
        }
    }
}
