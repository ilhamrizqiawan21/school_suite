<?php

declare(strict_types=1);

namespace App\Shared\Exports;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

final class SpreadsheetStyler
{
    public static function styleWorksheet(
        Worksheet $sheet,
        string $freezeCell = 'A2',
        string $orientation = PageSetup::ORIENTATION_LANDSCAPE,
        int $headerRow = 1
    ): void {
        $parent = $sheet->getParent();
        if ($parent !== null) {
            $parent->getDefaultStyle()->getFont()->setName('Calibri')->setSize(10);
        }

        $pageSetup = $sheet->getPageSetup();
        $pageSetup->setPaperSize(PageSetup::PAPERSIZE_A4);
        $pageSetup->setOrientation($orientation);
        $pageSetup->setFitToWidth(1);
        $pageSetup->setFitToHeight(0);
        if ($headerRow >= 1) {
            $pageSetup->setRowsToRepeatAtTopByStartAndEnd(1, $headerRow);
        }

        $margins = $sheet->getPageMargins();
        $margins->setTop(0.5);
        $margins->setBottom(0.5);
        $margins->setLeft(0.45);
        $margins->setRight(0.45);

        $highestColumn = $sheet->getHighestDataColumn();
        $highestRow = max($headerRow, $sheet->getHighestDataRow());
        $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);

        $fullRange = "A1:{$highestColumn}{$highestRow}";
        $headerRange = "A{$headerRow}:{$highestColumn}{$headerRow}";

        $sheet->getStyle($fullRange)->applyFromArray([
            'font' => [
                'name' => 'Calibri',
                'size' => 10,
                'color' => ['rgb' => '1E293B'],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CBD5E1'],
                ],
            ],
        ]);

        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => [
                'name' => 'Calibri',
                'size' => 10,
                'bold' => true,
                'color' => ['rgb' => '0F172A'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E2E8F0'],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '64748B'],
                ],
            ],
        ]);

        $sheet->getRowDimension($headerRow)->setRowHeight(24);
        for ($row = $headerRow + 1; $row <= $highestRow; $row++) {
            $sheet->getRowDimension($row)->setRowHeight(19);
        }

        for ($col = 1; $col <= $highestColumnIndex; $col++) {
            $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
        }

        if ($freezeCell !== '') {
            $sheet->freezePane($freezeCell);
        }
    }
}
