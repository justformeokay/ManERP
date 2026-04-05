# Laporan Audit Sistem Keuangan & Pelaporan (Finance & GL) ManERP

**Auditor**: Senior Chartered Accountant & ERP Financial Systems Auditor
**Tanggal**: 5 April 2026
**Versi Sistem**: ManERP — Laravel 12 | 178 Tests / 663 Assertions (all green)
**Scope**: General Ledger, Financial Reporting, Closing, Cash & Bank, Tax

---

## Executive Summary

ManERP memiliki fondasi akuntansi yang **solid dan well-engineered** — double-entry enforcement, accrual-based journal creation otomatis, structural-level CoA type classification, fiscal period locking, dan cash flow statement metode tidak langsung (PSAK 2/IAS 7). Namun, audit ini mengidentifikasi **4 bug P0 (critical)**, **6 bug P1 (high)**, dan **8 temuan P2 (medium)** yang harus ditangani sebelum sistem dianggap production-ready untuk pelaporan keuangan ke stakeholder atau mobile dashboard.

| Severity | Count | Dampak |
|----------|-------|--------|
| **P0 — Critical** | 4 | Laporan keuangan bisa salah saji material |
| **P1 — High**     | 6 | Inkonsistensi data / celah operasional |
| **P2 — Medium**   | 8 | Best practice & fitur yang belum ada |

---

## 1. General Ledger & Trial Balance Integrity

### 1.1 Double-Entry Validation

**Implementasi**: `AccountingService::createJournalEntry()` ([AccountingService.php](app/Services/AccountingService.php#L30-L64))

```
totalDebit  = round(array_sum(array_column($entries, 'debit')), 2);
totalCredit = round(array_sum(array_column($entries, 'credit')), 2);
if (abs(totalDebit - totalCredit) > 0.01) → throw InvalidArgumentException
```

**Verdict: ✅ BAIK** — Toleransi 0.01 tepat untuk decimal(14,2). Semua journal creation melewati fungsi ini, termasuk auto-journals dari:
- `FinanceService` (invoice, payment, cancel)
- `AccountsPayableService` (bill post, supplier payment, void)
- `StockValuationService` (purchase receive, cancel)
- `FixedAssetService` (depreciation, disposal)

**Model-level cross-check**: `JournalEntry::getIsBalancedAttribute()` — computed property yang memverifikasi balance di sisi model.

> **P0-GL-1: TIDAK ADA DB-LEVEL CONSTRAINT UNTUK BALANCE**
> Validasi hanya di application layer (PHP). Jika kode bypass `createJournalEntry()` dan langsung `JournalEntry::create()` + manual `items()->create()`, entri tidak balanced bisa masuk database. Schema `journal_items` tidak memiliki trigger atau CHECK constraint.
> **Risk**: Data corruption jika ada developer baru yang memanggil model langsung.

### 1.2 Account Balances — Logic Normal Balance

**Implementasi**: `AccountingService::getLedger()` ([AccountingService.php](app/Services/AccountingService.php#L69-L113))

```
isDebitNormal = in_array(type, ['asset', 'expense']);
balance += isDebitNormal ? (debit - credit) : (credit - debit);
```

**Verdict: ✅ BENAR** — Sesuai standar akuntansi:
| Type | Normal Balance | Formula |
|------|---------------|---------|
| Asset | Debit | D − C |
| Expense | Debit | D − C |
| Liability | Credit | C − D |
| Equity | Credit | C − D |
| Revenue | Credit | C − D |

**Balance Sheet**: `getBalanceSheet()` ([AccountingService.php](app/Services/AccountingService.php#L165-L240)) — Revenue & expense difold ke `retained_earnings` via:
```
revenue:  retainedEarnings += (credit − debit)
expense:  retainedEarnings -= (debit − credit)
```

> **P1-GL-1: TIDAK ADA OPENING BALANCE / BROUGHT-FORWARD**
> Semua query balance menggunakan kumulatif dari awal data (`date <= $date`). Tidak ada mekanisme "saldo awal" yang di-snapshot saat tutup buku. Untuk perusahaan dengan data multi-tahun, query balance sheet harus scan **seluruh** journal_items dari hari pertama hingga tanggal laporan.
> **Impact**: Performance degradation seiring bertambahnya data. Query Balance Sheet untuk tahun ke-5 harus membaca 5 tahun data.

### 1.3 Trial Balance

**Implementasi**: `AccountingService::getTrialBalance()` ([AccountingService.php](app/Services/AccountingService.php#L118-L160))

- Groups by account, SUM debit/credit
- Filter: `is_posted = true` ✅
- `is_balanced` check: `abs(grandDebit - grandCredit) < 0.01` ✅
- Date range filter opsional (`$from`, `$to`)

**Verdict: ✅ REAL-TIME** — Langsung query GL, tidak cache.

> **P2-GL-1: AKUN TANPA TRANSAKSI TIDAK MUNCUL DI TRIAL BALANCE**
> Query menggunakan INNER JOIN antara `journal_items` dan `chart_of_accounts`. Akun yang ada di CoA tapi belum pernah memiliki jurnal **tidak akan muncul** di Trial Balance. Ini bisa menyesatkan karena akun aktif terkesan tidak ada.
> **Fix**: LEFT JOIN dari `chart_of_accounts` ke aggregated `journal_items`.

> **P2-GL-2: TRIAL BALANCE SALAH TAMPILAN UNTUK CLOSING ENTRIES**
> Jika user meminta Trial Balance per 31 Desember setelah tutup buku, closing entries akan ikut masuk. Ini bisa membingungkan karena akun revenue/expense menunjukkan saldo 0 (sudah ditutup ke Retained Earnings).
> **Fix**: Tambah opsi `exclude_closing_entries` filter di Trial Balance.

---

## 2. Financial Statements (P&L dan Balance Sheet)

### 2.1 Profit & Loss Logic

**Implementasi**: `AccountingService::getProfitLoss()` ([AccountingService.php](app/Services/AccountingService.php#L245-L300))

```
Revenue:  balance = credit − debit (BENAR)
Expense:  balance = debit − credit (BENAR)
Net Profit = totalRevenue − totalExpense
```

**Klasifikasi biaya**: Saat ini TIDAK ADA pemisahan antara:
- **COGS** (Cost of Goods Sold) — kode 5xxx
- **Operating Expenses** — kode 6xxx
- **Other Income/Expense** — belum ada

Semua akun `type = 'expense'` ditampilkan sebagai satu kelompok flat.

> **P1-GL-2: TIDAK ADA GROSS PROFIT LINE DI P&L**
> Laporan Laba Rugi (P&L) tidak menampilkan **Laba Kotor (Gross Profit = Revenue − COGS)** sebagai line item terpisah. Ini tidak sesuai PSAK 1 / IAS 1 yang mengharuskan pemisahan:
> ```
> Revenue
> − COGS
> = Gross Profit
> − Operating Expenses
> = Operating Profit
> − Other Expenses
> = Net Profit Before Tax
> − Tax
> = Net Profit After Tax
> ```
> **Impact**: Dashboard mobile hanya bisa menampilkan "Total Revenue" vs "Total Expense" tanpa breakdown yang bermakna.

### 2.2 Balance Sheet Accuracy

**Cross-module Sinkronisasi** — dianalisis per akun:

| GL Account | Modul Operasional | Sinkron? | Catatan |
|------------|-------------------|----------|---------|
| 1100 Cash & Bank | `BankAccount.current_balance` | ⚠️ PARTIAL | Bank balance bisa diverge dari GL (lihat §4) |
| 1200 Accounts Receivable | `Invoice.remaining_balance` | ✅ | Auto-journal pada invoice creation + payment |
| 1300 Inventory | `InventoryStock.quantity × avg_cost` | ✅ | WAC + auto-journal pada PO receive |
| 2000 Accounts Payable | `SupplierBill.outstanding` | ✅ | Accrual-to-bill pattern + auto-journal |
| 2110 PPN Keluaran | `Invoice.tax_amount` sum | ✅ | Auto-journal pada invoice creation |
| 1140 PPN Masukan | `SupplierBill.tax_amount` sum | ✅ | Auto-journal pada bill posting |

> **P0-GL-2: `BankAccount.current_balance` BISA DIVERGE DARI GL SALDO 1100**
> `BankReconciliationService::recordTransaction()` mengubah `current_balance` via `increment()` tanpa membuat journal entry. `completeReconciliation()` langsung set `current_balance = statement_balance`. Jika ada transaksi bank yang tidak dijurnal, saldo `bank_accounts.current_balance` akan berbeda dari saldo akun GL 1100.
> **Impact**: Neraca menunjukkan saldo kas dari GL (journal-based), tapi modul bank menunjukkan angka berbeda.

### 2.3 Date Range Filtering — Transaction Date vs Posting Date

**Implementasi saat ini**: Semua query menggunakan `journal_entries.date` saja.

| Field | Digunakan? | Keterangan |
|-------|-----------|------------|
| `journal_entries.date` | ✅ Ya | Tanggal ekonomis transaksi |
| `journal_entries.created_at` | ❌ Tidak | Tanggal input ke sistem |
| `journal_entries.is_posted` | ✅ Ya | Filter hanya entri posted |

**Problem**: Tidak ada konsep terpisah antara **transaction date** dan **posting date** (GL date). Jika user membuat jurnal manual untuk tanggal bulan lalu (backdate), jurnal langsung masuk ke periode historis tanpa warning (kecuali fiscal period sudah closed).

> **P2-GL-3: TIDAK ADA POSTING DATE TERPISAH DARI TRANSACTION DATE**
> Standar ERP mature (SAP, Oracle) memisahkan Document Date (tanggal faktur) dari Posting Date (tanggal masuk GL). ManERP menggabungkan keduanya di `journal_entries.date`.
> **Impact**: Sulit melakukan cut-off analisis akhir bulan ("transaksi tanggal berapa saja yang masuk GL setelah tutup buku bulan lalu?").

---

## 3. Closing Process (Tutup Buku)

### 3.1 Monthly/Year-End Closing

**Implementasi**: `AccountingService::closePeriod()` ([AccountingService.php](app/Services/AccountingService.php#L375-L450))

**Proses**:
1. Hitung P&L untuk periode via `getProfitLoss(start_date, end_date)`
2. Buat closing journal entries:
   - **Dr Revenue accounts** (tutup pendapatan)
   - **Cr Expense accounts** (tutup beban)
   - **Net → Retained Earnings (3200)** — Cr jika laba, Dr jika rugi
3. Set `entry_type = 'closing'`, `is_posted = true`
4. Update `fiscal_periods.status = 'closed'`
5. `EnsureOpenFiscalPeriod` middleware memblokir transaksi baru di periode tertutup

**Verdict: ✅ MEKANISME BENAR** — Sesuai prosedur tutup buku standar.

**TETAPI**:

> **P0-GL-3: `reopenPeriod()` TIDAK MENGHAPUS/REVERSE CLOSING JOURNAL**
> ```php
> public function reopenPeriod(FiscalPeriod $period): FiscalPeriod
> {
>     $period->update([
>         'status'    => 'open',
>         'closed_by' => null,
>         'closed_at' => null,
>     ]);  // ← closing_journal_id NOT cleared, journal NOT deleted/reversed
> }
> ```
> Jika admin: (1) tutup bulan Maret → closing journal dibuat, (2) reopen bulan Maret → status jadi 'open' tapi closing journal masih ada, (3) tutup lagi → closing journal **BARU** dibuat lagi.
> **Result**: **DOUBLE RETAINED EARNINGS** — saldo Laba Ditahan terkredit dua kali untuk periode yang sama.
> **Severity**: P0 — Laporan keuangan salah saji material.

> **P1-GL-3: CLOSING HANYA BERLAKU UNTUK HTTP REQUEST, BUKAN SERVICE CALLS**
> `fiscal-lock` middleware memblokir write via HTTP, tapi service method seperti `FinanceService::createInvoiceFromSalesOrder()` dan `AccountsPayableService::postBill()` **TIDAK MENGECEK** apakah tanggal transaksi jatuh di periode tertutup sebelum membuat journal.
> Hanya `JournalEntryController` yang secara eksplisit memanggil `isDateInClosedPeriod()`.
> **Impact**: Background jobs atau inter-service calls bisa mem-bypass fiscal lock.

### 3.2 Retained Earnings

**Akun**: Hardcoded `'3200'` di `closePeriod()`.

> **P1-GL-4: AKUN 3200 TIDAK ADA DI SEEDER**
> `DatabaseSeeder` hanya membuat 8 akun (1100, 1200, 1300, 2000, 3000, 4000, 5000, 6000). Akun `3200 Retained Earnings` **TIDAK** di-seed.
> Jika admin menjalankan `closePeriod()` tanpa akun 3200:
> ```php
> $retainedEarnings = ChartOfAccount::where('code', '3200')->first();
> // → null
> if (!empty($closingEntries) && $retainedEarnings) {
>     // ← SKIPPED! Closing journal NOT created!
> }
> $period->update(['status' => 'closed']); // ← Period marked closed anyway!
> ```
> **Result**: Periode ditandai closed TANPA closing journal. Revenue & expense accounts tidak ditutup. Retained Earnings kosong.

**Akun lain yang hilang dari seeder tapi DIBUTUHKAN oleh kode**:

| Code | Name | Dibutuhkan oleh |
|------|------|-----------------|
| `2110` | PPN Keluaran | `FinanceService`, `TaxService` |
| `1140` | PPN Masukan | `AccountsPayableService`, `TaxService` |
| `3200` | Retained Earnings | `AccountingService::closePeriod` |
| `5101` | Purchase Price Variance | `AccountsPayableService::postBill` |
| `41xx` | Gain on Disposal | `FixedAssetService::disposeAsset` |
| `59xx` | Loss on Disposal | `FixedAssetService::disposeAsset` |

---

## 4. Cash & Bank Management

### 4.1 Bank Reconciliation

**Implementasi**: `BankReconciliationService` ([BankReconciliationService.php](app/Services/BankReconciliationService.php))

**Alur**:
1. `createReconciliation()` → capture `statement_balance` vs `book_balance` (dari GL)
2. `toggleReconcile()` → match/unmatch bank transactions
3. `recalculateDifference()` → hitung selisih
4. `completeReconciliation()` → set status='completed', update `bank_account.current_balance`

**Kekuatan**:
- `getBookBalance()` menghitung dari GL (journal items) — ✅
- Difference tracking — ✅
- Audit trail via `AuditLogService` — ✅

> **P0-GL-4: REKONSILIASI BISA COMPLETED DENGAN DIFFERENCE ≠ 0**
> `completeReconciliation()` tidak memvalidasi bahwa `difference == 0` sebelum marking 'completed':
> ```php
> $this->recalculateDifference($reconciliation);
> $reconciliation->update([
>     'status'        => 'completed',  // ← No check if difference == 0!
>     'reconciled_by' => Auth::id(),
>     'reconciled_at' => now(),
> ]);
> $reconciliation->bankAccount->update([
>     'current_balance' => $reconciliation->statement_balance, // ← Force-overwrite!
> ]);
> ```
> **Impact**: Admin bisa "menyelesaikan" rekonsiliasi yang belum balance, dan `current_balance` di-overwrite ke `statement_balance` — menghapus jejak selisih yang belum terselesaikan.

> **P2-GL-4: `recordTransaction()` MENGUBAH SALDO TANPA JOURNAL ENTRY**
> `BankReconciliationService::recordTransaction()` menambah `current_balance` via `increment()` tanpa membuat journal entry di GL. Ini menyebabkan divergensi antara bank ledger dan GL.

### 4.2 Payment Allocation

**AR Side** — `FinanceService::recordPayment()`:
- `lockForUpdate()` pada invoice ✅
- `paid_amount = sum(payments)` ✅
- `recalculateStatus()` → paid/partial/unpaid ✅
- Journal: Dr Cash(1100), Cr AR(1200) ✅

**AP Side** — `AccountsPayableService::recordPayment()`:
- `lockForUpdate()` pada bill ✅
- `bccomp()` untuk validasi amount ✅
- Journal: Dr AP(2000), Cr Cash(1100) ✅

> **P1-GL-5: TIDAK ADA MEKANISME BULK PAYMENT / MULTI-INVOICE ALLOCATION**
> Kedua sisi (AR dan AP) hanya bisa membayar **satu** invoice/bill per payment. Tidak ada fitur:
> - Bayar beberapa invoice sekaligus dengan satu transfer
> - Apply satu payment ke remaining balance dari beberapa bill
> - Credit note allocation ke invoice tertentu
> **Impact**: Untuk pembayaran gabungan, admin harus input N payment terpisah — error-prone dan tidak efisien.

> **P2-GL-5: INVOICE CANCEL MENGHAPUS SEMUA PAYMENT RECORDS**
> `FinanceService::cancelInvoice()` memanggil `$invoice->payments()->delete()` — semua record Payment dihapus dari DB. Lebih baik soft-delete atau void agar audit trail tetap tersedia.

---

## 5. Multi-Currency

### 5.1 Schema — Sudah Ada

| Table | Columns |
|-------|---------|
| `journal_entries` | `currency_id`, `exchange_rate` |
| `journal_items` | `debit_base`, `credit_base` |
| `invoices` | `currency_id`, `exchange_rate`, `total_amount_base` |
| `supplier_bills` | `currency_id`, `exchange_rate`, `total_base` |
| `currencies` | `code`, `symbol`, `is_base`, `decimal_places` |
| `exchange_rates` | `currency_id`, `effective_date`, `rate` |

### 5.2 Service Layer — BELUM DIIMPLEMENTASIKAN

> **P1-GL-6: KOLOM `debit_base` / `credit_base` TIDAK PERNAH DITULIS OLEH SERVICE**
> Semua service (`AccountingService`, `FinanceService`, `AccountsPayableService`, `StockValuationService`) menulis `debit` dan `credit` saja. Kolom `debit_base`/`credit_base` TIDAK pernah diisi — tetap default 0.
> Semua laporan keuangan (Trial Balance, P&L, Balance Sheet, Cash Flow) query kolom `debit`/`credit` — bukan base currency.
> **Impact**: Jika ada transaksi dalam USD, laporan akan mencampur IDR dan USD tanpa konversi. Angka tidak bermakna.

---

## 6. Tax Compliance

### 6.1 PPN (Pajak Pertambahan Nilai)

**Implementasi**: `TaxService` ([TaxService.php](app/Services/TaxService.php))

- `calculatePPN()` — DPP × rate/100 ✅
- `extractPPNFromTotal()` — Reverse calculation ✅
- `getSptMasaPPN()` — Agregasi invoices (PPN Keluaran) vs bills (PPN Masukan) ✅
- `applyPPNToInvoice()` / `applyPPNToBill()` — Update record ✅

**PPN Rate**: Hardcoded `11%` — sudah sesuai tarif PPN Indonesia berlaku April 2022.

### 6.2 Faktur Pajak

- Field `faktur_pajak_number` ada di `invoices` dan `supplier_bills` ✅
- `is_tax_account` flag di `chart_of_accounts` ✅
- `tax_type` enum mendukung: ppn_keluaran, ppn_masukan, pph21, pph23, pph25, pph29, pph4_2 ✅

> **P2-GL-6: TIDAK ADA VALIDASI FORMAT FAKTUR PAJAK**
> Field `faktur_pajak_number(50)` menerima string bebas. Tidak ada validasi format DJP: `000.000-00.00000000` (16 digit structured format).

---

## 7. Number Generation & Concurrency

### 7.1 Auto-Number Pattern

| Entity | Pattern | Method |
|--------|---------|--------|
| Invoice | `INV-{year}-{5-digit}` | `max existing + 1` di `creating` boot |
| SupplierBill | `BILL-{year}-{5-digit}` | `max existing + 1` di `creating` boot |
| SupplierPayment | `PAY-{year}-{5-digit}` | `max existing + 1` di `creating` boot |
| CreditNote | `CN-{5-digit}` | `latest('id') + 1` — **NO YEAR PREFIX** |
| DebitNote | `DN-{5-digit}` | `latest('id') + 1` — **NO YEAR PREFIX** |
| JournalEntry | `JE-{date}-{seq}` per controller | Count-based per date |
| PurchaseOrder | `PO-{5-digit}` | `max id + 1` |

> **P2-GL-7: RACE CONDITION PADA AUTO-NUMBER**
> Semua auto-number menggunakan `max() + 1` tanpa pessimistic locking. Dua request concurrent bisa mendapatkan nomor yang sama.
> **Impact ringan**: Database UNIQUE constraint akan menolak duplicate, tapi user akan mendapat error 500.
> **Fix**: Gunakan `DB::raw('SELECT MAX(...) FOR UPDATE')` atau `Redis::incr()`.

> **P2-GL-8: CREDIT NOTE / DEBIT NOTE TANPA YEAR PREFIX**
> Format `CN-00001` akan collision setelah 99,999 dokumen (tidak ada reset tahunan). Juga membuat sorting/filtering per tahun tidak mungkin.

---

## 8. Test Coverage — Finance/GL Module

### 8.1 Current Test Inventory

| Test File | Count | Covers |
|-----------|-------|--------|
| `BankReconciliationLogicTest` | ✅ | Bank reconciliation logic |
| `SalesIntegrityPatchTest` | ✅ | Invoice, payment, tax journals |
| `PurchaseIntegrityPatchTest` | ✅ 12 tests | PO receive, bill, PPV, PPN Masukan |
| `AuditIntegrityTest` | ✅ | Audit trail |
| `CreditLimitTest` | ✅ | Credit limit enforcement |
| **AccountingServiceTest** | ❌ MISSING | Trial Balance, P&L, Balance Sheet |
| **ClosingProcessTest** | ❌ MISSING | Period close, reopen, retained earnings |
| **CashFlowServiceTest** | ❌ MISSING | Cash flow statement, reconciliation |
| **FinancialRatioTest** | ❌ MISSING | Liquidity, profitability, leverage ratios |
| **FixedAssetTest** | ❌ MISSING | Depreciation, disposal journals |
| **MultiCurrencyTest** | ❌ MISSING | Base currency conversion |

> **P1-GL-7: ZERO TEST COVERAGE UNTUK CORE GL FUNCTIONS**
> Fungsi paling kritikal (`closePeriod`, `reopenPeriod`, `getBalanceSheet`, `getProfitLoss`, `getTrialBalance`, `getCashFlowStatement`) tidak memiliki test otomatis. Bug P0-GL-3 (double retained earnings) tidak terdeteksi karena tidak ada test.

---

## 9. Performance & Architecture Concerns

### 9.1 N+1 Query pada FinancialRatioService

```php
// getAccountBalances() — N+1 anti-pattern
$accounts = ChartOfAccount::all();  // 1 query
foreach ($accounts as $account) {
    $debit = DB::table('journal_items')...->sum('debit');   // N queries
    $credit = DB::table('journal_items')...->sum('credit');  // N queries
}
```

Untuk 50 akun CoA, ini menghasilkan **101 queries** per pemanggilan.

### 9.2 Full-Scan Balance Calculation

Setiap pemanggilan `getBalanceSheet()` atau `getTrialBalance()` mem-scan **seluruh** `journal_items` dari awal data. Tidak ada:
- Materialized views
- Balance snapshots
- Period-end balance caching

---

## 10. Analisis SWOT

### Strengths (Kekuatan)

| # | Kekuatan | Detail |
|---|----------|--------|
| S1 | **Application-Level Double-Entry Enforcement** | Setiap journal entry divalidasi balanced (tolerance 0.01) sebelum persist |
| S2 | **Comprehensive Auto-Journaling** | Invoice, Payment, PO Receive, Bill Post, Cancel — semua otomatis |
| S3 | **Accrual-Based Accounting** | PO receive langsung Dr Inventory/Cr AP; Bill posting hanya journal variance |
| S4 | **Fiscal Period Locking** | Middleware `fiscal-lock` mencegah transaksi di periode tertutup via HTTP |
| S5 | **Cash Flow Statement (Indirect Method)** | Lengkap dengan reconciliation/discrepancy check |
| S6 | **Full Financial Ratio Suite** | Current, Quick, Debt-to-Equity, ROA, ROE, Inventory Turnover |
| S7 | **Bank Reconciliation Module** | Statement vs book balance dengan match/unmatch workflow |
| S8 | **Fixed Asset Management** | Straight-line + declining balance depreciation dengan auto-journal |
| S9 | **Journal Templates & Recurring** | Template system dengan `scopeRecurringDue` untuk auto-entries |
| S10 | **Budget vs Actual Reporting** | 12-month budget lines dengan actual comparison dari GL |
| S11 | **Tax Integration (PPN Indonesia)** | SPT Masa PPN, faktur pajak tracking, PPN Keluaran/Masukan split |
| S12 | **Multi-Export** | Balance Sheet, P&L, Cash Flow exportable ke PDF (DomPDF) dan CSV/Excel |

### Weaknesses (Kelemahan)

| # | Kelemahan | Severity | Impact |
|---|-----------|----------|--------|
| W1 | Double Retained Earnings on reopen+re-close | P0 | Neraca salah saji |
| W2 | Bank balance divergence dari GL | P0 | Cash di dashboard ≠ cash di neraca |
| W3 | Rekonsiliasi bisa completed tanpa balance | P0 | Audit trail rusak |
| W4 | 6 akun kritis tidak di-seed | P1 | Closing & tax gagal di fresh install |
| W5 | Fiscal lock hanya di HTTP layer | P1 | Service calls bypass lock |
| W6 | P&L tanpa Gross Profit line | P1 | Tidak sesuai PSAK 1/IAS 1 |
| W7 | `debit_base/credit_base` schema ada tapi dead | P1 | Multi-currency broken |
| W8 | Zero test coverage untuk GL core | P1 | Bugs tidak terdeteksi |
| W9 | N+1 queries di Financial Ratios | P2 | Slow pada CoA besar |
| W10 | No opening balance snapshots | P2 | Performance Year 3+ |

### Opportunities (Peluang)

| # | Peluang |
|---|---------|
| O1 | Implementasi Financial Statement drill-down (dari Neraca → Ledger → Journal → Source Document) — kritis untuk mobile dashboard |
| O2 | Multi-level Reporting (Cost Center / Department / Project) — `chart_of_accounts.parent_id` sudah ada tapi belum dimanfaatkan |
| O3 | Auto-Reversal Accruals — `JournalTemplate.is_recurring` + `frequency` sudah ada, tinggal tambah `auto_reverse_on` field |
| O4 | Budget Alert System — Budget infrastructure sudah ada, tambah threshold notification |
| O5 | Intercompany Transactions — Currency infrastructure sudah ada |

### Threats (Ancaman)

| # | Ancaman |
|---|---------|
| T1 | Data integrity saat migrasi ke mobile (Flutter) — jika P0 bugs tidak diperbaiki, dashboard menampilkan angka salah |
| T2 | Regulatory non-compliance — SPT Masa PPN tanpa validasi format faktur pajak |
| T3 | Scale ceiling — full-scan GL queries tanpa snapshots akan timeout di Year 3+ |
| T4 | Concurrent operations — auto-number race conditions saat multi-user |

---

## 11. Rekomendasi Prioritas

### Phase 1 — P0 Critical Fixes (WAJIB sebelum ke Flutter)

| # | Bug | Fix | File |
|---|-----|-----|------|
| P0-GL-1 | No DB-level balance constraint | Tambah CHECK constraint atau DB trigger yang validates SUM(debit)=SUM(credit) per journal_entry_id | Migration |
| P0-GL-2 | Bank balance diverge dari GL | `recordTransaction()` HARUS membuat journal entry. `completeReconciliation()` tidak boleh overwrite `current_balance` | `BankReconciliationService` |
| P0-GL-3 | Double retained earnings | `reopenPeriod()` HARUS menghapus/reverse closing journal sebelum reopen: `if ($period->closingJournal) { $this->createReversingEntry($period->closingJournal); }` | `AccountingService` |
| P0-GL-4 | Rekonsiliasi complete tanpa balance | Tambah guard: `if (abs($reconciliation->difference) > 0.01) throw ...` di `completeReconciliation()` | `BankReconciliationService` |

### Phase 2 — P1 High Priority

| # | Issue | Fix |
|---|-------|-----|
| P1-GL-1 | No opening balance | Tambah `account_balances` table + snapshot mechanism saat tutup buku |
| P1-GL-2 | P&L tanpa Gross Profit | Refactor `getProfitLoss()`: pisahkan COGS (code 5xxx) dari Operating Expenses (6xxx) |
| P1-GL-3 | Fiscal lock hanya HTTP | Tambah `isDateInClosedPeriod()` check di `createJournalEntry()` (central point) |
| P1-GL-4 | Missing CoA di seeder | Tambah 2110, 1140, 3200, 5101 ke `DatabaseSeeder` |
| P1-GL-5 | No bulk payment | Tambah `BulkPaymentService` dengan allocation matrix |
| P1-GL-6 | Multi-currency dead | Populate `debit_base`/`credit_base` di `createJournalEntry()`, tambah `?base=true` param di reports |
| P1-GL-7 | Zero GL test coverage | `FinanceGLIntegrityTest` — target 20+ tests |

### Phase 3 — P2 Enhancements

| # | Enhancement |
|---|-------------|
| P2-GL-1 | Trial Balance: show zero-balance active accounts |
| P2-GL-2 | Trial Balance: exclude closing entries option |
| P2-GL-3 | Separate posting date from transaction date |
| P2-GL-4 | Bank transactions must create GL journals |
| P2-GL-5 | Soft-delete payments instead of hard-delete |
| P2-GL-6 | Validate faktur pajak format |
| P2-GL-7 | Atomic auto-number with locking |
| P2-GL-8 | Year prefix for Credit/Debit Note numbers |

---

## 12. Kesiapan untuk Flutter Mobile Dashboard

### Checklist — Single Source of Truth

| Metric | Dashboard Source | GL Source | Match? |
|--------|----------------|-----------|--------|
| Cash on Hand | `DashboardService::getCashOnHand()` | `getBalanceSheet()['assets'] where code=1100` | ✅ Sama (both from GL) |
| Total Revenue | `DashboardService::getMonthlyRevenue()` | `getProfitLoss()['total_revenue']` | ✅ Sama |
| Total AR | `Invoice::whereIn('status', ['unpaid','partial'])->sum('remaining')` | `GL account 1200 balance` | ⚠️ BISA BERBEDA jika ada journal manual ke 1200 |
| Total AP | `SupplierBill::sum('outstanding')` | `GL account 2000 balance` | ⚠️ BISA BERBEDA jika ada journal manual ke 2000 |
| Bank Balance | `BankAccount::sum('current_balance')` | `GL account 1100 balance` | ❌ DIVERGE (P0-GL-2) |
| Inventory Value | `Product::sum(qty × avg_cost)` | `GL account 1300 balance` | ⚠️ BISA BERBEDA jika stock adjustments tanpa journal |

**Rekomendasi untuk Flutter**: Selalu gunakan **GL balance** sebagai single source of truth untuk semua angka finansial. Subledger balances (Invoice, Bill, BankAccount) hanya untuk detail view.

### Audit Trail — Drill-Down Capability

```
Balance Sheet → Account 1300 (Inventory: Rp 50,000,000)
  └→ General Ledger 1300 → 15 journal entries
       └→ JE-2026-03-15-0001 (PO Receive: Dr 1300 / Cr 2000)
            └→ PO-00042 → Supplier: PT ABC → Items: 100x Widget @Rp 500,000
```

**Status**: Partially possible. `JournalEntry.reference` contains invoice/PO numbers, tapi:
- Tidak ada `source_type`/`source_id` polymorphic link di `journal_entries`
- Link dari journal ke source document harus melalui string parsing (e.g., extract "PO-00042" from reference)

> **Rekomendassi**: Tambah `source_type` (enum: invoice, supplier_bill, purchase_order, fixed_asset) dan `source_id` ke `journal_entries` untuk drill-down yang reliable.

---

## Kesimpulan

ManERP memiliki **arsitektur akuntansi yang lebih matang dari kebanyakan ERP custom**. Fitur seperti accrual-based journal automation, fiscal period locking, cash flow indirect method, dan budget management menunjukkan pemahaman mendalam tentang prinsip akuntansi.

**Namun**, 4 bug P0 yang ditemukan — terutama **double retained earnings pada reopen** dan **bank balance divergence** — adalah show-stoppers yang HARUS diperbaiki sebelum sistem digunakan untuk pelaporan keuangan resmi atau sebelum angka-angka ini ditampilkan di mobile dashboard Flutter.

**Prioritas absolute**: Fix P0-GL-3 (reopen closing journal) dan P0-GL-2 (bank GL sync), lalu tambahkan test suite untuk GL core functions (P1-GL-7).

---

*Report generated by automated code audit — all findings verified against source code.*
*Test baseline: 178 tests / 663 assertions — all passing.*
