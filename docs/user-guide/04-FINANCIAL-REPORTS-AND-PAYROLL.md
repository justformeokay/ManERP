# Chapter 4 — Financial Reports & Payroll
# Bab 4 — Laporan Keuangan & Penggajian
# 제4장 — 재무보고서 및 급여
# 第4章 — 财务报告与薪资

---

## 4.1 Cash Flow Statement (PSAK 2 / IAS 7)

### 🇬🇧 English

ManERP generates the **Cash Flow Statement** (`Cash Flow Statement`) in compliance with **PSAK 2** (Indonesian accounting standard) and **IAS 7** (international standard) using the **indirect method**.

**How to Generate:**

1. Navigate to **Accounting** → **Financial Reports** (`Financial Reports`).
2. Select **"Cash Flow Statement"** (`Cash Flow Statement`).
3. Choose the reporting period (start date and end date).
4. Click **"Generate"** to view the report.
5. Use the **"Export PDF"** or **"Export Excel"** buttons to download.

📸 **[SCREENSHOT: Cash Flow Statement report page with period selector and Export buttons — English UI]**

**Report Structure:**

| Section | Description | Examples |
|---------|-------------|----------|
| **Operating Activities** | Cash from day-to-day business operations | Customer receipts, supplier payments, salary payments, tax payments |
| **Investing Activities** | Cash from asset purchases/sales | Purchase/disposal of fixed assets, investment securities |
| **Financing Activities** | Cash from debt and equity | Loan proceeds/repayments, owner's equity contributions |

**Key Line Items:**

| Line Item | EN | ID |
|-----------|----|----|
| Net Income | Net Income | Laba Bersih |
| Depreciation | Depreciation | Penyusutan |
| Changes in Working Capital | Changes in Working Capital | Perubahan Modal Kerja |
| Net Cash from Operating | Net Cash from Operating Activities | Kas Bersih dari Aktivitas Operasi |
| Net Cash from Investing | Net Cash from Investing Activities | Kas Bersih dari Aktivitas Investasi |
| Net Cash from Financing | Net Cash from Financing Activities | Kas Bersih dari Aktivitas Pendanaan |
| Cash Reconciliation | Beginning Cash → Ending Cash | Rekonsiliasi Kas Awal → Kas Akhir |

> **How Classification Works:** Each account in the **Chart of Accounts** (`Chart of Accounts` / `Daftar Akun`) has a `cash_flow_category` attribute (operating/investing/financing). This determines which section the account's transactions appear in on the cash flow report.

📸 **[SCREENSHOT: Chart of Accounts showing cash_flow_category column — English UI]**

---

### 🇮🇩 Bahasa Indonesia

ManERP menghasilkan **Laporan Arus Kas** (`Laporan Arus Kas`) sesuai **PSAK 2** dan **IAS 7** menggunakan **metode tidak langsung**.

**Cara Menghasilkan:**

1. Navigasi ke **Akuntansi** → **Laporan Keuangan** (`Laporan Keuangan`).
2. Pilih **"Laporan Arus Kas"** (`Laporan Arus Kas`).
3. Pilih periode pelaporan (tanggal awal dan akhir).
4. Klik **"Hasilkan"** untuk melihat laporan.
5. Gunakan tombol **"Ekspor PDF"** atau **"Ekspor Excel"** untuk mengunduh.

📸 **[SCREENSHOT: Halaman laporan Arus Kas dengan pemilih periode dan tombol Ekspor — UI Bahasa Indonesia]**

**Struktur Laporan:**

| Bagian | Deskripsi |
|--------|-----------|
| **Aktivitas Operasi** | Kas dari operasi bisnis harian — penerimaan pelanggan, pembayaran pemasok, gaji, pajak |
| **Aktivitas Investasi** | Kas dari pembelian/penjualan aset — aset tetap, sekuritas investasi |
| **Aktivitas Pendanaan** | Kas dari hutang dan ekuitas — pencairan/pelunasan pinjaman, kontribusi pemilik |

> **Cara Klasifikasi Bekerja:** Setiap akun di **Daftar Akun** (`Daftar Akun`) memiliki atribut `cash_flow_category` (operasi/investasi/pendanaan) yang menentukan di bagian mana transaksi akun tersebut muncul pada laporan arus kas.

---

### 🇰🇷 한국어

ManERP는 **간접법**을 사용하여 **PSAK 2** 및 **IAS 7**에 준거한 **현금흐름표** (`현금흐름표`)를 생성합니다.

**생성 방법:**

1. **회계** → **재무보고서** (`재무보고서`)로 이동합니다.
2. **"현금흐름표"** (`현금흐름표`)를 선택합니다.
3. 보고 기간(시작일 및 종료일)을 선택합니다.
4. **"생성"**을 클릭하여 보고서를 확인합니다.
5. **"PDF 내보내기"** 또는 **"Excel 내보내기"** 버튼으로 다운로드합니다.

📸 **[SCREENSHOT: 기간 선택기와 내보내기 버튼이 있는 현금흐름표 보고서 페이지 — 한국어 UI]**

| 구분 | 설명 |
|------|------|
| **영업활동** | 일상 업무에서 발생한 현금 — 고객 수금, 공급업체 지급, 급여, 세금 |
| **투자활동** | 자산 매입/매각에서 발생한 현금 — 고정자산, 투자증권 |
| **재무활동** | 부채 및 자본에서 발생한 현금 — 차입금, 자본 출자 |

---

### 🇨🇳 中文

ManERP使用**间接法**生成符合 **PSAK 2** 和 **IAS 7** 的**现金流量表** (`现金流量表`)。

**生成方法：**

1. 导航至**会计** → **财务报告** (`财务报告`)。
2. 选择**"现金流量表"** (`现金流量表`)。
3. 选择报告期间（开始日期和结束日期）。
4. 点击**"生成"**查看报告。
5. 使用**"导出PDF"**或**"导出Excel"**按钮下载。

📸 **[SCREENSHOT: 带期间选择器和导出按钮的现金流量表报告页面 — 中文UI]**

| 板块 | 描述 |
|------|------|
| **经营活动** | 日常业务运营产生的现金 — 客户收款、供应商付款、工资、税款 |
| **投资活动** | 资产买卖产生的现金 — 固定资产、投资证券 |
| **筹资活动** | 债务和权益产生的现金 — 贷款、所有者权益出资 |

---

## 4.2 Balance Sheet & Profit/Loss

### 🇬🇧 English

**Balance Sheet** (`Balance Sheet` / `Neraca Keuangan` / `대차대조표` / `资产负债表`)

1. Navigate to **Accounting** → **Financial Reports** (`Financial Reports`).
2. Select **"Balance Sheet"** (`Balance Sheet`).
3. Choose the reporting **as-of date**.
4. The report displays:
   - **Assets** — Current Assets + Non-Current Assets (Fixed Assets, etc.)
   - **Liabilities** — Current Liabilities (AP, accruals) + Non-Current Liabilities
   - **Equity** — Share Capital + Retained Earnings + Current Year P&L
5. The fundamental equation must balance: **Assets = Liabilities + Equity**
6. Click **"Export PDF"** or **"Export Excel"** to download.

📸 **[SCREENSHOT: Balance Sheet report showing Assets = Liabilities + Equity — English UI]**

**Profit & Loss Statement** (`Profit & Loss` / `Laba Rugi` / `손익계산서` / `损益表`)

1. Select **"Profit & Loss"** from the Financial Reports menu.
2. Choose the reporting period.
3. The report shows:
   - **Revenue** — Sales income, service revenue
   - **Cost of Goods Sold (COGS)** — Direct material and manufacturing costs
   - **Gross Profit** = Revenue − COGS
   - **Operating Expenses** — Salary, rent, utilities, depreciation
   - **Net Income** = Gross Profit − Operating Expenses ± Other Income/Expense
4. Export as PDF or Excel.

📸 **[SCREENSHOT: Profit & Loss report with Revenue, COGS, and Net Income sections — English UI]**

---

### 🇮🇩 Bahasa Indonesia

**Neraca Keuangan** (`Neraca Keuangan`)

1. Navigasi ke **Akuntansi** → **Laporan Keuangan** (`Laporan Keuangan`).
2. Pilih **"Neraca Keuangan"** (`Neraca Keuangan`).
3. Pilih **tanggal per** untuk pelaporan.
4. Laporan menampilkan:
   - **Aset** — Aset Lancar + Aset Tidak Lancar (Aset Tetap, dll.)
   - **Kewajiban** — Kewajiban Lancar (Hutang Usaha, akrual) + Kewajiban Jangka Panjang
   - **Ekuitas** — Modal Saham + Laba Ditahan + Laba Rugi Tahun Berjalan
5. Persamaan dasar harus seimbang: **Aset = Kewajiban + Ekuitas**

**Laporan Laba Rugi** (`Laba Rugi`)

1. Pilih **"Laba Rugi"** dari menu Laporan Keuangan.
2. Pilih periode pelaporan.
3. Laporan menampilkan:
   - **Pendapatan** — Pendapatan penjualan, pendapatan jasa
   - **Harga Pokok Penjualan (HPP)** — Biaya langsung material dan manufaktur
   - **Laba Kotor** = Pendapatan − HPP
   - **Beban Operasional** — Gaji, sewa, utilitas, penyusutan
   - **Laba Bersih** = Laba Kotor − Beban Operasional ± Pendapatan/Beban Lain

📸 **[SCREENSHOT: Laporan Laba Rugi dengan bagian Pendapatan, HPP, dan Laba Bersih — UI Bahasa Indonesia]**

---

### 🇰🇷 한국어

**대차대조표** (`대차대조표`) — **회계** → **재무보고서** → **"대차대조표"**
- **자산** = **부채** + **자본** 등식이 성립해야 합니다.

**손익계산서** (`손익계산서`) — 수익, 매출원가(COGS), 매출총이익, 영업비용, 순이익 표시.

📸 **[SCREENSHOT: 자산 = 부채 + 자본을 보여주는 대차대조표 보고서 — 한국어 UI]**

---

### 🇨🇳 中文

**资产负债表** (`资产负债表`) — **会计** → **财务报告** → **"资产负债表"**
- 基本等式必须平衡：**资产 = 负债 + 权益**

**损益表** (`损益表`) — 显示收入、销货成本(COGS)、毛利、营业费用、净利润。

📸 **[SCREENSHOT: 显示收入、COGS和净利润部分的损益表报告 — 中文UI]**

---

## 4.3 Financial Ratios & Budget Analysis

### 🇬🇧 English

**Financial Ratios** provide key performance indicators from your financial statements.

Navigate to **Accounting** → **Financial Reports** → **Financial Ratios**.

| Category | Ratio | Formula |
|----------|-------|---------|
| **Liquidity** | Current Ratio | $\frac{\text{Current Assets}}{\text{Current Liabilities}}$ |
| **Liquidity** | Quick Ratio | $\frac{\text{Current Assets} - \text{Inventory}}{\text{Current Liabilities}}$ |
| **Profitability** | Gross Margin | $\frac{\text{Gross Profit}}{\text{Revenue}} \times 100\%$ |
| **Profitability** | Net Margin | $\frac{\text{Net Income}}{\text{Revenue}} \times 100\%$ |
| **Leverage** | Debt-to-Equity | $\frac{\text{Total Liabilities}}{\text{Total Equity}}$ |
| **Efficiency** | Inventory Turnover | $\frac{\text{COGS}}{\text{Average Inventory}}$ |

📸 **[SCREENSHOT: Financial Ratios dashboard showing all ratio categories — English UI]**

**Budget Analysis**

1. Navigate to **Accounting** → **Budgets** (`Budgets` / `Anggaran` / `예산` / `预算`).
2. Create budgets per account/period.
3. View **Budget vs Actual** comparison with variance analysis.
4. Variance = Budget − Actual; percentage shows over/under budget.

📸 **[SCREENSHOT: Budget vs Actual comparison table with variance — English UI]**

---

### 🇮🇩 Bahasa Indonesia

**Rasio Keuangan** memberikan indikator kinerja utama dari laporan keuangan Anda.

Navigasi ke **Akuntansi** → **Laporan Keuangan** → **Rasio Keuangan**.

| Kategori | Rasio | Rumus |
|----------|-------|-------|
| **Likuiditas** | Rasio Lancar | $\frac{\text{Aset Lancar}}{\text{Kewajiban Lancar}}$ |
| **Profitabilitas** | Marjin Kotor | $\frac{\text{Laba Kotor}}{\text{Pendapatan}} \times 100\%$ |
| **Leverage** | Rasio Hutang terhadap Ekuitas | $\frac{\text{Total Kewajiban}}{\text{Total Ekuitas}}$ |
| **Efisiensi** | Perputaran Persediaan | $\frac{\text{HPP}}{\text{Rata-rata Persediaan}}$ |

**Analisis Anggaran** (`Anggaran`)

1. Navigasi ke **Akuntansi** → **Anggaran** (`Anggaran`).
2. Buat anggaran per akun/periode.
3. Lihat perbandingan **Anggaran vs Aktual** dengan analisis variansi.

📸 **[SCREENSHOT: Tabel perbandingan Anggaran vs Aktual dengan variansi — UI Bahasa Indonesia]**

---

## 4.4 Payroll: PPh 21 TER 2024 & BPJS Calculations

### 🇬🇧 English

**Understanding the Payroll Structure**

ManERP's payroll module calculates Indonesian employee compensation including all statutory deductions: **PPh 21** (Income Tax) using the **TER 2024** (Tarif Efektif Rata-rata) system, and **BPJS** (Social Insurance) contributions.

**SOP: Running Payroll**

1. Navigate to **HR & Payroll** → **Payroll** (`Payroll`).
2. Click **"Generate Payroll"** (`Generate Payroll`).
3. Select the payroll period (month/year).
4. The system automatically calculates for each employee:
   - **Basic Salary** from the employee's salary structure
   - **BPJS Contributions** (company and employee portions)
   - **PPh 21** tax using TER rates
   - **Net Pay** (Take-Home Pay)
5. Review the payroll summary.
6. Click **"Approve"** (`Approve`) to finalize.
7. Click **"Post"** to create accounting journal entries.

📸 **[SCREENSHOT: Payroll generation page showing employee list with salary calculations — English UI]**

**BPJS Components:**

| Component | EN Label | ID Label | Company | Employee |
|-----------|----------|----------|---------|----------|
| BPJS JHT (Old Age Security) | `BPJS JHT (Company)` | `BPJS JHT (Perusahaan)` | 3.7% | 2.0% |
| BPJS JKK (Work Accident) | `BPJS JKK` | `BPJS JKK` | 0.24%–1.74% | — |
| BPJS JKM (Death Benefit) | `BPJS JKM` | `BPJS JKM` | 0.3% | — |
| BPJS JP (Pension) | `BPJS JP (Company)` | `BPJS JP (Perusahaan)` | 2.0% | 1.0% |
| BPJS Kesehatan (Health) | `BPJS Kesehatan (Company)` | `BPJS Kesehatan (Perusahaan)` | 4.0% | 1.0% |

> **Note:** BPJS JP has a monthly salary cap. Contributions are calculated on salary up to the maximum threshold set by government regulation.

**PPh 21 TER 2024 Calculation:**

The **TER (Tarif Efektif Rata-rata)** system simplifies monthly PPh 21 calculation:

$$\text{PPh 21 Monthly} = \text{Gross Monthly Income} \times \text{TER Rate}$$

TER rates vary by **PTKP status** (tax-free threshold category):
- **TK/0** — Single, no dependents
- **TK/1, TK/2, TK/3** — Single with 1–3 dependents
- **K/0, K/1, K/2, K/3** — Married with 0–3 dependents

The system automatically looks up the correct TER rate based on the employee's PTKP status and gross income bracket.

📸 **[SCREENSHOT: Employee payslip showing BPJS breakdown and PPh 21 calculation — English UI]**

**Viewing Payslip:**

1. Navigate to **Payroll** → click a payroll period → click employee name.
2. Or click **"Payslip"** (`Payslip`) button next to the employee.
3. The payslip shows:
   - Gross Salary components
   - BPJS deductions (company & employee)
   - PPh 21 amount
   - Net Pay (Take-Home Pay)

📸 **[SCREENSHOT: Detailed payslip with all components — BPJS, PPh 21, Net Pay — English UI]**

---

### 🇮🇩 Bahasa Indonesia

**Memahami Struktur Penggajian**

Modul penggajian ManERP menghitung kompensasi karyawan Indonesia termasuk semua potongan wajib: **PPh 21** (Pajak Penghasilan) menggunakan sistem **TER 2024** (Tarif Efektif Rata-rata), dan kontribusi **BPJS** (Jaminan Sosial).

**SOP: Menjalankan Penggajian**

1. Navigasi ke **SDM & Penggajian** → **Penggajian** (`Penggajian`).
2. Klik **"Buat Penggajian"** (`Buat Penggajian`).
3. Pilih periode penggajian (bulan/tahun).
4. Sistem secara otomatis menghitung untuk setiap karyawan:
   - **Gaji Pokok** dari struktur gaji karyawan
   - **Kontribusi BPJS** (bagian perusahaan dan karyawan)
   - **PPh 21** menggunakan tarif TER
   - **Gaji Bersih** (Take-Home Pay)
5. Tinjau ringkasan penggajian.
6. Klik **"Setujui"** (`Setujui`) untuk menyelesaikan.
7. Klik **"Posting"** untuk membuat entri jurnal akuntansi.

📸 **[SCREENSHOT: Halaman pembuatan penggajian menampilkan daftar karyawan dengan perhitungan gaji — UI Bahasa Indonesia]**

**Komponen BPJS:**

| Komponen | Label UI | Perusahaan | Karyawan |
|----------|----------|------------|----------|
| BPJS JHT (Jaminan Hari Tua) | `BPJS JHT (Perusahaan)` / `BPJS JHT (Karyawan)` | 3,7% | 2,0% |
| BPJS JKK (Jaminan Kecelakaan Kerja) | `BPJS JKK` | 0,24%–1,74% | — |
| BPJS JKM (Jaminan Kematian) | `BPJS JKM` | 0,3% | — |
| BPJS JP (Jaminan Pensiun) | `BPJS JP (Perusahaan)` / `BPJS JP (Karyawan)` | 2,0% | 1,0% |
| BPJS Kesehatan | `BPJS Kes (Perusahaan)` / `BPJS Kes (Karyawan)` | 4,0% | 1,0% |

> **Catatan:** BPJS JP memiliki batas upah bulanan maksimum. Kontribusi dihitung berdasarkan gaji hingga ambang batas yang ditetapkan oleh peraturan pemerintah.

**Perhitungan PPh 21 TER 2024:**

Sistem **TER (Tarif Efektif Rata-rata)** menyederhanakan perhitungan PPh 21 bulanan:

$$\text{PPh 21 Bulanan} = \text{Penghasilan Bruto Bulanan} \times \text{Tarif TER}$$

Tarif TER bervariasi berdasarkan **status PTKP** (Penghasilan Tidak Kena Pajak):
- **TK/0** — Tidak Kawin, tanpa tanggungan
- **TK/1, TK/2, TK/3** — Tidak Kawin, 1–3 tanggungan
- **K/0, K/1, K/2, K/3** — Kawin, 0–3 tanggungan

Sistem secara otomatis mencari tarif TER yang tepat berdasarkan status PTKP karyawan dan rentang penghasilan bruto.

📸 **[SCREENSHOT: Slip gaji karyawan menampilkan rincian BPJS dan perhitungan PPh 21 — UI Bahasa Indonesia]**

**Melihat Slip Gaji:**

1. Navigasi ke **Penggajian** → klik periode penggajian → klik nama karyawan.
2. Atau klik tombol **"Slip Gaji"** di samping nama karyawan.
3. Slip gaji menampilkan:
   - Komponen gaji bruto
   - Potongan BPJS (perusahaan & karyawan)
   - Jumlah PPh 21
   - Gaji Bersih (Take-Home Pay)

📸 **[SCREENSHOT: Slip gaji detail dengan semua komponen — BPJS, PPh 21, Gaji Bersih — UI Bahasa Indonesia]**

---

### 🇰🇷 한국어

**급여 구조 이해**

ManERP의 급여 모듈은 인도네시아 법정 공제를 포함한 직원 보상을 계산합니다: **PPh 21** (소득세, **TER 2024** 시스템 사용) 및 **BPJS** (사회보험) 기여금.

**SOP: 급여 실행**

1. **인사 & 급여** → **급여** (`급여`)로 이동합니다.
2. **"급여 생성"** (`급여 생성`)을 클릭합니다.
3. 급여 기간(월/년)을 선택합니다.
4. 시스템이 각 직원에 대해 자동으로 계산합니다:
   - 급여 구조에 따른 **기본급**
   - **BPJS 기여금** (회사 및 직원 부담분)
   - TER 세율을 사용한 **PPh 21** 세금
   - **실수령액** (Take-Home Pay)
5. 급여 요약을 검토합니다.
6. **"승인"** (`승인`)을 클릭하여 확정합니다.
7. **"전기"**를 클릭하여 회계 분개를 생성합니다.

📸 **[SCREENSHOT: 급여 계산이 있는 직원 목록을 보여주는 급여 생성 페이지 — 한국어 UI]**

**BPJS 구성요소:**

| 구성요소 | UI 라벨 | 회사 부담 | 직원 부담 |
|----------|---------|-----------|-----------|
| BPJS JHT (노후보장) | `BPJS JHT (회사)` / `BPJS JHT (직원)` | 3.7% | 2.0% |
| BPJS JKK (산재보험) | `BPJS JKK` | 0.24%–1.74% | — |
| BPJS JKM (사망보험) | `BPJS JKM` | 0.3% | — |
| BPJS JP (연금) | `BPJS JP (회사)` / `BPJS JP (직원)` | 2.0% | 1.0% |
| BPJS 건강보험 | `BPJS 건강보험 (회사)` / `BPJS 건강보험 (직원)` | 4.0% | 1.0% |

**PPh 21 TER 2024 계산:**

$$\text{월별 PPh 21} = \text{월 총 소득} \times \text{TER 세율}$$

📸 **[SCREENSHOT: BPJS 분류와 PPh 21 계산이 있는 급여명세서 — 한국어 UI]**

---

### 🇨🇳 中文

**理解薪资结构**

ManERP的薪资模块计算印尼员工薪酬，包括所有法定扣除项：使用 **TER 2024** 系统的 **PPh 21**（所得税），以及 **BPJS**（社会保险）缴费。

**SOP：运行薪资发放**

1. 导航至**人力资源与薪资** → **薪资发放** (`薪资发放`)。
2. 点击**"生成薪资"** (`生成薪资`)。
3. 选择薪资周期（月份/年份）。
4. 系统自动为每位员工计算：
   - 基于薪资结构的**基本工资**
   - **BPJS缴费**（公司和员工部分）
   - 使用TER税率的 **PPh 21** 税额
   - **实发工资**（Take-Home Pay）
5. 审核薪资汇总。
6. 点击**"批准"** (`批准`) 确定。
7. 点击**"过账"**创建会计日记账分录。

📸 **[SCREENSHOT: 显示员工列表和薪资计算的薪资生成页面 — 中文UI]**

**BPJS构成：**

| 构成 | UI标签 | 公司部分 | 员工部分 |
|------|--------|----------|----------|
| BPJS JHT（养老保障） | `BPJS JHT（公司）` / `BPJS JHT（员工）` | 3.7% | 2.0% |
| BPJS JKK（工伤保险） | `BPJS JKK` | 0.24%–1.74% | — |
| BPJS JKM（死亡保险） | `BPJS JKM` | 0.3% | — |
| BPJS JP（养老金） | `BPJS JP（公司）` / `BPJS JP（员工）` | 2.0% | 1.0% |
| BPJS健康保险 | `BPJS健康保险（公司）` / `BPJS健康保险（员工）` | 4.0% | 1.0% |

**PPh 21 TER 2024 计算：**

$$\text{月度 PPh 21} = \text{月总收入} \times \text{TER 税率}$$

📸 **[SCREENSHOT: 显示BPJS分项和PPh 21计算的工资单 — 中文UI]**

---

## 4.5 Tax Compliance: SPT Masa PPN

### 🇬🇧 English

ManERP supports **PPN (Pajak Pertambahan Nilai / Value Added Tax)** reporting for Indonesian tax compliance.

**Available Tax Reports:**

1. **SPT Masa PPN** — Monthly VAT return showing input tax (Pajak Masukan) vs output tax (Pajak Keluaran).
2. **Annual Tax Summary** — Yearly tax summary for financial reporting.
3. **PPN Calculator** — Calculate DPP (Dasar Pengenaan Pajak / Tax Base) and PPN amounts.

**How to Access:**

1. Navigate to **Accounting** → **Financial Reports** → click the **Tax** section.
2. Select report type and period.
3. Export for submission to the tax authority (DJP).

📸 **[SCREENSHOT: SPT Masa PPN report with Input Tax and Output Tax columns — English UI]**

---

### 🇮🇩 Bahasa Indonesia

ManERP mendukung pelaporan **PPN (Pajak Pertambahan Nilai)** untuk kepatuhan pajak Indonesia.

**Laporan Pajak yang Tersedia:**

1. **SPT Masa PPN** — Laporan PPN bulanan menampilkan Pajak Masukan vs Pajak Keluaran.
2. **Ringkasan Pajak Tahunan** — Ringkasan pajak tahunan untuk pelaporan keuangan.
3. **Kalkulator PPN** — Hitung DPP (Dasar Pengenaan Pajak) dan jumlah PPN.

**Cara Mengakses:**

1. Navigasi ke **Akuntansi** → **Laporan Keuangan** → klik bagian **Pajak**.
2. Pilih jenis laporan dan periode.
3. Ekspor untuk diserahkan ke otoritas pajak (DJP).

📸 **[SCREENSHOT: Laporan SPT Masa PPN dengan kolom Pajak Masukan dan Pajak Keluaran — UI Bahasa Indonesia]**

---

### 🇰🇷 한국어

ManERP는 인도네시아 세무 규정 준수를 위한 **PPN(부가가치세)** 보고를 지원합니다.

- **SPT Masa PPN** — 매입세액 vs 매출세액을 보여주는 월간 부가세 신고서.
- **연간 세무 요약** — 연간 세무 요약 보고서.
- **PPN 계산기** — DPP(과세표준)와 PPN 금액 계산.

📸 **[SCREENSHOT: 매입세액과 매출세액 열이 있는 SPT Masa PPN 보고서 — 한국어 UI]**

---

### 🇨🇳 中文

ManERP支持印尼税务合规的 **PPN（增值税）** 报告。

- **SPT Masa PPN** — 显示进项税与销项税的月度增值税申报表。
- **年度税务汇总** — 年度税务汇总报告。
- **PPN计算器** — 计算DPP（计税基础）和PPN金额。

📸 **[SCREENSHOT: 带进项税和销项税列的SPT Masa PPN报告 — 中文UI]**
