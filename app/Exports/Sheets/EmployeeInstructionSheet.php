<?php

namespace App\Exports\Sheets;

use App\Models\Employee;
use App\Models\Setting;
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

        $rows = [
            ['PETUNJUK PENGISIAN TEMPLATE IMPORT KARYAWAN'],
            [''],
            ['Kolom', 'Wajib?', 'Format / Aturan', 'Contoh'],
            ['NIK', 'Ya', "Teks, {$nikMin}–{$nikMax} karakter, harus unik. Awalan nol dipertahankan.", '0812345678901234'],
            ['Nama', 'Ya', 'Teks, maks 255 karakter', 'Budi Santoso'],
            ['Jabatan', 'Tidak', '🔽 DROPDOWN — Pilih dari daftar [KODE] - Nama', '[STF] - Staff'],
            ['Departemen', 'Tidak', '🔽 DROPDOWN — Pilih dari daftar [KODE] - Nama', '[PROD] - Produksi'],
            ['Shift', 'Tidak', '🔽 DROPDOWN — Pilih dari daftar shift aktif', 'Shift Pagi'],
            ['Tanggal Bergabung', 'Ya', '📅 DATE PICKER — Format: YYYY-MM-DD', '2024-01-15'],
            ['Status', 'Ya', "🔽 DROPDOWN — Pilihan: {$statusOptions}", 'active'],
            ['NPWP', 'Tidak', 'Teks, maks 30 karakter. Awalan nol dipertahankan.', '12.345.678.9-012.345'],
            ['PTKP', 'Ya', "🔽 DROPDOWN — Pilihan: {$ptkpOptions}", 'TK/0'],
            ['Kategori TER', 'Tidak', "🔽 DROPDOWN — Otomatis dari PTKP. Pilihan: {$terOptions}", 'A'],
            ['No. BPJS TK', 'Tidak', 'Teks, maks 30 karakter. Awalan nol dipertahankan.', '0001234567890'],
            ['No. BPJS Kesehatan', 'Tidak', 'Teks, maks 30 karakter. Awalan nol dipertahankan.', '0009876543210'],
            ['Nama Bank', 'Tidak', '🔽 DROPDOWN — Pilih dari daftar bank yang terdaftar', 'BCA'],
            ['Nomor Rekening', 'Tidak', "Teks, {$bankMin}–{$bankMax} karakter. Awalan nol dipertahankan.", '1234567890'],
            ['Nama Akun', 'Tidak', 'Teks, maks 255 karakter', 'Budi Santoso'],
            [''],
            ['KETERANGAN WARNA HEADER:'],
            ['🔵 Biru Muda = Kolom dengan dropdown (pilih dari daftar)'],
            ['🟡 Kuning = Kolom tanggal (gunakan date picker atau ketik YYYY-MM-DD)'],
            ['🟣 Ungu Tua = Kolom isian bebas (ketik manual)'],
            [''],
            ['CATATAN PENTING:'],
            ['1. Hapus baris contoh (baris 2) pada sheet "Template" sebelum mengisi data Anda.'],
            ['2. Kolom dengan dropdown akan menampilkan daftar pilihan saat diklik — WAJIB dipilih dari situ.'],
            ['3. Nama yang diimport berasal dari pilihan dropdown: Jabatan & Departemen otomatis sesuai master data.'],
            ['4. Format NIK dan Nomor Rekening sudah diatur sebagai TEKS agar awalan 0 tidak hilang.'],
            ['5. Seluruh proses import bersifat transaksional: jika satu baris gagal, semua dibatalkan.'],
            ['6. NIK harus unik — tidak boleh sama dengan karyawan yang sudah ada di database.'],
            ['7. Template mendukung hingga 500 baris data karyawan.'],
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

        // Color legend header
        $sheet->getStyle('A21')->applyFromArray([
            'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => '4338CA']],
        ]);

        // "CATATAN PENTING" bold red
        $sheet->getStyle('A26')->applyFromArray([
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
