# LAPORAN AUDIT MENDALAM — Modul Penjualan (Sales) ManERP

**Auditor:** Senior ERP Business Analyst & Financial Auditor
**Tanggal:** 5 April 2026
**Cakupan:** 18 file (Controllers, Models, Services, Migrations, Routes)
**Status Sistem:** 147 tests / 566 assertions — ALL PASS

---

## 1. Sales Lifecycle & State Machine

### 1A. Workflow & Transisi Status

State machine didefinisikan di SalesOrder.php melalui trait `HasStateMachine`:

```
draft → [confirmed, cancelled]
confirmed → [processing, shipped, cancelled]
processing → [shipped, cancelled]
partial → [shipped, cancelled]
shipped → [completed]
completed → []
cancelled → []
```

**Catatan Penting:** Tidak ada alur **Quotation** terpisah. Sistem langsung dimulai dari `draft` Sales Order. Status `processing` dan `partial` didefinisikan di state machine namun **tidak ada controller action yang secara otomatis mentransisikan ke status tersebut** — ini hanya placeholder untuk pengembangan partial delivery di masa depan.

Alur aktual saat ini:

```
draft → confirm() → confirmed → deliver() → shipped → invoice() → completed
                         ↓                       ↓
                      cancel()               cancel() [+ stock restore]
```

### 1B. Validasi Saat SO Dikonfirmasi

Di SalesOrderController.php:

1. **State gate** — `requireTransition('confirmed')` memastikan hanya `draft` yang bisa dikonfirmasi
2. **Stock availability check** — Loop setiap item, cek `availableQuantity()` = `quantity - reserved_quantity`
3. **Stock reservation** — Dalam `DB::transaction` dengan `lockForUpdate()`, increment `reserved_quantity` per item
4. **Notifikasi** — Kirim notifikasi ke admin

### 1C. Penanganan Backorder

**FINDING: TIDAK ADA FITUR BACKORDER.** Sistem memblokir konfirmasi sepenuhnya jika stok tidak cukup:

```php
if ($available < $item->quantity) {
    return back()->withErrors(['stock' => "Insufficient stock..."]);
}
```

Tidak ada opsi untuk partial-confirm atau membuat backorder otomatis.

### 1D. Pencegahan Overselling via `reserved_quantity`

Mekanismenya solid:

- **Confirm:** `$stock->increment('reserved_quantity', $item->quantity)` — mengunci stok
- **Deliver:** `$stock->decrement('reserved_quantity', $release)` — melepas reservasi setelah stok dikeluarkan
- **Cancel (confirmed):** `$stock->decrement('reserved_quantity', $release)` — melepas reservasi
- **Manufacturing** (setelah patch): `InventoryStock::availableQuantity()` dihormati saat produce

Semua operasi reservasi menggunakan `lockForUpdate()` untuk thread-safety.

---

## 2. Pricing, Taxes, & Discounts

### 2A. Price Logic

**Harga ditentukan manual per baris item saat store/update:**

```php
// SalesOrderRequest validation:
'items.*.unit_price' => ['required', 'numeric', 'min:0']
'items.*.discount'   => ['nullable', 'numeric', 'min:0']
```

- Tidak ada **Price List** model atau tabel
- Tidak ada **diskon bertingkat** (volume/quantity discount)
- Diskon hanya per baris item (flat amount, bukan persentase)
- `sell_price` ada di Product model tapi **tidak dipaksakan** — user mengetik harga manual

**FINDING [P2]:** Tidak ada mekanisme price-list atau proteksi terhadap penjualan di bawah harga pokok (sell below cost).

### 2B. Taxation (PPN)

TaxService.php menyediakan:

- `calculatePPN($dpp, $rate=11)` — menghitung PPN dari DPP
- `extractPPNFromTotal($totalInclusive)` — reverse-calculate DPP dari total inklusif
- `getSptMasaPPN($year, $month)` — **SPT Masa PPN summary** lengkap (PPN Keluaran vs Masukan)
- Constants: `PPN_KELUARAN = '2110'`, `PPN_MASUKAN = '1140'`

**FINDING [P1 — CRITICAL]:** Tax amount di SalesOrder disimpan sebagai input manual (`tax_amount`), bukan dihitung otomatis dari DPP dan rate. Di FinanceService.php, `tax_amount` hanya disalin dari SO ke Invoice:

```php
'tax_amount' => $salesOrder->tax_amount,
```

Namun **journal Invoice (Dr AR / Cr Revenue) menggunakan `total_amount` bulat** tanpa memisahkan komponen pajak:

```php
['account_id' => $ar->id, 'debit' => $invoice->total_amount, 'credit' => 0],
['account_id' => $revenue->id, 'debit' => 0, 'credit' => $invoice->total_amount],
```

**PPN Keluaran tidak otomatis dijurnal ke akun 2110 (Hutang PPN).** Ini menyebabkan:

- Revenue dicatat terlalu tinggi (termasuk PPN)
- Hutang PPN (PPN Keluaran) tidak tercatat di buku besar
- Laporan SPT Masa PPN mengandalkan field `tax_amount` di invoice, bukan jurnal aktual

### 2C. Multi-Currency

Invoice dan Payment model memiliki field `currency_id`, `exchange_rate`, `total_amount_base`, `amount_base`. Namun:

**FINDING [P1]:** Field multi-currency ada di tabel tapi **tidak digunakan di FinanceService**:

- `createInvoiceFromSalesOrder()` tidak set `currency_id`, `exchange_rate`, atau `total_amount_base`
- `recordPayment()` tidak memperhitungkan exchange rate
- `createPaymentJournal()` menjurnal `$payment->amount` langsung tanpa konversi ke mata uang dasar
- **Tidak ada perhitungan selisih kurs (exchange gain/loss)** saat pembayaran diterima dalam mata uang berbeda dari invoice

---

## 3. Integrasi Akuntansi (The Money Trail)

### 3A. Revenue Recognition

Pendapatan diakui saat **Invoice dibuat** (bukan saat barang dikirim):

```
Invoice created → Dr AR (1200) / Cr Revenue (4000) [total_amount]
```

Ini bertentangan dengan PSAK 72 (Pendapatan dari Kontrak dengan Pelanggan) yang mensyaratkan pengakuan saat kewajiban pelaksanaan terpenuhi (delivery). Namun karena alur ManERP mengharuskan confirm → deliver → invoice, secara praktis invoice biasanya dibuat setelah delivery.

**FINDING [P2]:** Invoice dapat dibuat dari SO berstatus `confirmed` (sebelum delivery):

```php
// InvoiceController::create/store
whereIn('status', ['confirmed', 'shipped', 'completed'])
```

Ini memungkinkan revenue recognition sebelum barang dikirim.

### 3B. Accounts Receivable (AR)

Alur jurnal lengkap:


| Event                  | Dr               | Cr               | Reference          |
| ---------------------- | ---------------- | ---------------- | ------------------ |
| Invoice issued         | AR (1200)        | Revenue (4000)   | INV-YYYY-XXXXX     |
| Payment received       | Cash/Bank (1100) | AR (1200)        | PMT-INV-YYYY-XXXXX |
| COGS on delivery       | COGS (5000)      | Inventory (1300) | SLO-XXXXX-COGS     |
| Sales cancel (shipped) | Inventory (1300) | COGS (5000)      | SLO-XXXXX-COGS-REV |
| Credit Note            | Revenue (4000)   | AR (1200)        | CN-XXXXX           |

**AR Aging Report** tersedia di AccountingService.php dengan bucket: Current, 1-30, 31-60, 61-90, 90+ hari.

### 3C. Credit Notes (Sales Returns)

Di CreditNoteController.php, approve() membuat jurnal:

```
Dr Revenue (4xxx) / Cr AR (12xx)
+ Dr Tax (21xx) / Cr AR [jika ada komponen pajak]
```

**FINDING [P1]:** Credit Note **TIDAK mengembalikan stok**. Ini murni penyesuaian akuntansi (reversing journal). Untuk mengembalikan stok fisik, harus cancel SO dari status `shipped` secara terpisah. Tidak ada link otomatis antara Credit Note dan stock restoration.

**FINDING [RESOLVED]:** WAC Drift pada sales cancel sudah diperbaiki di Inventory Audit Patch sebelumnya — cancel sekarang menggunakan `originalLayer->unit_cost` bukan `$product->avg_cost`.

---

## 4. CRM & Project Integration

### 4A. Project Linking

SalesOrder memiliki `project_id` (nullable FK ke `projects`):

- Hubungan tersimpan dan ditampilkan di view
- **Namun tidak ada konsolidasi otomatis** biaya/pendapatan proyek
- Tidak ada laporan profitabilitas per proyek yang menggabungkan SO revenue, COGS, dan project costs

### 4B. Credit Limit

**FINDING [P1]:** **Tidak ada fitur credit limit.** Client model hanya memiliki field dasar:

```php
protected $fillable = [
    'code', 'name', 'email', 'phone', 'company', 'tax_id',
    'npwp', 'tax_address', 'is_pkp', 'address', 'city', 'country',
    'type', 'status', 'notes',
];
```

Tidak ada `credit_limit`, `payment_terms`, atau pengecekan outstanding AR sebelum konfirmasi SO baru.

---

## 5. Analytics & Reporting

### 5A. Profitability

Sales Report (ReportController.php) menyediakan:

- Total sales count, total revenue, average order value
- Sales by status
- Daily sales trend
- Top 10 products by revenue
- Top 10 clients by revenue

**FINDING [P2]:** **Tidak ada perhitungan margin/profit per order.** Report hanya menampilkan revenue (sell price) tanpa mengurangi COGS. Untuk melihat profit, harus manual melihat P&L statement.

### 5B. Sales Performance

Data yang tersedia:

- Per produk (top products by qty & revenue)
- Per client (top clients by order count & revenue)

**FINDING [P2]:** Tidak tersedia:

- Per **wilayah/kota** (meskipun Client memiliki field `city` dan `country`)
- Per **agen penjual** (field `created_by` ada tapi tidak dianalisis sebagai sales agent)
- Per **kategori produk** (Product memiliki `category_id` tapi tidak di-join di sales report)
- Per **periode perbandingan** (month-over-month, year-over-year)

---

## 6. Temuan Audit & Klasifikasi Risiko

### Temuan P0 (Critical — HARUS diperbaiki sebelum production)


| #        | Temuan                                                                                                                                   | File                     | Dampak                                                                                                           |
| -------- | ---------------------------------------------------------------------------------------------------------------------------------------- | ------------------------ | ---------------------------------------------------------------------------------------------------------------- |
| **P0-1** | `cancel()` shipped order: stock restore + WAC layer + COGS reverse journal **TIDAK dibungkus `DB::transaction`**                         | SalesOrderController.php | Partial failure menyebabkan stok ter-restore tanpa COGS reverse, atau sebaliknya — neraca tidak balance         |
| **P0-2** | Invoice journal**tidak memisahkan PPN** — seluruh `total_amount` (termasuk pajak) diakui sebagai Revenue                                | FinanceService.php       | Revenue overstated, PPN Keluaran (Hutang Pajak 2110) tidak tercatat, SPT Masa PPN inconsistent dengan buku besar |
| **P0-3** | `cancelInvoice()` menghapus payment & set status cancelled tapi **TIDAK membuat reversing journal** untuk membatalkan Dr AR / Cr Revenue | FinanceService.php       | AR dan Revenue permanen terinflasi setelah cancel                                                                |

### Temuan P1 (High — Perbaiki segera)


| #        | Temuan                                                                                                    | File                     | Dampak                                                                                                  |
| -------- | --------------------------------------------------------------------------------------------------------- | ------------------------ | ------------------------------------------------------------------------------------------------------- |
| **P1-1** | Credit Note tidak mengembalikan stok — hanya jurnal keuangan                                             | CreditNoteController.php | Return barang memerlukan 2 proses manual terpisah                                                       |
| **P1-2** | Multi-currency field tersedia tapi tidak terintegrasi di FinanceService                                   | FinanceService.php       | Penjualan multi-currency akan menghasilkan jurnal yang salah (amount tidak dikonversi ke base currency) |
| **P1-3** | Tidak ada credit limit di Client — SO baru bisa dikonfirmasi tanpa cek outstanding AR                    | Client.php               | Risiko piutang tak tertagih tinggi                                                                      |
| **P1-4** | `User::where('is_admin', true)` — kolom `is_admin` tidak ada di tabel users (diganti `role` = `'admin'`) | SalesOrderController.php | Query mengembalikan empty collection — notifikasi admin tidak pernah terkirim                          |
| **P1-5** | `delivered_quantity` di SalesOrderItem tidak pernah di-update oleh `deliver()`                            | SalesOrderController.php | Tracking partial delivery tidak berfungsi                                                               |
| **P1-6** | Invoice bisa dibuat dari SO`confirmed` (sebelum delivery) — revenue diakui sebelum kewajiban terpenuhi   | InvoiceController.php    | Pelanggaran prinsip revenue recognition                                                                 |
| **P1-7** | Journal silent skip jika CoA belum di-seed:`if (!$ar || !$revenue) { return; }`                           | FinanceService.php       | Transaksi berhasil tapi jurnal tidak dibuat — buku besar incomplete tanpa peringatan                   |

### Temuan P2 (Medium — Roadmap)


| #        | Temuan                                                                              | Dampak                                              |
| -------- | ----------------------------------------------------------------------------------- | --------------------------------------------------- |
| **P2-1** | Tidak ada Quotation module (langsung dari SO draft)                                 | Tidak ada tracking conversion rate prospek → order |
| **P2-2** | Tidak ada Price List / diskon bertingkat                                            | Harga manual rawan human error dan inkonsistensi    |
| **P2-3** | Diskon hanya flat amount per item, bukan persentase                                 | Tidak mendukung kampanye diskon persen              |
| **P2-4** | Tidak ada proteksi sell-below-cost                                                  | Penjualan bisa dibuat di bawah harga pokok          |
| **P2-5** | Tax amount diinput manual, bukan auto-calculate dari DPP                            | Rawan salah hitung pajak                            |
| **P2-6** | Partial delivery tidak diimplementasikan (status`processing`/`partial` tidak aktif) | Full delivery only                                  |
| **P2-7** | Tidak ada profit margin per order dalam report                                      | Harus manual lihat P&L                              |
| **P2-8** | Tidak ada sales analysis per kategori/wilayah/agent                                 | Business intelligence terbatas                      |
| **P2-9** | Tidak ada dedicated Sales test suite                                                | Regresi sulit dideteksi                             |

---

## 7. Analisis SWOT

### Strengths

- **State machine yang robust** dengan validasi transisi yang ketat
- **Reserved quantity mechanism** yang thread-safe dengan `lockForUpdate()`
- **WAC compliance** per PSAK 14 — COGS menggunakan avg_cost, cancel menggunakan original unit_cost
- **Full audit trail** via `Auditable` trait
- **Fiscal-lock middleware** mencegah transaksi di periode tertutup
- **AR Aging report** yang lengkap (Current/30/60/90/90+)
- **SPT Masa PPN** reporting sudah tersedia
- **Double-entry validation** di AccountingService (`Debit ≠ Credit → exception`)

### Weaknesses

- **PPN tidak dijurnal terpisah** — ini cacat akuntansi paling kritis
- **Invoice cancel tanpa reversing journal** — menyebabkan inflasi permanen di AR & Revenue
- **Cancel shipped SO tanpa DB::transaction** — risiko partial failure
- **Multi-currency incomplete** — infrastruktur ada tapi integrasi tidak terkoneksi
- **No credit limit** — tidak ada proteksi terhadap customer berisiko tinggi
- **No partial delivery** — kolom `delivered_quantity` ada tapi tidak dimanfaatkan
- **No dedicated test coverage** untuk modul Sales/Invoice/Payment

### Opportunities

- **Sales Commission** — bisa dibangun di atas `created_by` + `items.total`
- **POS Integration** — alur SO sudah bisa diadaptasi ke POS (confirm + deliver + invoice atomik)
- **Customer Portal API** — Client model + Invoice + Payment sudah siap di-expose via API
- **Delivery Note / Surat Jalan** — bisa ditambahkan sebagai entitas terpisah dari SO
- **Recurring Invoice** — Invoice model sudah support multi-currency dan term

### Threats

- **Ketidaksesuaian SPT PPN** — jika auditor pajak membandingkan buku besar (Revenue tanpa split PPN) dengan report SPT Masa
- **Piutang tak tertagih** tanpa credit limit enforcement
- **Data corruption** dari cancel shipped tanpa transaction wrapping
- **Silent journal skip** menyebabkan buku besar tidak lengkap tanpa peringatan — sulit dideteksi sampai tutup buku

---

## 8. Rekomendasi Prioritas Perbaikan

### Phase 1: The Sales Integrity Patch (P0 + P1 kritis)


| Task        | Deskripsi                                                                                                         |
| ----------- | ----------------------------------------------------------------------------------------------------------------- |
| **TUGAS 1** | Bungkus`cancel()` shipped-branch dalam `DB::transaction`                                                          |
| **TUGAS 2** | Pisahkan PPN di invoice journal:`Dr AR (total) / Cr Revenue (DPP) / Cr PPN Keluaran 2110 (tax)`                   |
| **TUGAS 3** | `cancelInvoice()` harus membuat reversing journal (`Dr Revenue / Cr AR` + `Dr PPN Keluaran / Cr AR` jika ada tax) |
| **TUGAS 4** | Fix`User::where('is_admin', true)` → `User::where('role', 'admin')` di 3 controller                              |
| **TUGAS 5** | Journal missing CoA harus`throw RuntimeException` (bukan silent return)                                           |
| **TUGAS 6** | Write comprehensive Sales test suite                                                                              |

### Phase 2: Business Logic Enhancement (P1 remaining)

- Implementasi credit limit di Client + check saat SO confirm
- Partial delivery (update `delivered_quantity`, transisi ke `partial`/`shipped`)
- Multi-currency journal integration dengan exchange gain/loss
- Auto-calculate PPN dari DPP pada invoice creation
- Credit Note → auto stock return linkage

### Phase 3: Feature Expansion (P2)

- Quotation module (dengan conversion tracking)
- Price List & tiered discount
- Sell-below-cost warning
- Sales report: per kategori, per wilayah, per agent, margin per order
- Sales Commission tracking
- Customer Portal API

---

**Temuan Total:** 3 P0 | 7 P1 | 9 P2
**Rekomendasi:** Eksekusi Phase 1 (6 tugas) sebelum sistem digunakan untuk transaksi real. P0-2 (PPN journal) adalah temuan paling kritis karena berdampak langsung pada kepatuhan pajak.
