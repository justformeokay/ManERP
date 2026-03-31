# ManERP - Manufacturing Enterprise Resource Planning

![Laravel](https://img.shields.io/badge/Laravel-13.x-red)
![PHP](https://img.shields.io/badge/PHP-8.3+-blue)
![TailwindCSS](https://img.shields.io/badge/TailwindCSS-4.x-38B2AC)
![MySQL](https://img.shields.io/badge/MySQL-8.x-orange)
![Routes](https://img.shields.io/badge/Routes-145-green)
![Modules](https://img.shields.io/badge/Modules-13-purple)

## 📋 Deskripsi Proyek

**ManERP** adalah sistem Enterprise Resource Planning (ERP) berbasis web yang dirancang khusus untuk industri manufaktur. Sistem ini mengintegrasikan berbagai modul bisnis mulai dari manajemen inventori, produksi, penjualan, pembelian, keuangan, akuntansi berentri ganda, hingga pelaporan dalam satu platform terpadu.

### 🎯 Target Pengguna

- Pabrik manufaktur skala kecil hingga menengah
- Bengkel produksi
- Workshop/industri rumahan yang ingin scale-up
- Perusahaan dengan proses produksi berbasis Bill of Materials (BOM)

---

## 🏗️ Arsitektur Sistem

```
┌─────────────────────────────────────────────────────────────────┐
│                        PRESENTATION LAYER                        │
│    Blade Templates + TailwindCSS 4 + Alpine.js 3 + Vite 8       │
└─────────────────────────────────────────────────────────────────┘
                                │
┌─────────────────────────────────────────────────────────────────┐
│                        APPLICATION LAYER                         │
│              Laravel 13.x Controllers + Middleware               │
│         (Auth, Permission, Admin, Active, Notification)          │
└─────────────────────────────────────────────────────────────────┘
                                │
┌─────────────────────────────────────────────────────────────────┐
│                         SERVICE LAYER                            │
│  StockService │ FinanceService │ AccountingService │ AuditLogService │
└─────────────────────────────────────────────────────────────────┘
                                │
┌─────────────────────────────────────────────────────────────────┐
│                          DATA LAYER                              │
│              Eloquent ORM + MySQL 8 (XAMPP)                      │
│                      33+ Database Tables                         │
└─────────────────────────────────────────────────────────────────┘
```

---

## 📦 Modul & Fitur

### 1. 🔐 Authentication & User Management


| Fitur                | Status | Deskripsi                             |
| -------------------- | ------ | ------------------------------------- |
| Login/Logout         | ✅     | Autentikasi via Laravel Breeze        |
| User Registration    | ✅     | Pendaftaran user baru                 |
| Password Reset       | ✅     | Reset password via email              |
| Profile Management   | ✅     | Edit profil dan password              |
| Role-based Access    | ✅     | Role: admin, staff                    |
| Granular Permissions | ✅     | Per-modul: view, create, edit, delete |
| User CRUD (Admin)    | ✅     | Manajemen user oleh admin             |

**Permission Matrix:**

```
┌────────────────┬──────┬────────┬──────┬────────┐
│ Module         │ View │ Create │ Edit │ Delete │
├────────────────┼──────┼────────┼──────┼────────┤
│ Clients        │  ✓   │   ✓    │  ✓   │   ✓    │
│ Warehouses     │  ✓   │   ✓    │  ✓   │   ✓    │
│ Suppliers      │  ✓   │   ✓    │  ✓   │   ✓    │
│ Projects       │  ✓   │   ✓    │  ✓   │   ✓    │
│ Inventory      │  ✓   │   ✓    │  ✓   │   ✓    │
│ Manufacturing  │  ✓   │   ✓    │  ✓   │   ✓    │
│ Sales          │  ✓   │   ✓    │  ✓   │   ✓    │
│ Purchasing     │  ✓   │   ✓    │  ✓   │   ✓    │
│ Reports        │  ✓   │   -    │  -   │   -    │
└────────────────┴──────┴────────┴──────┴────────┘
```

---

### 2. 👥 CRM - Client Management


| Fitur               | Status | Deskripsi                    |
| ------------------- | ------ | ---------------------------- |
| Client CRUD         | ✅     | Tambah, edit, hapus client   |
| Contact Info        | ✅     | Nama, email, telepon, alamat |
| Client Status       | ✅     | Active/Inactive              |
| Search & Filter     | ✅     | Pencarian nama, email        |
| Client-Project Link | ✅     | Relasi client ke project     |
| Client-Sales Link   | ✅     | Relasi client ke sales order |

---

### 3. 🏭 Master Data

#### 3.1 Warehouses (Gudang)


| Fitur               | Status | Deskripsi                |
| ------------------- | ------ | ------------------------ |
| Warehouse CRUD      | ✅     | Multi-warehouse support  |
| Location Info       | ✅     | Nama, kode, alamat       |
| Active/Inactive     | ✅     | Status gudang            |
| Per-warehouse Stock | ✅     | Stok terpisah per gudang |

#### 3.2 Suppliers (Pemasok)


| Fitur           | Status | Deskripsi                   |
| --------------- | ------ | --------------------------- |
| Supplier CRUD   | ✅     | Manajemen pemasok           |
| Contact Details | ✅     | Telepon, email, alamat, PIC |
| Supplier Status | ✅     | Active/Inactive             |
| Link to PO      | ✅     | Relasi ke Purchase Order    |

#### 3.3 Categories (Kategori Produk)


| Fitur         | Status | Deskripsi                  |
| ------------- | ------ | -------------------------- |
| Category CRUD | ✅     | Kategori produk            |
| Hierarchical  | ❌     | Belum support sub-kategori |
| Product Count | ✅     | Jumlah produk per kategori |

---

### 4. 📦 Inventory Management

#### 4.1 Products (Produk)


| Fitur               | Status | Deskripsi                    |
| ------------------- | ------ | ---------------------------- |
| Product CRUD        | ✅     | SKU, nama, deskripsi         |
| Category Assignment | ✅     | Link ke kategori             |
| Unit of Measure     | ✅     | Satuan (pcs, kg, meter, dll) |
| Pricing             | ✅     | Harga beli & jual            |
| Min Stock Alert     | ✅     | Threshold stok minimum       |
| Active/Inactive     | ✅     | Status produk                |
| Product Images      | ❌     | Belum tersedia               |

#### 4.2 Stock Management


| Fitur                 | Status | Deskripsi                    |
| --------------------- | ------ | ---------------------------- |
| Multi-warehouse Stock | ✅     | Stok per gudang              |
| Stock Overview        | ✅     | Ringkasan stok semua produk  |
| Low Stock Alert       | ✅     | Notifikasi stok rendah       |
| Stock Valuation       | ⚠️   | Kalkulasi nilai stok (basic) |

#### 4.3 Stock Movements


| Fitur              | Status | Deskripsi              |
| ------------------ | ------ | ---------------------- |
| Stock In           | ✅     | Penerimaan barang      |
| Stock Out          | ✅     | Pengeluaran barang     |
| Adjustment         | ✅     | Penyesuaian stok       |
| Movement History   | ✅     | Riwayat pergerakan     |
| Reference Tracking | ✅     | Link ke SO/PO/MO       |
| Undo Movement      | ❌     | Belum bisa membatalkan |

#### 4.4 Stock Transfers


| Fitur            | Status | Deskripsi                      |
| ---------------- | ------ | ------------------------------ |
| Create Transfer  | ✅     | Request transfer antar gudang  |
| Execute Transfer | ✅     | Eksekusi transfer              |
| Cancel Transfer  | ✅     | Batalkan transfer pending      |
| Transfer Status  | ✅     | Pending → Completed/Cancelled |
| Transfer History | ✅     | Riwayat transfer               |

---

### 5. 🏭 Manufacturing (Produksi)

#### 5.1 Bill of Materials (BOM)


| Fitur           | Status | Deskripsi                    |
| --------------- | ------ | ---------------------------- |
| BOM CRUD        | ✅     | Resep produksi               |
| Multi-level BOM | ❌     | Belum support BOM bertingkat |
| Component Items | ✅     | Daftar bahan baku            |
| Output Product  | ✅     | Produk hasil                 |
| Output Quantity | ✅     | Qty output per batch         |
| Active/Inactive | ✅     | Status BOM                   |

#### 5.2 Manufacturing Orders (Work Orders)


| Fitur                | Status | Deskripsi                    |
| -------------------- | ------ | ---------------------------- |
| MO CRUD              | ✅     | Perintah produksi            |
| BOM Selection        | ✅     | Pilih BOM untuk produksi     |
| Planned Quantity     | ✅     | Target jumlah produksi       |
| Warehouse Assignment | ✅     | Gudang produksi              |
| Project Link         | ✅     | Link ke project (optional)   |
| Priority Level       | ✅     | Low, Normal, High, Urgent    |
| Scheduled Dates      | ✅     | Tanggal mulai & selesai      |
| **Workflow Status**  |        |                              |
| - Draft              | ✅     | Order baru dibuat            |
| - Confirmed          | ✅     | Order dikonfirmasi           |
| - In Progress        | ✅     | Sedang diproduksi            |
| - Done               | ✅     | Selesai                      |
| - Cancelled          | ✅     | Dibatalkan                   |
| Progress Tracking    | ✅     | % progress produksi          |
| Material Consumption | ✅     | Auto consume bahan baku      |
| Finished Goods       | ✅     | Auto stock in hasil produksi |
| Partial Production   | ✅     | Produksi bertahap            |

---

### 6. 💰 Sales Management

#### 6.1 Sales Orders


| Fitur                | Status | Deskripsi                  |
| -------------------- | ------ | -------------------------- |
| SO CRUD              | ✅     | Order penjualan            |
| Client Selection     | ✅     | Pilih client               |
| Multi-item Order     | ✅     | Multiple line items        |
| Auto Pricing         | ✅     | Harga dari master produk   |
| Qty & Discount       | ✅     | Per-item quantity & diskon |
| Tax Calculation      | ✅     | Kalkulasi pajak            |
| Order Notes          | ✅     | Catatan order              |
| Warehouse Selection  | ✅     | Gudang pengiriman          |
| Project Link         | ✅     | Link ke project (optional) |
| **Workflow Status**  |        |                            |
| - Draft              | ✅     | Order baru                 |
| - Confirmed          | ✅     | Stock dideduct otomatis    |
| - Processing         | ✅     | Dalam proses               |
| - Shipped            | ✅     | Sudah dikirim              |
| - Completed          | ✅     | Selesai                    |
| - Cancelled          | ✅     | Dibatalkan                 |
| Stock Validation     | ✅     | Cek ketersediaan stok      |
| Auto Stock Deduction | ✅     | Kurangi stok saat confirm  |
| Created By Tracking  | ✅     | Tracking pembuat order     |

---

### 7. 🛒 Purchasing Management

#### 7.1 Purchase Orders


| Fitur               | Status | Deskripsi                   |
| ------------------- | ------ | --------------------------- |
| PO CRUD             | ✅     | Order pembelian             |
| Supplier Selection  | ✅     | Pilih supplier              |
| Multi-item Order    | ✅     | Multiple line items         |
| Cost & Qty          | ✅     | Harga beli & quantity       |
| Tax & Discount      | ✅     | Kalkulasi pajak & diskon    |
| Expected Date       | ✅     | Tanggal kedatangan          |
| Warehouse Selection | ✅     | Gudang penerimaan           |
| Project Link        | ✅     | Link ke project (optional)  |
| **Workflow Status** |        |                             |
| - Draft             | ✅     | PO baru                     |
| - Confirmed         | ✅     | PO dikonfirmasi             |
| - Partial           | ✅     | Sebagian diterima           |
| - Received          | ✅     | Semua diterima              |
| - Cancelled         | ✅     | Dibatalkan                  |
| Partial Receiving   | ✅     | Terima barang bertahap      |
| Auto Stock In       | ✅     | Tambah stok saat terima     |
| Receive Tracking    | ✅     | Track qty diterima per item |

---

### 8. 📊 Projects Management


| Fitur              | Status | Deskripsi                      |
| ------------------ | ------ | ------------------------------ |
| Project CRUD       | ✅     | Manajemen project              |
| Client Assignment  | ✅     | Link ke client                 |
| Manager Assignment | ✅     | Project manager                |
| Budget Tracking    | ✅     | Budget project                 |
| Timeline           | ✅     | Start & end date               |
| Project Status     | ✅     | Planning, Active, On Hold, etc |
| Link to Orders     | ✅     | Relasi ke SO/PO/MO             |
| Project Progress   | ❌     | Belum ada % tracking           |
| Task Management    | ❌     | Belum ada task breakdown       |

---

### 9. 💵 Finance (Keuangan)

#### 10.1 Invoice (Tagihan)


| Fitur                    | Status | Deskripsi                                         |
| ------------------------ | ------ | ------------------------------------------------- |
| Generate Invoice dari SO | ✅     | Buat invoice langsung dari Sales Order            |
| Invoice CRUD             | ✅     | Buat, lihat, batalkan invoice                     |
| Invoice Status           | ✅     | Draft → Sent → Partial → Paid → Cancelled     |
| Line Items               | ✅     | Detail item dari Sales Order                      |
| Subtotal / Tax / Total   | ✅     | Kalkulasi otomatis                                |
| Nomor Invoice Otomatis   | ✅     | Format INV-YYYY-XXXX                              |
| Due Date                 | ✅     | Tanggal jatuh tempo                               |
| Auto Journal Entry       | ✅     | Jurnal akuntansi otomatis (Piutang × Pendapatan) |

#### 10.2 Payments (Pembayaran)


| Fitur               | Status | Deskripsi                        |
| ------------------- | ------ | -------------------------------- |
| Record Payment      | ✅     | Catat pembayaran dari customer   |
| Payment Methods     | ✅     | Cash, transfer, cek              |
| Partial Payment     | ✅     | Pembayaran sebagian              |
| Payment Reference   | ✅     | Nomor referensi transaksi        |
| Auto Invoice Update | ✅     | Update status invoice otomatis   |
| Auto Journal Entry  | ✅     | Jurnal otomatis (Kas × Piutang) |

---

### 10. 📒 Accounting (Akuntansi)

#### 11.1 Chart of Accounts (Daftar Akun)


| Fitur           | Status | Deskripsi                                  |
| --------------- | ------ | ------------------------------------------ |
| CoA CRUD        | ✅     | Buat, edit, hapus akun                     |
| Tipe Akun       | ✅     | Asset, Liability, Equity, Revenue, Expense |
| Kode Akun       | ✅     | Kode unik per akun                         |
| Active/Inactive | ✅     | Status akun                                |

#### 11.2 Journal Entries (Jurnal Umum)


| Fitur               | Status | Deskripsi                              |
| ------------------- | ------ | -------------------------------------- |
| Manual Journal      | ✅     | Input jurnal manual                    |
| Double-entry        | ✅     | Debet = Kredit wajib seimbang          |
| Multi-line Items    | ✅     | Multiple debit/kredit per jurnal       |
| Tanggal & Referensi | ✅     | Date & reference number                |
| Auto Journal        | ✅     | Dibuat otomatis dari Invoice & Payment |
| Jurnal Detail       | ✅     | View detail transaksi per jurnal       |

#### 11.3 General Ledger (Buku Besar)


| Fitur           | Status | Deskripsi                     |
| --------------- | ------ | ----------------------------- |
| Ledger per Akun | ✅     | Riwayat transaksi per akun    |
| Saldo Berjalan  | ✅     | Running balance per transaksi |
| Filter Tanggal  | ✅     | Period filter                 |
| Filter Akun     | ✅     | Pilih akun yang ditampilkan   |

#### 11.4 Trial Balance (Neraca Saldo)


| Fitur                | Status | Deskripsi                  |
| -------------------- | ------ | -------------------------- |
| Trial Balance        | ✅     | Ringkasan saldo semua akun |
| Total Debet = Kredit | ✅     | Verifikasi keseimbangan    |
| Filter Periode       | ✅     | Filter berdasarkan tanggal |

#### 11.5 Balance Sheet (Neraca Keuangan)


| Fitur          | Status | Deskripsi                          |
| -------------- | ------ | ---------------------------------- |
| Assets         | ✅     | Total aset (current + non-current) |
| Liabilities    | ✅     | Total kewajiban                    |
| Equity         | ✅     | Modal / ekuitas                    |
| Balance Check  | ✅     | Assets = Liabilities + Equity      |
| Filter Tanggal | ✅     | Per tanggal laporan                |

#### 11.6 Profit & Loss (Laporan Laba Rugi)


| Fitur           | Status | Deskripsi                    |
| --------------- | ------ | ---------------------------- |
| Revenue         | ✅     | Total pendapatan per akun    |
| Expenses        | ✅     | Total beban per akun         |
| Gross Profit    | ✅     | Laba kotor                   |
| Net Profit/Loss | ✅     | Laba/rugi bersih             |
| Filter Periode  | ✅     | Filter tanggal mulai & akhir |

---

### 11. 📈 Reports & Analytics


| Report               | Status | Deskripsi                    |
| -------------------- | ------ | ---------------------------- |
| Sales Report         | ✅     | Revenue, order count, trends |
| Purchasing Report    | ✅     | Spending, supplier analysis  |
| Inventory Report     | ✅     | Stock levels, valuation      |
| Manufacturing Report | ✅     | Production statistics        |
| Date Range Filter    | ✅     | Filter per periode           |
| CSV Export           | ✅     | Export semua report ke CSV   |
| PDF Export           | ❌     | Belum tersedia               |
| Chart Visualization  | ⚠️   | Basic (tabel)                |

---

### 12. 🔔 Notifications


| Notification          | Status | Trigger              |
| --------------------- | ------ | -------------------- |
| Low Stock Alert       | ✅     | Stok < min_stock     |
| Sales Order Confirmed | ✅     | SO dikonfirmasi      |
| PO Fully Received     | ✅     | PO selesai diterima  |
| MO Completed          | ✅     | MO selesai produksi  |
| Mark as Read          | ✅     | Per-notifikasi       |
| Mark All as Read      | ✅     | Batch action         |
| Email Notifications   | ❌     | Belum diimplementasi |
| Push Notifications    | ❌     | Belum diimplementasi |

---

### 13. ⚙️ Settings (Admin Only)


| Setting         | Status | Deskripsi                |
| --------------- | ------ | ------------------------ |
| Company Info    | ✅     | Nama, alamat, telepon    |
| Currency        | ✅     | Mata uang default        |
| Tax Rate        | ✅     | Persentase pajak default |
| User Management | ✅     | CRUD users & permissions |

---

## 🔄 Business Flow Diagrams

### Flow 1: Sales Order to Delivery

```
┌──────────┐     ┌──────────┐     ┌──────────┐     ┌──────────┐     ┌──────────┐
│  CREATE  │────▶│ CONFIRM  │────▶│ PROCESS  │────▶│   SHIP   │────▶│ COMPLETE │
│   DRAFT  │     │  (Stock  │     │          │     │          │     │          │
│          │     │ Deducted)│     │          │     │          │     │          │
└──────────┘     └──────────┘     └──────────┘     └──────────┘     └──────────┘
                      │
                      ▼
              ┌──────────────┐
              │ NOTIFICATION │
              │   Sent to    │
              │    Admin     │
              └──────────────┘
```

### Flow 2: Purchase Order to Stock

```
┌──────────┐     ┌──────────┐     ┌──────────┐     ┌──────────┐
│  CREATE  │────▶│ CONFIRM  │────▶│ RECEIVE  │────▶│ COMPLETE │
│   DRAFT  │     │          │     │ (Stock   │     │          │
│          │     │          │     │  Added)  │     │          │
└──────────┘     └──────────┘     └──────────┘     └──────────┘
                                       │
                      ┌────────────────┴────────────────┐
                      ▼                                 ▼
              ┌──────────────┐                  ┌──────────────┐
              │   PARTIAL    │                  │  FULL RECV   │
              │   RECEIVE    │                  │ NOTIFICATION │
              └──────────────┘                  └──────────────┘
```

### Flow 3: Manufacturing Order

```
┌──────────┐     ┌──────────┐     ┌──────────┐     ┌──────────┐
│  CREATE  │────▶│ CONFIRM  │────▶│ PRODUCE  │────▶│   DONE   │
│   DRAFT  │     │          │     │          │     │          │
└──────────┘     └──────────┘     └──────────┘     └──────────┘
                                       │
                                       ▼
                              ┌─────────────────┐
                              │  CONSUME RAW    │
                              │  MATERIALS      │
                              │  (Stock Out)    │
                              └────────┬────────┘
                                       │
                                       ▼
                              ┌─────────────────┐
                              │  PRODUCE        │
                              │  FINISHED GOODS │
                              │  (Stock In)     │
                              └────────┬────────┘
                                       │
                    ┌──────────────────┴──────────────────┐
                    ▼                                      ▼
            ┌──────────────┐                       ┌──────────────┐
            │   PARTIAL    │                       │  COMPLETION  │
            │  PRODUCTION  │                       │ NOTIFICATION │
            └──────────────┘                       └──────────────┘
```

### Flow 4: Stock Movement Integration

```
                        ┌─────────────────┐
                        │  STOCK SERVICE  │
                        │  (Central Hub)  │
                        └────────┬────────┘
                                 │
        ┌────────────────────────┼────────────────────────┐
        │                        │                        │
        ▼                        ▼                        ▼
┌──────────────┐        ┌──────────────┐        ┌──────────────┐
│ SALES ORDER  │        │ PURCHASE ORD │        │ MANUFACT ORD │
│   CONFIRM    │        │   RECEIVE    │        │   PRODUCE    │
│  (Stock OUT) │        │  (Stock IN)  │        │ (OUT + IN)   │
└──────────────┘        └──────────────┘        └──────────────┘
        │                        │                        │
        └────────────────────────┼────────────────────────┘
                                 ▼
                        ┌─────────────────┐
                        │  LOW STOCK      │
                        │  CHECK &        │
                        │  NOTIFICATION   │
                        └─────────────────┘
```

### Flow 5: Finance & Accounting

```
Sales Order ──► DELIVER ──► INVOICE ──► PAYMENT
                                │           │
                                ▼           ▼
                        ┌─────────────────────────────┐
                        │       ACCOUNTING SERVICE     │
                        │  (Double-Entry Journal Auto) │
                        └────────────┬────────────────┘
                                     │
             ┌───────────────────────┼────────────────────────┐
             │                       │                        │
             ▼                       ▼                        ▼
    ┌─────────────┐         ┌──────────────┐        ┌──────────────┐
    │  JOURNAL    │         │   GENERAL    │        │    TRIAL     │
    │  ENTRIES    │         │   LEDGER     │        │   BALANCE    │
    └─────────────┘         └──────────────┘        └──────────────┘
                                                           │
                              ┌────────────────────────────┤
                              │                            │
                              ▼                            ▼
                     ┌──────────────┐            ┌──────────────────┐
                     │   BALANCE    │            │   PROFIT & LOSS  │
                     │    SHEET     │            │     STATEMENT    │
                     └──────────────┘            └──────────────────┘
```

---

## ✅ Kelebihan Sistem

### 1. **Integrasi End-to-End**

- Semua modul terintegrasi (Sales ↔ Inventory ↔ Manufacturing ↔ Purchasing)
- Stock movement otomatis berdasarkan transaksi
- Tidak perlu input manual untuk update stok

### 2. **Real-time Inventory**

- Multi-warehouse support
- Tracking stok per gudang
- Low stock notification otomatis
- Stock transfer antar gudang

### 3. **Manufacturing Support**

- Bill of Materials (BOM)
- Auto material consumption
- Partial production support
- Progress tracking

### 4. **Security & Access Control**

- Role-based access (Admin/User)
- Granular permissions per modul per action
- Audit trail (created_by tracking)

### 5. **Akuntansi Berentri Ganda (Double-Entry Accounting)**

- Chart of Accounts lengkap (Asset, Liability, Equity, Revenue, Expense)
- Jurnal otomatis dari Invoice & Payment
- General Ledger dengan running balance
- Trial Balance, Balance Sheet, Profit & Loss Statement

### 6. **Modern Tech Stack**

- Laravel 13 (latest)
- TailwindCSS 4 (modern UI)
- MySQL (production ready)
- Clean, maintainable codebase

### 7. **Responsive UI**

- Mobile-friendly design
- Konsisten design system
- Intuitive navigation

---

## ❌ Kekurangan & Limitasi Saat Ini

### 1. **Fitur Belum Lengkap**


| Gap                     | Priority | Deskripsi                            |
| ----------------------- | -------- | ------------------------------------ |
| Multi-level BOM         | High     | Tidak support BOM bertingkat         |
| Product Images          | Medium   | Belum ada upload gambar produk       |
| PDF Export              | Medium   | Report hanya tersedia dalam CSV      |
| Email Notification      | Medium   | Notifikasi hanya via database        |
| Hierarchical Categories | Low      | Tidak ada sub-kategori produk        |
| Project Task Management | Medium   | Tidak ada task breakdown per project |
| Delivery Management     | Medium   | Tidak ada tracking pengiriman        |
| Return / Refund         | Medium   | Tidak ada proses retur barang        |

### 2. **Akuntansi Lanjutan**


| Gap                   | Priority | Deskripsi                              |
| --------------------- | -------- | -------------------------------------- |
| Accounts Payable (AP) | Medium   | Belum ada managemen hutang ke supplier |
| Multi-currency        | Low      | Hanya mendukung satu mata uang         |
| Depreciation          | Low      | Belum ada penyusutan aset tetap        |
| Tax Filing Reports    | Low      | Belum ada laporan pajak (PPN/PPh)      |

### 3. **Reporting & Analytics**


| Gap              | Priority | Deskripsi                               |
| ---------------- | -------- | --------------------------------------- |
| Dashboard Charts | Medium   | Dashboard masih tabel, belum ada grafik |
| Custom Reports   | Low      | Tidak bisa kustomisasi format laporan   |
| Forecasting      | Low      | Tidak ada prediksi demand / kebutuhan   |

### 4. **Technical Debt**


| Issue              | Priority | Deskripsi                                |
| ------------------ | -------- | ---------------------------------------- |
| No Unit Tests      | High     | Belum ada automated testing              |
| No REST API        | Medium   | Tidak ada API endpoint untuk integrasi   |
| Limited Validation | Medium   | Validasi bisnis belum sepenuhnya lengkap |

---

## 🗺️ Roadmap Pengembangan

### ✅ Phase 1: Core ERP (SELESAI)

- Manajemen Inventori (produk, gudang, stok, transfer)
- Manufacturing (BOM, Work Orders, material consumption)
- Sales & Purchase Order lifecycle
- CRM (Clients, Projects)
- Multi-warehouse support
- Notification system
- Role-based permissions & Audit Log

### ✅ Phase 2: Finance & Accounting (SELESAI)

- **Invoice Generation** — dari Sales Order ke Invoice
- **Payment Tracking** — record pembayaran dan update AR
- **Chart of Accounts** — daftar akun lengkap
- **Double-Entry Journal** — manual & otomatis
- **General Ledger** — buku besar dengan running balance
- **Trial Balance** — neraca saldo
- **Balance Sheet** — neraca keuangan
- **Profit & Loss Statement** — laporan laba rugi

### 🔄 Phase 3: Operational Enhancement (Rekomendasi)

1. **Delivery Management**

   - Delivery order generation
   - Shipping/tracking number
   - Proof of delivery
2. **Return & Refund**

   - Sales return (Credit Note)
   - Purchase return (Debit Note)
   - Stock adjustment otomatis
3. **Email Notifications**

   - SMTP configuration
   - Email templates per event
   - Notification preferences per user
4. **PDF Reports**

   - Invoice PDF (printable)
   - Financial report PDF
   - Custom templates

### 🔮 Phase 4: Analytics & Integration (Masa Depan)

1. **Advanced Dashboard**

   - Chart visualization (Chart.js / ApexCharts)
   - KPI widgets
   - Real-time updates
2. **REST API**

   - API endpoints for all modules
   - Sanctum API authentication
   - Webhook support
3. **Multi-level BOM**

   - Sub-assembly support
   - Auto Material Requirements Planning (MRP)
4. **Accounts Payable (AP)**

   - Hutang ke supplier
   - Aging report AP
   - Payment scheduling

---

## 🛠️ Technical Stack


| Component        | Technology     | Version           |
| ---------------- | -------------- | ----------------- |
| Framework        | Laravel        | 13.2.x            |
| Language         | PHP            | 8.3+              |
| Database         | MySQL          | 8.x (XAMPP)       |
| CSS Framework    | TailwindCSS    | 4.x (Vite Plugin) |
| JS Framework     | Alpine.js      | 3.x               |
| Build Tool       | Vite           | 8.x               |
| Auth Scaffolding | Laravel Breeze | 2.x               |
| HTTP Client      | Axios          | 1.x               |

### Key Service Classes


| Service             | Tanggung Jawab                                                   |
| ------------------- | ---------------------------------------------------------------- |
| `StockService`      | Manajemen stok, movement, validasi inventori                     |
| `FinanceService`    | Pembuatan invoice, pencatatan pembayaran, pembatalan             |
| `AccountingService` | Jurnal berentri ganda, ledger, trial balance, balance sheet, P&L |
| `AuditLogService`   | Pencatatan log aktivitas sistem                                  |

---

## 📁 Struktur Database

### Entity Relationship (Simplified)

```
Users ─────────────┬──────────────────────────────┐
                   │                              │
                   ▼                              ▼
              Projects ◄────────────────── Clients
                   │
    ┌──────────────┼──────────────┐
    │              │              │
    ▼              ▼              ▼
Sales Orders  Purchase Orders  Manufacturing Orders
    │              │              │
    ▼              ▼              ▼
SO Items       PO Items       BOM ──► BOM Items
    │              │              │
    └──────────────┼──────────────┘
                   │
                   ▼
              Products ◄──────── Categories
                   │
                   ▼
          Inventory Stocks ◄──── Warehouses
                   │
                   ▼
           Stock Movements ◄──── Stock Transfers

Sales Orders ──► Invoices ──► Payments
                    │              │
                    └──────┬───────┘
                           ▼
                    Journal Entries ──► Journal Items
                           │
                    Charts of Accounts
```

### Tabel Database (33+ Tables)


| Table                 | Deskripsi                                |
| --------------------- | ---------------------------------------- |
| users                 | User, role, dan JSON permissions         |
| clients               | Data customer/client                     |
| suppliers             | Data vendor/pemasok                      |
| categories            | Kategori produk                          |
| products              | Master produk (SKU, harga, stok minimum) |
| warehouses            | Lokasi gudang                            |
| inventory_stocks      | Stok per produk per gudang               |
| stock_movements       | Log pergerakan stok                      |
| stock_transfers       | Transfer stok antar gudang               |
| stock_transfer_items  | Item per transfer                        |
| projects              | Manajemen proyek                         |
| sales_orders          | Header sales order                       |
| sales_order_items     | Line item sales order                    |
| purchase_orders       | Header purchase order                    |
| purchase_order_items  | Line item purchase order                 |
| bill_of_materials     | Header BOM                               |
| bom_items             | Komponen BOM                             |
| manufacturing_orders  | Work order produksi                      |
| invoices              | Invoice/tagihan ke customer              |
| invoice_items         | Line item invoice                        |
| payments              | Pembayaran dari customer                 |
| charts_of_accounts    | Daftar akun akuntansi                    |
| journal_entries       | Header jurnal akuntansi                  |
| journal_items         | Baris debit/kredit jurnal                |
| activity_log          | Audit log seluruh aktivitas sistem       |
| settings              | Konfigurasi aplikasi                     |
| notifications         | Notifikasi in-app                        |
| sessions              | Sesi pengguna                            |
| cache                 | Cache aplikasi                           |
| jobs                  | Antrian pekerjaan                        |
| failed_jobs           | Pekerjaan gagal                          |
| job_batches           | Batch pekerjaan                          |
| password_reset_tokens | Token reset password                     |
| migrations            | Riwayat migrasi                          |

---

## 🚀 Instalasi & Setup

### Prerequisites

- PHP 8.3+
- Composer 2.x
- Node.js 20+
- MySQL 8.x (disarankan via XAMPP)
- Git

### Installation Steps

```bash
# 1. Clone repository
git clone https://github.com/your-repo/manerp.git
cd manerp

# 2. Install PHP dependencies
composer install

# 3. Install JS dependencies
npm install

# 4. Environment setup
cp .env.example .env
php artisan key:generate

# 5. Configure database in .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=manerp
DB_USERNAME=root
DB_PASSWORD=

# 6. Run migrations & seeder
php artisan migrate
php artisan db:seed

# 7. Build frontend assets
npm run build

# 8. Start development server
php artisan serve
```

### Default Login

- **Email:** admin@manerp.com
- **Password:** password

---

## 🗂️ Ringkasan Route (145 Routes)


| Prefix                      | Modul               | Metode                 |
| --------------------------- | ------------------- | ---------------------- |
| `/dashboard`                | Dashboard           | GET                    |
| `/clients`                  | CRM – Clients      | GET, POST, PUT, DELETE |
| `/projects`                 | Projects            | GET, POST, PUT, DELETE |
| `/warehouses`               | Gudang              | GET, POST, PUT, DELETE |
| `/suppliers`                | Pemasok             | GET, POST, PUT, DELETE |
| `/inventory/categories`     | Kategori Produk     | GET, POST, PUT, DELETE |
| `/inventory/products`       | Produk              | GET, POST, PUT, DELETE |
| `/inventory/stocks`         | Level Stok          | GET                    |
| `/inventory/movements`      | Pergerakan Stok     | GET, POST              |
| `/inventory/transfers`      | Transfer Stok       | GET, POST, DELETE      |
| `/manufacturing/boms`       | Bill of Materials   | GET, POST, PUT, DELETE |
| `/manufacturing/orders`     | Work Orders         | GET, POST, PUT, DELETE |
| `/sales`                    | Sales Orders        | GET, POST, PUT, DELETE |
| `/purchasing`               | Purchase Orders     | GET, POST, PUT, DELETE |
| `/finance/invoices`         | Invoice             | GET, POST              |
| `/finance/payments`         | Pembayaran          | POST                   |
| `/accounting/coa`           | Chart of Accounts   | GET, POST, PUT, DELETE |
| `/accounting/journals`      | Journal Entries     | GET, POST              |
| `/accounting/ledger`        | General Ledger      | GET                    |
| `/accounting/trial-balance` | Trial Balance       | GET                    |
| `/accounting/balance-sheet` | Balance Sheet       | GET                    |
| `/accounting/profit-loss`   | Profit & Loss       | GET                    |
| `/reports`                  | Laporan Operasional | GET                    |
| `/notifications`            | Notifikasi          | GET, POST              |
| `/settings`                 | Pengaturan Aplikasi | GET, POST              |
| `/settings/users`           | Manajemen User      | GET, POST, PUT, DELETE |
| `/audit-logs`               | Audit Log           | GET                    |

---

## 📝 Catatan untuk Reviewer

### Status Implementasi Terkini:

1. **Akuntansi** — Sistem ini memiliki modul akuntansi berentri ganda lengkap: CoA, Jurnal, Ledger, Trial Balance, Balance Sheet, dan Profit & Loss.
2. **Invoice** — Invoice dibuat otomatis dari Sales Order. Mendukung pembatalan invoice.
3. **Payment** — Pembayaran dari customer dicatat dan otomatis mengupdate status invoice serta membuat jurnal akuntansi.
4. **Audit Log** — Seluruh aktivitas CRUD tercatat via `AuditLogService` dan dapat diakses admin.
5. **Permissions** — Sistem permission granular per modul (view/create/edit/delete) dikelola via JSON column.
6. **Testing** — Belum ada automated test, diperlukan manual testing.

### Pertanyaan untuk Stakeholder:

1. Apakah perlu integrasi dengan sistem akuntansi eksternal (e.g. Accurate, SAP)?
2. Apakah perlu fitur multi-currency?
3. Apakah perlu fitur multi-company/branch?
4. Apakah proses approval workflow diperlukan (misal SO harus disetujui manager)?
5. Bagaimana proses retur/refund di lapangan?
6. Apakah ada kebutuhan mobile app atau REST API?
7. Apakah laporan pajak (PPN/PPh) perlu diakomodasi?

---

## 📞 Kontak & Kontribusi

Untuk pertanyaan, saran, atau kontribusi, silakan buat issue di repository atau hubungi tim pengembang.

---

*Dokumentasi ini diperbarui: Juli 2025*
*Versi Sistem: 1.2.0*
*Total Routes: 145 | Total Modul: 13 | Total Tabel: 33+*
