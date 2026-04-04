🏭 Deep Audit — Modul Manufaktur ManERP

**Auditor:** Industrial Engineering Consultant & Senior ERP Manufacturing Architect
**Tanggal:** 4 April 2026
**Cakupan:** BOM, Manufacturing Orders, Costing (HPP), QC, Accounting Integration
**File yang Diaudit:** 18 file (6 model, 4 controller, 4 service, 2 migration, 1 trait, 1 route)

---

## 1. Bill of Materials (BOM) & Costing Roll-up

### 1.1 Recursive Logic — Multi-level BOM ✅

Sistem **sudah mendukung multi-level BOM** penuh via metode rekursif `getFlattenedMaterials()` di BillOfMaterial.php:

```
BOM Level-0: Steel Frame (FG)
 ├── BOM Level-1: Welded Assembly (sub_bom_id → BOM #2)
 │    ├── Steel Rod (RM) ← leaf
 │    └── Bolt Set (RM)  ← leaf
 └── Paint (RM) ← leaf
```

**Cara kerja cost roll-up:**

1. `getFlattenedMaterials($parentQty)` menelusuri setiap `BomItem`
2. Jika item memiliki `sub_bom_id` → **rekursi** ke sub-BOM dengan adjusted quantity: $\text{requiredQty} = \frac{\text{item.quantity}}{\text{output\_quantity}} \times \text{parentQty}$
3. Jika item adalah **leaf material** → masuk ke array flat dengan `unit_cost` dari prioritas: `BomItem.unit_cost → product.avg_cost → product.cost_price → 0`
4. **Proteksi circular reference** via array `$visited` — jika BOM sudah dikunjungi, return `[]`

**`CostingService::calculateBomCost()`** (CostingService.php) kemudian menjumlahkan:

$$
\text{Material Cost} = \sum_{i=1}^{n} \text{quantity}_i \times \text{unit\_cost}_i
$$

### 1.2 Versioning ✅

Versioning diimplementasi via `createNewVersion()` (BillOfMaterial.php):

- Membuat **clone BOM** dengan `version + 1`
- Mereplikasi semua `BomItem` termasuk `sub_bom_id`, `unit_cost`, `line_cost`
- **ManufacturingOrder menyimpan `bom_id` eksplisit** — MO yang sudah jalan tetap merujuk BOM versi lama, sehingga **histori produksi aman** dari perubahan revisi


| Aspek                        | Status                                  |
| ---------------------------- | --------------------------------------- |
| Snapshot BOM per MO          | ✅ via`bom_id` FK                       |
| Soft-deactivation versi lama | ✅ via`is_active` flag                  |
| Rollback ke versi sebelumnya | ⚠️ Manual — tidak ada fitur "revert" |

### 1.3 Waste & Scrap ❌


| Item                                             | Status       |
| ------------------------------------------------ | ------------ |
| Kolom`scrap_factor` / `waste_pct` di BomItem     | ❌ Tidak ada |
| Adjustment otomatis kebutuhan material utk waste | ❌ Tidak ada |
| Tracking scrap quantity saat produksi            | ❌ Tidak ada |

**Risiko:** Kalkulasi kebutuhan material menggunakan formula **exact quantity** tanpa faktor safety. Contoh: jika BOM menyatakan 2 kg baja per frame dan waste rata-rata 5%, sistem tetap menghitung kebutuhan = 2 kg, bukan 2.1 kg.

**Severity: P2 — Medium Risk** (akurasi perencanaan material terdampak)

---

## 2. Production Workflow & State Machine

### 2.1 Lifecycle Management

State machine didefinisikan di ManufacturingOrder.php menggunakan trait `HasStateMachine`:

```
┌───────┐    confirm()    ┌───────────┐   produce()   ┌─────────────┐   auto    ┌──────┐
│ draft │───────────────→│ confirmed │─────────────→│ in_progress │────────→│ done │
└───┬───┘                └─────┬─────┘              └──────┬──────┘        └──────┘
    │                          │                           │
    └──────────┬───────────────┴───────────────────────────┘
               ▼
         ┌───────────┐
         │ cancelled │
         └───────────┘
```

**Validasi per transisi:**


| Transisi                   | Validasi                                                                              | File & Line                            |
| -------------------------- | ------------------------------------------------------------------------------------- | -------------------------------------- |
| `draft → confirmed`       | `requireTransition('confirmed')` — hanya cek status valid                            | ManufacturingOrderController.php       |
| `confirmed → in_progress` | **Auto-triggered** saat `produce()` pertama kali                                      | ManufacturingOrderController.php       |
| `in_progress → done`      | **Auto-triggered** ketika `produced_quantity ≥ planned_quantity`                     | ManufacturingOrderController.php       |
| `* → cancelled`           | Tidak ada validasi khusus — bisa cancel dari`draft`, `confirmed`, atau `in_progress` | Via`transitionTo()` di HasStateMachine |

**Temuan P1 — Tidak Ada Validasi Stok Saat Confirm:**
Ketika MO di-confirm (`draft → confirmed`), **tidak ada pengecekan ketersediaan bahan baku**. Stok baru dicek saat `produce()`. Ini berarti seorang planner bisa mengkonfirmasi 100 MO tanpa cukup stok.

### 2.2 Material Pick-list & Reservasi ❌ KRITIS

**Temuan P0 — Manufacturing TIDAK menggunakan `reserved_quantity`.**

Di ManufacturingOrderController.php, pre-validasi stok menggunakan **raw `quantity`**, bukan `availableQuantity()`:

```php
$available = $item->product->inventoryStocks()
    ->where('warehouse_id', $order->warehouse_id)
    ->value('quantity') ?? 0;  // ← mengabaikan reserved_quantity!
```

**Skenario Masalah:**

1. Stok Steel Rod: 100 pcs (80 sudah reserved utk SO)
2. `availableQuantity()` seharusnya = 20
3. MO cek: `quantity = 100 ≥ 50 needed` → **LOLOS** ✅
4. `produce()` deduct 50 → stok jadi 50
5. SO deliver mencoba deduct 80 → **GAGAL** karena stok hanya 50 ❌

**Severity: P0 — Critical** (konflik antara Sales reservation dan Manufacturing consumption)

### 2.3 Atomicity ❌ KRITIS

**Temuan P0 — `produce()` tidak dibungkus `DB::transaction()`.**

Di ManufacturingOrderController.php, alur produksi:

```
Pre-validate stock     ← Bukan dalam transaction
Consume RM #1          ← StockService::processMovement (punya transaction sendiri)
Consume RM #2          ← Transaction terpisah
Consume RM #3          ← Jika gagal di sini...
Produce FG             ← ...RM #1 dan #2 sudah hilang, FG belum masuk
Create journal         ← Journal bisa tidak terbuat
Update MO status       ← MO state bisa inkonsisten
```

Setiap `processMovement()` memiliki transaction **sendiri** (StockService.php), tapi **seluruh orkestrasi di `produce()` tidak di-wrap**. Jika server crash setelah consume RM #2 tapi sebelum RM #3:

- RM #1 & #2 **sudah terdeduct** (committed)
- RM #3 belum
- FG belum diproduksi
- Journal belum terbuat
- `produced_quantity` belum bertambah

**Severity: P0 — Critical** (risiko kehilangan stok)

---

## 3. Akuntansi Manufaktur & Variance Analysis

### 3.1 Cost Components

Sistem mendukung **3 komponen biaya** di level Product (Product.php):


| Komponen                    | Field           | Mekanisme                                             |
| --------------------------- | --------------- | ----------------------------------------------------- |
| Bahan Baku (Material)       | `avg_cost`      | Dihitung dinamis oleh StockValuationService (WAC)     |
| Tenaga Kerja (Direct Labor) | `labor_cost`    | **Statis** — diinput manual per produk, bukan per MO |
| Overhead Pabrik             | `overhead_cost` | **Statis** — diinput manual per produk, bukan per MO |

**`CostingService::calculateProductionCost()`** (CostingService.php):

$$
\text{HPP Total} = \text{Material Cost} + (\text{labor\_cost} \times \text{qty}) + (\text{overhead\_cost} \times \text{qty})
$$

**Temuan P1:** Labor & overhead bersifat statis per produk. Tidak ada mekanisme untuk mencatat **actual labor hours** atau **actual overhead** per MO. Jika produksi memakan waktu 2× lebih lama (lembur, mesin rusak), biaya labor tetap sama.

### 3.2 Actual vs Standard Cost — Variance Analysis ✅ (Parsial)

`CostingService::getCostVariance()` (CostingService.php) menghitung:

$$
\text{Variance} = \text{Actual Cost} - \text{Standard Cost}
$$

$$
\text{Variance \%} = \frac{\text{Actual} - \text{Standard}}{\text{Standard}} \times 100\%
$$

Di mana:

- **Standard Cost** = `product.standard_cost × produced_quantity`
- **Actual Cost** = `ProductionCost.total_cost` (material + labor + overhead)

Variance ditampilkan di CostingController.php dan view costing/show.blade.php.

**Temuan P1 — Variance tidak dipecah per komponen:**
Sistem hanya menghitung **total variance**. Tidak ada pemecahan menjadi:

- Material Price Variance (MPV)
- Material Usage Variance (MUV)
- Labor Rate Variance
- Labor Efficiency Variance
- Overhead Volume Variance

**Temuan P1 — Variance tidak dijurnal:**
Selisih biaya hanya ditampilkan di laporan. GAP antara standard cost dan actual cost **tidak pernah masuk ke General Ledger** sebagai jurnal variance debit/kredit.

### 3.3 WIP Accounting ❌ TIDAK ADA

**Temuan P1 — Tidak ada akun Work-in-Progress (WIP).**

Jurnal manufaktur saat ini (StockValuationService.php):

```
Dr  1300-FG  (Inventory — Finished Goods)
Cr  1300-RM  (Inventory — Raw Materials)
```

**Aliran yang seharusnya (PSAK 14 / IAS 2 compliant):**

```
STEP 1 — Material Consumption (saat produce consume RM):
    Dr  1400-WIP     (Work in Progress)
    Cr  1300-RM      (Raw Materials Inventory)

STEP 2 — Labor & Overhead Allocation:
    Dr  1400-WIP     (Work in Progress)
    Cr  5100-DL      (Direct Labor Payable)
    Cr  5200-OH      (Overhead Applied)

STEP 3 — FG Completion (saat MO done):
    Dr  1300-FG      (Finished Goods Inventory)
    Cr  1400-WIP     (Work in Progress)
```

**Implikasi:**

- Laporan neraca tidak bisa menampilkan nilai WIP secara akurat
- Untuk produksi yang memakan waktu berhari-hari (partial produce), tidak ada pencatatan intermediate value
- Auditor keuangan akan mempertanyakan ke mana biaya "setengah jadi" dicatat

**Severity: P1 — High Risk** (akuntansi tidak PSAK-compliant untuk WIP)

---

## 4. Resource & Capacity Planning

### 4.1 Work Centers ❌

Tidak ada konsep **work center**, **work station**, atau **machine** dalam sistem. Tabel, model, maupun referensi tidak ditemukan.

**Implikasi:**

- Tidak bisa mengalokasikan MO ke mesin tertentu
- Tidak bisa menghitung utilisasi mesin
- Tidak ada finite capacity scheduling

### 4.2 Labor Tracking ❌


| Fitur                            | Status                                   |
| -------------------------------- | ---------------------------------------- |
| Operator assignment per MO       | ❌ Hanya`created_by` (pembuat MO)        |
| Time tracking (jam kerja aktual) | ❌ Tidak ada                             |
| Labor cost per MO (aktual)       | ❌ Hanya statis dari`product.labor_cost` |
| Shift management                 | ❌ Tidak ada                             |
| Barcode scan lantai produksi     | ❌ Tidak ada                             |

Saat ini hanya ada `created_by` di ManufacturingOrder — ini adalah user yang **membuat** MO, bukan operator yang **mengerjakan** produksi.

---

## 5. Quality Control (QC) Integration

### 5.1 QC Architecture

QC sudah diimplementasi sebagai modul terpisah:


| Komponen               | File                       | Status                                       |
| ---------------------- | -------------------------- | -------------------------------------------- |
| QcParameter            | QcParameter.php            | ✅ Definisi parameter (numeric/boolean/text) |
| QcInspection           | QcInspection.php           | ✅ Inspeksi dengan polymorphic reference     |
| QcInspectionItem       | QcInspectionItem.php       | ✅ Item per parameter                        |
| QcInspectionController | QcInspectionController.php | ✅ CRUD + record results                     |

**Tipe Inspeksi:** `incoming` (PO receive), `in_process` (saat produksi), `final` (sebelum kirim)
**State Machine:** `draft → in_progress → completed`
**Hasil:** `pending`, `passed`, `failed`, `partial`

### 5.2 Checkpoints — Posisi QC dalam Alur Produksi ❌ DISCONNECTED

**Temuan P1:** QC dan Manufacturing berjalan **sepenuhnya terpisah**:

```
Manufacturing Flow:     draft → confirmed → in_progress → done
                          (tidak ada checkpoint QC di alur ini)

QC Flow:               draft → in_progress → completed
                          (berdiri sendiri, tidak blocking)
```


| Masalah                               | Detail                                                               |
| ------------------------------------- | -------------------------------------------------------------------- |
| Auto-trigger QC setelah produksi      | ❌ Tidak ada. QC harus dibuat manual.                                |
| QC gate sebelum FG masuk inventory    | ❌ FG langsung masuk stok saat`produce()` tanpa cek QC               |
| Scrap routing dari QC gagal           | ❌ Tidak ada otomatis. QC failed tidak menghasilkan stock adjustment |
| Rework routing                        | ❌ Tidak ada konsep rework order                                     |
| QC relationship di ManufacturingOrder | ❌ Tidak ada`$mo->qcInspections()` relationship                      |

**Implikasi:** Produk yang gagal QC sudah masuk ke Finished Goods inventory dan bisa dijual ke customer. QC hanya bersifat **dokumentasi**, bukan **enforcement**.

---

## 6. Analisis SWOT & Rekomendasi

### ✅ STRENGTHS (Kelebihan)


| #   | Kelebihan                                                 | Bukti                                                                   |
| --- | --------------------------------------------------------- | ----------------------------------------------------------------------- |
| S1  | **Multi-level BOM dengan circular reference protection**  | `getFlattenedMaterials()` tracking `$visited` array                     |
| S2  | **BOM Versioning** tanpa merusak histori                  | `createNewVersion()` + explicit `bom_id` FK di MO                       |
| S3  | **WAC (Weighted Average Cost)** sesuai PSAK 14            | `StockValuationService` dengan `bcmath` precision                       |
| S4  | **HPP Calculation lengkap** (material + labor + overhead) | `CostingService::calculateProductionCost()`                             |
| S5  | **Cost Variance Analysis** dengan persentase              | `getCostVariance()` dengan standard vs actual                           |
| S6  | **BOM Cost Simulator**                                    | `CostingController::simulate()` — bisa simulasi biaya sebelum produksi |
| S7  | **Partial Production**                                    | Bisa produce sebagian (10 dari 100), auto-track`progressPercent()`      |
| S8  | **Fiscal Period Lock** pada manufacturing routes          | Middleware`fiscal-lock` mencegah transaksi di periode tutup             |
| S9  | **QC Module** dengan parameter fleksibel                  | Numeric/boolean/text parameter, polymorphic reference                   |
| S10 | **Audit Trail** via `Auditable` trait                     | Setiap aksi (create/confirm/produce/cancel) tercatat                    |

### ❌ WEAKNESSES (Kelemahan)


| #   | Severity | Kelemahan                                                 | Risiko                                                           |
| --- | -------- | --------------------------------------------------------- | ---------------------------------------------------------------- |
| W1  | **P0**   | `produce()` tanpa `DB::transaction()`                     | Konsumsi material parsial jika gagal tengah jalan → stok hilang |
| W2  | **P0**   | Manufacturing tidak respect`reserved_quantity`            | Konflik stok antara SO reservation dan MO consumption            |
| W3  | **P0**   | Journal silently skips jika CoA 1300-FG/1300-RM tidak ada | Produksi berjalan tanpa pencatatan GL — selisih tak terdeteksi  |
| W4  | **P1**   | Tidak ada WIP accounting                                  | Neraca tidak akurat untuk produksi multi-hari                    |
| W5  | **P1**   | Cost variance tidak dijurnal ke GL                        | Selisih biaya hanya di laporan, tidak di buku besar              |
| W6  | **P1**   | QC disconnected dari manufacturing flow                   | Produk gagal QC bisa masuk inventory dan dijual                  |
| W7  | **P1**   | Tidak ada validasi stok saat MO confirm                   | Planner bisa confirm MO tanpa cukup material                     |
| W8  | **P1**   | Labor & overhead statis (per product, bukan per MO)       | Variance analysis tidak akurat untuk aktual vs standar           |
| W9  | **P2**   | Tidak ada waste/scrap factor di BOM                       | Perencanaan material under-estimate                              |
| W10 | **P2**   | BOM item deletion on update (full replace)                | Concurrent edit bisa hilangkan data                              |

### 🔮 OPPORTUNITIES (Peluang Pengembangan)


| #  | Fitur                                                     | Nilai Bisnis                                           | Kompleksitas |
| -- | --------------------------------------------------------- | ------------------------------------------------------ | ------------ |
| O1 | **Production Scheduling (Gantt Chart)**                   | Visualisasi timeline MO, drag-drop reschedule          | Medium       |
| O2 | **Work Center & Routing**                                 | Alokasi mesin, kapasitas, bottleneck analysis          | High         |
| O3 | **Barcode Scanning** untuk lantai produksi                | Operator scan untuk start/stop produksi, material pick | Medium       |
| O4 | **Machine Maintenance Link** (TPM)                        | Preventive maintenance schedule terikat ke work center | Medium       |
| O5 | **MRP (Material Requirements Planning)** auto-generate PO | Dari BOM + demand → otomatis buat Purchase Request    | High         |
| O6 | **Batch/Lot Tracking**                                    | Traceability bahan baku sampai produk jadi             | High         |
| O7 | **Shop Floor Dashboard** (real-time)                      | Monitor progress semua MO aktif, OEE metrics           | Medium       |
| O8 | **Scrap & Rework Module**                                 | Auto-route QC failures ke scrap stock atau rework MO   | Medium       |

### ⚠️ THREATS (Ancaman)


| #  | Ancaman                                              | Mitigasi                                        |
| -- | ---------------------------------------------------- | ----------------------------------------------- |
| T1 | **Data integrity loss** dari W1 (no transaction)     | Prioritas #1 untuk diperbaiki sebelum go-live   |
| T2 | **Audit finding** dari W4 (no WIP)                   | Auditor keuangan akan menolak laporan tanpa WIP |
| T3 | **Customer complaint** dari W6 (QC bypass)           | Produk cacat bisa terkirim tanpa blocking       |
| T4 | **Stock discrepancy** dari W2 (reservation conflict) | Selisih stok akan terakumulasi seiring waktu    |

---

## Ringkasan Prioritas Perbaikan


| Prioritas | ID | Perbaikan                                                                        | Estimasi Impact                   |
| --------- | -- | -------------------------------------------------------------------------------- | --------------------------------- |
| 🔴**P0**  | W1 | Wrap`produce()` dalam `DB::transaction()`                                        | Eliminasi risiko kehilangan stok  |
| 🔴**P0**  | W2 | Manufacturing harus respect`availableQuantity()` + reserve material saat confirm | Eliminasi konflik SO vs MO        |
| 🔴**P0**  | W3 | Throw exception (bukan silent skip) jika CoA manufacturing tidak ada             | Eliminasi missing journal entries |
| 🟠**P1**  | W4 | Implementasi WIP account (1400) dengan 3-step journal flow                       | PSAK 14 compliance                |
| 🟠**P1**  | W5 | Journal variance saat MO done:`Dr/Cr Variance Account`                           | GL completeness                   |
| 🟠**P1**  | W6 | QC gate: auto-create QC inspection pada`produce()`, block FG jika QC belum pass  | Quality enforcement               |
| 🟠**P1**  | W7 | Pre-check material availability pada`confirm()`                                  | Better planning                   |
| 🟡**P2**  | W9 | Tambah`scrap_factor` di BomItem, adjust qty calculation                          | Material planning accuracy        |

---

*Laporan ini dihasilkan berdasarkan pembacaan mendalam terhadap 18 file source code ManERP. Setiap temuan dirujuk ke file dan nomor baris spesifik untuk verifikasi.*
