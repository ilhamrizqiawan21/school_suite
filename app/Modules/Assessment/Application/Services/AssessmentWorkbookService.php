<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Application\Services;

use App\Shared\Exports\SpreadsheetStyler;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use RuntimeException;
use ZipArchive;

class AssessmentWorkbookService
{
    /** @return list<array<int, mixed>> */
    public function read(UploadedFile $file, string $preferredSheet = 'Jawaban'): array
    {
        if ($file->getSize() > 5 * 1024 * 1024) {
            throw new RuntimeException('Berkas maksimal 5 MB.');
        }
        $path = $file->getRealPath();
        if (strtolower($file->getClientOriginalExtension()) === 'csv') {
            return $this->csv($path);
        }
        if (strtolower($file->getClientOriginalExtension()) !== 'xlsx') {
            throw new RuntimeException('Gunakan XLSX atau CSV. Format XLS tidak didukung.');
        }
        $this->inspectArchive($path);
        $reader = new Xlsx;
        $reader->setReadDataOnly(false);
        $reader->setReadEmptyCells(false);
        $info = $reader->listWorksheetInfo($path);
        if (count($info) > 5) {
            throw new RuntimeException('Berkas maksimal memuat 5 sheet.');
        }
        $target = collect($info)->firstWhere('worksheetName', $preferredSheet) ?? ($info[0] ?? null);
        if (! $target || $target['totalRows'] > 510 || $target['totalColumns'] > 201) {
            throw new RuntimeException('Sheet tidak tersedia atau melampaui 500 siswa / 200 soal.');
        }
        $reader->setLoadSheetsOnly([$target['worksheetName']]);
        $book = $reader->load($path);
        try {
            $sheet = $book->getSheet(0);
            $rows = [];
            for ($row = 1; $row <= $target['totalRows']; $row++) {
                $values = [];
                for ($col = 1; $col <= $target['totalColumns']; $col++) {
                    $cell = $sheet->getCell([$col, $row]);
                    if ($cell->getDataType() === DataType::TYPE_FORMULA) {
                        throw new RuntimeException("Rumus tidak diizinkan (baris {$row}, kolom {$col}).");
                    }
                    $value = $cell->getValue();
                    if (is_object($value) && method_exists($value, 'getPlainText')) {
                        $value = $value->getPlainText();
                    }
                    if (mb_strlen((string) $value) > 10000) {
                        throw new RuntimeException('Isi sel terlalu panjang.');
                    }
                    $values[] = $value;
                }
                $rows[] = $values;
            }

            return $rows;
        } finally {
            $book->disconnectWorksheets();
        }
    }

    public function workbook(array $sheets): Spreadsheet
    {
        $book = new Spreadsheet;
        foreach ($sheets as $name => $rows) {
            $sheet = $book->getSheetCount() === 1 && $book->getActiveSheet()->getTitle() === 'Worksheet'
                ? $book->getActiveSheet() : $book->createSheet();
            $sheet->setTitle($name);
            foreach ($rows as $r => $row) {
                foreach (array_values($row) as $c => $value) {
                    $sheet->setCellValueExplicit([$c + 1, $r + 1], (string) ($value ?? ''), DataType::TYPE_STRING);
                }
            }
            SpreadsheetStyler::styleWorksheet(
                $sheet,
                $name === 'Jawaban' ? 'B7' : 'A2',
                headerRow: $name === 'Jawaban' ? 6 : 1
            );
            $sheet->getColumnDimension('A')->setAutoSize(false);
            $sheet->getColumnDimension('A')->setWidth(24);
        }
        $book->setActiveSheetIndex(0);

        return $book;
    }

    private function csv(string $path): array
    {
        $text = file_get_contents($path);
        if (! mb_check_encoding($text, 'UTF-8')) {
            $text = mb_convert_encoding($text, 'UTF-8', 'Windows-1252');
        }
        $text = preg_replace('/^\xEF\xBB\xBF/', '', $text);
        $line = strtok($text, "\r\n") ?: '';
        $delimiter = collect([';', ',', "\t"])->sortByDesc(fn ($char) => count(str_getcsv($line, $char, '"', '')))->first();
        $stream = fopen('php://temp', 'w+');
        fwrite($stream, $text);
        rewind($stream);
        $rows = [];
        try {
            while (($row = fgetcsv($stream, null, $delimiter, '"', '')) !== false) {
                if (count($rows) >= 510 || count($row) > 201) {
                    throw new RuntimeException('CSV melampaui batas jumlah baris atau kolom.');
                }
                $rows[] = $row;
            }
        } finally {
            fclose($stream);
        }

        return $rows;
    }

    private function inspectArchive(string $path): void
    {
        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            throw new RuntimeException('Berkas XLSX tidak valid.');
        }
        try {
            if ($zip->numFiles > 100) {
                throw new RuntimeException('Terlalu banyak bagian pada XLSX.');
            }
            $total = 0;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                if (preg_match('~vbaProject|externalLinks|embeddings|(^|/)\.\.(/|$)~i', $name)) {
                    throw new RuntimeException('Makro, objek tertanam, atau tautan eksternal tidak diizinkan.');
                }
                $stream = $zip->getStream($name);
                if (! $stream) {
                    continue;
                }
                $relationship = '';
                try {
                    while (! feof($stream)) {
                        $chunk = fread($stream, 8192);
                        if ($chunk === false) {
                            throw new RuntimeException('Bagian XLSX tidak dapat dibaca.');
                        }
                        $total += strlen($chunk);
                        if (str_ends_with($name, '.rels')) {
                            $relationship .= $chunk;
                        }
                        if ($total > 30 * 1024 * 1024) {
                            throw new RuntimeException('Isi XLSX melewati batas 30 MB.');
                        }
                    }
                } finally {
                    fclose($stream);
                }
                if (preg_match('/TargetMode\s*=\s*["\x27]External["\x27]/i', $relationship)) {
                    throw new RuntimeException('Tautan eksternal tidak diizinkan.');
                }
            }
        } finally {
            $zip->close();
        }
    }
}
