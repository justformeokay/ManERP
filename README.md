# ManERP - Manufacturing Enterprise Resource Planning

![Laravel](https://img.shields.io/badge/Laravel-12.x-red)
![PHP](https://img.shields.io/badge/PHP-8.4-blue)
![TailwindCSS](https://img.shields.io/badge/TailwindCSS-4.x-38B2AC)
![MySQL](https://img.shields.io/badge/MySQL-8.x-orange)

## 📋 Deskripsi Proyek

**ManERP** adalah sistem Enterprise Resource Planning (ERP) berbasis web yang dirancang khusus untuk industri manufaktur. Sistem ini mengintegrasikan berbagai modul bisnis mulai dari manajemen inventori, produksi, penjualan, pembelian, hingga pelaporan dalam satu platform terpadu.

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
│    Blade Templates + TailwindCSS 4 + Alpine.js + Vite 8         │
└─────────────────────────────────────────────────────────────────┘
                                │
┌─────────────────────────────────────────────────────────────────┐
│                        APPLICATION LAYER                         │
│              Laravel 12.x Controllers + Middleware               │
│         (Auth, Permission, Admin, Notification)                  │
└─────────────────────────────────────────────────────────────────┘
                                │
┌─────────────────────────────────────────────────────────────────┐
│                         SERVICE LAYER                            │
│                    StockService (Inventory)                      │
└─────────────────────────────────────────────────────────────────┘
                                │
┌─────────────────────────────────────────────────────────────────┐
│                          DATA LAYER                              │
│           Eloquent ORM + MySQL 8 / SQLite (dev)                  │
│                      27 Database Tables                          │
└─────────────────────────────────────────────────────────────────┘
```

---

## 📦 Modul & Fitur

### 1. 🔐 Authentication & User Management
| Fitur | Status | Deskripsi |
|-------|--------|-----------|
| Login/Logout | ✅ | Autentikasi via Laravel Breeze |
| User Registration | ✅ | Pendaftaran user baru |
| Password Reset | ✅ | Reset password via email |
| Profile Management | ✅ | Edit profil dan password |
| Role-based Access | ✅ | Role: admin, user |
| Granular Permissions | ✅ | Per-modul: view, create, edit, delete |
| User CRUD (Admin) | ✅ | Manajemen user oleh admin |

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
| Fitur | Status | Deskripsi |
|-------|--------|-----------|
| Client CRUD | ✅ | Tambah, edit, hapus client |
| Contact Info | ✅ | Nama, email, telepon, alamat |
| Client Status | ✅ | Active/Inactive |
| Search & Filter | ✅ | Pencarian nama, email |
| Client-Project Link | ✅ | Relasi client ke project |
| Client-Sales Link | ✅ | Relasi client ke sales order |

---

### 3. 🏭 Master Data

#### 3.1 Warehouses (Gudang)
| Fitur | Status | Deskripsi |
|-------|--------|-----------|
| Warehouse CRUD | ✅ | Multi-warehouse support |
| Location Info | ✅ | Nama, kode, alamat |
| Active/Inactive | ✅ | Status gudang |
| Per-warehouse Stock | ✅ | Stok terpisah per gudang |

#### 3.2 Suppliers (Pemasok)
| Fitur | Status | Deskripsi |
|-------|--------|-----------|
| Supplier CRUD | ✅ | Manajemen pemasok |
| Contact Details | ✅ | Telepon, email, alamat, PIC |
| Supplier Status | ✅ | Active/Inactive |
| Link to PO | ✅ | Relasi ke Purchase Order |

#### 3.3 Categories (Kategori Produk)
| Fitur | Status | Deskripsi |
|-------|--------|-----------|
| Category CRUD | ✅ | Kategori produk |
| Hierarchical | ❌ | Belum support sub-kategori |
| Product Count | ✅ | Jumlah produk per kategori |

---

### 4. 📦 Inventory Management

#### 4.1 Products (Produk)
| Fitur | Status | Deskripsi |
|-------|--------|-----------|
| Product CRUD | ✅ | SKU, nama, deskripsi |
| Category Assignment | ✅ | Link ke kategori |
| Unit of Measure | ✅ | Satuan (pcs, kg, meter, dll) |
| Pricing | ✅ | Harga beli & jual |
| Min Stock Alert | ✅ | Threshold stok minimum |
| Active/Inactive | ✅ | Status produk |
| Product Images | ❌ | Belum tersedia |

#### 4.2 Stock Management
| Fitur | Status | Deskripsi |
|-------|--------|-----------|
| Multi-warehouse Stock | ✅ | Stok per gudang |
| Stock Overview | ✅ | Ringkasan stok semua produk |
| Low Stock Alert | ✅ | Notifikasi stok rendah |
| Stock Valuation | ⚠️ | Kalkulasi nilai stok (basic) |

#### 4.3 Stock Movements
| Fitur | Status | Deskripsi |
|-------|--------|-----------|
| Stock In | ✅ | Penerimaan barang |
| Stock Out | ✅ | Pengeluaran barang |
| Adjustment | ✅ | Penyesuaian stok |
| Movement History | ✅ | Riwayat pergerakan |
| Reference Tracking | ✅ | Link ke SO/PO/MO |
| Undo Movement | ❌ | Belum bisa membatalkan |

#### 4.4 Stock Transfers
| Fitur | Status | Deskripsi |
|-------|--------|-----------|
| Create Transfer | ✅ | Request transfer antar gudang |
| Execute Transfer | ✅ | Eksekusi transfer |
| Cancel Transfer | ✅ | Batalkan transfer pending |
| Transfer Status | ✅ | Pending → Completed/Cancelled |
| Transfer History | ✅ | Riwayat transfer |

---

### 5. �icing Manufacturing (Produksi)

#### 5.1 Bill of Materials (BOM)
| Fitur | Status | Deskripsi |
|-------|--------|-----------|
| BOM CRUD | ✅ | Resep produksi |
| Multi-level BOM | ❌ | Belum support BOM bertingkat |
| Component Items | ✅ | Daftar bahan baku |
| Output Product | ✅ | Produk hasil |
| Output Quantity | ✅ | Qty output per batch |
| Active/Inactive | ✅ | Status BOM |

#### 5.2 Manufacturing Orders (Work Orders)
| Fitur | Status | Deskripsi |
|-------|--------|-----------|
| MO CRUD | ✅ | Perintah produksi |
| BOM Selection | ✅ | Pilih BOM untuk produksi |
| Planned Quantity | ✅ | Target jumlah produksi |
| Warehouse Assignment | ✅ | Gudang produksi |
| Project Link | ✅ | Link ke project (optional) |
| Priority Level | ✅ | Low, Normal, High, Urgent |
| Scheduled Dates | ✅ | Tanggal mulai & selesai |
| **Workflow Status** | | |
| - Draft | ✅ | Order baru dibuat |
| - Confirmed | ✅ | Order dikonfirmasi |
| - In Progress | ✅ | Sedang diproduksi |
| - Done | ✅ | Selesai |
| - Cancelled | ✅ | Dibatalkan |
| Progress Tracking | ✅ | % progress produksi |
| Material Consumption | ✅ | Auto consume bahan baku |
| Finished Goods | ✅ | Auto stock in hasil produksi |
| Partial Production | ✅ | Produksi bertahap |

---

### 6. 💰 Sales Management

#### 6.1 Sales Orders
| Fitur | Status | Deskripsi |
|-------|--------|-----------|
| SO CRUD | ✅ | Order penjualan |
| Client Selection | ✅ | Pilih client |
| Multi-item Order | ✅ | Multiple line items |
| Auto Pricing | ✅ | Harga dari master produk |
| Qty & Discount | ✅ | Per-item quantity & diskon |
| Tax Calculation | ✅ | Kalkulasi pajak |
| Order Notes | ✅ | Catatan order |
| Warehouse Selection | ✅ | Gudang pengiriman |
| Project Link | ✅ | Link ke project (optional) |
| **Workflow Status** | | |
| - Draft | ✅ | Order baru |
| - Confirmed | ✅ | Stock dideduct otomatis |
| - Processing | ✅ | Dalam proses |
| - Shipped | ✅ | Sudah dikirim |
| - Completed | ✅ | Selesai |
| - Cancelled | ✅ | Dibatalkan |
| Stock Validation | ✅ | Cek ketersediaan stok |
| Auto Stock Deduction | ✅ | Kurangi stok saat confirm |
| Created By Tracking | ✅ | Tracking pembuat order |

---

### 7. 🛒 Purchasing Management

#### 7.1 Purchase Orders
| Fitur | Status | Deskripsi |
|-------|--------|-----------|
| PO CRUD | ✅ | Order pembelian |
| Supplier Selection | ✅ | Pilih supplier |
| Multi-item Order | ✅ | Multiple line items |
| Cost & Qty | ✅ | Harga beli & quantity |
| Tax & Discount | ✅ | Kalkulasi pajak & diskon |
| Expected Date | ✅ | Tanggal kedatangan |
| Warehouse Selection | ✅ | Gudang penerimaan |
| Project Link | ✅ | Link ke project (optional) |
| **Workflow Status** | | |
| - Draft | ✅ | PO baru |
| - Confirmed | ✅ | PO dikonfirmasi |
| - Partial | ✅ | Sebagian diterima |
| - Received | ✅ | Semua diterima |
| - Cancelled | ✅ | Dibatalkan |
| Partial Receiving | ✅ | Terima barang bertahap |
| Auto Stock In | ✅ | Tambah stok saat terima |
| Receive Tracking | ✅ | Track qty diterima per item |

---

### 8. 📊 Projects Management
| Fitur | Status | Deskripsi |
|-------|--------|-----------|
| Project CRUD | ✅ | Manajemen project |
| Client Assignment | ✅ | Link ke client |
| Manager Assignment | ✅ | Project manager |
| Budget Tracking | ✅ | Budget project |
| Timeline | ✅ | Start & end date |
| Project Status | ✅ | Planning, Active, On Hold, etc |
| Link to Orders | ✅ | Relasi ke SO/PO/MO |
| Project Progress | ❌ | Belum ada % tracking |
| Task Management | ❌ | Belum ada task breakdown |

---

### 9. 📈 Reports & Analytics

| Report | Status | Deskripsi |
|--------|--------|-----------|
| Sales Report | ✅ | Revenue, order count, trends |
| Purchasing Report | ✅ | Spending, supplier analysis |
| Inventory Report | ✅ | Stock levels, valuation |
| Manufacturing Report | ✅ | Production statistics |
| Date Range Filter | ✅ | Filter per periode |
| CSV Export | ✅ | Export semua report ke CSV |
| PDF Export | ❌ | Belum tersedia |
| Chart Visualization | ⚠️ | Basic (tabel) |

---

### 10. 🔔 Notifications

| Notification | Status | Trigger |
|--------------|--------|---------|
| Low Stock Alert | ✅ | Stok < min_stock |
| Sales Order Confirmed | ✅ | SO dikonfirmasi |
| PO Fully Received | ✅ | PO selesai diterima |
| MO Completed | ✅ | MO selesai produksi |
| Mark as Read | ✅ | Per-notifikasi |
| Mark All as Read | ✅ | Batch action |
| Email Notifications | ❌ | Belum diimplementasi |
| Push Notifications | ❌ | Belum diimplementasi |

---

### 11. ⚙️ Settings (Admin Only)

| Setting | Status | Deskripsi |
|---------|--------|-----------|
| Company Info | ✅ | Nama, alamat, telepon |
| Currency | ✅ | Mata uang default |
| Tax Rate | ✅ | Persentase pajak default |
| User Management | ✅ | CRUD users & permissions |

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

### 5. **Modern Tech Stack**
- Laravel 12 (latest)
- TailwindCSS 4 (modern UI)
- MySQL (production ready)
- Clean, maintainable codebase

### 6. **Responsive UI**
- Mobile-friendly design
- Konsisten design system
- Intuitive navigation

---

## ❌ Kekurangan & Limitasi Saat Ini

### 1. **Fitur Belum Lengkap**
| Gap | Priority | Deskripsi |
|-----|----------|-----------|
| Multi-level BOM | High | Tidak support BOM bertingkat |
| Product Images | Medium | Belum ada upload gambar |
| PDF Export | Medium | Report hanya CSV |
| Email Notification | Medium | Notifikasi hanya database |
| Hierarchical Categories | Low | Tidak ada sub-kategori |
| Project Task Management | Medium | Tidak ada task breakdown |

### 2. **Akuntansi & Keuangan**
| Gap | Priority | Deskripsi |
|-----|----------|-----------|
| General Ledger | High | Tidak ada jurnal akuntansi |
| Invoice Generation | High | Tidak ada pembuatan invoice |
| Payment Tracking | High | Tidak tracking pembayaran |
| Accounts Receivable | High | Tidak ada AR management |
| Accounts Payable | High | Tidak ada AP management |
| Financial Reports | High | Tidak ada laporan keuangan |

### 3. **Operasional**
| Gap | Priority | Deskripsi |
|-----|----------|-----------|
| Delivery/Shipping | Medium | Tidak ada tracking pengiriman |
| Return/Refund | Medium | Tidak ada proses retur |
| Quality Control | Low | Tidak ada QC workflow |
| Barcode/QR | Low | Tidak ada barcode scanning |
| Serial Number | Low | Tidak tracking serial number |

### 4. **Reporting & Analytics**
| Gap | Priority | Deskripsi |
|-----|----------|-----------|
| Dashboard Charts | Medium | Dashboard masih basic |
| Custom Reports | Low | Tidak bisa custom report |
| Forecasting | Low | Tidak ada prediksi demand |

### 5. **Technical Debt**
| Issue | Priority | Deskripsi |
|-------|----------|-----------|
| No Unit Tests | High | Belum ada automated testing |
| No API | Medium | Tidak ada REST API untuk integrasi |
| Limited Validation | Medium | Validasi bisnis belum lengkap |

---

## 🗺️ Roadmap Pengembangan (Rekomendasi)

### Phase 1: Critical Business Features
1. **Invoice & Payment Module**
   - Generate invoice dari SO
   - Payment recording
   - AR/AP aging report

2. **Return & Refund**
   - Sales return (Credit Note)
   - Purchase return (Debit Note)
   - Stock adjustment otomatis

3. **Multi-level BOM**
   - Support sub-assembly
   - Auto-calculate material requirement

### Phase 2: Operational Enhancement
1. **Delivery Management**
   - Delivery order generation
   - Shipping tracking
   - Proof of delivery

2. **Email Notifications**
   - Configure SMTP
   - Email templates
   - Notification preferences

3. **PDF Reports**
   - Invoice PDF
   - Report PDF export
   - Custom templates

### Phase 3: Analytics & Integration
1. **Advanced Dashboard**
   - Chart visualization (Chart.js)
   - KPI widgets
   - Real-time updates

2. **REST API**
   - API endpoints for all modules
   - API authentication
   - Webhook support

3. **Barcode Integration**
   - Product barcode
   - Scan for stock movement
   - Mobile scanning

---

## 🛠️ Technical Stack

| Component | Technology | Version |
|-----------|------------|---------|
| Framework | Laravel | 12.x |
| Language | PHP | 8.4+ |
| Database | MySQL | 8.x |
| CSS | TailwindCSS | 4.x |
| JS | Alpine.js | 3.x |
| Build Tool | Vite | 8.x |
| Auth | Laravel Breeze | 2.x |

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
```

### Tabel Database (27 Tables)
| Table | Deskripsi |
|-------|-----------|
| users | User & authentication |
| clients | Customer data |
| suppliers | Vendor/supplier data |
| categories | Product categories |
| products | Product master |
| warehouses | Warehouse locations |
| inventory_stocks | Stock per product per warehouse |
| stock_movements | Stock transaction log |
| stock_transfers | Inter-warehouse transfers |
| projects | Project management |
| sales_orders | Sales order header |
| sales_order_items | Sales order lines |
| purchase_orders | Purchase order header |
| purchase_order_items | Purchase order lines |
| bill_of_materials | BOM header |
| bom_items | BOM components |
| manufacturing_orders | Work orders |
| settings | Application settings |
| notifications | User notifications |
| sessions | User sessions |
| cache | Application cache |
| jobs | Queue jobs |
| failed_jobs | Failed queue jobs |
| job_batches | Job batches |
| password_reset_tokens | Password reset |
| migrations | Migration history |

---

## 🚀 Instalasi & Setup

### Prerequisites
- PHP 8.4+
- Composer 2.x
- Node.js 20+
- MySQL 8.x atau SQLite
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

# 7. Build assets
npm run build

# 8. Start server
php artisan serve
```

### Default Login
- **Email:** admin@manerp.com
- **Password:** password

---

## 📝 Catatan untuk Reviewer

### Hal yang Perlu Diperhatikan:
1. **Akuntansi** - Sistem ini BUKAN sistem akuntansi. Tidak ada jurnal, GL, atau laporan keuangan.
2. **Invoice** - Tidak ada fitur invoice generation. Sales Order bukan invoice.
3. **Payment** - Tidak ada tracking pembayaran dari customer atau ke supplier.
4. **Delivery** - Tidak ada management pengiriman atau tracking kurir.
5. **Testing** - Belum ada automated test, manual testing diperlukan.

### Pertanyaan untuk Stakeholder:
1. Apakah perlu integrasi dengan sistem akuntansi eksternal?
2. Apakah perlu fitur multi-currency?
3. Apakah perlu fitur multi-company/branch?
4. Apakah proses approval workflow diperlukan?
5. Bagaimana proses retur/refund di lapangan?
6. Apakah ada kebutuhan mobile app?

---

## 📞 Kontak & Kontribusi

Untuk pertanyaan, saran, atau kontribusi, silakan buat issue di repository atau hubungi tim pengembang.

---

*Dokumentasi ini dibuat pada: Maret 2026*
*Versi Sistem: 1.0.0-beta*
