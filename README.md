# ManERP — Manufacturing Enterprise Resource Planning

![Laravel](https://img.shields.io/badge/Laravel-13.x-FF2D20?style=flat&logo=laravel)
![PHP](https://img.shields.io/badge/PHP-8.3+-777BB4?style=flat&logo=php)
![TailwindCSS](https://img.shields.io/badge/TailwindCSS-3.x-38B2AC?style=flat&logo=tailwindcss)
![Alpine.js](https://img.shields.io/badge/Alpine.js-3.x-8BC0D0?style=flat)
![MySQL](https://img.shields.io/badge/MySQL-8.x-4479A1?style=flat&logo=mysql)
![Routes](https://img.shields.io/badge/Routes-174-22c55e?style=flat)
![Modules](https://img.shields.io/badge/Modules-16-a855f7?style=flat)
![Tables](https://img.shields.io/badge/Tables-43-f59e0b?style=flat)
![License](https://img.shields.io/badge/License-MIT-blue?style=flat)

---

## 📋 Deskripsi Proyek

**ManERP** adalah sistem *Enterprise Resource Planning* (ERP) berbasis web yang dirancang khusus untuk kebutuhan industri manufaktur skala kecil hingga menengah. Sistem ini mengintegrasikan seluruh siklus bisnis — mulai dari pembelian bahan baku, produksi, manajemen gudang, penjualan, keuangan, hingga akuntansi — dalam satu platform terpadu yang modern, aman, dan mudah digunakan.

### 🎯 Target Pengguna

| Segmen | Deskripsi |
|---|---|
| Pabrik manufaktur | Skala kecil hingga menengah (UKM – Menengah) |
| Workshop / Bengkel | Produksi berbasis pesanan (job order) |
| Trading company | Yang membutuhkan manajemen stok & pembelian |
| Kontraktor / Proyek | Yang membutuhkan manajemen proyek & biaya |

---

## 🏗️ Arsitektur Sistem

```
┌──────────────────────────────────────────────────────────────────────┐
│                         PRESENTATION LAYER                            │
│     Blade Templates · TailwindCSS 3 · Alpine.js 3 · Chart.js 4      │
└──────────────────────────────────────────────────────────────────────┘
                                  │
┌──────────────────────────────────────────────────────────────────────┐
│                         APPLICATION LAYER                             │
│          Laravel 13.x Controllers · Middleware · FormRequests         │
│     (Auth · Permission · Admin · Active · Locale · Notification)      │
└──────────────────────────────────────────────────────────────────────┘
                                  │
┌──────────────────────────────────────────────────────────────────────┐
│                           SERVICE LAYER                               │
│  StockService · FinanceService · AccountingService · ApprovalService  │
│               AuditLogService · PDFService                            │
└──────────────────────────────────────────────────────────────────────┘
                                  │
┌──────────────────────────────────────────────────────────────────────┐
│                            DATA LAYER                                 │
│           Eloquent ORM (34 Models) · MySQL 8 · 43 Tables             │
└──────────────────────────────────────────────────────────────────────┘
```

---

## 🛠️ Tech Stack

| Komponen | Teknologi | Versi |
|---|---|---|
| Framework Backend | Laravel | 13.x |
| Bahasa | PHP | 8.3+ |
| Database | MySQL | 8.x |
| CSS Framework | TailwindCSS | 3.x |
| JS Framework | Alpine.js | 3.x |
| Charting | Chart.js | 4.x |
| Build Tool | Vite | 8.x |
| HTTP Client | Axios | 1.x |
| PDF Generator | barryvdh/laravel-dompdf | 3.x |
| Server Dev | XAMPP / Laravel Sail | — |

---

## 📦 Modul & Fitur Lengkap

### 1. 🔐 Authentication & User Management

| Fitur | Status | Deskripsi |
|---|---|---|
| Login / Logout | ✅ | Autentikasi aman via Laravel Breeze |
| Pendaftaran User | ✅ | Registrasi user baru oleh admin |
| Reset Password | ✅ | Reset via email token |
| Manajemen Profil | ✅ | Edit nama, email, password, bahasa |
| Role System | ✅ | Role: `admin` dan `staff` |
| Granular Permissions | ✅ | Per modul: `view`, `create`, `edit`, `delete` |
| User CRUD (Admin) | ✅ | Create, edit, disable, hapus user |
| Status Aktif/Non-aktif | ✅ | Blokir akses tanpa hapus user |
| Multi-language Preference | ✅ | Pilih bahasa per user (EN / ID / ZH) |

**Permission Matrix:**
```
┌──────────────────┬──────┬────────┬──────┬────────┐
│ Modul            │ View │ Create │ Edit │ Delete │
├──────────────────┼──────┼────────┼──────┼────────┤
│ Clients          │  ✓   │   ✓    │  ✓   │   ✓    │
│ Warehouses       │  ✓   │   ✓    │  ✓   │   ✓    │
│ Suppliers        │  ✓   │   ✓    │  ✓   │   ✓    │
│ Projects         │  ✓   │   ✓    │  ✓   │   ✓    │
│ Inventory        │  ✓   │   ✓    │  ✓   │   ✓    │
│ Manufacturing    │  ✓   │   ✓    │  ✓   │   ✓    │
│ Sales            │  ✓   │   ✓    │  ✓   │   ✓    │
│ Purchasing       │  ✓   │   ✓    │  ✓   │   ✓    │
│ Reports          │  ✓   │   —    │  —   │   —    │
│ Approvals        │  ✓   │   —    │  —   │   —    │
└──────────────────┴──────┴────────┴──────┴────────┘
```

---

### 2. 👥 CRM — Manajemen Klien

| Fitur | Status | Deskripsi |
|---|---|---|
| Client CRUD | ✅ | Tambah, edit, hapus klien |
| Informasi Kontak | ✅ | Nama, email, telepon, alamat, PIC |
| Status Aktif/Nonaktif | ✅ | Kelola status klien |
| Pencarian & Filter | ✅ | Cari berdasarkan nama dan email |
| Relasi ke Proyek | ✅ | Klien terhubung ke proyek |
| Relasi ke Sales Order | ✅ | Klien terhubung ke penjualan |

---

### 3. 🏭 Master Data

#### Gudang (Warehouses)
| Fitur | Status | Deskripsi |
|---|---|---|
| CRUD Gudang | ✅ | Multi-warehouse support |
| Kode & Lokasi | ✅ | Nama, kode, alamat gudang |
| Status Aktif | ✅ | Aktifkan/nonaktifkan gudang |
| Stok per Gudang | ✅ | Stok dikelola terpisah per gudang |

#### Pemasok (Suppliers)
| Fitur | Status | Deskripsi |
|---|---|---|
| CRUD Pemasok | ✅ | Manajemen data vendor |
| Kontak Lengkap | ✅ | Telepon, email, alamat, PIC |
| Status Aktif | ✅ | Aktifkan/nonaktifkan pemasok |
| Relasi ke PO | ✅ | Terhubung ke Purchase Order |
| Relasi ke Tagihan | ✅ | Terhubung ke Supplier Bill (AP) |

#### Kategori Produk
| Fitur | Status | Deskripsi |
|---|---|---|
| CRUD Kategori | ✅ | Pengelompokan produk |
| Jumlah Produk | ✅ | Tampilkan total produk per kategori |
| Sub-kategori | ❌ | Belum tersedia (flat structure) |

---

### 4. 📦 Manajemen Inventori

#### Produk
| Fitur | Status | Deskripsi |
|---|---|---|
| CRUD Produk | ✅ | SKU, nama, deskripsi, kategori |
| Satuan Pengukuran | ✅ | pcs, kg, liter, meter, dll. |
| Harga Beli & Jual | ✅ | Harga master untuk kalkulasi otomatis |
| Alert Stok Minimum | ✅ | Threshold min_stock per produk |
| Status Aktif | ✅ | Nonaktifkan produk tanpa menghapus |
| Gambar Produk | ❌ | Belum tersedia |

#### Level Stok
| Fitur | Status | Deskripsi |
|---|---|---|
| Stok Multi-Gudang | ✅ | Stok dikelola per produk per gudang |
| Ringkasan Stok | ✅ | Semua produk dan level stok |
| Alert Stok Rendah | ✅ | Notifikasi otomatis ke semua admin |
| Penilaian Stok | ⚠️ | Kalkulasi nilai stok (basic) |

#### Pergerakan Stok
| Fitur | Status | Deskripsi |
|---|---|---|
| Stok Masuk (In) | ✅ | Penerimaan barang manual |
| Stok Keluar (Out) | ✅ | Pengeluaran barang manual |
| Penyesuaian (Adjustment) | ✅ | Koreksi stok fisik |
| Riwayat Pergerakan | ✅ | Log lengkap semua gerakan stok |
| Referensi Transaksi | ✅ | Link ke SO / PO / MO |
| Batalkan Gerakan | ❌ | Belum bisa undo movement |

#### Transfer Stok
| Fitur | Status | Deskripsi |
|---|---|---|
| Buat Transfer | ✅ | Request transfer antar gudang |
| Eksekusi Transfer | ✅ | Konfirmasi dan proses transfer |
| Batalkan Transfer | ✅ | Cancel transfer yang pending |
| Status Transfer | ✅ | Pending → Completed / Cancelled |
| Riwayat Transfer | ✅ | Log semua transfer |

---

### 5. 🏭 Manufaktur (Manufacturing)

#### Bill of Materials (BOM)
| Fitur | Status | Deskripsi |
|---|---|---|
| CRUD BOM | ✅ | Resep / formula produksi |
| Komponen BOM | ✅ | Daftar bahan baku + qty |
| Produk Output | ✅ | Tentukan produk hasil produksi |
| Qty Output | ✅ | Kuantitas output per batch |
| Multi-level BOM | ❌ | Belum support BOM bertingkat |
| Status Aktif | ✅ | Aktifkan/nonaktifkan BOM |

#### Manufacturing Orders (Work Orders)
| Fitur | Status | Deskripsi |
|---|---|---|
| CRUD Work Order | ✅ | Perintah produksi |
| Pilih BOM | ✅ | Load bahan baku otomatis dari BOM |
| Target Kuantitas | ✅ | Planned quantity produksi |
| Gudang Produksi | ✅ | Tentukan gudang sumber & output |
| Link ke Proyek | ✅ | Opsional hubungkan ke proyek |
| Prioritas | ✅ | Low / Normal / High / Urgent |
| Jadwal Produksi | ✅ | Tanggal mulai & selesai rencana |
| **Status Workflow** | | |
| — Draft | ✅ | Order baru dibuat |
| — Confirmed | ✅ | Order dikonfirmasi, siap produksi |
| — In Progress | ✅ | Sedang berjalan |
| — Done | ✅ | Produksi selesai |
| — Cancelled | ✅ | Dibatalkan |
| Tracking Progress | ✅ | % progress produksi (produced/planned) |
| Konsumsi Material | ✅ | Otomatis deduct bahan baku saat produksi |
| Barang Jadi Masuk | ✅ | Otomatis stock-in hasil produksi |
| Produksi Bertahap | ✅ | Partial production supported |

---

### 6. 💰 Manajemen Penjualan (Sales)

| Fitur | Status | Deskripsi |
|---|---|---|
| CRUD Sales Order | ✅ | Pesan penjualan |
| Pilih Klien | ✅ | Link ke master klien |
| Multi-item Order | ✅ | Multiple line items per order |
| Harga Otomatis | ✅ | Ambil harga dari master produk |
| Diskon per Item | ✅ | Diskon per baris item |
| Kalkulasi Pajak | ✅ | Tax otomatis per setting |
| Catatan Order | ✅ | Notes / keterangan tambahan |
| Pilih Gudang | ✅ | Gudang pengiriman |
| Link ke Proyek | ✅ | Opsional hubungkan ke proyek |
| **Status Workflow** | | |
| — Draft | ✅ | Order dibuat, stok belum berkurang |
| — Confirmed | ✅ | Stok dideduct otomatis |
| — Processing | ✅ | Dalam proses pengerjaan |
| — Shipped | ✅ | Sudah dikirim |
| — Completed | ✅ | Order selesai |
| — Cancelled | ✅ | Dibatalkan, stok dikembalikan |
| Validasi Stok | ✅ | Cek ketersediaan sebelum konfirmasi |
| Auto Deduct Stok | ✅ | Kurangi stok saat konfirmasi |
| Buat Invoice | ✅ | Generate invoice langsung dari SO |
| Tracking Pembuat | ✅ | Catat siapa yang membuat order |

---

### 7. 🛒 Manajemen Pembelian (Purchasing)

| Fitur | Status | Deskripsi |
|---|---|---|
| CRUD Purchase Order | ✅ | Order pembelian ke supplier |
| Pilih Supplier | ✅ | Link ke master supplier |
| Multi-item Order | ✅ | Multiple line items |
| Harga Beli & Qty | ✅ | Input harga beli aktual |
| Pajak & Diskon | ✅ | Kalkulasi per item |
| Tanggal Kedatangan | ✅ | Expected delivery date |
| Pilih Gudang | ✅ | Gudang tujuan penerimaan |
| Link ke Proyek | ✅ | Opsional hubungkan ke proyek |
| **Status Workflow** | | |
| — Draft | ✅ | PO baru |
| — Confirmed | ✅ | PO dikonfirmasi |
| — Partial | ✅ | Sebagian item diterima |
| — Received | ✅ | Semua item diterima |
| — Cancelled | ✅ | Dibatalkan |
| Penerimaan Bertahap | ✅ | Terima barang sebagian (partial receive) |
| Auto Stock-in | ✅ | Tambah stok otomatis saat terima barang |
| Tracking Penerimaan | ✅ | Pantau qty diterima vs qty order |

---

### 8. 📊 Manajemen Proyek

| Fitur | Status | Deskripsi |
|---|---|---|
| CRUD Proyek | ✅ | Manajemen proyek bisnis |
| Assign ke Klien | ✅ | Hubungkan proyek ke klien |
| Manajer Proyek | ✅ | Assign project manager |
| Budget Tracking | ✅ | Monitor anggaran proyek |
| Timeline | ✅ | Tanggal mulai & selesai |
| Status Proyek | ✅ | Planning / Active / On Hold / Completed / Cancelled |
| Link ke Order | ✅ | SO / PO / MO bisa terkoneksi ke proyek |
| Task Management | ❌ | Belum ada breakdown tugas |
| % Progress | ❌ | Belum ada tracking persentase |

---

### 9. 💵 Keuangan (Finance — Accounts Receivable)

#### Invoice / Tagihan ke Pelanggan
| Fitur | Status | Deskripsi |
|---|---|---|
| Generate dari SO | ✅ | Buat invoice 1-klik dari Sales Order |
| CRUD Invoice | ✅ | Buat, lihat, batalkan |
| Nomor Otomatis | ✅ | Format `INV-YYYY-XXXX` |
| Line Items | ✅ | Detail item dari SO |
| Subtotal / Pajak / Total | ✅ | Kalkulasi otomatis |
| Due Date | ✅ | Tanggal jatuh tempo |
| **Status Invoice** | | |
| — Draft | ✅ | Belum dikirim |
| — Sent | ✅ | Sudah dikirim ke pelanggan |
| — Partial | ✅ | Dibayar sebagian |
| — Paid | ✅ | Lunas |
| — Cancelled | ✅ | Dibatalkan |
| Auto Jurnal Akuntansi | ✅ | Debet Piutang / Kredit Pendapatan |
| Export PDF | ✅ | Cetak invoice ke PDF |

#### Pembayaran dari Pelanggan
| Fitur | Status | Deskripsi |
|---|---|---|
| Catat Pembayaran | ✅ | Record payment dari customer |
| Metode Pembayaran | ✅ | Cash / Transfer / Cek |
| Pembayaran Parsial | ✅ | Bayar sebagian invoice |
| Nomor Referensi | ✅ | Reference number transaksi |
| Auto Update Invoice | ✅ | Status invoice update otomatis |
| Auto Jurnal | ✅ | Debet Kas / Kredit Piutang |

---

### 10. 🧾 Accounts Payable (AP) — Hutang ke Pemasok

| Fitur | Status | Deskripsi |
|---|---|---|
| Supplier Bills CRUD | ✅ | Pencatatan tagihan dari supplier |
| Nomor Otomatis | ✅ | Format `BILL-YYYY-XXXX` |
| Link ke Supplier | ✅ | Terhubung ke master pemasok |
| Line Items | ✅ | Detail item tagihan |
| Subtotal / Pajak / Total | ✅ | Kalkulasi otomatis |
| Due Date | ✅ | Tanggal jatuh tempo |
| **Status Tagihan** | | |
| — Draft | ✅ | Tagihan baru |
| — Posted | ✅ | Diposting ke akuntansi |
| — Partial | ✅ | Dibayar sebagian |
| — Paid | ✅ | Lunas |
| — Cancelled | ✅ | Dibatalkan |
| Posting ke Jurnal | ✅ | Debet Beban / Kredit Hutang |
| Pembayaran ke Supplier | ✅ | Catat pembayaran ke pemasok |
| Laporan Aging AP | ✅ | Analisis umur hutang per supplier |
| Export PDF | ✅ | Cetak tagihan ke PDF |

---

### 11. 📒 Akuntansi Berentri Ganda (Double-Entry Accounting)

#### Chart of Accounts (Daftar Akun)
| Fitur | Status | Deskripsi |
|---|---|---|
| CRUD Akun | ✅ | Kelola daftar akun |
| Tipe Akun | ✅ | Asset / Liability / Equity / Revenue / Expense |
| Kode Akun | ✅ | Kode unik per akun |
| Status Aktif | ✅ | Aktifkan/nonaktifkan akun |

#### Jurnal Umum (Journal Entries)
| Fitur | Status | Deskripsi |
|---|---|---|
| Jurnal Manual | ✅ | Input entri jurnal manual |
| Double-Entry | ✅ | Debet = Kredit (wajib seimbang) |
| Multi-baris | ✅ | Banyak baris debit/kredit per jurnal |
| Nomor & Referensi | ✅ | Penomoran otomatis + referensi |
| Auto-Jurnal | ✅ | Dibuat otomatis dari Invoice & Payment |

#### General Ledger (Buku Besar)
| Fitur | Status | Deskripsi |
|---|---|---|
| Ledger per Akun | ✅ | Riwayat semua transaksi per akun |
| Saldo Berjalan | ✅ | Running balance per baris |
| Filter Tanggal | ✅ | Filter berdasarkan periode |

#### Laporan Keuangan
| Laporan | Status | Deskripsi |
|---|---|---|
| Trial Balance | ✅ | Neraca saldo — verifikasi Total D = K |
| Balance Sheet | ✅ | Neraca keuangan (Assets = Liabilities + Equity) |
| Profit & Loss | ✅ | Laporan laba rugi per periode |

---

### 12. ✅ Approval Workflow (Alur Persetujuan)

| Fitur | Status | Deskripsi |
|---|---|---|
| Approval Roles | ✅ | Definisi role approver (Manager, Direktur, dll.) |
| Approval Flows | ✅ | Atur alur persetujuan per modul |
| Multi-step Approval | ✅ | Persetujuan berjenjang (step 1 → 2 → N) |
| Conditional Steps | ✅ | Step bersyarat berdasarkan nilai transaksi |
| Submit & Review | ✅ | Ajukan permintaan persetujuan |
| Approve / Reject | ✅ | Tombol setuju atau tolak per langkah |
| Cancel & Resubmit | ✅ | Batalkan persetujuan, ajukan ulang |
| Catatan Approval | ✅ | Tambahkan komentar di setiap keputusan |
| Progress Tracking | ✅ | Progress bar visual per tahapan |
| Log Approval | ✅ | Riwayat lengkap semua keputusan |
| Dashboard Widget | ✅ | Tampil di dashboard sebagai task pending |
| Admin Flow Editor | ✅ | Konfigurasi alur langsung dari UI |

---

### 13. 📈 Laporan & Analitik (Reports)

| Laporan | Status | Deskripsi |
|---|---|---|
| Sales Report | ✅ | Revenue, jumlah order, tren penjualan |
| Purchasing Report | ✅ | Pengeluaran, analisis supplier |
| Inventory Report | ✅ | Level stok, valuasi inventori |
| Manufacturing Report | ✅ | Statistik produksi, output, waste |
| Finance Report | ✅ | Ringkasan keuangan, AR/AP summary |
| Filter Rentang Tanggal | ✅ | Filter semua laporan per periode |
| Export CSV | ✅ | Download semua laporan ke CSV |
| Export PDF | ✅ | Cetak laporan ke PDF |
| Chart Visualization | ✅ | Grafik bar revenue vs expense (Dashboard) |

---

### 14. 📄 PDF Generation

| Fitur | Status | Deskripsi |
|---|---|---|
| Invoice PDF | ✅ | Cetak tagihan ke pelanggan |
| Supplier Bill PDF | ✅ | Cetak tagihan dari supplier |
| Purchase Order PDF | ✅ | Cetak surat order pembelian |
| Template Profesional | ✅ | Layout siap cetak dengan info perusahaan |
| Menggunakan DomPDF | ✅ | `barryvdh/laravel-dompdf` |

---

### 15. 🔔 Notifikasi

| Notifikasi | Status | Trigger |
|---|---|---|
| Alert Stok Rendah | ✅ | Stok turun di bawah `min_stock` |
| SO Dikonfirmasi | ✅ | Sales Order berstatus Confirmed |
| PO Diterima Penuh | ✅ | PO berstatus Received |
| MO Selesai | ✅ | Manufacturing Order berstatus Done |
| Tandai Dibaca | ✅ | Per-notifikasi atau sekaligus |
| Email Notifikasi | ❌ | Belum diimplementasi |
| Push Notification | ❌ | Belum diimplementasi |

---

### 16. ⚙️ Pengaturan Sistem

| Fitur | Status | Deskripsi |
|---|---|---|
| Informasi Perusahaan | ✅ | Nama, logo, alamat, telepon, email |
| Mata Uang | ✅ | Konfigurasi mata uang default |
| Tarif Pajak | ✅ | Persentase pajak default |
| Manajemen User | ✅ | CRUD user dan permission |
| Multi-language (i18n) | ✅ | English / Bahasa Indonesia / 中文 |
| Audit Log | ✅ | Lihat semua aktivitas sistem (admin only) |

---

## 🔄 Alur Proses Bisnis (Business Flows)

### Flow 1: Siklus Penjualan (Order-to-Cash)

```
  [Buat SO]──►[Konfirmasi SO]──►[Proses]──►[Kirim]──►[Selesai]
                    │                                     │
                    ▼                                     ▼
             [Stok Berkurang]                    [Buat Invoice]
                                                      │
                                          ┌───────────┴──────────┐
                                          ▼                      ▼
                                   [Terima Bayaran]       [Kirim ke PDF]
                                          │
                                          ▼
                                   [Jurnal Akuntansi]
                                   Kas / Piutang
```

**Detail langkah:**
1. **Draft** — Buat Sales Order, isi data klien, produk, qty, harga
2. **Confirm** — Sistem validasi stok cukup, lalu deduct stok otomatis
3. **Processing / Shipped** — Update status sesuai progres pengiriman
4. **Completed** — Tandai order selesai
5. **Invoice** — Generate invoice dari SO dengan 1 klik
6. **Payment** — Catat pembayaran (full/partial), sistem update status invoice & buat jurnal

---

### Flow 2: Siklus Pembelian (Purchase-to-Pay)

```
  [Buat PO]──►[Konfirmasi PO]──►[Terima Barang]──►[Received]
                                      │
                          ┌───────────┴──────────┐
                          ▼                      ▼
                   [Partial Receive]        [Full Receive]
                          │                      │
                          ▼                      ▼
                   [Stok Bertambah]       [Notifikasi]
                                               │
                                               ▼
                                     [Buat Supplier Bill]
                                               │
                                               ▼
                                       [Post → Bayar]
                                               │
                                               ▼
                                       [Jurnal Akuntansi]
                                       Hutang / Beban
```

**Detail langkah:**
1. **Draft** — Buat PO, pilih supplier, isi item & harga
2. **Confirm** — PO dikonfirmasi, siap untuk penerimaan
3. **Receive** — Catat penerimaan barang (bisa partial), stok bertambah otomatis
4. **Supplier Bill** — Buat tagihan dari supplier, posting ke akuntansi
5. **Payment** — Catat pembayaran ke supplier, update hutang, buat jurnal

---

### Flow 3: Siklus Produksi (Manufacturing)

```
  [Buat MO]──►[Pilih BOM]──►[Konfirmasi]──►[Prosess Produksi]──►[Done]
                                                  │                 │
                                                  ▼                 ▼
                                         [Konsumsi Bahan]   [Output ke Stok]
                                         (Stock Out)         (Stock In)
                                              │
                                    ┌─────────┴────────┐
                                    ▼                  ▼
                             [Produksi Penuh]   [Produksi Parsial]
                                    │                  │
                                    ▼                  ▼
                             [Notifikasi]        [Masih bisa produksi lagi]
                             MO Selesai          sampai planned qty terpenuhi
```

**Detail langkah:**
1. **Draft** — Buat Manufacturing Order, pilih BOM, tentukan jumlah & gudang
2. **Confirm** — Konfirmasi order, cek ketersediaan bahan baku
3. **Produce** — Input jumlah yang diproduksi, sistem otomatis:
   - Kurangi stok bahan baku (berdasarkan proporsi BOM)
   - Tambah stok produk jadi
4. **Done** — Status berubah otomatis saat produced qty = planned qty

---

### Flow 4: Alur Persetujuan (Approval Workflow)

```
  [User Ajukan Dokumen]
         │
         ▼
  [Approval Dibuat - Step 1]
         │
         ▼
  [Notifikasi ke Approver Step 1]
         │
    ┌────┴────┐
    ▼         ▼
[Setuju]   [Tolak]
    │         │
    ▼         ▼
[Step 2]  [Dokumen Rejected]
    │       (User dapat resubmit)
    ▼
[... dst hingga step terakhir]
    │
    ▼
[Dokumen APPROVED ✓]
```

**Detail:**
- Alur persetujuan dikonfigurasi admin per modul (SO, PO, Invoice, dll.)
- Setiap step bisa memiliki kondisi (misal: hanya berlaku jika nilai > Rp 10 juta)
- Approver dapat menambahkan catatan saat approve/reject
- Semua keputusan tercatat di approval log (tidak bisa dihapus)

---

### Flow 5: Integrasi Stok Terpusat

```
                    ┌─────────────────────┐
                    │    STOCK SERVICE    │
                    │   (Central Hub)     │
                    └──────────┬──────────┘
                               │
    ┌──────────────────────────┼──────────────────────┐
    │                          │                      │
    ▼                          ▼                      ▼
[Sales Order]         [Purchase Order]     [Manufacturing Order]
  Confirmed             Received               Produce
  (Stock OUT)          (Stock IN)           (OUT bahan baku)
                                            (IN barang jadi)
                                                      │
            ◄─────────────────────────────────────────┘
            │
            ▼
   [Cek Level Stok]
            │
   [Stok < Min Stock?]──YES──► [Buat Notifikasi Low Stock]
            │
           NO
            │
            ▼
      [Selesai]
```

---

### Flow 6: Akuntansi Terintegrasi

```
  Invoice Terbit ──► Auto Journal: Piutang (D) / Pendapatan (K)
  Payment Masuk ──► Auto Journal: Kas (D) / Piutang (K)
  Supplier Bill ──► Auto Journal: Beban (D) / Hutang Usaha (K)
  Bayar Supplier ──► Auto Journal: Hutang Usaha (D) / Kas (K)
         │
         ▼
  [Journal Entries] ──► [General Ledger per Akun]
         │                        │
         ▼                        ▼
  [Trial Balance]        [Running Balance]
         │
    ┌────┴────┐
    ▼         ▼
[Balance  [Profit &
 Sheet]    Loss]
```

---

## 🖥️ Panduan Penggunaan

### Cara Menggunakan ManERP untuk Bisnis

#### Langkah 1 — Setup Awal (Admin)
1. Login sebagai admin (`admin@manerp.com`)
2. Buka **Settings → Company** → isi data perusahaan (nama, alamat, logo)
3. Buka **Settings → Users** → buat akun untuk setiap karyawan, atur permission
4. Buka **Warehouses** → tambahkan gudang utama
5. Buka **Inventory → Categories** → buat kategori produk
6. Buka **Inventory → Products** → masukkan semua produk (SKU, harga, min stock)
7. Masukkan stok awal via **Inventory → Movements** (tipe: Stock In)

#### Langkah 2 — Setup Data Master
```
Suppliers → Masukkan data semua vendor/pemasok
Clients   → Masukkan data semua pelanggan
BOM       → Buat Bill of Materials untuk setiap produk yang diproduksi
```

#### Langkah 3 — Proses Harian Penjualan
```
Sales → New Order
  → Pilih klien
  → Tambah item produk + qty + harga
  → Simpan (Draft)
  → Confirm (stok otomatis berkurang)
  → Update status saat diproses / dikirim
  → Buat Invoice dari SO
  → Catat Pembayaran saat pelanggan bayar
```

#### Langkah 4 — Proses Harian Pembelian
```
Purchasing → New PO
  → Pilih supplier
  → Tambah item + qty + harga beli
  → Confirm PO
  → Saat barang tiba: Record Receive (stok otomatis bertambah)
  → Buat Supplier Bill (AP Payable)
  → Catat pembayaran ke supplier
```

#### Langkah 5 — Proses Produksi
```
Manufacturing → BOM → Buat BOM untuk produk
Manufacturing → Orders → New Order
  → Pilih BOM
  → Tentukan planned quantity
  → Confirm
  → Saat produksi berjalan: klik Produce
    → Input produced quantity
    → Bahan baku otomatis berkurang
    → Produk jadi otomatis bertambah
```

#### Langkah 6 — Monitoring & Laporan
```
Dashboard  → Ringkasan bisnis real-time (AR, AP, revenue, chart)
Reports    → Sales / Purchasing / Inventory / Manufacturing / Finance
Accounting → Cek General Ledger, Trial Balance, Balance Sheet, P&L
```

---

## ✅ Keunggulan Sistem

### 1. Integrasi End-to-End
Seluruh modul terhubung dalam satu alur data: Sales → Invoice → Payment → Jurnal. Tidak perlu input ulang data yang sama di modul berbeda.

### 2. Stok Real-time & Multi-Gudang
Setiap transaksi (penjualan, pembelian, produksi) langsung memperbarui saldo stok per gudang. Alert otomatis saat stok di bawah minimum.

### 3. Akuntansi Terintegrasi (Double-Entry)
Setiap transaksi keuangan otomatis menghasilkan jurnal akuntansi yang balance. Laporan keuangan (Balance Sheet, P&L) tergenerate real-time.

### 4. Approval Workflow Fleksibel
Proses persetujuan multi-level yang bisa dikonfigurasi per modul dan kondisi nilai transaksi. Semua keputusan tercatat dan tidak bisa dimanipulasi.

### 5. Keamanan & Kontrol Akses
Role-based access + granular permission per modul + audit log lengkap. Admin dapat mengatur siapa boleh melakukan apa dengan detail.

### 6. PDF & Export
Invoice, PO, Supplier Bill bisa langsung dicetak ke PDF siap kirim ke partner bisnis.

### 7. Multi-bahasa
Mendukung Bahasa Indonesia, English, dan 中文 (Mandarin). User dapat memilih bahasa dari profil masing-masing.

### 8. Dashboard Komprehensif
Dashboard menampilkan ringkasan AR/AP, grafik revenue vs expense (6 bulan), pending approvals, proyek aktif, progres manufaktur, dan aktivitas terkini.

### 9. Tech Stack Modern
Laravel 13 terbaru, TailwindCSS, Alpine.js — codebase bersih, mudah dikembangkan, dan nyaman di-maintain jangka panjang.

---

## ❌ Keterbatasan Saat Ini

### Fitur yang Belum Ada

| Fitur | Prioritas | Keterangan |
|---|---|---|
| Multi-level BOM | Tinggi | BOM satu level, tidak support sub-assembly |
| Gambar Produk | Sedang | Belum ada upload foto produk |
| Email Notifikasi | Sedang | Notifikasi hanya in-app database |
| Task Management (Proyek) | Sedang | Belum ada breakdown tugas per proyek |
| Tracking Pengiriman | Sedang | Belum ada delivery order / resi |
| Proses Retur Barang | Sedang | Return ke supplier / dari pelanggan |
| Sub-kategori Produk | Rendah | Kategori masih flat, belum hierarki |
| Multi-currency | Rendah | Hanya satu mata uang |
| Penyusutan Aset Tetap | Rendah | Depreciation belum ada |
| Laporan Pajak | Rendah | Belum ada laporan PPN / PPh |
| REST API | Sedang | Belum ada API untuk integrasi pihak ketiga |
| Automated Testing | Tinggi | Belum ada unit / feature test |
| Mobile App | Rendah | Hanya web-based |

---

## 🗺️ Roadmap

### ✅ Phase 1 — Core ERP (Selesai)
- Inventori multi-gudang (produk, stok, movement, transfer)
- Manufacturing: BOM & Work Orders
- Sales & Purchase Order lifecycle
- CRM: Clients, Projects, Suppliers
- Role-based permissions & Audit Log
- Notifikasi in-app

### ✅ Phase 2 — Finance & Accounting (Selesai)
- Invoice Generation dari Sales Order
- Payment Tracking (AR)
- Accounts Payable: Supplier Bills & Payments
- Chart of Accounts, Double-Entry Journal
- General Ledger, Trial Balance, Balance Sheet, P&L
- PDF Generation (Invoice, PO, Supplier Bill)
- Approval Workflow multi-level
- Dashboard revamp: AR/AP, grafik Chart.js, widgets

### 🔄 Phase 3 — Operational Enhancement (Rekomendasi)
- Delivery Management (Delivery Order, tracking pengiriman)
- Return & Refund (Sales Return / Purchase Return)
- Email Notifications (SMTP, template per event)
- Project Task Management (breakdown tugas per proyek)

### 🔮 Phase 4 — Advanced & Integration (Masa Depan)
- REST API + Sanctum authentication
- Multi-currency support
- Multi-level BOM & MRP (Material Requirements Planning)
- Modul HR & Payroll dasar
- Mobile-optimized PWA

---

## 🗃️ Struktur Database (43 Tabel)

### Peta Relasi Entitas

```
Users ──────────────────────────────────────────────┐
  │                                                  │
  ▼                                                  ▼
Projects ◄──── Clients                         ActivityLogs
  │
  ├──► Sales Orders ──► SO Items
  │          │
  │          └──► Invoices ──► Invoice Items
  │                    │
  │                    └──► Payments
  │
  ├──► Purchase Orders ──► PO Items
  │
  ├──► Manufacturing Orders ──► BOM ──► BOM Items
  │
  └──► (semua terhubung ke Products & Warehouses)

Products ◄──── Categories
    │
    └──► Inventory Stocks (per Warehouse)
               │
               └──► Stock Movements
               └──► Stock Transfer Items ◄── Stock Transfers

Suppliers ──► Purchase Orders
          └──► Supplier Bills ──► Supplier Bill Items
                    │
                    └──► Supplier Payments

Chart of Accounts ──► Journal Items ◄── Journal Entries

Approval Flows ──► Approval Steps ──► Approvals ──► Approval Logs
Approval Roles ◄── approval_role_user (pivot) ──► Users

Company Settings | Settings | Notifications | Sessions | Cache | Jobs
```

### Daftar Lengkap Tabel

| # | Tabel | Deskripsi |
|---|---|---|
| 1 | `users` | User, role, JSON permissions |
| 2 | `clients` | Data pelanggan/klien |
| 3 | `suppliers` | Data vendor/pemasok |
| 4 | `categories` | Kategori produk |
| 5 | `products` | Master produk (SKU, harga, min_stock) |
| 6 | `warehouses` | Data lokasi gudang |
| 7 | `inventory_stocks` | Stok per produk per gudang |
| 8 | `stock_movements` | Log semua pergerakan stok |
| 9 | `stock_transfers` | Transfer stok antar gudang |
| 10 | `stock_transfer_items` | Item per transfer |
| 11 | `projects` | Data proyek bisnis |
| 12 | `sales_orders` | Header sales order |
| 13 | `sales_order_items` | Line item penjualan |
| 14 | `purchase_orders` | Header purchase order |
| 15 | `purchase_order_items` | Line item pembelian |
| 16 | `bill_of_materials` | Header BOM / resep produksi |
| 17 | `bom_items` | Komponen bahan baku BOM |
| 18 | `manufacturing_orders` | Work order produksi |
| 19 | `invoices` | Tagihan ke pelanggan (AR) |
| 20 | `invoice_items` | Line item invoice |
| 21 | `payments` | Pembayaran dari pelanggan |
| 22 | `supplier_bills` | Tagihan dari pemasok (AP) |
| 23 | `supplier_bill_items` | Line item supplier bill |
| 24 | `supplier_payments` | Pembayaran ke pemasok |
| 25 | `chart_of_accounts` | Daftar akun akuntansi |
| 26 | `journal_entries` | Header jurnal akuntansi |
| 27 | `journal_items` | Baris debit/kredit |
| 28 | `approval_roles` | Role approver |
| 29 | `approval_role_user` | Pivot user ↔ approval role |
| 30 | `approval_flows` | Definisi alur persetujuan |
| 31 | `approval_steps` | Tahapan per alur |
| 32 | `approvals` | Instance persetujuan |
| 33 | `approval_logs` | Log setiap keputusan approval |
| 34 | `activity_logs` | Audit log semua aktivitas sistem |
| 35 | `settings` | Konfigurasi global |
| 36 | `company_settings` | Informasi perusahaan |
| 37 | `notifications` | Notifikasi in-app |
| 38 | `sessions` | Sesi login pengguna |
| 39 | `cache` | Cache aplikasi |
| 40 | `cache_locks` | Cache locks |
| 41 | `jobs` | Antrian pekerjaan |
| 42 | `job_batches` | Batch job |
| 43 | `failed_jobs` | Job yang gagal |

---

## 🗂️ Ringkasan Route (174 Routes)

| Prefix | Modul | HTTP Methods |
|---|---|---|
| `/dashboard` | Dashboard | GET |
| `/clients` | CRM – Klien | GET, POST, PUT, DELETE |
| `/projects` | Proyek | GET, POST, PUT, DELETE |
| `/warehouses` | Gudang | GET, POST, PUT, DELETE |
| `/suppliers` | Pemasok | GET, POST, PUT, DELETE |
| `/inventory/categories` | Kategori Produk | GET, POST, PUT, DELETE |
| `/inventory/products` | Produk | GET, POST, PUT, DELETE |
| `/inventory/stocks` | Level Stok | GET |
| `/inventory/movements` | Pergerakan Stok | GET, POST |
| `/inventory/transfers` | Transfer Stok | GET, POST, DELETE |
| `/manufacturing/boms` | Bill of Materials | GET, POST, PUT, DELETE |
| `/manufacturing/orders` | Work Orders | GET, POST, PUT, DELETE |
| `/sales` | Sales Orders | GET, POST, PUT, DELETE |
| `/purchasing` | Purchase Orders | GET, POST, PUT, DELETE |
| `/finance/invoices` | Invoice AR | GET, POST |
| `/finance/payments` | Pembayaran AR | POST |
| `/ap/bills` | Supplier Bills AP | GET, POST, PUT, DELETE |
| `/ap/payments` | Pembayaran AP | GET |
| `/ap/aging` | Laporan Aging AP | GET |
| `/accounting/coa` | Chart of Accounts | GET, POST, PUT, DELETE |
| `/accounting/journals` | Journal Entries | GET, POST |
| `/accounting/ledger` | General Ledger | GET |
| `/accounting/trial-balance` | Trial Balance | GET |
| `/accounting/balance-sheet` | Balance Sheet | GET |
| `/accounting/profit-loss` | Profit & Loss | GET |
| `/approvals` | Approval Tasks | GET, POST |
| `/approvals/admin/roles` | Manage Roles | GET, PUT |
| `/approvals/admin/flows` | Manage Flows | GET, PUT |
| `/reports` | Laporan (6 sub) | GET |
| `/reports/export` | Export CSV | GET |
| `/pdf/invoice/{id}` | PDF Invoice | GET |
| `/pdf/po/{id}` | PDF Purchase Order | GET |
| `/pdf/bill/{id}` | PDF Supplier Bill | GET |
| `/notifications` | Notifikasi | GET, POST |
| `/settings` | Pengaturan | GET, POST |
| `/settings/users` | Manajemen User | GET, POST, PUT, DELETE |
| `/audit-logs` | Audit Log | GET |
| `/lang/{locale}` | Ganti Bahasa | GET |

---

## 🚀 Instalasi & Setup

### Prasyarat

- PHP 8.3+
- Composer 2.x
- Node.js 20+ & npm
- MySQL 8.x (disarankan via XAMPP atau Laravel Herd)
- Git

### Langkah Instalasi

```bash
# 1. Clone repository
git clone https://github.com/your-repo/manerp.git
cd manerp

# 2. Install dependensi PHP
composer install

# 3. Install dependensi JavaScript
npm install

# 4. Konfigurasi environment
cp .env.example .env
php artisan key:generate

# 5. Konfigurasi database di .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=manerp
DB_USERNAME=root
DB_PASSWORD=

# 6. Jalankan migrasi & seeder
php artisan migrate
php artisan db:seed

# 7. Build aset frontend
npm run build

# 8. Jalankan server (mode dev)
php artisan serve
# atau dengan hot-reload:
# Terminal 1: php artisan serve
# Terminal 2: npm run dev
```

### Login Default

| Role | Email | Password |
|---|---|---|
| Admin | admin@manerp.com | password |
| Staff | staff@manerp.com | password |

> ⚠️ **Ganti password default segera setelah instalasi pertama!**

---

## 🏗️ Struktur Direktori

```
manerp/
├── app/
│   ├── Http/
│   │   ├── Controllers/        # 20+ controller modul
│   │   ├── Middleware/         # Auth, Permission, Locale, dll.
│   │   └── Requests/           # Form validation
│   ├── Models/                 # 34 Eloquent models
│   └── Services/               # Business logic services
│       ├── StockService.php
│       ├── FinanceService.php
│       ├── AccountingService.php
│       ├── ApprovalService.php
│       ├── AuditLogService.php
│       └── PDFService.php
├── database/
│   ├── migrations/             # 26 migration files
│   └── seeders/                # Data awal (CoA, roles, dll.)
├── lang/
│   ├── en/                     # Terjemahan bahasa Inggris
│   ├── id/                     # Terjemahan bahasa Indonesia
│   └── zh/                     # Terjemahan bahasa Mandarin
├── resources/
│   └── views/                  # Blade templates per modul
├── routes/
│   └── web.php                 # 174 route definitions
└── public/                     # Asset publik
```

---

## 📝 Catatan Teknis

### Service Layer
Logika bisnis utama dipisahkan ke dalam Service Class, bukan di Controller, untuk menjaga *separation of concerns*:

| Service | Tanggung Jawab |
|---|---|
| `StockService` | Semua operasi stok: in, out, transfer, validasi |
| `FinanceService` | Invoice, payment, update status AR |
| `AccountingService` | Jurnal double-entry otomatis, ledger, laporan keuangan |
| `ApprovalService` | Workflow persetujuan: submit, approve, reject, cek kondisi |
| `AuditLogService` | Pencatatan log aktivitas secara terpusat |
| `PDFService` | Render template ke file PDF menggunakan DomPDF |

### Keamanan
- **CSRF Protection** — Semua form POST dilindungi CSRF token Laravel
- **Auth Middleware** — Semua route memerlukan autentikasi
- **Permission Middleware** — Akses per-tindakan diverifikasi via JSON permission
- **Admin Middleware** — Route admin hanya bisa diakses role admin
- **SQL Injection** — Menggunakan Eloquent / Query Builder dengan parameter binding
- **Mass Assignment** — Model dilindungi dengan `$fillable`

---

## 📞 Kontribusi & Pengembangan

Untuk laporan bug, permintaan fitur, atau kontribusi kode, silakan buat *issue* atau *pull request* di repository.

---

*📅 Dokumentasi diperbarui: 31 Maret 2026*
*🔖 Versi: 2.0.0*
*📊 Total Routes: 174 | Total Modul: 16 | Total Tabel: 43 | Total Model: 34*
