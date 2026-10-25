<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Application\Services;

use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use RuntimeException;
use ZipArchive;

class QuestionSpreadsheetService
{
    public const HEADERS = [
        'type', 'jenjang', 'curriculum', 'subject_code', 'prompt', 'indicator_text', 'cognitive_level', 'kko_text',
        'options_json', 'matching_pairs_json', 'essay_rubric', 'correct_answer', 'explanation', 'points', 'category_names', 'tag_names',
    ];

    public const MAX_IMPORT_ROWS = 500;

    public const MAX_EXPORT_ROWS = 2000;

    public function template(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;
        $data = $spreadsheet->getActiveSheet();
        $data->setTitle('Data');
        $this->writeRow($data, 1, self::HEADERS);
        $data->freezePane('A2');
        $data->getStyle('A1:P1')->getFont()->setBold(true);
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
        $examples->getStyle('A1:P1')->getFont()->setBold(true);

        return $spreadsheet;
    }

    /** @return list<array{row:int,values:array<string,mixed>}> */
    public function read(UploadedFile $file): array
    {
        $this->assertArchiveLimits($file->getRealPath());

        $reader = new Xlsx;
        $reader->setReadDataOnly(true);
        $reader->setReadEmptyCells(false);
        $reader->setLoadSheetsOnly(['Data']);
        $info = collect($reader->listWorksheetInfo($file->getRealPath()))->firstWhere('worksheetName', 'Data');
        if (! $info || (int) $info['totalRows'] > self::MAX_IMPORT_ROWS + 1 || (int) $info['totalColumns'] > count(self::HEADERS)) {
            throw new RuntimeException('Sheet Data tidak ditemukan atau melewati batas 500 baris dan 16 kolom.');
        }

        $reader->setReadFilter(new class implements IReadFilter
        {
            public function readCell(string $columnAddress, int $row, string $worksheetName = ''): bool
            {
                return $worksheetName === 'Data' && $row <= QuestionSpreadsheetService::MAX_IMPORT_ROWS + 1
                    && in_array($columnAddress, range('A', 'P'), true);
            }
        });

        $book = $reader->load($file->getRealPath());
        $sheet = $book->getSheetByName('Data');
        if (! $sheet) {
            throw new RuntimeException('Sheet Data tidak ditemukan.');
        }

        $headers = $sheet->rangeToArray('A1:P1', null, false, false)[0] ?? [];
        if (array_map(fn ($value) => strtolower(trim((string) $value)), $headers) !== self::HEADERS) {
            throw new RuntimeException('Header sheet Data tidak sesuai template. Unduh ulang template bank soal.');
        }

        $rows = [];
        for ($row = 2; $row <= (int) $info['totalRows']; $row++) {
            $values = [];
            $nonEmpty = false;
            foreach (range('A', 'P') as $index => $column) {
                $cell = $sheet->getCell($column.$row);
                if ($cell->getDataType() === DataType::TYPE_FORMULA) {
                    throw new RuntimeException("Rumus tidak diizinkan (baris {$row}, kolom {$column}). Gunakan nilai teks biasa.");
                }
                $value = $cell->getValue();
                $values[self::HEADERS[$index]] = is_string($value) ? trim($value) : $value;
                $nonEmpty = $nonEmpty || ($value !== null && trim((string) $value) !== '');
            }
            if ($nonEmpty) {
                $rows[] = ['row' => $row, 'values' => $values];
            }
        }
        $book->disconnectWorksheets();

        if ($rows === []) {
            throw new RuntimeException('Tidak ada baris soal pada sheet Data.');
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
    }

    private function writeRow(Worksheet $sheet, int $row, array $values): void
    {
        foreach (array_values($values) as $index => $value) {
            $sheet->setCellValueExplicit([$index + 1, $row], (string) ($value ?? ''), DataType::TYPE_STRING);
        }
    }

    private function assertArchiveLimits(string|false $path): void
    {
        if (! $path) {
            throw new RuntimeException('Berkas unggahan tidak dapat dibaca.');
        }
        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            throw new RuntimeException('Berkas bukan XLSX yang valid.');
        }
        $total = 0;
        $valid = $zip->numFiles <= 30;
        for ($i = 0; $valid && $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            $total += (int) ($stat['size'] ?? 0);
            $valid = $total <= 30 * 1024 * 1024;
        }
        $zip->close();
        if (! $valid) {
            throw new RuntimeException('Isi XLSX terkompresi melewati batas keamanan.');
        }
    }
}
