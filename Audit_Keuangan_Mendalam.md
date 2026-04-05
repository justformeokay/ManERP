# LAPORAN AUDIT KEUANGAN MENDALAM — ManERP

## Deep Financial Audit & Accounting Standard Stress-Test

### Auditor: Senior Chartered Accountant & ERP Financial System Architect

---

## 1. GENERAL LEDGER & JOURNAL INTEGRITY (The Foundation)

### 1A. Polymorphic Traceability (`sourceable`)

**Status: PASS — Implementasi Solid**

Kolom `sourceable_type` + `sourceable_id` pada tabel `journal_entries` sudah terimplementasi dengan benar:


| Modul            | sourceable_type              | Referensi                     |
| ---------------- | ---------------------------- | ----------------------------- |
| Sales Invoice    | `App\Models\Invoice`         | FinanceService.php            |
| Payment AR       | `App\Models\Payment`         | FinanceService.php            |
| Supplier Bill    | `App\Models\SupplierBill`    | AccountsPayableService.php    |
| Supplier Payment | `App\Models\SupplierPayment` | AccountsPayableService.php    |
| Payroll          | `App\Models\PayrollPeriod`   | PayrollService.php            |
| Bank Transaction | `App\Models\BankTransaction` | via BankReconciliationService |

**Test coverage**: `test_drill_down_link_consistency()` di FinanceIntegrityPatchTest.php memverifikasi relationship morphTo berfungsi.

> **TEMUAN [P2]**: Jurnal dari **StockValuationService** (purchase receive, sales COGS, manufacturing) **TIDAK** menyertakan `sourceable` link. Metode `journalPurchaseReceive()`, `journalSalesCogs()`, `journalMaterialToWip()`, dan `journalWipToFinishedGoods()` hanya mem-pass `reference` dan `description` tanpa parameter sourceable. Drill-down dari GL kembali ke PO/SO/MO **tidak dimungkinkan** untuk jurnal inventory.

### 1B. Manual Journal Restrictions

**Status: CRITICAL GAP — Risiko Selisih Sub-Ledger ↔ GL**

Pemeriksaan pada JournalEntryController.php dan JournalEntryRequest:

```php
// JournalEntryRequest rules:
'items.*.account_id' => 'required|exists:chart_of_accounts,id'
// ↑ Tidak ada filter — SEMUA akun bisa dipilih untuk jurnal manual
```

> **TEMUAN [P0 — HIGH RISK]**: Sistem **TIDAK memiliki restriksi** untuk jurnal manual ke akun kontrol:
>
> - **1200 (Piutang/AR)** — Manual debit/credit akan menyebabkan selisih AR Sub-ledger vs GL
> - **2000 (Hutang/AP)** — Manual entry merusak AP aging report
> - **1300 (Persediaan)** — Manual entry membuat GL Inventory ≠ Stock Valuation
> - **2110/2120/2130 (Utang Pajak/BPJS)** — Bisa merusak rekonsiliasi pajak
>
> **Dampak**: Jika akuntan entry jurnal manual ke akun 1200, saldo Piutang di Neraca tidak akan cocok dengan total outstanding Invoice. Auditor eksternal akan memberikan **opini Wajar Dengan Pengecualian (Qualified Opinion)**.

### 1C. Balanced Enforcement

**Status: PASS — Triple-Layer Protection**

Enforcement keseimbangan Debit = Kredit terjadi di 3 lapisan:


| Layer              | Lokasi                                   | Mekanisme                                          |
| ------------------ | ---------------------------------------- | -------------------------------------------------- |
| Request Validation | JournalEntryRequest.php                  | `abs($totalDebit - $totalCredit) > 0.01` → error  |
| Service Level      | AccountingService.php                    | `bccomp(abs(bcsub(...)), '0.01', 2) > 0` → throws |
| Model Level        | `JournalEntry::getIsBalancedAttribute()` | Runtime check                                      |

**bcmath digunakan** secara konsisten di service layer untuk presisi:

```php
$totalDebit  = bcadd((string)$totalDebit, (string)($line['debit'] ?? 0), 2);
$totalCredit = bcadd((string)$totalCredit, (string)($line['credit'] ?? 0), 2);
```

**Test coverage**: `test_unbalanced_journal_throws_exception()` — PASS

> **TEMUAN [P3 — LOW]**: Request-level validation menggunakan `round()` native PHP bukan `bcmath`:
>
> ```php
> $totalDebit = round(array_sum(array_column($items, 'debit')), 2); // JournalEntryRequest
> ```
>
> Sementara service-level sudah bcmath. Inkonsistensi ini aman untuk saat ini karena service-layer adalah gerbang terakhir, tapi idealnya diseragamkan.

> **TEMUAN [P2]**: Payroll `postToAccounting()` memiliki **rounding adjustment** yang men-adjust `totalNet` jika selisih ≤ 1.00:
>
> ```php
> $diff = bcsub($totalDebit, $totalCredit, 2);
> if (bccomp(...abs($diff)..., '1.00', 2) <= 0) {
>     $totalNet = bcadd($totalNet, $diff, 2);
> }
> ```
>
> Ini aman secara akuntansi (pembulatan ke Utang Gaji), tapi **tidak ada audit trail** untuk adjustment amount ini. Auditor akan menanyakan dari mana selisih < Rp 1 ini berasal.

---

## 2. FINANCIAL REPORTING ACCURACY (The Output)

### 2A. Balance Sheet (Neraca)

**Status: PASS dengan CATATAN**

[AccountingService::getBalanceSheet()](app/Services/AccountingService.php#L195) mengklasifikasi dengan benar:

- **Asset** = Debit − Credit (debit-normal)
- **Liability** = Credit − Debit (credit-normal)
- **Equity** = Credit − Debit (credit-normal)
- **Retained Earnings** = computed dari Revenue − Expense yang belum ditutup

$$
\text{Total Assets} = \text{Total Liabilities} + \text{Total Equity} + \text{Retained Earnings}
$$

> **TEMUAN [P1 — MEDIUM-HIGH]**: **Sinkronisasi Persediaan GL ↔ Inventory Module**
>
> Neraca menampilkan saldo akun **1300 (Persediaan)** dari GL, yang seharusnya = `SUM(qty × avg_cost)` dari `inventory_stocks`. Namun:
>
> 1. **Stock Adjustment TIDAK membuat jurnal GL** — Jika admin menambah/kurangi stock via Stock Movement (type=adjustment), hanya `inventory_stocks` yang berubah. Akun 1300 di GL **tidak tersentuh**.
> 2. **Tidak ada reconciliation report** yang membandingkan GL 1300 vs nilai Stock Valuation.
>
> **Dampak**: Setelah stock opname dan adjustment, Neraca akan menunjukkan saldo Persediaan yang **berbeda** dengan laporan Stock Valuation. Ini adalah **material misstatement** yang akan ditangkap auditor.

> **TEMUAN [P3]**: Balance Sheet tidak memisahkan **Aset Lancar vs Aset Tidak Lancar**. Data di COA hanya punya `type: 'asset'` tanpa subklasifikasi. Klasifikasi likuiditas bergantung sepenuhnya pada `code` prefix (11xx=cash, 12xx=AR, 13xx=inventory, 15xx=fixed asset), tapi view menampilkan semua sebagai "Assets" tanpa grouping.

### 2B. Profit & Loss (Laba Rugi)

**Status: PASS**

[AccountingService::getProfitLoss()](app/Services/AccountingService.php#L273) sudah benar:

- Revenue = Credit − Debit (credit-normal)
- Expense = Debit − Credit (debit-normal)
- Net Profit = Revenue − Expense

**Mapping akun dari modul operasional ke P&L**:


| Sumber                 | Akun (Code)                  | Tipe di P&L |
| ---------------------- | ---------------------------- | ----------- |
| Sales Invoice          | 4000 Revenue                 | Revenue     |
| COGS (Sales Delivery)  | 5000 COGS                    | Expense     |
| Payroll Gaji           | 5100 Beban Gaji & Upah       | Expense     |
| Payroll Tunjangan      | 5110 Beban Tunjangan         | Expense     |
| Payroll BPJS Company   | 5120 Beban BPJS Perusahaan   | Expense     |
| PPV                    | 5101 Purchase Price Variance | Expense     |
| Manufacturing Variance | 6500 Manufacturing Variance  | Expense     |
| Supplier Bill (non-PO) | 5000 General Expense         | Expense     |

> **TEMUAN [P2]**: Akun payroll (**5100, 5110, 5120**) dibuat oleh PayrollService tapi **tidak ada di migration COA seeder**. Akun ini hanya ada jika PayrollEngineTest atau manual seeder membuatnya. Jika belum pernah ada payroll yang dijalankan, P&L akan kehilangan line item gaji.

> **TEMUAN [P2]**: **Payroll accounting tidak breakdown per departemen.** Jurnal `postToAccounting()` mengagregasi SELURUH payslip ke satu baris Dr 5100. Jika perusahaan memiliki departemen Produksi, Marketing, Admin — semua beban gaji masuk ke satu akun. Ini melanggar prinsip **cost center accounting** dan membuat laporan biaya per departemen tidak mungkin dilakukan dari GL.

### 2C. Cash Flow (Arus Kas)

**Status: PASS dengan DESAIN BAIK**

[CashFlowService](app/Services/CashFlowService.php) mengimplementasi **Indirect Method** sesuai PSAK 2/IAS 7:

$$
\text{Net Cash} = \text{Net Income} + \text{Depreciation} \pm \Delta\text{Working Capital} + \text{Investing} + \text{Financing}
$$

Kekuatan implementasi:

- ✅ Depreciation add-back terdeteksi via 2 metode (reference `DEP-%` + contra akun depresiasi)
- ✅ Working capital changes otomatis dari `cash_flow_category = 'operating'`
- ✅ **Golden Rule Reconciliation**: Ending Cash (computed) vs Ending Cash (actual GL balance)
- ✅ Discrepancy detection dengan toleransi 0.01

> **TEMUAN [P2]**: Cash Flow **discrepancy hanya dilaporkan, tidak di-enforce**. Jika `has_discrepancy = true`, view hanya menampilkan warning. Tidak ada mekanisme untuk memblokir closing period jika cash flow tidak reconciled. Ini bisa menyebabkan laporan arus kas yang tidak akurat lolos tanpa koreksi.

> **TEMUAN [P3]**: Jurnal **bank transaction** (via BankReconciliationService) menggunakan pembukuan langsung (Dr/Cr Cash). Tapi pembayaran Invoice dan Supplier Bill juga mengakui Cash. Jika kedua metode digunakan bersamaan untuk transaksi yang sama, **double-counting** bisa terjadi.

---

## 3. CLOSING & ADJUSTMENT LOGIC (The Guardrails)

### 3A. Retained Earnings Consistency

**Status: PASS — Excellent Implementation**

[closePeriod()](app/Services/AccountingService.php#L423) dan [reopenPeriod()](app/Services/AccountingService.php#L504):

```
CLOSE: P&L → Closing Journal → Dr Revenue accounts, Cr Expense accounts, net to 3200 (RE)
REOPEN: DELETE closing journal (cascade deletes journal_items) → reset period to 'open'
RE-CLOSE: New closing journal created cleanly
```

**Test coverage (3 tests)**:

- `test_reopen_period_removes_closing_journals()` — Verifies closing journal deleted
- `test_close_reopen_close_cycle_produces_correct_retained_earnings()` — Verifies no double RE
- `test_reopen_already_open_period_throws_exception()` — Guard clause

> **TEMUAN [P3]**: Saat `reopenPeriod()`, jurnal penutup dihapus (`JournalEntry::where()->delete()`). Ini menggunakan cascade delete yang benar, tapi **tidak ada audit trail** untuk penghapusan jurnal penutup. Idealnya, tambahkan AuditLogService::log() sebelum delete.

### 3B. Fiscal Lock Enforcement

**Status: PASS — Dual-Layer Protection**


| Layer      | Mekanisme                                                                                                          |
| ---------- | ------------------------------------------------------------------------------------------------------------------ |
| Middleware | [EnsureOpenFiscalPeriod](app/Http/Middleware/EnsureOpenFiscalPeriod.php) — blocks HTTP writes                     |
| Service    | [AccountingService::isDateInClosedPeriod()](app/Services/AccountingService.php#L410) — blocks programmatic writes |

Middleware applied pada semua mutation routes:

```
middleware('fiscal-lock')           // auto-detect
middleware('fiscal-lock:bill_date') // explicit field
```

> **TEMUAN [P1]**: **Fiscal Lock Default-to-Today Bypass Risk**
>
> Pada [EnsureOpenFiscalPeriod::resolveDate()](app/Http/Middleware/EnsureOpenFiscalPeriod.php#L85):
>
> ```php
> // Default to today — any write in a closed period must be blocked
> return now()->toDateString();
> ```
>
> Jika POST request **tidak menyertakan field tanggal** (misal: API call malformed), middleware default ke `today()`. Jika today() ada di period yang sudah tutup → blocked (safe). Tapi jika today() open, tapi transaksi seharusnya bertanggal di bulan lalu yang sudah closed → **transaksi lolos** fiscal lock karena service-level hanya menerima tanggal dari request.
>
> **Skenario**: User submit form dengan `date` field kosong → middleware checks today (April 2026, open) → PASS → Service receives tanggal dari request (kosong) → menggunakan tanggal default → bisa jadi hari ini (open) meskipun intent user adalah backdate ke bulan lalu.
>
> **Mitigasi**: Field `date` sudah `required` di JournalEntryRequest, jadi ini sebagian ter-mitigasi. Tapi route yang tidak menggunakan FormRequest (misal, beberapa endpoint AP/AR) berpotensi terpapar.

### 3C. Opening Balance

**Status: GAP — Tidak Ada Mekanisme Formal**

> **TEMUAN [P2]**: Sistem **tidak memiliki mekanisme opening balance** formal. Tidak ada:
>
> - Migration atau endpoint untuk input saldo awal saat go-live
> - Jurnal entry khusus bertipe `'opening'` untuk membedakan dari transaksi reguler
> - Entri Equity/Retained Earnings awal
>
> **Implikasi**: Saat migrasi dari sistem lama:
>
> 1. Admin harus membuat jurnal manual → semua masuk ke laporan periode berjalan
> 2. P&L akan terkontaminasi dengan saldo awal jika tidak menggunakan tanggal cut-off yang benar
> 3. Cash Flow akan menghitung saldo awal sebagai "perubahan" bukan saldo awal
>
> **Rekomendasi**: Tambahkan `entry_type = 'opening'` dan exclude dari P&L tapi include di Balance Sheet.

---

## 4. TAX & COMPLIANCE (The Integrity)

### 4A. VAT Reconciliation (PPN)

**Status: PASS**

[TaxService](app/Services/TaxService.php) menyediakan rekonsiliasi PPN yang lengkap:

```
PPN Keluaran (Output VAT) → 2110 (Liability) — dari Invoice
PPN Masukan (Input VAT)   → 1140 (Asset) — dari Supplier Bill
PPN Kurang/Lebih Bayar    → Keluaran − Masukan
```

Method `getSptMasaPPN()` mengagregasi dari dokumen sumber (Invoice & SupplierBill), **bukan dari GL**. Ini adalah **best practice** karena cross-check antara sub-ledger dan GL memberikan validation tambahan.

> **TEMUAN [P3]**: Tidak ada rekonsiliasi otomatis antara `getSptMasaPPN()` (dari sub-ledger) vs saldo GL akun 2110/1140. Jika ada jurnal manual ke akun PPN, SPT dan GL **akan berbeda**. Terkait langsung dengan temuan P0 di section 1B.

### 4B. Audit Trail

**Status: PASS — Best-in-Class Implementation**

[AuditLogService](app/Services/AuditLogService.php) + [ActivityLog](app/Models/ActivityLog.php):


| Fitur                | Status                                              |
| -------------------- | --------------------------------------------------- |
| Immutability         | ✅`update()`/`delete()` throw `RuntimeException`    |
| HMAC-SHA256 Checksum | ✅ Anti-tampering dengan APP_KEY                    |
| Field-Level Diff     | ✅`computeChanges()` mendeteksi perubahan per field |
| Metadata             | ✅ user_id, ip_address, user_agent, session_id      |
| `verifyChecksum()`   | ✅ Perbandingan hash_equals (timing-safe)           |

> **TEMUAN [P2]**: **HMAC Checksum tidak mencakup `old_data`/`new_data`**
>
> ```php
> // computeChecksum() hanya hash:
> [$record['user_id'], $record['module'], $record['action'],
>  $record['description'], $record['ip_address'], $record['created_at']]
> // old_data dan new_data TIDAK termasuk
> ```
>
> **Dampak**: Seseorang dengan akses database bisa mengubah kolom `old_data`/`new_data` (JSON) tanpa terdeteksi oleh checksum. Misalnya: mengubah amount dari 10.000.000 menjadi 1.000.000 di new_data. Verifikasi checksum tetap PASS karena hanya metadata yang di-hash.
>
> **Rekomendasi**: Tambahkan hash dari `old_data` + `new_data` JSON ke dalam payload HMAC.

---

## 5. ANALISIS SWOT & REKOMENDASI FINAL

### STRENGTHS (Kekuatan)


| #   | Kekuatan                             | Detail                                                          |
| --- | ------------------------------------ | --------------------------------------------------------------- |
| S1  | **Triple-Layer Balance Enforcement** | Request → Service → Model, semua menggunakan bcmath           |
| S2  | **Dual Fiscal Lock**                 | Middleware (HTTP) + Service (programmatic) mencegah post-dating |
| S3  | **Immutable Audit Log + HMAC**       | Jejak audit tidak bisa diubah; checksum mendeteksi tampering    |
| S4  | **Proper WAC Implementation**        | Sesuai PSAK 14, menggunakan 4-digit precision, lockForUpdate    |
| S5  | **Cash Flow Indirect Method**        | Full PSAK 2/IAS 7 compliance dengan reconciliation              |
| S6  | **PPV Handling**                     | Accrual-to-bill dengan variance akun terpisah (5101)            |
| S7  | **Manufacturing 2-Stage WIP**        | Material→WIP + WIP→FG, sesuai cost accounting standards       |
| S8  | **Tax Compliance**                   | SPT Masa PPN, PPh 21 TER/Pasal 17 per PMK 168/2023              |
| S9  | **Comprehensive Test Suite**         | 217 tests, covering balance, closing, locks, RBAC               |
| S10 | **Sourceable Polymorphic**           | Drill-down dari GL ke dokumen transaksi (AR, AP, Payroll)       |

### WEAKNESSES (Kelemahan — Hidden Bugs)


| #  | Temuan                                  | Severity | Dampak                                                                          |
| -- | --------------------------------------- | -------- | ------------------------------------------------------------------------------- |
| W1 | **No Control Account Restriction**      | P0       | Manual journal ke AR/AP/Inventory → Sub-ledger ≠ GL.**Material misstatement** |
| W2 | **Stock Adjustment tanpa GL Journal**   | P1       | Setelah stock opname, Neraca Persediaan ≠ Stock Valuation.**Audit finding**    |
| W3 | **HMAC tidak cover data payload**       | P2       | Perubahan`old_data`/`new_data` tidak terdeteksi checksum                        |
| W4 | **Inventory journals tanpa sourceable** | P2       | PO receive/Sales COGS journal tidak drill-downable                              |
| W5 | **Payroll tidak per departemen**        | P2       | Semua gaji ke 5100 — cost center analysis impossible                           |
| W6 | **No Opening Balance mechanism**        | P2       | Go-live migration kontaminasi laporan periode berjalan                          |
| W7 | **Cash Flow discrepancy not enforced**  | P2       | Periode bisa ditutup meski arus kas tidak reconciled                            |
| W8 | **No GL ↔ Stock reconciliation**       | P2       | Tidak ada report pembanding GL 1300 vs inventory value                          |

### OPPORTUNITIES (Peluang Fitur)


| #  | Fitur                                   | Prioritas | Alasan                                                                                           |
| -- | --------------------------------------- | --------- | ------------------------------------------------------------------------------------------------ |
| O1 | **Multi-Currency Accounting**           | High      | Sudah ada kolom`currency_id` + `exchange_rate` di journal/invoice; tinggal implement revaluation |
| O2 | **Budget vs Actual Report**             | High      | BudgetService sudah ada; connect ke dashboard                                                    |
| O3 | **Fixed Asset Depreciation Schedule**   | Medium    | FixedAssetService sudah ada; auto-run monthly                                                    |
| O4 | **Inter-Company Transactions**          | Medium    | Untuk group perusahaan                                                                           |
| O5 | **Cost Center / Department Accounting** | High      | Tambah`department_id` di journal_items                                                           |
| O6 | **Bank Feed Integration**               | Medium    | Auto-import mutasi rekening                                                                      |
| O7 | **e-Faktur Integration**                | High      | Generate faktur pajak electronic                                                                 |

### THREATS (Ancaman)


| #  | Ancaman                                        | Mitigasi                                            |
| -- | ---------------------------------------------- | --------------------------------------------------- |
| T1 | **Auditor menemukan selisih Sub-ledger ↔ GL** | Implement W1 (control account restriction) segera   |
| T2 | **Stock opname menyebabkan silent GL drift**   | Implement W2 (stock adjustment journal)             |
| T3 | **Data tampering pada audit log**              | Fix W3 (HMAC data fields)                           |
| T4 | **Dashboard Mobile menampilkan angka salah**   | Karena W2/W8 — GL Persediaan bisa stale            |
| T5 | **Tax underpayment penalty**                   | Jika manual journal ke 2110 PPN tanpa restrict (W1) |

---

## RINGKASAN EKSEKUTIF

### Skor Audit Keseluruhan: **B+ (Baik, dengan catatan penting)**


| Area                         | Skor   | Catatan                                         |
| ---------------------------- | ------ | ----------------------------------------------- |
| Journal Balance Integrity    | **A**  | Triple-layer validation, bcmath                 |
| Polymorphic Traceability     | **B**  | AR/AP/Payroll ✅, Inventory ✗                  |
| Fiscal Period Locking        | **A-** | Dual-layer, sedikit default-to-today risk       |
| Financial Reports (BS/PL/CF) | **A-** | Correct math, missing liquiditas classification |
| Control Account Protection   | **F**  | Tidak ada — risiko tertinggi                   |
| Stock ↔ GL Sync             | **C**  | Adjustment gap, no reconciliation               |
| Tax Compliance               | **A**  | PPN + PPh 21 TER solid                          |
| Audit Trail                  | **B+** | Immutable + HMAC, tapi data tidak di-hash       |
| Closing Cycle                | **A**  | Tested: close→reopen→close tanpa duplikasi    |
| Test Coverage                | **A**  | 217 tests, 792 assertions                       |

### Rekomendasi Prioritas Implementasi

1. **[URGENT]** Tambahkan `is_system_account` flag pada COA dan block manual journal ke akun yang flagged
2. **[HIGH]** Buat `journalStockAdjustment()` di StockValuationService
3. **[HIGH]** Tambahkan sourceable link ke semua inventory journal methods
4. **[MEDIUM]** Perluas HMAC checksum untuk include `old_data`/`new_data`
5. **[MEDIUM]** Tambahkan `department_id` ke journal_items untuk cost center
6. **[LOW]** Tambahkan `entry_type = 'opening'` untuk saldo awal migrasiMethod `getSptMasaPPN()` mengagregasi dari dokumen sumber (Invoice & SupplierBill), **bukan dari GL**. Ini adalah **best practice** karena cross-check antara sub-ledger dan GL memberikan validation tambahan.

> **TEMUAN [P3]**: Tidak ada rekonsiliasi otomatis antara `getSptMasaPPN()` (dari sub-ledger) vs saldo GL akun 2110/1140. Jika ada jurnal manual ke akun PPN, SPT dan GL **akan berbeda**. Terkait langsung dengan temuan P0 di section 1B.

### 4B. Audit Trail

**Status: PASS — Best-in-Class Implementation**

[AuditLogService](app/Services/AuditLogService.php) + [ActivityLog](app/Models/ActivityLog.php):


| Fitur                | Status                                              |
| -------------------- | --------------------------------------------------- |
| Immutability         | ✅`update()`/`delete()` throw `RuntimeException`    |
| HMAC-SHA256 Checksum | ✅ Anti-tampering dengan APP_KEY                    |
| Field-Level Diff     | ✅`computeChanges()` mendeteksi perubahan per field |
| Metadata             | ✅ user_id, ip_address, user_agent, session_id      |
| `verifyChecksum()`   | ✅ Perbandingan hash_equals (timing-safe)           |

> **TEMUAN [P2]**: **HMAC Checksum tidak mencakup `old_data`/`new_data`**
>
> ```php
> // computeChecksum() hanya hash:
> [$record['user_id'], $record['module'], $record['action'],
>  $record['description'], $record['ip_address'], $record['created_at']]
> // old_data dan new_data TIDAK termasuk
> ```
>
> **Dampak**: Seseorang dengan akses database bisa mengubah kolom `old_data`/`new_data` (JSON) tanpa terdeteksi oleh checksum. Misalnya: mengubah amount dari 10.000.000 menjadi 1.000.000 di new_data. Verifikasi checksum tetap PASS karena hanya metadata yang di-hash.
>
> **Rekomendasi**: Tambahkan hash dari `old_data` + `new_data` JSON ke dalam payload HMAC.

---

## 5. ANALISIS SWOT & REKOMENDASI FINAL

### STRENGTHS (Kekuatan)


| #   | Kekuatan                             | Detail                                                          |
| --- | ------------------------------------ | --------------------------------------------------------------- |
| S1  | **Triple-Layer Balance Enforcement** | Request → Service → Model, semua menggunakan bcmath           |
| S2  | **Dual Fiscal Lock**                 | Middleware (HTTP) + Service (programmatic) mencegah post-dating |
| S3  | **Immutable Audit Log + HMAC**       | Jejak audit tidak bisa diubah; checksum mendeteksi tampering    |
| S4  | **Proper WAC Implementation**        | Sesuai PSAK 14, menggunakan 4-digit precision, lockForUpdate    |
| S5  | **Cash Flow Indirect Method**        | Full PSAK 2/IAS 7 compliance dengan reconciliation              |
| S6  | **PPV Handling**                     | Accrual-to-bill dengan variance akun terpisah (5101)            |
| S7  | **Manufacturing 2-Stage WIP**        | Material→WIP + WIP→FG, sesuai cost accounting standards       |
| S8  | **Tax Compliance**                   | SPT Masa PPN, PPh 21 TER/Pasal 17 per PMK 168/2023              |
| S9  | **Comprehensive Test Suite**         | 217 tests, covering balance, closing, locks, RBAC               |
| S10 | **Sourceable Polymorphic**           | Drill-down dari GL ke dokumen transaksi (AR, AP, Payroll)       |

### WEAKNESSES (Kelemahan — Hidden Bugs)


| #  | Temuan                                  | Severity | Dampak                                                                          |
| -- | --------------------------------------- | -------- | ------------------------------------------------------------------------------- |
| W1 | **No Control Account Restriction**      | P0       | Manual journal ke AR/AP/Inventory → Sub-ledger ≠ GL.**Material misstatement** |
| W2 | **Stock Adjustment tanpa GL Journal**   | P1       | Setelah stock opname, Neraca Persediaan ≠ Stock Valuation.**Audit finding**    |
| W3 | **HMAC tidak cover data payload**       | P2       | Perubahan`old_data`/`new_data` tidak terdeteksi checksum                        |
| W4 | **Inventory journals tanpa sourceable** | P2       | PO receive/Sales COGS journal tidak drill-downable                              |
| W5 | **Payroll tidak per departemen**        | P2       | Semua gaji ke 5100 — cost center analysis impossible                           |
| W6 | **No Opening Balance mechanism**        | P2       | Go-live migration kontaminasi laporan periode berjalan                          |
| W7 | **Cash Flow discrepancy not enforced**  | P2       | Periode bisa ditutup meski arus kas tidak reconciled                            |
| W8 | **No GL ↔ Stock reconciliation**       | P2       | Tidak ada report pembanding GL 1300 vs inventory value                          |

### OPPORTUNITIES (Peluang Fitur)


| #  | Fitur                                   | Prioritas | Alasan                                                                                           |
| -- | --------------------------------------- | --------- | ------------------------------------------------------------------------------------------------ |
| O1 | **Multi-Currency Accounting**           | High      | Sudah ada kolom`currency_id` + `exchange_rate` di journal/invoice; tinggal implement revaluation |
| O2 | **Budget vs Actual Report**             | High      | BudgetService sudah ada; connect ke dashboard                                                    |
| O3 | **Fixed Asset Depreciation Schedule**   | Medium    | FixedAssetService sudah ada; auto-run monthly                                                    |
| O4 | **Inter-Company Transactions**          | Medium    | Untuk group perusahaan                                                                           |
| O5 | **Cost Center / Department Accounting** | High      | Tambah`department_id` di journal_items                                                           |
| O6 | **Bank Feed Integration**               | Medium    | Auto-import mutasi rekening                                                                      |
| O7 | **e-Faktur Integration**                | High      | Generate faktur pajak electronic                                                                 |

### THREATS (Ancaman)


| #  | Ancaman                                        | Mitigasi                                            |
| -- | ---------------------------------------------- | --------------------------------------------------- |
| T1 | **Auditor menemukan selisih Sub-ledger ↔ GL** | Implement W1 (control account restriction) segera   |
| T2 | **Stock opname menyebabkan silent GL drift**   | Implement W2 (stock adjustment journal)             |
| T3 | **Data tampering pada audit log**              | Fix W3 (HMAC data fields)                           |
| T4 | **Dashboard Mobile menampilkan angka salah**   | Karena W2/W8 — GL Persediaan bisa stale            |
| T5 | **Tax underpayment penalty**                   | Jika manual journal ke 2110 PPN tanpa restrict (W1) |

---

## RINGKASAN EKSEKUTIF

### Skor Audit Keseluruhan: **B+ (Baik, dengan catatan penting)**


| Area                         | Skor   | Catatan                                         |
| ---------------------------- | ------ | ----------------------------------------------- |
| Journal Balance Integrity    | **A**  | Triple-layer validation, bcmath                 |
| Polymorphic Traceability     | **B**  | AR/AP/Payroll ✅, Inventory ✗                  |
| Fiscal Period Locking        | **A-** | Dual-layer, sedikit default-to-today risk       |
| Financial Reports (BS/PL/CF) | **A-** | Correct math, missing liquiditas classification |
| Control Account Protection   | **F**  | Tidak ada — risiko tertinggi                   |
| Stock ↔ GL Sync             | **C**  | Adjustment gap, no reconciliation               |
| Tax Compliance               | **A**  | PPN + PPh 21 TER solid                          |
| Audit Trail                  | **B+** | Immutable + HMAC, tapi data tidak di-hash       |
| Closing Cycle                | **A**  | Tested: close→reopen→close tanpa duplikasi    |
| Test Coverage                | **A**  | 217 tests, 792 assertions                       |

### Rekomendasi Prioritas Implementasi

1. **[URGENT]** Tambahkan `is_system_account` flag pada COA dan block manual journal ke akun yang flagged
2. **[HIGH]** Buat `journalStockAdjustment()` di StockValuationService
3. **[HIGH]** Tambahkan sourceable link ke semua inventory journal methods
4. **[MEDIUM]** Perluas HMAC checksum untuk include `old_data`/`new_data`
5. **[MEDIUM]** Tambahkan `department_id` ke journal_items untuk cost center
6. **[LOW]** Tambahkan `entry_type = 'opening'` untuk saldo awal migrasi
