<?php

namespace App\Exports\Sheets;

use App\Models\Setting;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EmployeeDataSheet implements FromArray, WithHeadings, WithStyles, WithTitle
{
    /** Max data rows with validation (A2:A501) */
    public const MAX_ROWS = 500;

    /** Column letters that use dropdown validation (light-blue header) */
    public const DROPDOWN_COLUMNS = ['C', 'D', 'E', 'G', 'I', 'J', 'M'];

    public function title(): string
    {
        return 'Template';
    }

    public function headings(): array
    {
        return [
            'NIK',                  // A
            'Nama',                 // B
            'Jabatan',              // C  ← dropdown
            'Departemen',           // D  ← dropdown
            'Shift',                // E  ← dropdown
            'Tanggal Bergabung',    // F  ← date
            'Status',               // G  ← dropdown
            'NPWP',                 // H
            'PTKP',                 // I  ← dropdown
            'Kategori TER',         // J  ← dropdown
            'No. BPJS TK',         // K
            'No. BPJS Kesehatan',  // L
            'Nama Bank',            // M  ← dropdown
            'Nomor Rekening',       // N
            'Nama Akun',            // O
        ];
    }

    public function array(): array
    {
        // One example row to guide the user
        return [
            [
                '1234567890123456',
                'Budi Santoso',
                '[STF] - Staff',
                '[PROD] - Produksi',
                'Shift Pagi',
                '2024-01-15',
                'active',
                '12.345.678.9-012.345',
                'TK/0',
                'A',
                '0001234567890',
                '0009876543210',
                'BCA',
                '1234567890',
                'Budi Santoso',
            ],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $lastRow = self::MAX_ROWS + 1; // row 501

        // ── Header styling ──────────────────────────────────────
        // Default header: indigo
        $sheet->getStyle('A1:O1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4F46E5'],
            ],
        ]);

        // Dropdown columns: light blue header to signal "has choices"
        foreach (self::DROPDOWN_COLUMNS as $col) {
            $sheet->getStyle("{$col}1")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '0EA5E9'], // sky-500
                ],
            ]);
        }

        // Date column header: amber
        $sheet->getStyle('F1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'F59E0B'],
            ],
        ]);

        // Example row: italic + light gray
        $sheet->getStyle('A2:O2')->applyFromArray([
            'font' => ['italic' => true, 'color' => ['rgb' => '6B7280']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'F3F4F6'],
            ],
        ]);

        // ── TEXT format for NIK, Rekening, BPJS columns ─────────
        // Prevents leading zeros from being stripped
        $textColumns = ['A', 'H', 'K', 'L', 'N'];
        foreach ($textColumns as $col) {
            $sheet->getStyle("{$col}2:{$col}{$lastRow}")
                ->getNumberFormat()
                ->setFormatCode(NumberFormat::FORMAT_TEXT);
        }

        // ── DATE format for Tanggal Bergabung (col F) ───────────
        $sheet->getStyle("F2:F{$lastRow}")
            ->getNumberFormat()
            ->setFormatCode('YYYY-MM-DD');

        // ── Data Validations (dropdowns from Lists sheet) ───────
        $this->applyListValidation($sheet, 'C', 'Lists!$A$2', 'positions', 'Pilih jabatan dari daftar');
        $this->applyListValidation($sheet, 'D', 'Lists!$B$2', 'departments', 'Pilih departemen dari daftar');
        $this->applyListValidation($sheet, 'E', 'Lists!$C$2', 'shifts', 'Pilih shift dari daftar');
        $this->applyListValidation($sheet, 'G', 'Lists!$E$2', 'statuses', 'Pilih status: active/inactive');
        $this->applyListValidation($sheet, 'I', 'Lists!$F$2', 'ptkp', 'Pilih status PTKP');
        $this->applyListValidation($sheet, 'J', 'Lists!$G$2', 'ter', 'Pilih kategori TER: A/B/C');
        $this->applyListValidation($sheet, 'M', 'Lists!$D$2', 'banks', 'Pilih nama bank dari daftar');

        // ── Date validation for Tanggal Bergabung ───────────────
        $this->applyDateValidation($sheet);

        // ── Auto-size columns ───────────────────────────────────
        foreach (range('A', 'O') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return [];
    }

    /**
     * Apply list-type data validation (dropdown) to a column range.
     * Uses a formula reference to the hidden Lists sheet.
     */
    private function applyListValidation(Worksheet $sheet, string $col, string $listStart, string $tag, string $prompt): void
    {
        $lastRow = self::MAX_ROWS + 1;

        // Build formula: reference a column range on Lists sheet
        // We dynamically compute the last row in applyValidationsFromCounts()
        // For now, use a placeholder that EmployeeTemplateExport will finalize
        for ($row = 2; $row <= $lastRow; $row++) {
            $cell = $sheet->getCell("{$col}{$row}");
            $validation = $cell->getDataValidation();
            $validation->setType(DataValidation::TYPE_LIST);
            $validation->setErrorStyle(DataValidation::STYLE_STOP);
            $validation->setAllowBlank(true);
            $validation->setShowDropDown(true);
            $validation->setShowInputMessage(true);
            $validation->setShowErrorMessage(true);
            $validation->setPromptTitle('Pilih dari daftar');
            $validation->setPrompt($prompt);
            $validation->setErrorTitle('Nilai tidak valid');
            $validation->setError('Pilih nilai dari dropdown yang tersedia.');
            // Formula will be set by EmployeeTemplateExport after sheet counts are known
            $validation->setFormula1("__{$tag}__");
        }
    }

    /**
     * Apply date validation on the join_date column (F).
     */
    private function applyDateValidation(Worksheet $sheet): void
    {
        $lastRow = self::MAX_ROWS + 1;

        for ($row = 2; $row <= $lastRow; $row++) {
            $cell = $sheet->getCell("F{$row}");
            $validation = $cell->getDataValidation();
            $validation->setType(DataValidation::TYPE_DATE);
            $validation->setErrorStyle(DataValidation::STYLE_STOP);
            $validation->setOperator(DataValidation::OPERATOR_BETWEEN);
            $validation->setAllowBlank(true);
            $validation->setShowInputMessage(true);
            $validation->setShowErrorMessage(true);
            $validation->setPromptTitle('Tanggal Bergabung');
            $validation->setPrompt('Format: YYYY-MM-DD');
            $validation->setErrorTitle('Format salah');
            $validation->setError('Masukkan tanggal dengan format YYYY-MM-DD.');
            $validation->setFormula1('Date(2000,1,1)');
            $validation->setFormula2('Date(2099,12,31)');
        }
    }
}
