# Chapter 3 — Core Business Workflows
# Bab 3 — Alur Bisnis Utama
# 제3장 — 핵심 비즈니스 워크플로
# 第3章 — 核心业务流程

---

## 3.1 Purchasing Cycle

### Complete Flow Diagram (All Languages)

```
┌──────────────────┐     ┌──────────────────┐     ┌──────────────────┐     ┌──────────────────┐     ┌──────────────────┐
│ Purchase Request  │────▶│  Approval Flow   │────▶│  Purchase Order  │────▶│  Goods Receipt   │────▶│ Accounts Payable │
│ (Permintaan       │     │  (Persetujuan)   │     │  (Pesanan        │     │  (Penerimaan     │     │ (Hutang Usaha)   │
│  Pembelian)       │     │  (승인 절차)      │     │   Pembelian)     │     │   Barang)        │     │ (매입채무)        │
│ (구매요청)         │     │  (审批流程)       │     │  (구매 주문)      │     │  (입고)           │     │ (应付账款)        │
│ (采购申请)         │     │                  │     │  (采购订单)       │     │  (收货)           │     │                  │
└──────────────────┘     └──────────────────┘     └──────────────────┘     └──────────────────┘     └──────────────────┘
     Draft                    Pending                  Confirmed              Received               Bill Posted
     (Draf)                  (Tertunda)               (Dikonfirmasi)         (Diterima)             (Terposting)
     (초안)                   (승인 대기)               (확인됨)               (수령됨)               (전기됨)
     (草稿)                   (待审批)                  (已确认)               (已收货)               (已过账)
```

---

### 🇬🇧 English

**SOP: Purchase Request to Payment**

**Step 1 — Create Purchase Request (PR)**

1. Navigate to **Purchase Requests** (`Purchase Requests`) in the Purchasing section.
2. Click **"New Purchase Request"** (`New Purchase Request`).
3. Fill in the required fields:
   - **Requested By** — auto-filled with current user
   - **Required Date** — when the items are needed
   - **Priority** — Low / Medium / High (`Low` / `Medium` / `High`)
   - **Line Items** — add products, quantities, and estimated prices
4. Click **"Save"** (`Save`) to save as Draft.

📸 **[SCREENSHOT: Purchase Request creation form with line items — English UI]**

**Step 2 — Submit for Approval**

1. Open the draft PR.
2. Click **"Submit"** (`Submit`).
3. The PR status changes from **Draft** to **Pending Approval** (`Pending Approval`).
4. The designated approver(s) receive a notification.

**Step 3 — Approval Process**

1. Approvers navigate to **Approvals** (`Approvals`) or see the notification badge.
2. Review the PR details and line items.
3. Click **"Approve"** (`Approve`) or **"Reject"** (`Reject`) with notes.
4. Upon approval, PR status changes to **Approved** (`Approved`).

📸 **[SCREENSHOT: Approval detail page showing PR with Approve/Reject buttons — English UI]**

**Step 4 — Convert PR to Purchase Order (PO)**

1. Open the approved PR.
2. Click **"Convert to PO"** (`Convert to PO`).
3. Select the **Supplier** (`Supplier`) and adjust quantities if needed.
4. Click **"Create Purchase Order"** to generate the PO.
5. PR status changes to **Converted** (`Converted`).

📸 **[SCREENSHOT: PR to PO conversion dialog with supplier selection — English UI]**

**Step 5 — Confirm and Receive Goods**

1. Navigate to **Purchase Orders** (`Purchase Orders`).
2. Open the newly created PO and review it.
3. Click **"Confirm"** (`Confirm`) to finalize the PO.
4. When goods arrive, click **"Receive"** (`Receive`).
5. Record received quantities. PO status changes to **Received** (`Received`).
6. Inventory stock levels are automatically updated.

📸 **[SCREENSHOT: Purchase Order detail page with Confirm and Receive buttons — English UI]**

**Step 6 — Process Accounts Payable**

1. Navigate to **Accounts Payable** → **Bills** section.
2. A supplier bill is auto-generated or manually created referencing the PO.
3. Review and click **"Post"** to create journal entries.
4. Process payment via **"Pay"** button when ready.
5. The system records the payment and updates the AP aging report.

📸 **[SCREENSHOT: Supplier bill detail with Post and Pay buttons — English UI]**

---

### 🇮🇩 Bahasa Indonesia

**SOP: Permintaan Pembelian hingga Pembayaran**

**Langkah 1 — Buat Permintaan Pembelian (PR)**

1. Navigasi ke **Permintaan Pembelian** (`Permintaan Pembelian`) di bagian Pembelian.
2. Klik **"Permintaan Pembelian Baru"** (`Permintaan Pembelian Baru`).
3. Isi kolom yang diperlukan:
   - **Diminta Oleh** — terisi otomatis dengan pengguna saat ini
   - **Tanggal Dibutuhkan** — kapan barang dibutuhkan
   - **Prioritas** — Rendah / Sedang / Tinggi (`Rendah` / `Sedang` / `Tinggi`)
   - **Item** — tambahkan produk, jumlah, dan perkiraan harga
4. Klik **"Simpan"** (`Simpan`) untuk menyimpan sebagai Draf.

📸 **[SCREENSHOT: Formulir pembuatan Permintaan Pembelian dengan item — UI Bahasa Indonesia]**

**Langkah 2 — Ajukan untuk Persetujuan**

1. Buka PR yang berstatus Draf.
2. Klik **"Ajukan"** (`Ajukan`).
3. Status PR berubah dari **Draf** menjadi **Menunggu Persetujuan** (`Menunggu Persetujuan`).
4. Penyetuju yang ditunjuk menerima notifikasi.

**Langkah 3 — Proses Persetujuan**

1. Penyetuju membuka **Persetujuan** (`Persetujuan`) atau melihat lencana notifikasi.
2. Tinjau detail dan item PR.
3. Klik **"Setujui"** (`Setujui`) atau **"Tolak"** (`Tolak`) dengan catatan.
4. Setelah disetujui, status PR berubah menjadi **Disetujui** (`Disetujui`).

📸 **[SCREENSHOT: Halaman detail persetujuan menampilkan PR dengan tombol Setujui/Tolak — UI Bahasa Indonesia]**

**Langkah 4 — Konversi PR ke Pesanan Pembelian (PO)**

1. Buka PR yang telah disetujui.
2. Klik **"Konversi ke PO"** (`Konversi ke PO`).
3. Pilih **Pemasok** (`Pemasok`) dan sesuaikan jumlah jika diperlukan.
4. Klik **"Buat Pesanan Pembelian"** untuk membuat PO.
5. Status PR berubah menjadi **Terkonversi** (`Terkonversi`).

📸 **[SCREENSHOT: Dialog konversi PR ke PO dengan pemilihan pemasok — UI Bahasa Indonesia]**

**Langkah 5 — Konfirmasi dan Terima Barang**

1. Navigasi ke **Pesanan Pembelian** (`Pesanan Pembelian`).
2. Buka PO yang baru dibuat dan tinjau.
3. Klik **"Konfirmasi"** (`Konfirmasi`) untuk menyelesaikan PO.
4. Saat barang tiba, klik **"Terima"** (`Terima`).
5. Catat jumlah yang diterima. Status PO berubah menjadi **Diterima** (`Diterima`).
6. Tingkat stok inventori diperbarui secara otomatis.

📸 **[SCREENSHOT: Halaman detail Pesanan Pembelian dengan tombol Konfirmasi dan Terima — UI Bahasa Indonesia]**

**Langkah 6 — Proses Hutang Usaha**

1. Navigasi ke **Hutang Usaha** → bagian **Tagihan**.
2. Tagihan pemasok otomatis dibuat atau dibuat manual merujuk PO.
3. Tinjau dan klik **"Posting"** untuk membuat entri jurnal.
4. Proses pembayaran melalui tombol **"Bayar"** jika sudah siap.
5. Sistem mencatat pembayaran dan memperbarui laporan aging AP.

📸 **[SCREENSHOT: Detail tagihan pemasok dengan tombol Posting dan Bayar — UI Bahasa Indonesia]**

---

### 🇰🇷 한국어

**SOP: 구매요청에서 결제까지**

**단계 1 — 구매요청(PR) 작성**

1. 구매 섹션의 **구매요청** (`구매요청`)으로 이동합니다.
2. **"새 구매요청"** (`새 구매요청`)을 클릭합니다.
3. 필수 필드를 작성합니다:
   - **요청자** — 현재 사용자로 자동 입력
   - **필요일** — 물품이 필요한 날짜
   - **우선순위** — 낮음 / 보통 / 높음
   - **항목** — 제품, 수량 및 예상 가격 추가
4. **"저장"** (`저장`)을 클릭하여 초안으로 저장합니다.

📸 **[SCREENSHOT: 항목이 있는 구매요청 작성 양식 — 한국어 UI]**

**단계 2 — 승인 제출**

1. 초안 PR을 엽니다.
2. **"제출"** (`제출`)을 클릭합니다.
3. PR 상태가 **초안**에서 **승인 대기** (`승인 대기`)로 변경됩니다.
4. 지정된 승인자가 알림을 받습니다.

**단계 3 — 승인 프로세스**

1. 승인자가 **승인** (`승인`)으로 이동하거나 알림 배지를 확인합니다.
2. PR 세부 정보와 항목을 검토합니다.
3. 메모와 함께 **"승인"** (`승인`) 또는 **"반려"** (`반려`)를 클릭합니다.
4. 승인되면 PR 상태가 **승인됨** (`승인됨`)으로 변경됩니다.

📸 **[SCREENSHOT: 승인/반려 버튼이 있는 승인 상세 페이지 — 한국어 UI]**

**단계 4 — PR을 구매 주문(PO)으로 전환**

1. 승인된 PR을 엽니다.
2. **"PO로 전환"**을 클릭합니다.
3. **공급업체** (`공급업체`)를 선택하고 필요시 수량을 조정합니다.
4. **"구매 주문 생성"**을 클릭하여 PO를 생성합니다.
5. PR 상태가 **전환됨** (`전환됨`)으로 변경됩니다.

📸 **[SCREENSHOT: 공급업체 선택이 있는 PR-PO 전환 대화상자 — 한국어 UI]**

**단계 5 — 확인 및 입고**

1. **구매 주문** (`구매 주문`)으로 이동합니다.
2. 새로 생성된 PO를 열고 검토합니다.
3. **"확인"** (`확인`)을 클릭하여 PO를 확정합니다.
4. 물품 도착 시 **"수령"**을 클릭합니다.
5. 수령 수량을 기록합니다. PO 상태가 **수령됨** (`수령됨`)으로 변경됩니다.
6. 재고 수준이 자동으로 업데이트됩니다.

📸 **[SCREENSHOT: 확인 및 수령 버튼이 있는 구매 주문 상세 페이지 — 한국어 UI]**

**단계 6 — 매입채무 처리**

1. **매입채무** → **청구서** 섹션으로 이동합니다.
2. 공급업체 청구서가 자동 생성되거나 PO를 참조하여 수동 생성합니다.
3. 검토 후 **"전기"**를 클릭하여 분개를 생성합니다.
4. 준비되면 **"결제"** 버튼으로 결제를 처리합니다.
5. 시스템이 결제를 기록하고 AP 에이징 보고서를 업데이트합니다.

📸 **[SCREENSHOT: 전기 및 결제 버튼이 있는 공급업체 청구서 상세 — 한국어 UI]**

---

### 🇨🇳 中文

**SOP：从采购申请到付款**

**步骤1 — 创建采购申请（PR）**

1. 导航至采购部分的**采购申请** (`采购申请`)。
2. 点击**"新建采购申请"** (`新建采购申请`)。
3. 填写必填字段：
   - **申请人** — 自动填充当前用户
   - **需求日期** — 物品需要的日期
   - **优先级** — 低 / 中 / 高
   - **明细项** — 添加产品、数量和估计价格
4. 点击**"保存"** (`保存`) 保存为草稿。

📸 **[SCREENSHOT: 带明细项的采购申请创建表单 — 中文UI]**

**步骤2 — 提交审批**

1. 打开草稿PR。
2. 点击**"提交"** (`提交`)。
3. PR状态从**草稿**变更为**待审批** (`待审批`)。
4. 指定的审批人收到通知。

**步骤3 — 审批流程**

1. 审批人导航至**审批** (`审批`) 或查看通知徽章。
2. 审核PR详情和明细项。
3. 附注后点击**"批准"** (`批准`) 或**"拒绝"** (`拒绝`)。
4. 批准后，PR状态变更为**已批准** (`已批准`)。

📸 **[SCREENSHOT: 显示PR的审批详情页面，带批准/拒绝按钮 — 中文UI]**

**步骤4 — 将PR转换为采购订单（PO）**

1. 打开已批准的PR。
2. 点击**"转换为PO"**。
3. 选择**供应商** (`供应商`) 并按需调整数量。
4. 点击**"创建采购订单"**生成PO。
5. PR状态变更为**已转换** (`已转换`)。

📸 **[SCREENSHOT: 带供应商选择的PR转PO对话框 — 中文UI]**

**步骤5 — 确认并收货**

1. 导航至**采购订单** (`采购订单`)。
2. 打开新创建的PO并审核。
3. 点击**"确认"** (`确认`) 确定PO。
4. 货物到达时，点击**"收货"**。
5. 记录收货数量。PO状态变更为**已收货** (`已收货`)。
6. 库存水平自动更新。

📸 **[SCREENSHOT: 带确认和收货按钮的采购订单详情页 — 中文UI]**

**步骤6 — 处理应付账款**

1. 导航至**应付账款** → **账单** 部分。
2. 供应商账单自动生成或手动参考PO创建。
3. 审核后点击**"过账"**创建日记账分录。
4. 准备就绪时通过**"付款"**按钮处理付款。
5. 系统记录付款并更新AP账龄报表。

📸 **[SCREENSHOT: 带过账和付款按钮的供应商账单详情 — 中文UI]**

---

## 3.2 Inventory Management

### 🇬🇧 English

**Understanding Stock Valuation — Weighted Average Cost (WAC)**

ManERP uses the **Weighted Average Cost** method for inventory valuation. This means every time inventory is purchased at a different price, the system recalculates the average cost per unit.

**Formula:**

$$\text{WAC} = \frac{\text{Total Cost of Inventory on Hand}}{\text{Total Units on Hand}}$$

**How to View Stock Valuation:**

1. Navigate to **Inventory** → **Stock Management** (`Stock Management`).
2. The stock list shows current quantities and average cost per unit.
3. For detailed valuation, go to **Inventory** → **Stock Valuation** page.
4. The system displays valuation broken down by product type:
   - **Raw Material** (`Raw Material` / `Bahan Baku` / `원재료` / `原材料`)
   - **Finished Good** (`Finished Good` / `Barang Jadi` / `완제품` / `成品`)
   - **Consumable** (`Consumable` / `Consumable` / `소모품` / `消耗品`)

📸 **[SCREENSHOT: Stock Valuation page showing WAC per product with breakdown by type — English UI]**

**Minimum Stock Threshold:**

- Each product can have a **minimum stock** (`Min Stock`) value configured.
- When stock falls below this threshold, it appears in the **Low Stock Alert** on the Dashboard.
- Navigate to **Products** (`Products`) → Edit a product to set `Min Stock`.

📸 **[SCREENSHOT: Product edit form showing Min Stock field — English UI]**

**Stock Movements:**

| Movement Type | Description |
|---------------|-------------|
| **Purchase Receipt** | Stock increases when goods are received from a PO |
| **Sales Delivery** | Stock decreases when goods are delivered to customers |
| **Stock Transfer** | Move stock between warehouses | 
| **Manufacturing** | Raw materials consumed → Finished goods produced |
| **Stock Adjustment** | Manual corrections (does not affect WAC) |

---

### 🇮🇩 Bahasa Indonesia

**Memahami Valuasi Stok — Biaya Rata-rata Tertimbang (WAC)**

ManERP menggunakan metode **Biaya Rata-rata Tertimbang** (Weighted Average Cost) untuk valuasi inventori. Setiap kali inventori dibeli dengan harga berbeda, sistem menghitung ulang biaya rata-rata per unit.

**Rumus:**

$$\text{WAC} = \frac{\text{Total Biaya Inventori yang Ada}}{\text{Total Unit yang Ada}}$$

**Cara Melihat Valuasi Stok:**

1. Navigasi ke **Inventori** → **Manajemen Stok** (`Manajemen Stok`).
2. Daftar stok menampilkan jumlah saat ini dan biaya rata-rata per unit.
3. Untuk valuasi detail, buka halaman **Inventori** → **Valuasi Stok**.
4. Sistem menampilkan valuasi berdasarkan jenis produk:
   - **Bahan Baku** (`Bahan Baku`)
   - **Barang Jadi** (`Barang Jadi`)
   - **Consumable** (`Consumable`)

📸 **[SCREENSHOT: Halaman Valuasi Stok menampilkan WAC per produk dengan rincian per jenis — UI Bahasa Indonesia]**

**Ambang Batas Stok Minimum:**

- Setiap produk dapat dikonfigurasi dengan nilai **stok minimum** (`Stok Min`).
- Ketika stok turun di bawah ambang batas, akan muncul di **Peringatan Stok Rendah** pada Dasbor.
- Navigasi ke **Produk** (`Produk`) → Edit produk untuk mengatur `Stok Min`.

📸 **[SCREENSHOT: Formulir edit produk menampilkan kolom Stok Min — UI Bahasa Indonesia]**

---

### 🇰🇷 한국어

**재고 평가 이해 — 가중평균원가법 (WAC)**

ManERP는 재고 평가에 **가중평균원가법**을 사용합니다. 다른 가격으로 재고를 구매할 때마다 시스템이 단위당 평균 원가를 재계산합니다.

**공식:**

$$\text{WAC} = \frac{\text{보유 재고 총 원가}}{\text{보유 총 수량}}$$

**재고 평가 보기:**

1. **재고** → **재고 관리** (`재고 관리`)로 이동합니다.
2. 재고 목록에 현재 수량과 단위당 평균 원가가 표시됩니다.
3. 상세 평가를 보려면 **재고** → **재고 평가** 페이지로 이동합니다.
4. 제품 유형별로 평가가 표시됩니다:
   - **원재료** (`원재료`)
   - **완제품** (`완제품`)
   - **소모품** (`소모품`)

📸 **[SCREENSHOT: 유형별 분류와 함께 제품별 WAC를 보여주는 재고 평가 페이지 — 한국어 UI]**

---

### 🇨🇳 中文

**理解库存估值 — 加权平均成本法（WAC）**

ManERP使用**加权平均成本法**进行库存估值。每次以不同价格采购库存时，系统都会重新计算每单位的平均成本。

**公式：**

$$\text{WAC} = \frac{\text{现有库存总成本}}{\text{现有总数量}}$$

**查看库存估值：**

1. 导航至**库存** → **库存管理** (`库存管理`)。
2. 库存列表显示当前数量和每单位平均成本。
3. 详细估值请前往**库存** → **库存估值** 页面。
4. 系统按产品类型显示估值：
   - **原材料** (`原材料`)
   - **成品** (`成品`)
   - **消耗品** (`消耗品`)

📸 **[SCREENSHOT: 按类型分类显示每个产品WAC的库存估值页面 — 中文UI]**

---

## 3.3 Manufacturing

### 🇬🇧 English

**SOP: Bill of Materials (BOM) → Work Order → Quality Control**

**A. Managing Bill of Materials** (`Bill of Materials` / `Daftar Bahan` / `자재 명세서` / `物料清单`)

1. Navigate to **Manufacturing** → **Bill of Materials** (`Bill of Materials`).
2. Click **"Create"** (`Create`) to define a new BOM.
3. Select the **Finished Product** to be manufactured.
4. Add **Component** lines:
   - **Product** — raw material or semi-finished component
   - **Quantity** — required quantity per 1 unit of finished product
   - **Sub-BOM** — link to another BOM if the component is a semi-finished product (multi-level BOM)
5. Click **"Save"** (`Save`).

📸 **[SCREENSHOT: BOM creation form with multi-level component tree — English UI]**

**BOM Versioning:**
- Each BOM supports versioning. Click **"New Version"** to create a revision without losing the previous definition.
- The system maintains the flattened BOM view to show the total raw material requirements across all sub-assembly levels.

📸 **[SCREENSHOT: BOM version list and flattened BOM view — English UI]**

**B. Manufacturing Orders** (`Work Orders` / `Perintah Kerja` / `작업 주문` / `工单`)

| Status | EN | ID | KO | ZH |
|--------|----|----|----|----|
| Draft | `Draft` | `Draf` | `초안` | `草稿` |
| Confirmed | `Confirmed` | `Dikonfirmasi` | `확인됨` | `已确认` |
| In Progress | `In Progress` | `Sedang Berjalan` | `진행 중` | `进行中` |
| Completed | `Completed` | `Selesai` | `완료됨` | `已完成` |
| Cancelled | `Cancelled` | `Dibatalkan` | `취소됨` | `已取消` |

**Workflow:**

1. Navigate to **Manufacturing** → **Work Orders** (`Work Orders`).
2. Click **"Create"** to create a new manufacturing order.
3. Select the **BOM** and specify the **Quantity** to produce.
4. Set **Priority**: Low / Medium / High (`Low` / `Medium` / `High`) — (`Rendah` / `Sedang` / `Tinggi`).
5. Click **"Save"**, then **"Confirm"** (`Confirm`) to start the order.
6. When production is complete, click **"Produce"** (`Produce`).
7. The system automatically:
   - Deducts raw material stock (based on BOM quantities)
   - Adds finished goods to inventory
   - Calculates production cost using WAC of consumed materials

📸 **[SCREENSHOT: Manufacturing Order detail page with Confirm and Produce buttons — English UI]**

**C. Quality Control (QC)** (`Quality Control` / `Kontrol Kualitas` / `품질 관리` / `质量控制`)

**QC Parameters** — Define what to check:

| Parameter Type | EN | ID | KO | ZH |
|---------------|----|----|----|----|
| Numeric | `Numeric` | `Numerik` | `수치` | `数值` |
| Boolean (Pass/Fail) | `Boolean` | `Boolean` | `부울` | `布尔` |
| Visual | `Visual` | `Visual` | `시각` | `目视` |

**QC Inspections** — Record results:

| Inspection Type | EN | ID | KO | ZH |
|----------------|----|----|----|----|
| Incoming | `Incoming` | `Masuk` | `입고` | `来料` |
| In-Process | `In Process` | `Dalam Proses` | `공정 중` | `过程中` |
| Final | `Final` | `Akhir` | `최종` | `最终` |

**SOP:**
1. Navigate to **Quality Control** → **QC Inspections** (`QC Inspections`).
2. Click **"Create"** to start a new inspection.
3. Select the inspection type and reference (MO or PO).
4. For each QC parameter, record the measurement or result.
5. Click **"Record Results"** to save.
6. The system determines the overall result: **Passed** / **Failed** / **Partial** (`Passed` / `Failed` / `Partial`).

📸 **[SCREENSHOT: QC Inspection results recording form with parameter values — English UI]**

---

### 🇮🇩 Bahasa Indonesia

**SOP: Daftar Bahan (BOM) → Perintah Kerja → Kontrol Kualitas**

**A. Mengelola Daftar Bahan** (`Daftar Bahan`)

1. Navigasi ke **Manufaktur** → **Daftar Bahan** (`Daftar Bahan`).
2. Klik **"Buat"** (`Buat`) untuk mendefinisikan BOM baru.
3. Pilih **Produk Jadi** yang akan diproduksi.
4. Tambahkan baris **Komponen**:
   - **Produk** — bahan baku atau komponen semi-jadi
   - **Jumlah** — jumlah yang dibutuhkan per 1 unit produk jadi
   - **Sub-BOM** — tautkan ke BOM lain jika komponen adalah produk semi-jadi (BOM multi-level)
5. Klik **"Simpan"** (`Simpan`).

📸 **[SCREENSHOT: Formulir pembuatan BOM dengan pohon komponen multi-level — UI Bahasa Indonesia]**

**Versioning BOM:**
- Setiap BOM mendukung versioning. Klik **"Versi Baru"** untuk membuat revisi tanpa kehilangan definisi sebelumnya.
- Sistem memelihara tampilan BOM terurai (flattened) untuk menunjukkan total kebutuhan bahan baku di semua level sub-assembly.

**B. Perintah Manufaktur** (`Perintah Kerja`)

1. Navigasi ke **Manufaktur** → **Perintah Kerja** (`Perintah Kerja`).
2. Klik **"Buat"** untuk membuat perintah manufaktur baru.
3. Pilih **BOM** dan tentukan **Jumlah** yang akan diproduksi.
4. Atur **Prioritas**: Rendah / Sedang / Tinggi.
5. Klik **"Simpan"**, lalu **"Konfirmasi"** (`Konfirmasi`).
6. Saat produksi selesai, klik **"Produksi"** (`Produksi`).
7. Sistem secara otomatis:
   - Mengurangi stok bahan baku (berdasarkan jumlah BOM)
   - Menambahkan barang jadi ke inventori
   - Menghitung biaya produksi menggunakan WAC material yang dikonsumsi

📸 **[SCREENSHOT: Halaman detail Perintah Manufaktur dengan tombol Konfirmasi dan Produksi — UI Bahasa Indonesia]**

**C. Kontrol Kualitas (QC)** (`Kontrol Kualitas`)

1. Navigasi ke **Kontrol Kualitas** → **Inspeksi QC** (`Inspeksi QC`).
2. Klik **"Buat"** untuk memulai inspeksi baru.
3. Pilih jenis inspeksi dan referensi (MO atau PO).
4. Untuk setiap parameter QC, catat hasil pengukuran.
5. Klik **"Catat Hasil"** untuk menyimpan.
6. Sistem menentukan hasil keseluruhan: **Lolos** / **Gagal** / **Sebagian**.

📸 **[SCREENSHOT: Formulir pencatatan hasil Inspeksi QC dengan nilai parameter — UI Bahasa Indonesia]**

---

### 🇰🇷 한국어

**SOP: 자재 명세서(BOM) → 작업 주문 → 품질 관리**

**A. 자재 명세서 관리** (`자재 명세서`)

1. **제조** → **자재 명세서** (`자재 명세서`)로 이동합니다.
2. **"생성"** (`생성`)을 클릭하여 새 BOM을 정의합니다.
3. 제조할 **완제품**을 선택합니다.
4. **구성품** 라인을 추가합니다:
   - **제품** — 원재료 또는 반제품
   - **수량** — 완제품 1단위당 필요 수량
   - **하위 BOM** — 구성품이 반제품인 경우 다른 BOM과 연결 (다단계 BOM)
5. **"저장"** (`저장`)을 클릭합니다.

📸 **[SCREENSHOT: 다단계 구성품 트리가 있는 BOM 생성 양식 — 한국어 UI]**

**B. 제조 주문** (`작업 주문`)

1. **제조** → **작업 주문** (`작업 주문`)으로 이동합니다.
2. **"생성"**을 클릭하여 새 제조 주문을 생성합니다.
3. **BOM**을 선택하고 생산할 **수량**을 지정합니다.
4. **우선순위** 설정: 낮음 / 보통 / 높음.
5. **"저장"** 후 **"확인"** (`확인`)을 클릭합니다.
6. 생산 완료 시 **"생산"**을 클릭합니다.

📸 **[SCREENSHOT: 확인 및 생산 버튼이 있는 제조 주문 상세 페이지 — 한국어 UI]**

**C. 품질 관리 (QC)** (`품질 관리`)

1. **품질 관리** → **QC 검사** (`QC 검사`)로 이동합니다.
2. **"생성"**을 클릭하여 새 검사를 시작합니다.
3. 검사 유형과 참조(MO 또는 PO)를 선택합니다.
4. 각 QC 매개변수에 대한 측정값 또는 결과를 기록합니다.
5. **"결과 기록"**을 클릭하여 저장합니다.
6. 시스템이 전체 결과를 판정합니다: **합격** / **불합격** / **부분 합격**.

📸 **[SCREENSHOT: 매개변수 값이 있는 QC 검사 결과 기록 양식 — 한국어 UI]**

---

### 🇨🇳 中文

**SOP：物料清单(BOM) → 工单 → 质量控制**

**A. 管理物料清单** (`物料清单`)

1. 导航至**制造** → **物料清单** (`物料清单`)。
2. 点击**"创建"** (`创建`) 定义新BOM。
3. 选择要制造的**成品**。
4. 添加**组件**行：
   - **产品** — 原材料或半成品
   - **数量** — 每1单位成品所需数量
   - **子BOM** — 如果组件是半成品，则链接到另一个BOM（多级BOM）
5. 点击**"保存"** (`保存`)。

📸 **[SCREENSHOT: 带多级组件树的BOM创建表单 — 中文UI]**

**B. 制造工单** (`工单`)

1. 导航至**制造** → **工单** (`工单`)。
2. 点击**"创建"**创建新制造工单。
3. 选择**BOM**并指定生产**数量**。
4. 设置**优先级**：低 / 中 / 高。
5. 点击**"保存"**，然后**"确认"** (`确认`)。
6. 生产完成后，点击**"生产"**。

📸 **[SCREENSHOT: 带确认和生产按钮的制造工单详情页 — 中文UI]**

**C. 质量控制 (QC)** (`质量控制`)

1. 导航至**质量控制** → **QC检验** (`QC检验`)。
2. 点击**"创建"**开始新检验。
3. 选择检验类型和参考（MO或PO）。
4. 对每个QC参数记录测量值或结果。
5. 点击**"记录结果"**保存。
6. 系统判定总体结果：**合格** / **不合格** / **部分合格**。

📸 **[SCREENSHOT: 带参数值的QC检验结果记录表单 — 中文UI]**

---

## 3.4 Sales Cycle

### Complete Flow Diagram (All Languages)

```
┌──────────────────┐     ┌──────────────────┐     ┌──────────────────┐     ┌──────────────────┐
│   Sales Order     │────▶│    Delivery      │────▶│    Invoice       │────▶│  Payment (AR)    │
│  (Pesanan         │     │  (Pengiriman)    │     │  (Faktur)        │     │  (Piutang Usaha) │
│   Penjualan)      │     │  (배송)           │     │  (송장)           │     │  (매출채권)       │
│  (판매 주문)       │     │  (交货)           │     │  (发票)           │     │  (应收账款)       │
│  (销售订单)        │     │                  │     │                  │     │                  │
└──────────────────┘     └──────────────────┘     └──────────────────┘     └──────────────────┘
     Draft → Confirmed        Delivered              Issued                  Paid
```

---

### 🇬🇧 English

**SOP: Sales Order to Payment Collection**

**Step 1 — Create Sales Order**

1. Navigate to **Sales Orders** (`Sales Orders`).
2. Click **"New Sales Order"** (`New Sales Order`).
3. Select the **Client** (`Client`), **Warehouse**, and optionally a **Project**.
4. Add **Line Items**: product, quantity, unit price, discount, and tax.
5. Click **"Save"** (`Save`).

📸 **[SCREENSHOT: Sales Order creation form with line items — English UI]**

**Step 2 — Confirm Order**

1. Review the Sales Order.
2. Click **"Confirm"** (`Confirm`).
3. Status changes from Draft to **Confirmed** (`Confirmed`).

**Step 3 — Deliver Goods**

1. Click **"Deliver"** (`Deliver`) on the confirmed SO.
2. The system deducts stock from the designated warehouse.
3. Status updates to **Delivered**.

**Step 4 — Generate Invoice**

1. Click **"Invoice"** (`Invoice`) on the delivered SO.
2. An invoice is auto-generated with the SO details.
3. The invoice appears in **Invoices** (`Invoices`) with journal entries posted.

📸 **[SCREENSHOT: Invoice generated from Sales Order showing line items and totals — English UI]**

**Step 5 — Collect Payment**

1. Navigate to the invoice detail page.
2. Click **"Record Payment"** to log received payment.
3. The system updates the **Accounts Receivable** (`Accounts Receivable`) balance.
4. AR Aging report reflects the collection.

---

### 🇮🇩 Bahasa Indonesia

**SOP: Pesanan Penjualan hingga Penerimaan Pembayaran**

**Langkah 1 — Buat Pesanan Penjualan**

1. Navigasi ke **Pesanan Penjualan** (`Pesanan Penjualan`).
2. Klik **"Pesanan Penjualan Baru"** (`Pesanan Penjualan Baru`).
3. Pilih **Klien** (`Klien`), **Gudang**, dan opsional **Proyek**.
4. Tambahkan **Item**: produk, jumlah, harga satuan, diskon, dan pajak.
5. Klik **"Simpan"** (`Simpan`).

📸 **[SCREENSHOT: Formulir pembuatan Pesanan Penjualan dengan item — UI Bahasa Indonesia]**

**Langkah 2 — Konfirmasi Pesanan**

1. Tinjau Pesanan Penjualan.
2. Klik **"Konfirmasi"** (`Konfirmasi`).
3. Status berubah dari Draf menjadi **Dikonfirmasi** (`Dikonfirmasi`).

**Langkah 3 — Kirim Barang**

1. Klik **"Kirim"** pada SO yang telah dikonfirmasi.
2. Sistem mengurangi stok dari gudang yang ditunjuk.

**Langkah 4 — Buat Faktur**

1. Klik **"Faktur"** (`Faktur`) pada SO yang telah dikirim.
2. Faktur otomatis dibuat dengan detail SO.
3. Faktur muncul di **Faktur** (`Faktur`) dengan entri jurnal yang terposting.

📸 **[SCREENSHOT: Faktur yang dihasilkan dari Pesanan Penjualan — UI Bahasa Indonesia]**

**Langkah 5 — Terima Pembayaran**

1. Buka halaman detail faktur.
2. Klik **"Catat Pembayaran"** untuk mencatat pembayaran yang diterima.
3. Sistem memperbarui saldo **Piutang Usaha** (`Piutang Usaha`).

---

### 🇰🇷 한국어

**SOP: 판매 주문에서 대금 수금까지**

1. **판매 주문** (`판매 주문`)으로 이동합니다.
2. **"새 판매 주문"** (`새 판매 주문`)을 클릭합니다.
3. **고객** (`고객`), **창고**, 선택적으로 **프로젝트**를 선택합니다.
4. **항목**을 추가합니다: 제품, 수량, 단가, 할인, 세금.
5. **"저장"** (`저장`) → **"확인"** (`확인`)으로 주문을 확정합니다.
6. **"배송"**을 클릭하여 출고합니다.
7. **"송장"** (`송장`)을 클릭하여 송장을 생성합니다.
8. 송장 상세에서 **"결제 기록"**을 통해 수금합니다.

📸 **[SCREENSHOT: 항목과 합계가 표시된 판매 주문 양식 — 한국어 UI]**

---

### 🇨🇳 中文

**SOP：从销售订单到收款**

1. 导航至**销售订单** (`销售订单`)。
2. 点击**"新销售订单"** (`新销售订单`)。
3. 选择**客户** (`客户`)、**仓库**，可选**项目**。
4. 添加**明细项**：产品、数量、单价、折扣和税额。
5. **"保存"** (`保存`) → **"确认"** (`确认`) 确定订单。
6. 点击**"交货"**进行出库。
7. 点击**"发票"** (`发票`) 生成发票。
8. 在发票详情中通过**"记录收款"**收取款项。

📸 **[SCREENSHOT: 带明细项和合计的销售订单表单 — 中文UI]**

---

## 3.5 Approval Workflows

### 🇬🇧 English

ManERP includes a configurable multi-step approval system for critical business documents.

**Documents Requiring Approval:**

| Document | EN | ID | KO | ZH |
|----------|----|----|----|----|
| Purchase Order | `Purchase Order` | `Purchase Order` | `구매 주문` | `采购订单` |
| Invoice | `Invoice` | `Invoice` | `송장` | `发票` |
| Supplier Bill | `Supplier Bill` | `Tagihan Supplier` | `공급업체 청구서` | `供应商账单` |
| Payment | `Payment` | `Pembayaran` | `결제` | `付款` |
| Sales Order | `Sales Order` | `Sales Order` | `판매 주문` | `销售订单` |

**Approval Flow:**

1. Admin configures approval flows in **Administration** → **Approval Flows** (`Approval Flows` / `Alur Persetujuan`).
2. When a document is submitted, it follows the configured flow (single or multi-step).
3. Approvers receive notifications and see pending items on their Dashboard.
4. Each approval step records the action, notes, and timestamp.
5. If rejected, the document returns to the requester who can modify and resubmit.

📸 **[SCREENSHOT: Approval flow configuration page showing steps — English UI]**

**Approval Status Reference:**

| Status | EN | ID | KO | ZH |
|--------|----|----|----|----|
| Pending | `Pending` | `Menunggu` | `대기 중` | `待处理` |
| Approved | `Approved` | `Disetujui` | `승인됨` | `已批准` |
| Rejected | `Rejected` | `Ditolak` | `반려됨` | `已拒绝` |
| Cancelled | `Cancelled` | `Dibatalkan` | `취소됨` | `已取消` |
| Resubmitted | `Resubmitted` | `Diajukan Ulang` | `재제출` | `重新提交` |
