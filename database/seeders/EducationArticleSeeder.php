<?php

namespace Database\Seeders;

use App\Models\EducationArticle;
use Illuminate\Database\Seeder;

class EducationArticleSeeder extends Seeder
{
    /**
     * Seed ManERP Academy educational content.
     */
    public function run(): void
    {
        $articles = [
            // ═══════════════════════════════════════════════════════════
            // GLOSSARY
            // ═══════════════════════════════════════════════════════════
            [
                'title'      => 'CAPEX (Capital Expenditure)',
                'slug'       => 'capex',
                'category'   => 'glossary',
                'icon'       => '💰',
                'sort_order' => 1,
                'content'    => <<<'MD'
## CAPEX — Capital Expenditure

**CAPEX** (Capital Expenditure) adalah pengeluaran modal untuk memperoleh atau meningkatkan aset tetap perusahaan, seperti mesin, gedung, kendaraan, atau peralatan IT.

### Karakteristik CAPEX
- Nilai besar dan bersifat jangka panjang
- Dicatat sebagai **aset** di neraca, bukan langsung sebagai beban
- Disusutkan (depresiasi) selama masa manfaat aset
- Memerlukan proses persetujuan (approval) khusus

### CAPEX vs OPEX
| Aspek | CAPEX | OPEX |
|-------|-------|------|
| Sifat | Investasi jangka panjang | Biaya operasional rutin |
| Pencatatan | Aset di neraca | Beban di laba rugi |
| Contoh | Pembelian server | Biaya listrik bulanan |
| Approval | Multi-level | Standar |

### Dalam ManERP
Gunakan tipe proyek **Internal CAPEX** untuk melacak pengeluaran modal. Setiap Purchase Request yang terkait CAPEX akan diberi label khusus dan memerlukan approval tambahan dari manajemen.
MD,
            ],
            [
                'title'      => 'Nota Debit (Debit Note)',
                'slug'       => 'debit-note',
                'category'   => 'glossary',
                'icon'       => '📋',
                'sort_order' => 2,
                'content'    => <<<'MD'
## Nota Debit — Debit Note

**Nota Debit** adalah dokumen yang diterbitkan kepada supplier untuk mengurangi jumlah hutang (Accounts Payable). Biasanya digunakan saat terjadi retur pembelian atau klaim atas barang yang tidak sesuai.

### Kapan Menggunakan Nota Debit?
1. **Retur Pembelian** — Barang rusak atau tidak sesuai spesifikasi
2. **Kelebihan Harga** — Supplier menagih lebih dari yang disepakati
3. **Kekurangan Kuantitas** — Barang diterima kurang dari yang ditagih

### Dampak pada Sistem
- **Hutang berkurang** — Saldo AP otomatis terpotong
- **Stok disesuaikan** — Jika ada retur fisik, stok di warehouse berkurang
- **Jurnal otomatis** — Sistem membuat jurnal: Debit AP, Kredit Inventory/Retur

### Anti-Overclaim
ManERP memiliki validasi **anti-overclaim** yang mencegah total nota debit melebihi nilai tagihan supplier asli. Ini melindungi dari kesalahan input dan potensi fraud.
MD,
            ],
            [
                'title'      => 'Nota Kredit (Credit Note)',
                'slug'       => 'credit-note',
                'category'   => 'glossary',
                'icon'       => '📝',
                'sort_order' => 3,
                'content'    => <<<'MD'
## Nota Kredit — Credit Note

**Nota Kredit** adalah dokumen yang diterbitkan kepada pelanggan untuk mengurangi jumlah piutang (Accounts Receivable). Biasanya digunakan saat terjadi retur penjualan atau pemberian diskon setelah invoice terbit.

### Kapan Menggunakan Nota Kredit?
1. **Retur Penjualan** — Pelanggan mengembalikan barang
2. **Diskon Tambahan** — Pemberian potongan harga setelah invoice
3. **Koreksi Harga** — Perbedaan harga yang perlu diperbaiki

### Dampak pada Sistem
- **Piutang berkurang** — Saldo AR otomatis terpotong
- **Stok masuk kembali** — Jika ada retur fisik, stok di warehouse bertambah
- **Jurnal otomatis** — Sistem membuat jurnal: Debit Retur Penjualan, Kredit AR

### Anti-Overclaim
Sama seperti Nota Debit, ManERP menerapkan validasi anti-overclaim pada Nota Kredit untuk mencegah pengembalian melebihi nilai invoice asli.
MD,
            ],
            [
                'title'      => 'Anti-Overclaim',
                'slug'       => 'anti-overclaim',
                'category'   => 'glossary',
                'icon'       => '🛡️',
                'sort_order' => 4,
                'content'    => <<<'MD'
## Anti-Overclaim

**Anti-Overclaim** adalah mekanisme validasi di ManERP yang mencegah pembuatan nota debit atau nota kredit melebihi nilai dokumen sumber (supplier bill atau invoice).

### Cara Kerja
1. Sistem menghitung total klaim yang sudah dibuat untuk sebuah dokumen sumber
2. Saat membuat klaim baru, sisa yang bisa diklaim = nilai asli − total klaim sebelumnya
3. Jika jumlah klaim baru > sisa, sistem akan menolak dengan pesan error

### Contoh
- Invoice Rp 10.000.000
- Nota Kredit #1: Rp 3.000.000 ✅
- Nota Kredit #2: Rp 5.000.000 ✅ (sisa Rp 2.000.000)
- Nota Kredit #3: Rp 3.000.000 ❌ (melebihi sisa Rp 2.000.000)

### Manfaat
- Mencegah kesalahan input yang merugikan perusahaan
- Melindungi dari potensi fraud atau manipulasi data
- Menjaga integritas data keuangan
MD,
            ],
            [
                'title'      => 'Purchase Request vs Purchase Order',
                'slug'       => 'pr-vs-po',
                'category'   => 'glossary',
                'icon'       => '🔄',
                'sort_order' => 5,
                'content'    => <<<'MD'
## PR vs PO — Purchase Request vs Purchase Order

### Purchase Request (PR)
**PR** adalah dokumen internal yang diajukan oleh departemen/divisi untuk meminta pembelian barang atau jasa. PR bersifat internal dan belum mengikat secara hukum dengan supplier.

### Purchase Order (PO)
**PO** adalah dokumen resmi yang dikirim ke supplier sebagai komitmen pembelian. PO bersifat mengikat secara hukum dan menjadi dasar pengiriman barang serta penagihan.

### Perbedaan Utama
| Aspek | PR | PO |
|-------|----|----|
| Sifat | Internal | Eksternal |
| Ditujukan ke | Manajemen/Purchasing | Supplier |
| Status hukum | Tidak mengikat | Mengikat |
| Dibuat oleh | Semua departemen | Tim Purchasing |

### Alur dalam ManERP
1. Departemen membuat **PR** dengan detail kebutuhan
2. PR di-approve oleh atasan/manajemen
3. Tim Purchasing mengkonversi PR yang approved menjadi **PO**
4. PO dikirim ke supplier
5. Barang diterima → Goods Receipt
6. Invoice dari supplier → Pembayaran

### Pentingnya Departemen dalam PR
Setiap PR **wajib** dikaitkan dengan departemen pemohon. Ini memungkinkan tracking budget per departemen, analisis spending, dan audit trail yang lengkap.
MD,
            ],
            [
                'title'      => 'Chart of Accounts (CoA)',
                'slug'       => 'chart-of-accounts',
                'category'   => 'glossary',
                'icon'       => '📊',
                'sort_order' => 6,
                'content'    => <<<'MD'
## Chart of Accounts — Bagan Akun

**Chart of Accounts (CoA)** adalah daftar sistematis semua akun yang digunakan dalam pencatatan transaksi keuangan perusahaan.

### Kategori Akun
1. **Asset (Aset)** — Kode 1xxx: Cash, Bank, Piutang, Persediaan
2. **Liability (Kewajiban)** — Kode 2xxx: Hutang Usaha, Hutang Pajak
3. **Equity (Modal)** — Kode 3xxx: Modal Pemilik, Laba Ditahan
4. **Revenue (Pendapatan)** — Kode 4xxx: Penjualan, Pendapatan Lain
5. **Expense (Beban)** — Kode 5xxx-6xxx: HPP, Biaya Operasional

### System Account
Beberapa akun ditandai sebagai **System Account** yang digunakan otomatis oleh ManERP untuk jurnal-jurnal yang dihasilkan secara otomatis (seperti pencatatan inventory, AP/AR).

### Tips
- Jangan menghapus System Account karena akan mengganggu proses otomatis
- Gunakan kode akun yang konsisten dan terstruktur
- Review CoA secara berkala untuk memastikan relevansi
MD,
            ],
            [
                'title'      => 'PPN (Pajak Pertambahan Nilai)',
                'slug'       => 'ppn',
                'category'   => 'glossary',
                'icon'       => '🏛️',
                'sort_order' => 7,
                'content'    => <<<'MD'
## PPN — Pajak Pertambahan Nilai

**PPN** adalah pajak yang dikenakan atas penyerahan Barang Kena Pajak (BKP) dan Jasa Kena Pajak (JKP) di dalam daerah pabean Indonesia.

### Tarif PPN
- Tarif umum: **11%** (sejak April 2022)
- Tarif ekspor: **0%**

### PPN dalam ManERP
- **PPN Keluaran** — Otomatis dihitung pada Invoice penjualan
- **PPN Masukan** — Otomatis dihitung pada Supplier Bill
- **SPT PPN** — Laporan bulanan yang merangkum PPN Keluaran dan Masukan

### Pelaporan
ManERP menyediakan fitur SPT PPN yang mengkonsolidasi semua transaksi PPN dalam periode tertentu untuk memudahkan pelaporan ke DJP.
MD,
            ],
            [
                'title'      => 'BPJS (Ketenagakerjaan & Kesehatan)',
                'slug'       => 'bpjs',
                'category'   => 'glossary',
                'icon'       => '🏥',
                'sort_order' => 8,
                'content'    => <<<'MD'
## BPJS — Badan Penyelenggara Jaminan Sosial

**BPJS** adalah badan hukum publik yang menyelenggarakan program jaminan sosial di Indonesia.

### BPJS Ketenagakerjaan
- **JHT** (Jaminan Hari Tua) — Tabungan pensiun: 5.7% (3.7% perusahaan + 2% karyawan)
- **JKK** (Jaminan Kecelakaan Kerja) — 0.24%-1.74% ditanggung perusahaan
- **JKM** (Jaminan Kematian) — 0.3% ditanggung perusahaan
- **JP** (Jaminan Pensiun) — 3% (2% perusahaan + 1% karyawan)

### BPJS Kesehatan
- Tarif: **5%** dari gaji (4% perusahaan + 1% karyawan)
- Batas atas gaji: Rp 12.000.000

### Dalam ManERP
Modul HR & Payroll secara otomatis menghitung iuran BPJS berdasarkan komponen gaji karyawan dan tarif yang dikonfigurasi di Settings.
MD,
            ],
            [
                'title'      => 'HPP (Harga Pokok Penjualan)',
                'slug'       => 'hpp',
                'category'   => 'glossary',
                'icon'       => '🧮',
                'sort_order' => 9,
                'content'    => <<<'MD'
## HPP — Harga Pokok Penjualan (COGS)

**HPP** atau **COGS** (Cost of Goods Sold) adalah total biaya langsung yang dikeluarkan untuk memproduksi atau memperoleh barang yang dijual.

### Komponen HPP
1. **Bahan Baku** — Material langsung yang digunakan
2. **Tenaga Kerja Langsung** — Upah pekerja produksi
3. **Overhead Pabrik** — Biaya tidak langsung (listrik pabrik, depresiasi mesin)

### Perhitungan
```
HPP = Persediaan Awal + Pembelian − Persediaan Akhir
```

### Dalam ManERP
- HPP dihitung otomatis berdasarkan metode **Average Cost**
- Setiap penjualan otomatis membuat jurnal HPP
- BOM (Bill of Materials) di Manufacturing membantu tracking komponen HPP
MD,
            ],
            [
                'title'      => 'BOM (Bill of Materials)',
                'slug'       => 'bom',
                'category'   => 'glossary',
                'icon'       => '📦',
                'sort_order' => 10,
                'content'    => <<<'MD'
## BOM — Bill of Materials

**BOM** (Bill of Materials) adalah daftar lengkap bahan baku, komponen, dan sub-assembly yang dibutuhkan untuk memproduksi satu unit produk jadi.

### Struktur BOM
- **Parent Item** — Produk jadi yang akan diproduksi
- **Component** — Bahan baku atau sub-assembly
- **Quantity** — Jumlah komponen per unit produk
- **Unit of Measure** — Satuan ukuran komponen

### Dalam ManERP
BOM digunakan di modul **Manufacturing** untuk:
1. Membuat **Work Order** dengan kebutuhan material otomatis
2. Menghitung **estimated cost** produksi
3. Melakukan **material consumption** saat produksi berjalan
4. Tracking **variance** antara estimasi dan aktual
MD,
            ],

            // ═══════════════════════════════════════════════════════════
            // WORKFLOWS
            // ═══════════════════════════════════════════════════════════
            [
                'title'      => 'Alur Retur Barang hingga Koreksi Stok dan Jurnal Otomatis',
                'slug'       => 'workflow-retur-stok-jurnal',
                'category'   => 'workflow',
                'icon'       => '🔁',
                'sort_order' => 1,
                'content'    => <<<'MD'
## Alur Retur Barang → Koreksi Stok → Jurnal Otomatis

Berikut adalah alur lengkap penanganan retur barang dari supplier di ManERP:

1. **Identifikasi Masalah** — Tim Gudang menemukan barang rusak/tidak sesuai dari penerimaan supplier
2. **Buat Nota Debit** — Buka modul Nota Debit, pilih Supplier Bill terkait, masukkan item yang diretur beserta kuantitas dan alasan
3. **Validasi Anti-Overclaim** — Sistem otomatis memvalidasi bahwa total retur tidak melebihi nilai bill asli
4. **Pilih Warehouse** — Tentukan gudang asal barang yang diretur (wajib untuk integrasi stok)
5. **Submit untuk Approval** — Nota Debit masuk ke antrian persetujuan
6. **Approval oleh Manager** — Manager mereview dan menyetujui nota debit
7. **Stok Otomatis Berkurang** — Setelah approved, stok di warehouse terpilih otomatis berkurang sesuai kuantitas retur
8. **Jurnal Otomatis Tercipta** — Sistem membuat jurnal: Debit Accounts Payable, Kredit Inventory
9. **Rekonsiliasi** — Saldo AP dengan supplier otomatis terupdate, siap untuk rekonsiliasi

### Tips
- Selalu pilih warehouse yang benar agar stok akurat
- Lampirkan foto/dokumentasi kerusakan jika perlu
- Review laporan retur secara berkala untuk evaluasi kualitas supplier
MD,
            ],
            [
                'title'      => 'Alur Purchase Request ke Purchase Order',
                'slug'       => 'workflow-pr-to-po',
                'category'   => 'workflow',
                'icon'       => '📤',
                'sort_order' => 2,
                'content'    => <<<'MD'
## Alur Purchase Request → Purchase Order

Berikut adalah alur lengkap dari permintaan pembelian hingga pesanan ke supplier:

1. **Identifikasi Kebutuhan** — Departemen mengidentifikasi kebutuhan barang/jasa yang diperlukan
2. **Buat Purchase Request** — Buka modul PR, pilih departemen, masukkan item dengan spesifikasi dan estimasi harga
3. **Pilih Tipe Proyek** — Tentukan apakah pembelian terkait proyek Sales, Internal CAPEX, atau Operational
4. **Submit PR** — Ajukan PR untuk proses persetujuan
5. **Approval oleh Atasan** — Atasan/Manager mereview kebutuhan dan budget
6. **PR Diterima oleh Purchasing** — Tim Purchasing menerima PR yang sudah approved
7. **Konversi PR ke PO** — Purchasing memilih supplier, negosiasi harga, dan mengkonversi PR menjadi PO
8. **Kirim PO ke Supplier** — PO resmi dikirim ke supplier sebagai komitmen pembelian
9. **Penerimaan Barang** — Barang diterima di warehouse, dibuat Goods Receipt Note
10. **Invoice Matching** — Invoice supplier dicocokkan dengan PO dan GRN (3-Way Matching)

### Tips
- Pastikan departemen terisi dengan benar untuk tracking budget
- Untuk CAPEX, sertakan justifikasi bisnis yang lengkap
- Manfaatkan fitur partial PO jika tidak semua item PR dibeli sekaligus
MD,
            ],
            [
                'title'      => 'Alur Penjualan dari Quotation hingga Pembayaran',
                'slug'       => 'workflow-sales-order',
                'category'   => 'workflow',
                'icon'       => '💼',
                'sort_order' => 3,
                'content'    => <<<'MD'
## Alur Penjualan — Quotation → Invoice → Payment

Berikut adalah alur lengkap proses penjualan di ManERP:

1. **Lead Masuk** — Calon pelanggan menghubungi perusahaan, dicatat sebagai Lead di CRM
2. **Buat Quotation** — Sales membuat penawaran harga berdasarkan kebutuhan pelanggan
3. **Negosiasi** — Proses negosiasi harga dan terms dengan pelanggan
4. **Quotation Disetujui** — Pelanggan menyetujui penawaran
5. **Konversi ke Sales Order** — Quotation dikonversi menjadi Sales Order
6. **Delivery** — Barang dikirim ke pelanggan dari warehouse
7. **Buat Invoice** — Invoice diterbitkan berdasarkan Sales Order dan Delivery
8. **Pembayaran Diterima** — Pelanggan melakukan pembayaran
9. **Rekonsiliasi** — Payment dicocokkan dengan invoice, piutang lunas

### Tips
- Gunakan pipeline CRM untuk tracking progress setiap lead
- Set payment terms yang jelas di quotation
- Manfaatkan fitur partial delivery dan partial invoice untuk transaksi bertahap
MD,
            ],
            [
                'title'      => 'Alur Penggajian (Payroll)',
                'slug'       => 'workflow-payroll',
                'category'   => 'workflow',
                'icon'       => '💳',
                'sort_order' => 4,
                'content'    => <<<'MD'
## Alur Penggajian — Payroll Process

Berikut adalah alur lengkap proses penggajian di ManERP:

1. **Setup Struktur Gaji** — HR mengkonfigurasi salary structure: gaji pokok, tunjangan, potongan
2. **Data Kehadiran** — Sistem mengumpulkan data presensi dari modul Attendance
3. **Hitung Komponen** — Sistem menghitung otomatis: BPJS, PPh 21, lembur, dan potongan lainnya
4. **Generate Payslip** — HR menjalankan proses generate payslip untuk periode tertentu
5. **Review & Approval** — Manager HR mereview hasil perhitungan
6. **Proses Pembayaran** — Transfer gaji ke rekening karyawan
7. **Jurnal Otomatis** — Sistem membuat jurnal beban gaji di Accounting

### Tips
- Pastikan data kehadiran sudah lengkap sebelum generate payslip
- Review perubahan tarif BPJS dan pajak setiap tahun
- Gunakan fitur Settings HR untuk konfigurasi komponen gaji
MD,
            ],

            // ═══════════════════════════════════════════════════════════
            // TUTORIALS
            // ═══════════════════════════════════════════════════════════
            [
                'title'      => 'Cara Kerja Integrasi Stok pada Nota Debit',
                'slug'       => 'tutorial-stock-integration-debit-note',
                'category'   => 'tutorial',
                'icon'       => '📚',
                'sort_order' => 1,
                'content'    => <<<'MD'
## Tutorial: Integrasi Stok pada Nota Debit

### Mengapa Penting?
Saat Anda membuat Nota Debit untuk retur barang, stok di gudang harus otomatis berkurang. Tanpa integrasi ini, data stok menjadi tidak akurat dan bisa menyebabkan masalah di seluruh rantai pasokan.

### Langkah-langkah
1. Buka menu **Purchasing → Debit Notes**
2. Klik **Create Debit Note**
3. Pilih **Supplier Bill** yang menjadi dasar retur
4. Tambahkan **line items** — produk yang diretur beserta kuantitas
5. Pilih **Warehouse** — gudang tempat barang berada (WAJIB)
6. Isi alasan retur di kolom keterangan
7. Klik **Submit**

### Yang Terjadi di Belakang Layar
Setelah Nota Debit di-approve:
- Stok produk di warehouse terpilih **berkurang** sesuai kuantitas
- Jurnal akuntansi otomatis dibuat:
  - **Debit**: Accounts Payable (hutang berkurang)
  - **Kredit**: Inventory (stok berkurang)
- Saldo hutang supplier terupdate otomatis

### Troubleshooting
- **Stok tidak berkurang?** Pastikan warehouse sudah dipilih dan nota sudah di-approve
- **Error overclaim?** Total retur melebihi nilai bill — periksa nota debit sebelumnya
- **Warehouse salah?** Buat nota debit baru dengan warehouse yang benar (nota yang sudah approved tidak bisa diedit)
MD,
            ],
            [
                'title'      => 'Pentingnya Pemilihan Departemen dalam Purchase Request',
                'slug'       => 'tutorial-department-pr',
                'category'   => 'tutorial',
                'icon'       => '🏢',
                'sort_order' => 2,
                'content'    => <<<'MD'
## Tutorial: Pentingnya Departemen dalam Purchase Request

### Mengapa Departemen Wajib Diisi?
Departemen dalam Purchase Request bukan sekadar formalitas. Data ini krusial untuk:
1. **Budget Tracking** — Melacak pengeluaran per departemen
2. **Cost Center** — Mengalokasikan biaya ke pusat biaya yang tepat
3. **Approval Routing** — Menentukan siapa yang perlu menyetujui
4. **Analisis Spending** — Mengidentifikasi pola pengeluaran

### Cara Memilih Departemen yang Tepat
- Pilih departemen yang **membutuhkan** barang/jasa, bukan yang memproses
- Untuk belanja bersama, gunakan departemen **General Affairs**
- Untuk proyek CAPEX, pastikan departemen sesuai dengan pemilik proyek

### Dampak jika Salah Pilih
- Laporan spending per departemen menjadi tidak akurat
- Budget departemen bisa terlampaui tanpa terdeteksi
- Audit trail menjadi menyesatkan
- Approval bisa salah routing

### Best Practice
1. Tetapkan kebijakan clear tentang departemen default
2. Review PR yang ditolak — sering karena departemen tidak sesuai
3. Gunakan fitur filter di laporan untuk cross-check per departemen
MD,
            ],
            [
                'title'      => 'Memahami 3-Way Matching dalam Purchasing',
                'slug'       => 'tutorial-3way-matching',
                'category'   => 'tutorial',
                'icon'       => '✅',
                'sort_order' => 3,
                'content'    => <<<'MD'
## Tutorial: 3-Way Matching

### Apa itu 3-Way Matching?
3-Way Matching adalah proses mencocokkan tiga dokumen sebelum melakukan pembayaran ke supplier:
1. **Purchase Order (PO)** — Pesanan yang dikirim ke supplier
2. **Goods Receipt Note (GRN)** — Bukti penerimaan barang di gudang
3. **Supplier Invoice** — Tagihan dari supplier

### Mengapa Penting?
- Mencegah **pembayaran ganda**
- Mendeteksi **perbedaan harga** antara PO dan invoice
- Memastikan **barang sudah diterima** sebelum bayar
- Melindungi dari **invoice fiktif**

### Dalam ManERP
Saat membuat pembayaran, sistem akan otomatis mencocokkan:
- Kuantitas di invoice ≤ kuantitas di GRN
- Harga di invoice ≤ harga di PO
- Total invoice sesuai dengan dokumen pendukung

Jika ada ketidaksesuaian, sistem akan memberikan peringatan sebelum Anda melanjutkan pembayaran.
MD,
            ],
        ];

        foreach ($articles as $article) {
            EducationArticle::updateOrCreate(
                ['slug' => $article['slug']],
                $article
            );
        }
    }
}
