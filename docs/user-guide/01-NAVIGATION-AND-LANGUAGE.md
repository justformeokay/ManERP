# Chapter 1 — System Navigation & Language Settings
# Bab 1 — Navigasi Sistem & Pengaturan Bahasa
# 제1장 — 시스템 내비게이션 및 언어 설정
# 第1章 — 系统导航与语言设置

---

## 1.1 Changing Language Preference

ManERP supports four languages. Users can switch the interface language at any time.

### 🇬🇧 English

**How to Change Language:**

1. Click your **profile avatar** in the top-right corner of the navigation bar.
2. In the dropdown menu, locate **"Language"** (`Language`).
3. Select your preferred language from the available options:
   - English
   - Bahasa Indonesia
   - 한국어 (Korean)
   - 中文 (Chinese)
4. The system will immediately reload the interface in the selected language.
5. If you are logged in, the preference is saved to your user profile and persists across sessions.

> **Note:** All UI labels, menu items, form fields, and system messages will update to the selected language. PDF exports (invoices, PO, payslips) will respect the active language setting at the time of generation.

📸 **[SCREENSHOT: Top-right navigation bar showing the profile dropdown with "Language" option highlighted — English UI]**

---

### 🇮🇩 Bahasa Indonesia

**Cara Mengubah Bahasa:**

1. Klik **avatar profil** Anda di sudut kanan atas bilah navigasi.
2. Pada menu dropdown, temukan opsi **"Bahasa"** (`Bahasa`).
3. Pilih bahasa yang dikehendaki dari daftar yang tersedia:
   - English
   - Bahasa Indonesia
   - 한국어 (Korea)
   - 中文 (Mandarin)
4. Sistem akan segera memuat ulang tampilan dengan bahasa yang dipilih.
5. Jika Anda telah masuk (login), preferensi bahasa akan tersimpan di profil pengguna dan berlaku untuk sesi berikutnya.

> **Catatan:** Semua label UI, item menu, kolom formulir, dan pesan sistem akan berubah sesuai bahasa yang dipilih. Ekspor PDF (faktur, PO, slip gaji) mengikuti pengaturan bahasa aktif saat pembuatan.

📸 **[SCREENSHOT: Bilah navigasi kanan atas menampilkan dropdown profil dengan opsi "Bahasa" disorot — UI Bahasa Indonesia]**

---

### 🇰🇷 한국어

**언어 변경 방법:**

1. 내비게이션 바 오른쪽 상단의 **프로필 아바타**를 클릭합니다.
2. 드롭다운 메뉴에서 **"언어"** (`언어`) 항목을 찾습니다.
3. 사용 가능한 옵션에서 원하는 언어를 선택합니다:
   - English
   - Bahasa Indonesia
   - 한국어
   - 中文
4. 시스템이 즉시 선택한 언어로 인터페이스를 다시 로드합니다.
5. 로그인 상태인 경우 설정이 사용자 프로필에 저장되어 다음 세션에도 유지됩니다.

> **참고:** 모든 UI 라벨, 메뉴 항목, 양식 필드 및 시스템 메시지가 선택한 언어로 업데이트됩니다. PDF 내보내기(송장, PO, 급여명세서)는 생성 시점의 활성 언어 설정을 따릅니다.

📸 **[SCREENSHOT: 오른쪽 상단 내비게이션 바에서 "언어" 옵션이 강조된 프로필 드롭다운 — 한국어 UI]**

---

### 🇨🇳 中文

**如何更改语言：**

1. 点击导航栏右上角的**个人头像**。
2. 在下拉菜单中找到 **"语言"** (`语言`) 选项。
3. 从可用选项中选择您的首选语言：
   - English
   - Bahasa Indonesia
   - 한국어（韩语）
   - 中文
4. 系统将立即以所选语言重新加载界面。
5. 如果您已登录，语言偏好将保存到您的用户资料中，并在后续会话中保持有效。

> **注意：** 所有UI标签、菜单项、表单字段和系统消息将更新为所选语言。PDF导出（发票、采购订单、工资单）将使用生成时的活动语言设置。

📸 **[SCREENSHOT: 右上角导航栏显示带有"语言"选项高亮的个人下拉菜单 — 中文UI]**

---

## 1.2 Date & Currency Format

### 🇬🇧 English

ManERP uses the following system-wide format conventions:

| Element | Format | Example |
|---------|--------|---------|
| **Date** | `DD MMM YYYY` | 04 Apr 2026 |
| **Currency** | Indonesian Rupiah (IDR) | Rp 1.500.000,00 |
| **Number Separator** | Period (`.`) for thousands, comma (`,`) for decimals | 1.234.567,89 |
| **Timezone** | Configurable per system (default: `UTC`) | Set in **Settings** (`Settings`) → General |

> The default currency is **IDR (Rp)** as configured in system settings. Administrators can change the default currency symbol and code via **Settings** (`Settings`) → **General** tab.

📸 **[SCREENSHOT: Settings page showing currency and timezone configuration — English UI]**

---

### 🇮🇩 Bahasa Indonesia

ManERP menggunakan konvensi format berikut secara keseluruhan sistem:

| Elemen | Format | Contoh |
|--------|--------|--------|
| **Tanggal** | `DD MMM YYYY` | 04 Apr 2026 |
| **Mata Uang** | Rupiah Indonesia (IDR) | Rp 1.500.000,00 |
| **Pemisah Angka** | Titik (`.`) untuk ribuan, koma (`,`) untuk desimal | 1.234.567,89 |
| **Zona Waktu** | Dapat dikonfigurasi per sistem (default: `UTC`) | Diatur di **Pengaturan** (`Pengaturan`) → Umum |

> Mata uang bawaan adalah **IDR (Rp)** sesuai pengaturan sistem. Administrator dapat mengubah simbol dan kode mata uang bawaan melalui **Pengaturan** (`Pengaturan`) → tab **Umum**.

📸 **[SCREENSHOT: Halaman Pengaturan menampilkan konfigurasi mata uang dan zona waktu — UI Bahasa Indonesia]**

---

### 🇰🇷 한국어

ManERP는 시스템 전체에 걸쳐 다음과 같은 형식 규칙을 사용합니다:

| 요소 | 형식 | 예시 |
|------|------|------|
| **날짜** | `DD MMM YYYY` | 04 Apr 2026 |
| **통화** | 인도네시아 루피아 (IDR) | Rp 1.500.000,00 |
| **숫자 구분자** | 천 단위에 마침표(`.`), 소수점에 쉼표(`,`) | 1.234.567,89 |
| **시간대** | 시스템별 설정 가능 (기본값: `UTC`) | **설정** (`설정`) → 일반에서 설정 |

> 기본 통화는 시스템 설정에 따라 **IDR (Rp)**입니다. 관리자는 **설정** (`설정`) → **일반** 탭에서 기본 통화 기호 및 코드를 변경할 수 있습니다.

📸 **[SCREENSHOT: 통화 및 시간대 구성을 보여주는 설정 페이지 — 한국어 UI]**

---

### 🇨🇳 中文

ManERP在全系统范围内使用以下格式规范：

| 元素 | 格式 | 示例 |
|------|------|------|
| **日期** | `DD MMM YYYY` | 04 Apr 2026 |
| **货币** | 印尼盾 (IDR) | Rp 1.500.000,00 |
| **数字分隔符** | 千位使用句点(`.`)，小数使用逗号(`,`) | 1.234.567,89 |
| **时区** | 可按系统配置（默认：`UTC`） | 在 **设置** (`设置`) → 常规 中设定 |

> 默认货币为系统设置中配置的 **IDR (Rp)**。管理员可通过 **设置** (`设置`) → **常规** 标签页更改默认货币符号和代码。

📸 **[SCREENSHOT: 设置页面显示货币和时区配置 — 中文UI]**

---

## 1.3 Sidebar Navigation Map

### 🇬🇧 English — Full Navigation Structure

The left sidebar organizes all ManERP modules. Access is controlled by user roles and permissions.

📸 **[SCREENSHOT: Full left sidebar in expanded state showing all menu groups — English UI]**

| Section | Menu Item | Route | Access |
|---------|-----------|-------|--------|
| **Main** | Dashboard (`Dashboard`) | `/dashboard` | All users |
| **Sales** | CRM / Clients (`CRM / Clients`) | `/clients` | `clients` permission |
| | Projects (`Projects`) | `/projects` | `projects` permission |
| | Sales Orders (`Sales Orders`) | `/sales` | `sales` permission |
| | Invoices (`Invoices`) | `/finance/invoices` | `finance` permission |
| | Credit Notes (`Credit Notes`) | `/accounting/credit-notes` | `accounting` permission |
| **Purchasing** | Suppliers (`Suppliers`) | `/suppliers` | `suppliers` permission |
| | Purchase Requests (`Purchase Requests`) | `/purchase-requests` | `purchasing` permission |
| | Purchase Orders (`Purchase Orders`) | `/purchasing` | `purchasing` permission |
| | Debit Notes (`Debit Notes`) | `/accounting/debit-notes` | `accounting` permission |
| **Inventory** | Products (`Products`) | `/inventory/products` | `products` permission |
| | Warehouses (`Warehouses`) | `/warehouses` | `warehouses` permission |
| | Stock Management (`Stock Management`) | `/inventory/stock` | `inventory` permission |
| **Manufacturing** | Bill of Materials (`Bill of Materials`) | `/manufacturing/boms` | `manufacturing` permission |
| | Work Orders (`Work Orders`) | `/manufacturing/orders` | `manufacturing` permission |
| | Costing / HPP (`Costing / HPP`) | `/manufacturing/costing` | `manufacturing` permission |
| | Quality Control (`Quality Control`) | `/qc/inspections` | `manufacturing` permission |
| **HR & Payroll** | Employees (`Employees`) | `/hr/employees` | `hr` permission |
| | Payroll (`Payroll`) | `/hr/payroll` | `hr` permission |
| | Payroll Dashboard (`Payroll Dashboard`) | `/hr/payroll/dashboard` | `hr` permission |
| **Accounting** | Chart of Accounts (`Chart of Accounts`) | `/accounting/coa` | `accounting` permission |
| | Journal Entries (`Journal Entries`) | `/accounting/journals` | `accounting` permission |
| | Bank & Cash (`Bank & Cash`) | `/accounting/bank` | `accounting` permission |
| | Fixed Assets (`Fixed Assets`) | `/accounting/assets` | `accounting` permission |
| | Budgets (`Budgets`) | `/accounting/budgets` | `accounting` permission |
| | Financial Reports (`Financial Reports`) | `/accounting/reports` | `accounting` permission |
| **Administration** | Settings (`Settings`) | `/settings` | Admin only |
| | Users (`Users`) | `/settings/users` | Admin only |
| | Reports (`Reports`) | `/reports` | `reports` permission |
| | Audit Logs (`Audit Logs`) | `/audit-logs` | Admin only |
| | System Maintenance (`System Maintenance`) | `/maintenance` | Admin only |

---

### 🇮🇩 Bahasa Indonesia — Struktur Navigasi Lengkap

Sidebar kiri mengatur semua modul ManERP. Akses dikendalikan berdasarkan peran dan izin pengguna.

📸 **[SCREENSHOT: Sidebar kiri penuh dalam keadaan terbuka menampilkan semua grup menu — UI Bahasa Indonesia]**

| Bagian | Item Menu | Rute | Akses |
|--------|-----------|------|-------|
| **Utama** | Dasbor (`Dasbor`) | `/dashboard` | Semua pengguna |
| **Penjualan** | CRM / Klien (`CRM / Klien`) | `/clients` | Izin `clients` |
| | Proyek (`Proyek`) | `/projects` | Izin `projects` |
| | Pesanan Penjualan (`Pesanan Penjualan`) | `/sales` | Izin `sales` |
| | Faktur (`Faktur`) | `/finance/invoices` | Izin `finance` |
| | Nota Kredit (`Nota Kredit`) | `/accounting/credit-notes` | Izin `accounting` |
| **Pembelian** | Pemasok (`Pemasok`) | `/suppliers` | Izin `suppliers` |
| | Permintaan Pembelian (`Permintaan Pembelian`) | `/purchase-requests` | Izin `purchasing` |
| | Pesanan Pembelian (`Pesanan Pembelian`) | `/purchasing` | Izin `purchasing` |
| | Nota Debit (`Nota Debit`) | `/accounting/debit-notes` | Izin `accounting` |
| **Inventori** | Produk (`Produk`) | `/inventory/products` | Izin `products` |
| | Gudang (`Gudang`) | `/warehouses` | Izin `warehouses` |
| | Manajemen Stok (`Manajemen Stok`) | `/inventory/stock` | Izin `inventory` |
| **Manufaktur** | Daftar Bahan (`Daftar Bahan`) | `/manufacturing/boms` | Izin `manufacturing` |
| | Perintah Kerja (`Perintah Kerja`) | `/manufacturing/orders` | Izin `manufacturing` |
| | Kalkulasi HPP (`Kalkulasi HPP`) | `/manufacturing/costing` | Izin `manufacturing` |
| | Kontrol Kualitas (`Kontrol Kualitas`) | `/qc/inspections` | Izin `manufacturing` |
| **SDM & Penggajian** | Karyawan (`Karyawan`) | `/hr/employees` | Izin `hr` |
| | Penggajian (`Penggajian`) | `/hr/payroll` | Izin `hr` |
| | Dasbor Penggajian (`Dasbor Penggajian`) | `/hr/payroll/dashboard` | Izin `hr` |
| **Akuntansi** | Daftar Akun (`Daftar Akun`) | `/accounting/coa` | Izin `accounting` |
| | Entri Jurnal (`Entri Jurnal`) | `/accounting/journals` | Izin `accounting` |
| | Bank & Kas (`Bank & Kas`) | `/accounting/bank` | Izin `accounting` |
| | Aset Tetap (`Aset Tetap`) | `/accounting/assets` | Izin `accounting` |
| | Anggaran (`Anggaran`) | `/accounting/budgets` | Izin `accounting` |
| | Laporan Keuangan (`Laporan Keuangan`) | `/accounting/reports` | Izin `accounting` |
| **Administrasi** | Pengaturan (`Pengaturan`) | `/settings` | Hanya Admin |
| | Pengguna (`Pengguna`) | `/settings/users` | Hanya Admin |
| | Laporan (`Laporan`) | `/reports` | Izin `reports` |
| | Log Audit (`Log Audit`) | `/audit-logs` | Hanya Admin |
| | Pemeliharaan Sistem (`Pemeliharaan Sistem`) | `/maintenance` | Hanya Admin |

---

### 🇰🇷 한국어 — 전체 내비게이션 구조

좌측 사이드바에서 모든 ManERP 모듈을 구성합니다. 접근 권한은 사용자 역할과 권한으로 제어됩니다.

📸 **[SCREENSHOT: 모든 메뉴 그룹이 표시된 확장된 좌측 사이드바 — 한국어 UI]**

| 섹션 | 메뉴 항목 | 경로 | 접근 권한 |
|------|-----------|------|-----------|
| **메인** | 대시보드 (`대시보드`) | `/dashboard` | 모든 사용자 |
| **판매** | CRM / 고객 (`CRM / 고객`) | `/clients` | `clients` 권한 |
| | 프로젝트 (`프로젝트`) | `/projects` | `projects` 권한 |
| | 판매 주문 (`판매 주문`) | `/sales` | `sales` 권한 |
| | 송장 (`송장`) | `/finance/invoices` | `finance` 권한 |
| | 대변메모 (`대변메모`) | `/accounting/credit-notes` | `accounting` 권한 |
| **구매** | 공급업체 (`공급업체`) | `/suppliers` | `suppliers` 권한 |
| | 구매요청 (`구매요청`) | `/purchase-requests` | `purchasing` 권한 |
| | 구매 주문 (`구매 주문`) | `/purchasing` | `purchasing` 권한 |
| | 차변메모 (`차변메모`) | `/accounting/debit-notes` | `accounting` 권한 |
| **재고** | 제품 (`제품`) | `/inventory/products` | `products` 권한 |
| | 창고 (`창고`) | `/warehouses` | `warehouses` 권한 |
| | 재고 관리 (`재고 관리`) | `/inventory/stock` | `inventory` 권한 |
| **제조** | 자재 명세서 (`자재 명세서`) | `/manufacturing/boms` | `manufacturing` 권한 |
| | 작업 주문 (`작업 주문`) | `/manufacturing/orders` | `manufacturing` 권한 |
| | 원가 계산 (`원가 계산`) | `/manufacturing/costing` | `manufacturing` 권한 |
| | 품질 관리 (`품질 관리`) | `/qc/inspections` | `manufacturing` 권한 |
| **인사 & 급여** | 직원 (`직원`) | `/hr/employees` | `hr` 권한 |
| | 급여 (`급여`) | `/hr/payroll` | `hr` 권한 |
| | 급여 대시보드 (`급여 대시보드`) | `/hr/payroll/dashboard` | `hr` 권한 |
| **회계** | 계정과목 (`계정과목`) | `/accounting/coa` | `accounting` 권한 |
| | 분개 (`분개`) | `/accounting/journals` | `accounting` 권한 |
| | 은행 & 현금 (`은행 & 현금`) | `/accounting/bank` | `accounting` 권한 |
| | 고정자산 (`고정자산`) | `/accounting/assets` | `accounting` 권한 |
| | 예산 (`예산`) | `/accounting/budgets` | `accounting` 권한 |
| | 재무보고서 (`재무보고서`) | `/accounting/reports` | `accounting` 권한 |
| **관리** | 설정 (`설정`) | `/settings` | 관리자만 |
| | 사용자 (`사용자`) | `/settings/users` | 관리자만 |
| | 보고서 (`보고서`) | `/reports` | `reports` 권한 |
| | 감사 로그 (`감사 로그`) | `/audit-logs` | 관리자만 |
| | 시스템 유지보수 (`시스템 유지보수`) | `/maintenance` | 관리자만 |

---

### 🇨🇳 中文 — 完整导航结构

左侧边栏组织了ManERP的所有模块。访问权限通过用户角色和权限控制。

📸 **[SCREENSHOT: 展开状态下显示所有菜单组的完整左侧边栏 — 中文UI]**

| 板块 | 菜单项 | 路径 | 访问权限 |
|------|--------|------|----------|
| **主页** | 仪表板 (`仪表板`) | `/dashboard` | 所有用户 |
| **销售** | CRM / 客户 (`CRM / 客户`) | `/clients` | `clients` 权限 |
| | 项目 (`项目`) | `/projects` | `projects` 权限 |
| | 销售订单 (`销售订单`) | `/sales` | `sales` 权限 |
| | 发票 (`发票`) | `/finance/invoices` | `finance` 权限 |
| | 贷项通知单 (`贷项通知单`) | `/accounting/credit-notes` | `accounting` 权限 |
| **采购** | 供应商 (`供应商`) | `/suppliers` | `suppliers` 权限 |
| | 采购申请 (`采购申请`) | `/purchase-requests` | `purchasing` 权限 |
| | 采购订单 (`采购订单`) | `/purchasing` | `purchasing` 权限 |
| | 借项通知单 (`借项通知单`) | `/accounting/debit-notes` | `accounting` 权限 |
| **库存** | 产品 (`产品`) | `/inventory/products` | `products` 权限 |
| | 仓库 (`仓库`) | `/warehouses` | `warehouses` 权限 |
| | 库存管理 (`库存管理`) | `/inventory/stock` | `inventory` 权限 |
| **制造** | 物料清单 (`物料清单`) | `/manufacturing/boms` | `manufacturing` 权限 |
| | 工单 (`工单`) | `/manufacturing/orders` | `manufacturing` 权限 |
| | 成本核算 (`成本核算`) | `/manufacturing/costing` | `manufacturing` 权限 |
| | 质量控制 (`质量控制`) | `/qc/inspections` | `manufacturing` 权限 |
| **人力资源与薪资** | 员工 (`员工`) | `/hr/employees` | `hr` 权限 |
| | 薪资发放 (`薪资发放`) | `/hr/payroll` | `hr` 权限 |
| | 薪资仪表盘 (`薪资仪表盘`) | `/hr/payroll/dashboard` | `hr` 权限 |
| **会计** | 科目表 (`科目表`) | `/accounting/coa` | `accounting` 权限 |
| | 日记账 (`日记账`) | `/accounting/journals` | `accounting` 权限 |
| | 银行与现金 (`银行与现金`) | `/accounting/bank` | `accounting` 权限 |
| | 固定资产 (`固定资产`) | `/accounting/assets` | `accounting` 权限 |
| | 预算 (`预算`) | `/accounting/budgets` | `accounting` 权限 |
| | 财务报告 (`财务报告`) | `/accounting/reports` | `accounting` 权限 |
| **管理** | 设置 (`设置`) | `/settings` | 仅管理员 |
| | 用户 (`用户`) | `/settings/users` | 仅管理员 |
| | 报告 (`报告`) | `/reports` | `reports` 权限 |
| | 审计日志 (`审计日志`) | `/audit-logs` | 仅管理员 |
| | 系统维护 (`系统维护`) | `/maintenance` | 仅管理员 |

---

## 1.4 Dashboard Overview

### 🇬🇧 English

The **Dashboard** (`Dashboard`) is the first screen after login. It provides a real-time business intelligence overview with auto-refresh every 15 minutes.

📸 **[SCREENSHOT: Full dashboard page showing all widget cards — English UI]**

**Dashboard Widgets:**

| Widget | Description |
|--------|-------------|
| **Stats Cards** (6 cards) | Total Clients, Total Products, Sales Orders, Purchase Orders, Pending Manufacturing, Low Stock Items |
| **Cash & Bank Balance** (`Cash & Bank Balance`) | Current cash position with total revenue and monthly revenue |
| **Accounts Receivable** (`Accounts Receivable`) | Outstanding AR with overdue count, sparkline trend chart |
| **Accounts Payable** (`Accounts Payable`) | Outstanding AP with overdue count, sparkline trend chart |
| **Profit & Loss** (`Profit & Loss`) | Interactive bar chart with period filter (Month/Quarter/Year); Revenue, COGS, Net Income |
| **Inventory Valuation** (`Inventory Valuation`) | Breakdown by Raw Material, Finished Good, Consumable & Other |
| **QC Rejection Rate** (`QC Rejection Rate`) | Gauge chart showing rejection percentage, total inspections, failed qty |
| **Pending Approvals** (`Pending Approvals`) | List of pending PO/Invoice/Bill approvals with progress bars |
| **Active Projects** (`Active Projects`) | Active and on-hold projects with budget and deadline info |
| **Manufacturing Progress** (`Manufacturing Progress`) | Active work orders with priority and completion progress |
| **Recent Sales / Purchases** | Latest 5 sales and purchase orders with status badges |
| **Recent Activity** (`Recent Activity`) | Audit trail of latest system actions (create/update/delete) |
| **Low Stock Alert** | Products below minimum stock threshold |

---

### 🇮🇩 Bahasa Indonesia

**Dasbor** (`Dasbor`) adalah layar pertama setelah masuk. Dasbor menyediakan ringkasan inteligensi bisnis secara real-time dengan pembaruan otomatis setiap 15 menit.

📸 **[SCREENSHOT: Halaman dasbor penuh menampilkan semua kartu widget — UI Bahasa Indonesia]**

**Widget Dasbor:**

| Widget | Deskripsi |
|--------|-----------|
| **Kartu Statistik** (6 kartu) | Total Klien, Total Produk, Pesanan Penjualan, Pesanan Pembelian, Manufaktur Tertunda, Stok Rendah |
| **Saldo Kas & Bank** (`Saldo Kas & Bank`) | Posisi kas saat ini dengan total pendapatan dan pendapatan bulanan |
| **Piutang Usaha** (`Piutang Usaha`) | Piutang dengan jumlah jatuh tempo, grafik tren sparkline |
| **Hutang Usaha** (`Hutang Usaha`) | Hutang dengan jumlah jatuh tempo, grafik tren sparkline |
| **Laba Rugi** (`Laba Rugi`) | Grafik batang interaktif dengan filter periode (Bulan/Kuartal/Tahun); Pendapatan, HPP, Laba Bersih |
| **Valuasi Inventaris** (`Valuasi Inventaris`) | Rincian berdasarkan Bahan Baku, Barang Jadi, Consumable & Lainnya |
| **Tingkat Penolakan QC** (`Tingkat Penolakan QC`) | Gauge menampilkan persentase penolakan, total inspeksi, qty gagal |
| **Persetujuan Tertunda** (`Persetujuan Tertunda`) | Daftar persetujuan PO/Faktur/Tagihan yang menunggu |
| **Proyek Aktif** (`Proyek Aktif`) | Proyek aktif dan ditunda dengan info anggaran dan tenggat |
| **Progres Manufaktur** (`Progres Manufaktur`) | Perintah kerja aktif dengan prioritas dan progres |
| **Penjualan/Pembelian Terkini** | 5 pesanan penjualan dan pembelian terbaru |
| **Aktivitas Terkini** (`Aktivitas Terkini`) | Jejak audit aksi sistem terkini (buat/ubah/hapus) |
| **Peringatan Stok Rendah** | Produk di bawah ambang batas stok minimum |

---

### 🇰🇷 한국어

**대시보드** (`대시보드`)는 로그인 후 첫 화면입니다. 15분마다 자동 새로 고침으로 실시간 비즈니스 인텔리전스 개요를 제공합니다.

📸 **[SCREENSHOT: 모든 위젯 카드가 표시된 전체 대시보드 페이지 — 한국어 UI]**

| 위젯 | 설명 |
|------|------|
| **통계 카드** (6장) | 총 고객, 총 제품, 판매 주문, 구매 주문, 대기 제조, 재고 부족 |
| **현금 및 은행 잔액** (`현금 및 은행 잔액`) | 총 수익 및 월별 수익과 함께 현재 현금 포지션 |
| **매출채권** (`매출채권`) | 연체 건수, 스파크라인 트렌드 차트 포함 |
| **매입채무** (`매입채무`) | 연체 건수, 스파크라인 트렌드 차트 포함 |
| **손익계산서** (`손익계산서`) | 기간 필터(월/분기/연) 인터랙티브 차트; 수익, 매출원가, 순이익 |
| **재고 평가** (`재고 평가`) | 원재료, 완제품, 소모품별 분류 |
| **QC 불량률** (`QC 불량률`) | 불량 비율, 총 검사 수, 불합격 수량 게이지 차트 |
| **승인 대기** (`승인 대기`) | 대기 중인 PO/송장/청구서 승인 목록 |
| **활성 프로젝트** (`활성 프로젝트`) | 예산 및 마감 정보가 있는 활성/보류 프로젝트 |
| **제조 진행** (`제조 진행`) | 우선순위 및 완료 진행률이 있는 활성 작업 주문 |
| **최근 판매/구매** | 최근 5건의 판매 및 구매 주문 |
| **최근 활동** (`최근 활동`) | 시스템 작업의 감사 추적 (생성/수정/삭제) |
| **재고 부족 경보** | 최소 재고 기준 이하 제품 |

---

### 🇨🇳 中文

**仪表板** (`仪表板`) 是登录后的第一个页面，每15分钟自动刷新，提供实时商业智能概览。

📸 **[SCREENSHOT: 显示所有组件卡片的完整仪表板页面 — 中文UI]**

| 组件 | 描述 |
|------|------|
| **统计卡片** (6张) | 客户总数、产品总数、销售订单、采购订单、待处理制造、库存不足 |
| **现金及银行余额** (`现金及银行余额`) | 当前现金状况、总收入及月度收入 |
| **应收账款** (`应收账款`) | 逾期笔数、趋势迷你图 |
| **应付账款** (`应付账款`) | 逾期笔数、趋势迷你图 |
| **损益表** (`损益表`) | 带期间筛选器（月/季/年）的交互式图表；收入、销货成本、净利润 |
| **库存估值** (`库存估值`) | 按原材料、成品、消耗品分类 |
| **QC不良率** (`QC不良率`) | 仪表图显示不良率、总检验数、不合格数量 |
| **待审批** (`待审批`) | 待处理PO/发票/账单审批列表 |
| **活跃项目** (`活跃项目`) | 含预算和截止日期的活跃/暂停项目 |
| **制造进度** (`制造进度`) | 含优先级和完成进度的活跃工单 |
| **最近销售/采购** | 最近5笔销售和采购订单 |
| **最近活动** (`最近活动`) | 系统操作审计跟踪（创建/修改/删除） |
| **库存不足警报** | 低于最低库存阈值的产品 |
