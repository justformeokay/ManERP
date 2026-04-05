# 🏢 Laporan Audit Sistem SDM & Penggajian (HR & Payroll) ManERP

**Auditor**: Senior HR Consultant & Payroll Tax Specialist  
**Tanggal Audit**: 5 April 2026  
**Versi Aplikasi**: ManERP Laravel 12 — Post Phase 3 (190 test, 0 failure)  
**Cakupan**: Employee Master, Salary Structure, Payroll Engine, PPh 21 TER, BPJS, Integrasi GL

---

## Ringkasan Eksekutif

ManERP telah membangun fondasi modul HR & Payroll yang **solid secara arsitektural** — meliputi
PPh 21 metode TER (PMK 168/2023), perhitungan BPJS sesuai regulasi 2024, state machine pada
PayrollPeriod (`draft → approved → posted`), dan integrasi auto-journal ke GL. Namun audit ini
mengidentifikasi **4 bug P0 (Critical)**, **5 isu P1 (High)**, dan **8 isu P2 (Medium)** yang
harus ditangani sebelum modul ini digunakan di lingkungan produksi.

| Severity | Jumlah | Status |
|----------|--------|--------|
| **P0 — Critical** | 4 | Harus diperbaiki sebelum production |
| **P1 — High** | 5 | Diperlukan dalam 1–2 sprint |
| **P2 — Medium** | 8 | Perbaikan terjadwal |
| **P3 — Low** | 5 | Nice-to-have / Enhancement |

---

## 1. Employee Lifecycle & Data Integrity

### 1.1 Employee Master Data

**File**: `app/Models/Employee.php` (113 baris), `database/migrations/2026_04_02_400001_create_hr_payroll_tables.php`

| Field | Status | Catatan |
|-------|--------|---------|
| NIK | ✅ Unique constraint | `string(20)->unique()` |
| Nama | ✅ Required | validated in `EmployeeRequest` |
| Status PTKP | ✅ 8 opsi valid | `TK/0..TK/3, K/0..K/3` sesuai PP 101/2016 |
| Tanggal Bergabung | ✅ Required date | `join_date` |
| Jabatan (Position) | ⚠️ Nullable string | Tidak ada master jabatan — free text |
| Departemen | ⚠️ Nullable string | Tidak ada master departemen — free text |
| NPWP | ✅ Ada | Opsional, max 30 char |
| BPJS TK/KES Number | ✅ Ada | Keduanya opsional |
| Bank Info | ✅ Ada | `bank_name`, `bank_account_number`, `bank_account_name` |
| SoftDeletes | ✅ | Karyawan yang dihapus tidak hilang permanen |

**[P2-HR-1] Tidak Ada Master Departemen & Jabatan**
- `department` dan `position` adalah free-text string. Ini menyebabkan inkonsistensi data
  (misalnya: "IT", "I.T.", "Information Technology" dianggap berbeda).
- **Rekomendasi**: Buat tabel `departments` dan `positions` dengan foreign key.

**[P2-HR-2] Tidak Ada Riwayat Mutasi/Promosi**
- Saat ini tidak ada tabel `employee_transfers` atau `employee_history`. Jika seorang karyawan
  dipindahkan dari Dept A ke Dept B, data lama tertimpa langsung di kolom `department`.
- **Dampak**: Tidak mungkin melacak riwayat karir karyawan untuk reporting atau audit ketenagakerjaan.

### 1.2 Contract Management (PKWT vs PKWTT)

**[P1-HR-1] TIDAK ADA Manajemen Kontrak**
- Model `Employee` tidak memiliki field `employment_type` (PKWT/PKWTT), `contract_start_date`,
  `contract_end_date`, atau `contract_number`.
- **Dampak Hukum**: UU Cipta Kerja (PP 35/2021) mengatur batas perpanjangan PKWT. Tanpa tracking
  kontrak, perusahaan bisa melanggar regulasi tanpa sadar.
- **Dampak Operasional**: Tidak ada notifikasi otomatis saat kontrak akan berakhir.
- **Rekomendasi**: Tambah tabel `employee_contracts` dengan kolom:
  ```
  employee_id, contract_type (pkwt/pkwtt), contract_number,
  start_date, end_date, renewal_count, notes
  ```

### 1.3 Offboarding

**[P2-HR-3] Offboarding Sangat Minimal**
- Proses resign hanya mengisi `resign_date` dan mengubah `status → inactive`.
- **Tidak ada**: Perhitungan sisa cuti yang dibayarkan, uang pesangon (UU Cipta Kerja Pasal 156),
  berita acara serah terima, atau checklist offboarding.
- `EmployeeController::destroy()` menggunakan SoftDelete, tapi tidak ada flow "final payslip"
  untuk karyawan yang keluar di tengah bulan.

---

## 2. Attendance & Leave Management

### **[P0-HR-1] 🔴 TIDAK ADA Modul Kehadiran & Cuti**

Ini adalah temuan paling kritis dalam audit ini.

**Tidak ditemukan** tabel, model, atau service untuk:
- ❌ `attendances` — Absensi harian (clock in/out)
- ❌ `leave_types` — Jenis cuti (tahunan, sakit, melahirkan, dll)
- ❌ `leave_requests` — Pengajuan cuti
- ❌ `leave_balances` — Saldo jatah cuti
- ❌ `overtime_requests` — Pengajuan lembur

**Konsekuensi**:
1. **Keterlambatan, Pulang Cepat, Lembur** — Tidak bisa dihitung otomatis. Kolom
   `overtime_hours` dan `absence_deduction` pada payslip harus di-input **manual** via
   parameter `overrides` saat generate payroll (lihat `PayrollService::generatePayslips()` L226).
2. **Jatah Cuti Tahunan** — Tidak ada mekanisme akrual cuti (12 hari/tahun per UU 13/2003
   Pasal 79), tidak ada carry-forward, tidak ada hangus otomatis.
3. **Celah Manipulasi** — Karena `overtime_hours` dan `absence_deduction` di-input manual tanpa
   validasi dari data absensi mesin, pihak HR bisa memanipulasi jumlah lembur/potongan tanpa
   jejak audit yang memadai.

**Rekomendasi Arsitektur**:
```
┌──────────────┐     ┌──────────────┐     ┌──────────────┐
│ attendances  │────→│  payroll     │←────│leave_requests│
│ (clock data) │     │  engine      │     │ (approved)   │
└──────────────┘     └──────────────┘     └──────────────┘
        ↓                    ↓
  Auto-calculate       overtime_hours
  absence_deduction    auto-populated
```

---

## 3. Payroll Engine — Analisis Mendalam

### 3.1 PPh 21 — Kepatuhan TER (PMK 168/2023)

**File**: `app/Services/PayrollService.php` L112–204, `app/Models/Pph21TerRate.php`

#### ✅ Sudah Benar

| Aspek | Implementasi | Referensi |
|-------|-------------|-----------|
| Metode TER bulan 1–11 | `PPh = Bruto × TER rate` | PMK 168/2023 Pasal 2(2) |
| Penyesuaian Desember | `PPh = Pajak Tahunan (Pasal 17) − Σ PPh(Jan..Nov)` | PMK 168/2023 Pasal 17 |
| Tabel TER 3 Kategori | 125 baris (A:44, B:40, C:41) sesuai Lampiran PP 58/2023 | PP 58/2023 |
| Biaya Jabatan | 5% maks Rp6.000.000/tahun | Pasal 21 ayat (3) |
| PTKP | 8 status, nilai sesuai PP 101/2016 (masih berlaku) | PP 101/PMK-PTKP/2016 |
| Pasal 17 brackets | 5%, 15%, 25%, 30%, 35% per UU HPP 7/2021 | UU HPP 7/2021 Pasal 17 |

#### ⚠️ Bug & Risiko

**[P1-HR-2] Desember: Basis BPJS Employee Menggunakan Gross, Bukan Monthly Fixed**
- **Lokasi**: `PayrollService::calculatePph21December()` L159–160
- **Kode bermasalah**:
  ```php
  $annualJhtEmployee = $annualGross * self::JHT_EMPLOYEE;  // 2%
  $annualJpEmployee  = min($annualGross, self::JP_MAX_SALARY * 12) * self::JP_EMPLOYEE; // 1%
  ```
- **Masalah**: BPJS JHT & JP dihitung dari `basic_salary + fixed_allowance` (monthly fixed),
  bukan dari `gross_salary`. Menggunakan gross sebagai basis menyebabkan pengurang neto
  over-estimated untuk karyawan dengan banyak tunjangan variabel/lembur.
- **Dampak**: PPh 21 Desember bisa **kurang bayar** karena neto dihitung lebih rendah dari seharusnya.
- **Fix**: Simpan kumulatif `monthly_fixed` per karyawan selama setahun, atau query dari payslip.

**[P1-HR-3] TER Category Tidak Auto-Derived dari PTKP**
- **Lokasi**: `app/Http/Requests/EmployeeRequest.php` L31 — `ter_category` di-input manual.
- **Masalah**: Per PMK 168/2023, mapping TER category ditentukan oleh PTKP:
  | PTKP | TER Category |
  |------|-------------|
  | TK/0, TK/1 | **A** |
  | TK/2, TK/3, K/0, K/1 | **B** |
  | K/2, K/3 | **C** |
- Jika user salah memilih (misal: PTKP=K/3 tapi TER=A), pajak bulanan akan **jauh lebih kecil**
  dari seharusnya, menyebabkan koreksi besar di Desember.
- **Rekomendasi**: Auto-set `ter_category` dari `ptkp_status` pada model level. Hapus input
  manual di form.

**[P2-HR-4] Tidak Ada Penanganan Mid-Year Entry/Exit**
- Jika karyawan bergabung di bulan Juni, perhitungan Desember menggunakan `annualGrossSoFar`
  dari payslip Jan–Nov. Karena Jan–Mei kosong (belum ada payslip), gross annualized lebih kecil
  dan PTKP tetap penuh setahun — ini **sudah benar** per regulasi (PTKP disetahunkan).
- **Namun**, untuk karyawan yang **resign** di tengah tahun, tidak ada mekanisme final settlement
  (perhitungan ulang PPh 21 proporsional). Payslip terakhir dihitung sebagai bulan biasa tanpa
  adjustment Pasal 17.

**[P2-HR-5] Bonus/THR Tidak Dimodelkan**
- Kolom `other_earnings` bisa menampung THR/bonus, tapi TER rate di-lookup berdasarkan
  `gross_salary` (yang sudah termasuk THR). Ini **benar** per PMK 168/2023 karena TER memang
  dihitung dari total bruto bulanan termasuk penghasilan tidak teratur.
- **Namun**, tidak ada field khusus untuk tracking THR vs bonus vs insentif untuk pelaporan
  SPT 1721.

### 3.2 BPJS — Perhitungan & Batas Atas

**File**: `app/Services/PayrollService.php` L64–104

| Komponen | Rate | Basis | Cap | Status |
|----------|------|-------|-----|--------|
| JHT Company | 3.7% | Monthly Fixed | Tidak ada ceiling | ✅ Benar |
| JHT Employee | 2% | Monthly Fixed | Tidak ada ceiling | ✅ Benar |
| JKK Company | 0.24% | Monthly Fixed | — | ⚠️ Hardcoded (lihat P2-HR-6) |
| JKM Company | 0.3% | Monthly Fixed | — | ✅ Benar |
| JP Company | 2% | Monthly Fixed | Cap Rp10.042.300 | ✅ Benar (2024) |
| JP Employee | 1% | Monthly Fixed | Cap Rp10.042.300 | ✅ Benar |
| KES Company | 4% | Gross Salary | Floor Rp2.942.421, Cap Rp12.000.000 | ✅ Benar |
| KES Employee | 1% | Gross Salary | Floor Rp2.942.421, Cap Rp12.000.000 | ✅ Benar |

**[P2-HR-6] JKK Rate Hardcoded Sebagai Risiko Rendah**
- `JKK_RATE = 0.0024` (0.24%) — ini untuk kelompok risiko **sangat rendah** (KR-I).
- Per PP 44/2015, ada 5 kelompok risiko:
  | Kelompok | Rate |
  |----------|------|
  | KR-I (Sangat Rendah) | 0.24% |
  | KR-II (Rendah) | 0.54% |
  | KR-III (Sedang) | 0.89% |
  | KR-IV (Tinggi) | 1.27% |
  | KR-V (Sangat Tinggi) | 1.74% |
- Jika perusahaan client bergerak di manufaktur (KR-III), perhitungan JKK **under-reported**.
- **Rekomendasi**: Tambahkan kolom `jkk_risk_group` di tabel company settings atau per-employee.

**[P3-HR-1] Ceiling JP dan KES Bisa Berubah Tahunan**
- `JP_MAX_SALARY = 10042300` dan `BPJS_KES_MAX_SALARY = 12000000` di-hardcode sebagai konstanta.
- BPJS menaikkan ceiling ini setiap tahun. Idealnya disimpan di tabel konfigurasi atau settings.

### 3.3 Komponen Gaji — Fleksibilitas

**File**: `app/Models/SalaryStructure.php` (51 baris)

| Komponen | Tipe | Konfigurasi |
|----------|------|-------------|
| Gaji Pokok (`basic_salary`) | Tetap | Per karyawan ✅ |
| Tunj. Tetap (`fixed_allowance`) | Tetap | Per karyawan ✅ |
| Tunj. Makan (`meal_allowance`) | Semi-tetap | Per karyawan ✅ |
| Tunj. Transport (`transport_allowance`) | Semi-tetap | Per karyawan ✅ |
| Tarif Lembur (`overtime_rate`) | Per jam | Per karyawan ✅ |
| Custom Components | ❌ | **Tidak ada** |

**[P2-HR-7] Komponen Gaji Tidak Extensible**
- Komponen gaji di-hardcode sebagai kolom (`basic_salary`, `fixed_allowance`, dll) alih-alih
  menggunakan desain EAV (Entity-Attribute-Value) atau tabel `salary_components`.
- Jika perusahaan ingin menambah "Tunjangan Jabatan", "Komisi Penjualan", atau "Insentif
  Kehadiran", harus modify migration dan seluruh pipeline payroll.
- **Rekomendasi jangka panjang**: Tabel `salary_components` + `salary_component_values`.

---

## 4. Integrasi Akuntansi (The Money Trail)

### 4.1 Payroll Journal — Kapan Beban Diakui?

**File**: `app/Services/PayrollService.php` L440–510

**Metode saat ini**: **Accrual basis** — Beban diakui saat payroll di-*post* (`postToAccounting()`).

```
┌──────────────────────────────────────────────────┐
│              PAYROLL JOURNAL ENTRY                 │
├────────────────────────┬─────────┬────────────────┤
│ Account                │ Debit   │ Credit         │
├────────────────────────┼─────────┼────────────────┤
│ 5100 Beban Gaji & Upah │ Σ basic+OT│              │
│ 5110 Beban Tunjangan   │ Σ allowances│            │
│ 5120 Beban BPJS Co.    │ Σ BPJS co │              │
│ 2110 Utang Gaji        │         │ Σ net salary   │
│ 2120 Utang PPh 21      │         │ Σ PPh 21       │
│ 2130 Utang BPJS        │         │ Σ BPJS all     │
├────────────────────────┼─────────┼────────────────┤
│ Total                  │   X     │      X         │
└────────────────────────┴─────────┴────────────────┘
```

**Jurnal ini BENAR secara standar akuntansi (PSAK)** — beban gaji diakui saat terjadi (accrual),
bukan saat dibayar. Pembayaran aktual (Dr Utang Gaji / Cr Kas 1100) dilakukan terpisah.

#### ⚠️ Isu Integrasi

**[P0-HR-2] 🔴 Jurnal Payroll Tidak Memiliki Sourceabe Link**
- `postToAccounting()` memanggil `$this->accountingService->createJournalEntry()` **tanpa**
  parameter `sourceableType` dan `sourceableId`.
- Ini bertentangan dengan sistem drill-down yang baru diimplementasikan di Phase 3 (TUGAS 4).
- **Dampak**: Jurnal payroll tidak bisa di-trace balik ke `PayrollPeriod` dari ledger.

**[P0-HR-3] 🔴 Jurnal Payroll Menggunakan `round()` Bukan `bcmath`**
- Aggregasi di `postToAccounting()` L458–463 menggunakan `round()`:
  ```php
  $totalBasicOT = round($payslips->sum(...), 2);
  ```
- Semua service lain sudah menggunakan `bcmath` per standar Phase 3.
- **Dampak**: Potensi floating-point drift saat jumlah karyawan besar.

**[P1-HR-4] Jurnal Payroll Mengandalkan `cal_days_in_month()`**
- L481: `cal_days_in_month(CAL_GREGORIAN, $period->month, $period->year)` memerlukan PHP
  extension `calendar`. Extension ini **tidak** tersedia secara default pada beberapa environment
  (Alpine Linux, beberapa Docker image).
- **Fix**: Gunakan `Carbon::create($period->year, $period->month, 1)->endOfMonth()->day`.

**[P2-HR-8] Tidak Ada Jurnal Pembayaran Gaji**
- Hanya ada jurnal accrual (Dr Beban / Cr Utang). Tidak ada mekanisme otomatis untuk jurnal
  payment (Dr Utang Gaji 2110 / Cr Kas 1100) saat gaji ditransfer ke rekening karyawan.
- Saat ini pembayaran gaji harus dijurnal manual.

### 4.2 Sourceable Link — Drill-Down

Setelah Phase 3 patch, `createJournalEntry()` menerima parameter `sourceableType` dan
`sourceableId`. Namun PayrollService **belum diupdate** untuk memanfaatkan fitur ini.

| Service | Sourceable Passed? |
|---------|-------------------|
| FinanceService | ✅ Invoice, Payment |
| AccountsPayableService | ✅ SupplierBill, SupplierPayment |
| PayrollService | ❌ **TIDAK** |
| StockValuationService | ❌ (out of scope audit ini) |
| FixedAssetService | ❌ (out of scope audit ini) |

---

## 5. Security & Access Control

### 5.1 RBAC

**[P0-HR-4] 🔴 Permission `hr.*` TIDAK Di-Seed ke Admin**
- **Lokasi**: `database/seeders/DatabaseSeeder.php` L29–41
- Array `permissions` admin **tidak mengandung** `hr.view`, `hr.create`, `hr.edit`, `hr.delete`.
- **Dampak pada admin**: Tidak ada — karena `User::hasPermission()` mengembalikan `true` untuk
  semua admin (bypass di L78: `if ($this->isAdmin()) return true;`).
- **Dampak pada staff**: Tidak mungkin memberikan akses HR ke staff user karena permission key
  tersebut tidak ada di daftar permission yang dikenali sistem. UI hanya menampilkan permission
  yang ada di seeder.
- **Rekomendasi**: Tambahkan `hr.view`, `hr.create`, `hr.edit`, `hr.delete` ke admin permissions.

### 5.2 Route Priority Bug

**[P1-HR-5] Route `/{period}` Menangkap `/payslip/{payslip}`**
- **Lokasi**: `routes/web.php` L471–472
- Urutan saat ini:
  ```php
  Route::get('/{period}', ...)->name('show');           // L471
  Route::get('/payslip/{payslip}', ...)->name('payslip'); // L472
  ```
- `GET /hr/payroll/payslip/5` → Laravel mencocokkan `/{period}` dengan `period=payslip` →
  Model binding gagal → 404 error.
- **Fix**: Pindahkan route `/payslip/{payslip}` **sebelum** `/{period}`.

### 5.3 Data Privacy

**[P2-HR-9] Tidak Ada Enkripsi Gaji di Database**
- Kolom `basic_salary`, `net_salary`, `pph21_amount`, dll disimpan sebagai `decimal(15,2)`
  tanpa enkripsi. Siapa pun dengan akses database langsung dapat melihat seluruh data gaji.
- **Rekomendasi**: Untuk compliance Keputusan Menteri Ketenagakerjaan tentang perlindungan
  upah — pertimbangkan enkripsi at-rest untuk kolom sensitif, atau minimal pastikan database
  access terbatas ketat.

### 5.4 Authorization Gaps

**[P2-HR-10] FormRequest `authorize()` Return `true` Tanpa Syarat**
- `EmployeeRequest::authorize()` L12 mengembalikan `true` tanpa pengecekan role/permission.
- Saat ini bergantung **sepenuhnya** pada route middleware.
- **Risiko**: Jika route middleware dihapus/diubah, tidak ada secondary check.

---

## 6. Missing Test Coverage

**[P0 — PRODUCTION BLOCKER]**

| File | Exists? |
|------|---------|
| `tests/Feature/PayrollTest.php` | ❌ TIDAK ADA |
| `tests/Feature/EmployeeTest.php` | ❌ TIDAK ADA |
| `database/factories/EmployeeFactory.php` | ❌ TIDAK ADA |

Seluruh modul HR & Payroll **tidak memiliki SATU PUN test**. Ini adalah blocker untuk production
deployment mengingat:
- Perhitungan pajak yang kompleks (TER + Pasal 17)
- BPJS dengan multiple caps
- Integrasi GL dengan balanced journal entries

**Test Yang Wajib Ditulis**:
1. `test_bpjs_calculation_with_caps` — JP cap, KES floor/ceiling
2. `test_pph21_ter_jan_to_nov` — TER rate lookup dan kalkulasi
3. `test_pph21_december_adjustment` — Pasal 17 tahunan minus YTD
4. `test_payslip_generation_balanced` — Gross - Deductions = Net
5. `test_post_to_accounting_creates_balanced_journal` — Dr = Cr
6. `test_payroll_state_machine` — draft → approved → posted transitions
7. `test_employee_crud_with_permissions` — RBAC enforcement
8. `test_duplicate_period_prevention` — unique(month, year)

---

## 7. Analisis SWOT

### ✅ Kekuatan (Strengths)

| # | Kekuatan | Detail |
|---|----------|--------|
| S1 | **PPh 21 TER Compliance** | Implementasi lengkap PMK 168/2023 dengan 125 baris tarif TER 3 kategori dan mekanisme adjustment Desember via Pasal 17. Sangat sedikit ERP lokal yang sudah menerapkan ini dengan benar. |
| S2 | **BPJS Calculation Lengkap** | JHT, JKK, JKM, JP, KES — semua dengan persentase dan ceiling 2024. Pemisahan company vs employee portion benar. |
| S3 | **State Machine Payroll** | `draft → approved → posted` dengan kemampuan revert `approved → draft`. Ini mencegah posting teledor. |
| S4 | **Auto-Journal Integration** | Posting ke GL otomatis dengan balanced double-entry. Rounding adjustment mechanism ada. |
| S5 | **Audit Trail** | Semua controller action menggunakan `Auditable` trait — setiap perubahan terekam. |
| S6 | **SoftDeletes pada Employee** | Karyawan yang dihapus dapat dipulihkan. Payslip history tidak hilang. |

### ⚠️ Kelemahan (Weaknesses)

| # | Kelemahan | Severity | Dampak |
|---|-----------|----------|--------|
| W1 | **Tidak ada modul Kehadiran & Cuti** | P0 | Lembur dan potongan di-input manual tanpa validasi dari data riil |
| W2 | **Tidak ada manajemen kontrak (PKWT/PKWTT)** | P1 | Risiko pelanggaran regulasi ketenagakerjaan |
| W3 | **TER category manual (bisa mismatch PTKP)** | P1 | Pajak bulanan salah hitung |
| W4 | **Desember BPJS basis salah** | P1 | PPh 21 akhir tahun kurang/lebih bayar |
| W5 | **Tidak ada test untuk modul HR/Payroll** | P0 | Zero confidence pada akurasi perhitungan |
| W6 | **Komponen gaji hardcoded** | P2 | Tidak fleksibel untuk perusahaan berbeda |
| W7 | **Payslip tidak bisa export PDF** | P2 | Operasional HR terhambat |

### 🚀 Peluang (Opportunities)

| # | Peluang | Business Value |
|---|---------|---------------|
| O1 | **Employee Self-Service (ESS)** | Karyawan akses slip gaji, ajukan cuti, lihat sisa cuti via app/portal. Mengurangi beban admin HR 60–80%. |
| O2 | **Loan Management (Kasbon)** | Sudah ada kolom `loan_deduction` — tinggal buat modul tracking kasbon berjalan dengan angsuran otomatis per bulan. |
| O3 | **Direct Labor Cost → HPP** | Integrasi gaji produksi ke Work Order costings. Data `department` karyawan bisa digunakan untuk alokasi biaya ke WIP. |
| O4 | **Multi-Company Payroll** | Dengan arsitektur yang ada, bisa ditambahkan `company_id` pada Employee & PayrollPeriod. |
| O5 | **e-Filing SPT 1721** | Data TER, PTKP, dan gross salary sudah lengkap — bisa generate CSV/XML untuk DJP Online. |
| O6 | **Attendance Integration** | Integrasi dengan mesin absen (fingerprint/face) via API — auto-sync data kehadiran ke payroll. |

### ⛔ Ancaman (Threats)

| # | Ancaman | Mitigasi |
|---|---------|---------|
| T1 | **Perubahan regulasi pajak** | Ceiling BPJS & TER rate disimpan di tabel, tapi JKK rate & beberapa konstanta di-hardcode |
| T2 | **Data gaji bocor** | Tidak ada enkripsi at-rest; bergantung sepenuhnya pada RBAC route middleware |
| T3 | **Duplikasi pembayaran** | Unique constraint `(payroll_period_id, employee_id)` mencegah duplikasi payslip — ✅ sudah handled |
| T4 | **Regenerasi payslip di status non-draft** | `generatePayslips()` tidak cek status period — bisa timpa data approved |

---

## 8. Fitur Yang Disarankan Ditiadakan / Disederhanakan

| Fitur | Alasan | Rekomendasi |
|-------|--------|-------------|
| **Performance Appraisal / KPI** | Over-engineered untuk startup tahap awal. Setiap perusahaan punya metode berbeda (OKR, BSC, 360). | ❌ Jangan buat dulu. Fokus ke core payroll & attendance. |
| **Multi-shift / Roster Management** | Sangat kompleks (shift rotation, swap, handover). Hanya relevan untuk manufaktur/retail. | ❌ Tunda ke Phase 5. Gunakan static shift dulu. |
| **Recruitment Pipeline (ATS)** | Bukan core ERP. Banyak SaaS ATS lebih baik (Kalibrr, LinkedIn). | ❌ Out of scope. Integrasikan via API jika perlu. |
| **Detailed Tax Reporting per Form** | 1721-A1, 1721-VI, Bukti Potong — kompleksitas tinggi. | ⏸️ Tunda sampai payroll core sudah stabil. Data foundation sudah cukup. |

---

## 9. Tabel Prioritas Bug-Fix

### P0 — CRITICAL (Harus sebelum production)

| ID | Bug | Service / File | Fix |
|----|-----|---------------|-----|
| P0-HR-1 | Tidak ada modul kehadiran & cuti | — (belum ada) | Buat tabel + service minimal |
| P0-HR-2 | Jurnal payroll tidak ada sourceable link | `PayrollService::postToAccounting()` | Tambah `PayrollPeriod::class, $period->id` |
| P0-HR-3 | Jurnal payroll pakai `round()` bukan `bcmath` | `PayrollService::postToAccounting()` | Migrasi ke `bcadd`/`bcsub` |
| P0-HR-4 | HR permissions tidak di-seed | `DatabaseSeeder.php` | Tambah `hr.view/create/edit/delete` |

### P1 — HIGH (Sprint berikutnya)

| ID | Bug | Fix |
|----|-----|-----|
| P1-HR-1 | Tidak ada manajemen kontrak PKWT/PKWTT | Buat tabel `employee_contracts` |
| P1-HR-2 | Desember BPJS basis menggunakan gross, bukan monthly fixed | Query dari payslip YTD |
| P1-HR-3 | TER category bisa mismatch PTKP | Auto-derive dari PTKP, hapus input manual |
| P1-HR-4 | `cal_days_in_month()` butuh ext `calendar` | Ganti ke Carbon |
| P1-HR-5 | Route `/{period}` menangkap `/payslip/{payslip}` | Pindahkan route sebelum wildcard |

### P2 — MEDIUM (Terjadwal)

| ID | Bug | Fix |
|----|-----|-----|
| P2-HR-1 | Dept & position free text | Buat master table |
| P2-HR-2 | Tidak ada riwayat mutasi | Buat `employee_transfers` |
| P2-HR-3 | Offboarding minimal | Final settlement calculation |
| P2-HR-4 | Mid-year exit tanpa adjustment | Final PPh 21 settlement |
| P2-HR-5 | THR/Bonus tidak dimodelkan terpisah | Tambah field tracked |
| P2-HR-6 | JKK rate hardcoded | Settings / per-company config |
| P2-HR-7 | Komponen gaji tidak extensible | Tabel `salary_components` |
| P2-HR-8 | Tidak ada jurnal pembayaran gaji | Dr Utang / Cr Kas otomatis |
| P2-HR-9 | Tidak ada enkripsi gaji di DB | Encrypted casting / at-rest |
| P2-HR-10 | FormRequest authorize() always true | Add permission check |

### P3 — LOW (Nice-to-have)

| ID | Issue | Note |
|----|-------|------|
| P3-HR-1 | BPJS ceiling hardcoded | Pindah ke settings table |
| P3-HR-2 | Payslip item label hardcoded bahasa Indonesia | Gunakan `__()` |
| P3-HR-3 | PayrollPeriod month label hardcoded | Gunakan `__()` |
| P3-HR-4 | Tidak ada payslip PDF export | Buat PDF route + template |
| P3-HR-5 | Tidak ada Employee Self-Service | Enhancement masa depan |

---

## 10. Kesimpulan

ManERP memiliki **fondasi payroll engine yang kuat dan tax-compliant**. Implementasi PPh 21 TER
(PMK 168/2023) dengan 125 tarif, adjustment Desember via Pasal 17, dan BPJS calculation sesuai
regulasi 2024 menempatkan sistem ini **di atas rata-rata** dibanding ERP lokal kelas UMKM.

Namun, **ketiadaan modul kehadiran (attendance) dan cuti (leave)** adalah lubang besar yang
membuat seluruh payroll pipeline bergantung pada input manual — rentan terhadap kesalahan
manusia dan manipulasi.

**Prioritas utama**:
1. Tulis test suite untuk payroll engine (P0)
2. Fix bug sourceable & bcmath pada jurnal payroll (P0)
3. Seed HR permissions (P0)
4. Fix route priority bug (P1)
5. Auto-derive TER category dari PTKP (P1)
6. Buat modul kehadiran & cuti minimal (P0, tapi bisa staged)

> *"Payroll yang akurat bukan soal kecepatan — tapi soal kepercayaan.  
> Satu slip gaji yang salah bisa menghancurkan moral seluruh perusahaan."*

---

**Audit disusun pada 5 April 2026 | Baseline: 190 tests, 0 failures**  
**File inventory scanned**: 17 source files, 10 Blade views, 6 DB tables, 125 TER rate rows
