<?php

namespace App\Exports\Sheets;

use App\Models\Bank;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\Setting;
use App\Models\Shift;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EmployeeInstructionSheet implements FromArray, WithStyles, WithTitle
{
    public function title(): string
    {
        return 'Instructions';
    }

    public function array(): array
    {
        $nikMin = (int) Setting::get('nik_min_length', 1);
        $nikMax = (int) Setting::get('nik_max_length', 20);
        $bankMin = (int) Setting::get('bank_account_min_length', 1);
        $bankMax = (int) Setting::get('bank_account_max_length', 30);

        $ptkpOptions = implode(', ', Employee::ptkpOptions());
        $terOptions = implode(', ', Employee::TER_CATEGORIES);
        $statusOptions = implode(', ', Employee::statusOptions());

        $departments = Department::active()->pluck('name')->implode(', ') ?: '(Belum ada departemen)';
        $positions = Position::active()->pluck('name')->implode(', ') ?: '(Belum ada jabatan)';
        $shifts = Shift::active()->pluck('name')->implode(', ') ?: '(Belum ada shift)';
        $banks = Bank::active()->pluck('name')->implode(', ') ?: '(Belum ada bank)';

        $rows = [
            ['PETUNJUK PENGISIAN TEMPLATE IMPORT KARYAWAN'],
            [''],
            ['Kolom', 'Wajib?', 'Format / Aturan', 'Contoh'],
            ['NIK', 'Ya', "Teks, {$nikMin}–{$nikMax} karakter, harus unik", '1234567890123456'],
            ['Nama', 'Ya', 'Teks, maks 255 karakter', 'Budi Santoso'],
            ['Jabatan', 'Tidak', "Nama jabatan yang tersedia: {$positions}", 'Staff IT'],
            ['Departemen', 'Tidak', "Nama departemen yang tersedia: {$departments}", 'IT'],
            ['Shift', 'Tidak', "Nama shift yang tersedia: {$shifts}", 'Shift Pagi'],
            ['Tanggal Bergabung', 'Ya', 'Format: YYYY-MM-DD', '2024-01-15'],
            ['Status', 'Ya', "Pilihan: {$statusOptions}", 'active'],
            ['NPWP', 'Tidak', 'Teks, maks 30 karakter', '12.345.678.9-012.345'],
            ['PTKP', 'Ya', "Pilihan: {$ptkpOptions}", 'TK/0'],
            ['Kategori TER', 'Tidak', "Otomatis dari PTKP. Pilihan: {$terOptions}", 'A'],
            ['No. BPJS TK', 'Tidak', 'Teks, maks 30 karakter', '0001234567890'],
            ['No. BPJS Kesehatan', 'Tidak', 'Teks, maks 30 karakter', '0009876543210'],
            ['Nama Bank', 'Tidak', "Nama bank yang tersedia: {$banks}", 'BCA'],
            ['Nomor Rekening', 'Tidak', "Teks, {$bankMin}–{$bankMax} karakter", '1234567890'],
            ['Nama Akun', 'Tidak', 'Teks, maks 255 karakter', 'Budi Santoso'],
            [''],
            ['CATATAN PENTING:'],
            ['1. Hapus baris contoh (baris 2) pada sheet "Template" sebelum mengisi data Anda.'],
            ['2. Nama Departemen, Jabatan, Shift, dan Bank harus sesuai dengan data master (tidak case-sensitive).'],
            ['3. Jika Departemen/Jabatan/Shift/Bank tidak ditemukan, import akan gagal.'],
            ['4. Seluruh proses import bersifat transaksional: jika satu baris gagal, semua dibatalkan.'],
            ['5. NIK harus unik — tidak boleh sama dengan karyawan yang sudah ada di database.'],
        ];

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        // Title row
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '1F2937']],
        ]);

        // Table header
        $sheet->getStyle('A3:D3')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4F46E5'],
            ],
        ]);

        // "CATATAN PENTING" bold
        $sheet->getStyle('A21')->applyFromArray([
            'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'DC2626']],
        ]);

        // Auto-size
        $sheet->getColumnDimension('A')->setWidth(22);
        $sheet->getColumnDimension('B')->setWidth(10);
        $sheet->getColumnDimension('C')->setWidth(70);
        $sheet->getColumnDimension('D')->setWidth(25);

        return [];
    }
}
