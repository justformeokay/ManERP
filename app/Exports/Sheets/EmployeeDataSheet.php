<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EmployeeDataSheet implements FromArray, WithHeadings, WithStyles, WithTitle
{
    public function title(): string
    {
        return 'Template';
    }

    public function headings(): array
    {
        return [
            'NIK',
            'Nama',
            'Jabatan',
            'Departemen',
            'Shift',
            'Tanggal Bergabung',
            'Status',
            'NPWP',
            'PTKP',
            'Kategori TER',
            'No. BPJS TK',
            'No. BPJS Kesehatan',
            'Nama Bank',
            'Nomor Rekening',
            'Nama Akun',
        ];
    }

    public function array(): array
    {
        // One example row to guide the user
        return [
            [
                '1234567890123456',
                'Budi Santoso',
                'Staff IT',
                'IT',
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
        // Header row bold + background
        $sheet->getStyle('A1:O1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4F46E5'],
            ],
        ]);

        // Example row italic + light gray
        $sheet->getStyle('A2:O2')->applyFromArray([
            'font' => ['italic' => true, 'color' => ['rgb' => '6B7280']],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'F3F4F6'],
            ],
        ]);

        // Auto-size columns
        foreach (range('A', 'O') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return [];
    }
}
