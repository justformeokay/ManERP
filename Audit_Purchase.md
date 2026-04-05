I'll perform a thorough audit of the entire Purchase module. Let me gather all relevant code first.

Deep audit Purchase module code

Read [](file:///Users/putramac/Desktop/ManERP/app/Http/Controllers/PurchaseOrderController.php)

Read [](file:///Users/putramac/Desktop/ManERP/app/Models/PurchaseOrder.php)

Read [](file:///Users/putramac/Desktop/ManERP/app/Models/PurchaseOrderItem.php)

Read [](file:///Users/putramac/Desktop/ManERP/app/Models/Supplier.php)

Read [](file:///Users/putramac/Desktop/ManERP/app/Models/Payment.php)

Read [](file:///Users/putramac/Desktop/ManERP/app/Http/Requests/PurchaseOrderRequest.php)

Read [](file:///Users/putramac/Desktop/ManERP/app/Services/StockValuationService.php#1-1), lines 1 to 500

Read [](file:///Users/putramac/Desktop/ManERP/app/Services/StockService.php)

Read [](file:///Users/putramac/Desktop/ManERP/routes/web.php#1-1), lines 1 to 500

Searched for files matching `**/migrations/*purchase*`, 2 matches

Read [](file:///Users/putramac/Desktop/ManERP/resources/views/purchasing)

Searched for regex `landed_cost|shipping_cost|freight`, no results

Read [](file:///Users/putramac/Desktop/ManERP/database/migrations/2026_03_26_000008_create_sales_and_purchase_orders_tables.php)

Read [](file:///Users/putramac/Desktop/ManERP/database/migrations/2026_04_02_000001_phase2_multilevel_bom_costing_purchase_request.php)

Read [](file:///Users/putramac/Desktop/ManERP/resources/views/purchasing/form.blade.php)

Read [](file:///Users/putramac/Desktop/ManERP/resources/views/purchasing/show.blade.php)

Read [](file:///Users/putramac/Desktop/ManERP/app/Http/Controllers/AccountsPayableController.php)

Read [](file:///Users/putramac/Desktop/ManERP/app/Http/Controllers/SupplierController.php)

Read [](file:///Users/putramac/Desktop/ManERP/app/Services/AccountsPayableService.php)

Read [](file:///Users/putramac/Desktop/ManERP/app/Models/SupplierBill.php)

Read [](file:///Users/putramac/Desktop/ManERP/app/Models/SupplierPayment.php)

Searched for regex `accounts_payable|hutang|payable.*aging|AP aging`, 20 results

Searched for regex `ppn_masukan|ppn masukan|PPN Masukan|tax_input|pajak masukan`, 20 results

Searched for files matching `**/tests/Feature/*Purchase*`, 1 match

Read [](file:///Users/putramac/Desktop/ManERP/app/Services/AccountingService.php#1-1), lines 1 to 500

Read [](file:///Users/putramac/Desktop/ManERP/app/Http/Controllers/TaxController.php)

Read [](file:///Users/putramac/Desktop/ManERP/tests/Feature/PurchaseRequestWorkflowTest.php)

Read [](file:///Users/putramac/Desktop/ManERP/app/Http/Controllers/PurchaseRequestController.php)

Read [](file:///Users/putramac/Desktop/ManERP/app/Models/PurchaseRequest.php)

Searched for files matching `**/migrations/*supplier_bill*`, 2 matches

Read [](file:///Users/putramac/Desktop/ManERP/app/Services/TaxService.php)

Read [](file:///Users/putramac/Desktop/ManERP/database/migrations/2026_03_31_100001_create_supplier_bills_table.php)

Read [](file:///Users/putramac/Desktop/ManERP/database/migrations/2026_03_31_100002_create_supplier_bill_items_table.php)

Read [](file:///Users/putramac/Desktop/ManERP/app/Services/FinanceService.php)

Read [](file:///Users/putramac/Desktop/ManERP/resources/views/purchasing/requests)

Read [](file:///Users/putramac/Desktop/ManERP/resources/views/ap)

Read [](file:///Users/putramac/Desktop/ManERP/app/Services/StockValuationService.php#440-440), lines 440 to 511

Read [](file:///Users/putramac/Desktop/ManERP/app/Notifications/PurchaseOrderReceivedNotification.php)

Read [](file:///Users/putramac/Desktop/ManERP/app/Models/SupplierBillItem.php)

Searched for files matching `**/migrations/*supplier_payment*`, 1 match

Searched for regex `GoodsReceipt|GRN|goods_receipt`, no results

Read [](file:///Users/putramac/Desktop/ManERP/database/migrations/2026_03_31_100003_create_supplier_payments_table.php)

Read [](file:///Users/putramac/Desktop/ManERP/resources/views/purchasing/index.blade.php#1-1), lines 1 to 100

Read [](file:///Users/putramac/Desktop/ManERP/app/Http/Controllers/ReportController.php#1-1), lines 1 to 300

Read [](file:///Users/putramac/Library/Application%20Support/Code/User/workspaceStorage/60def03e2acbb8cbd7744007f53e556f/GitHub.copilot-chat/chat-session-resources/adc17118-0dc9-49c6-9821-2df02e8c3367/toolu_012f88iimRxN59fVkfP6eXCi__vscode-1775315344909/content.txt)

Excellent research. Now let me deep-dive into the specific critical areas — the journal logic, the receive flow, and the AP bill posting:

Read [](file:///Users/putramac/Desktop/ManERP/app/Http/Controllers/PurchaseOrderController.php#1-1), lines 1 to 300

Read [](file:///Users/putramac/Desktop/ManERP/app/Http/Controllers/PurchaseOrderController.php#260-260), lines 260 to 342

Now let me read the critical service methods:

Read [](file:///Users/putramac/Desktop/ManERP/app/Services/StockValuationService.php#270-270), lines 270 to 340

Searched for regex `function journalPurchaseReceive|function journalPurchaseCancel|function recordPurchaseReturn` (`**/app/Services/StockValuationService.php`), 3 results

Read [](file:///Users/putramac/Desktop/ManERP/app/Services/StockValuationService.php#115-115), lines 115 to 250

Now let me read the AccountsPayableService fully:

Read [](file:///Users/putramac/Desktop/ManERP/app/Services/AccountsPayableService.php#1-1), lines 1 to 350

Compacted conversation

Read [](file:///Users/putramac/Desktop/ManERP/app/Services/AccountsPayableService.php#280-280), lines 280 to 350

Read [](file:///Users/putramac/Desktop/ManERP/app/Http/Controllers/PurchaseOrderController.php#1-1), lines 1 to 100

Read [](file:///Users/putramac/Desktop/ManERP/app/Http/Controllers/PurchaseOrderController.php#100-100), lines 100 to 342

Read [](file:///Users/putramac/Desktop/ManERP/app/Services/StockValuationService.php#115-115), lines 115 to 260

Now let me read the TaxService and models for complete evidence:

Read [](file:///Users/putramac/Desktop/ManERP/app/Services/TaxService.php#1-1), lines 1 to 200

Read [](file:///Users/putramac/Desktop/ManERP/app/Models/SupplierBill.php#1-1), lines 1 to 200

Read [](file:///Users/putramac/Desktop/ManERP/app/Models/PurchaseOrder.php#1-1), lines 1 to 120

Let me check the Supplier model and existing purchase tests:

Read [](file:///Users/putramac/Desktop/ManERP/app/Models/Supplier.php)

Read [](file:///Users/putramac/Desktop/ManERP/app/Models/PurchaseOrderItem.php)

Searched for files matching `**/tests/**/*urchas*`, 1 match

Now let me check the SupplierPayment model and the PO `received` state transition:

Read [](file:///Users/putramac/Desktop/ManERP/app/Models/SupplierPayment.php)

Searched for regex `received.*cancelled` (`**/PurchaseOrder.php`), 3 results

I now have all evidence. Let me compile the full audit report.

---

# 🔍 DEEP AUDIT: Modul Purchase — ManERP

## Audit Scope & Methodology

**Peran**: Senior Procurement Manager & Financial Auditor
**File diperiksa**: 12 file utama (controller, service, model, test)
**Fokus**: Purchase Lifecycle, Vendor Management, AP Accounting, Landed Cost, SWOT

---

## 1. PURCHASE LIFECYCLE & RECEIVING FLOW

### 1.1 State Machine

```
draft → confirmed → partial → received
  ↓         ↓          ↓
cancelled  cancelled  cancelled
```

Definisi di PurchaseOrder.php:


| From        | Allowed To                          |
| ----------- | ----------------------------------- |
| `draft`     | `confirmed`, `cancelled`            |
| `confirmed` | `partial`, `received`, `cancelled`  |
| `partial`   | `received`, `cancelled`             |
| `received`  | **∅ (terminal — no transitions)** |
| `cancelled` | **∅ (terminal)**                   |

**Temuan**: `received` adalah **dead-end state**. Tidak ada mekanisme Purchase Return setelah PO fully received. Barang yang sudah diterima 100% tidak bisa dikembalikan secara sistem.

---

### 1.2 Receiving Flow — `PurchaseOrderController::receive()`

PurchaseOrderController.php

Flow saat ini:

```
validate → per-item stockIN → WAC recordIncoming → bcadd totalReceiveValue
→ journalPurchaseReceive (Dr 1300 / Cr 2000) → increment received_quantity
→ transition to partial/received → notify admins
```

#### ⛔ P0-BUG: `receive()` NOT wrapped in `DB::transaction`

```php
// Line 168 — NO DB::transaction wrapper!
public function receive(Request $request, PurchaseOrder $order)
{
    // ...
    foreach ($request->input('receive') as $row) {
        $this->stockService->processMovement([...]); // ← stock IN
        $this->valuationService->recordIncoming(...);  // ← WAC layer
        $item->increment('received_quantity', $qty);   // ← qty update
    }
    $this->valuationService->journalPurchaseReceive(...); // ← journal
    $order->transitionToAndSave(...); // ← status
}
```

**Risiko**: Jika journal gagal (misal CoA hilang — lihat P0-BUG #2 di bawah), stock sudah masuk tapi **tidak ada jurnal akuntansi**. Inventory fisik naik, neraca tidak berubah. **Phantom inventory**.

Bandingkan: SalesOrderController sudah di-fix dengan `DB::transaction` di patch sebelumnya.

---

### 1.3 Cancel Flow — `PurchaseOrderController::cancel()`

PurchaseOrderController.php

#### ⛔ P0-BUG: `cancel()` NOT wrapped in `DB::transaction`

```php
// Line 245 — NO DB::transaction wrapper!
public function cancel(PurchaseOrder $order)
{
    foreach ($order->items as $item) {
        $this->stockService->processMovement([...]); // ← stock OUT
        $this->valuationService->recordPurchaseReturn(...); // ← WAC recalc
        $item->update(['received_quantity' => 0]);
    }
    $this->valuationService->journalPurchaseCancel(...); // ← journal
    $order->transitionToAndSave('cancelled');
}
```

**Risiko**: Jika item ke-3 dari 5 gagal, 2 item sudah di-reverse tapi 3 sisanya belum. `received_quantity` sebagian 0, sebagian tetap. **State korup**.

---

## 2. ACCOUNTING INTEGRITY

### 2.1 Purchase Journal Entries — Silent Failure

StockValuationService.php

#### ⛔ P0-BUG: `journalPurchaseReceive()` & `journalPurchaseCancel()` silently return on missing CoA

```php
public function journalPurchaseReceive(...): void
{
    $inventoryAccount = ChartOfAccount::where('code', '1300')->first();
    $payableAccount   = ChartOfAccount::where('code', '2000')->first();

    if (!$inventoryAccount || !$payableAccount || $totalValue <= 0) {
        return; // ← SILENT FAIL! No exception, no log
    }
    // ... create journal
}
```

**Bandingkan** dengan `journalSalesCogs()` yang sudah di-fix di Sales Integrity Patch:

```php
public function journalSalesCogs(...): void
{
    if (!$inventoryAccount || !$cogsAccount) {
        throw new \RuntimeException(
            'Required CoA accounts for Sales COGS not found...'
        );
    }
}
```

Purchase journal methods **belum di-patch** dengan pola yang sama. Receive bisa sukses tanpa jurnal = **neraca tidak balance**.

---

### 2.2 ⛔ P0-BUG: AP DOUBLE-COUNTING — Bill vs PO Receive Conflict

Ini adalah bug akuntansi **paling kritis** di modul Purchase.

**Alur saat ini:**


| Event       | Journal                          | Sumber                                            |
| ----------- | -------------------------------- | ------------------------------------------------- |
| PO Receive  | Dr Inventory(1300) / Cr AP(2000) | `StockValuationService::journalPurchaseReceive()` |
| Bill Posted | Dr Expense(5000) / Cr AP(2000)   | `AccountsPayableService::postBill()`              |

AccountsPayableService.php — `postBill()`:

```php
// Debit: Expense/Inventory for total amount
$entries[] = [
    'account_id' => $expenseAccount->id,  // ← 5000 EXPENSE, bukan 1300 INVENTORY!
    'debit'      => $bill->total,
    'credit'     => 0,
];
// Credit: Accounts Payable
$entries[] = [
    'account_id' => $apAccount->id,       // ← 2000 AP — CREDITED AGAIN!
    'debit'      => 0,
    'credit'     => $bill->total,
];
```

**Dampak (untuk PO senilai Rp 100.000.000):**


| Step       | Debit            | Credit      | AP Balance                            |
| ---------- | ---------------- | ----------- | ------------------------------------- |
| PO Receive | Inventory +100jt | AP +100jt   | **100jt**                             |
| Bill Post  | Expense +100jt   | AP +100jt   | **200jt** ← DOUBLE!                  |
| Payment    | AP -100jt        | Cash -100jt | **100jt** ← phantom liability tetap! |

**3 masalah sekaligus:**

1. AP di-credit **DUA KALI** — liability over-stated 2x
2. Bill post debit ke **Expense(5000)**, bukan Inventory(1300) — seharusnya tidak ada debit baru karena inventory sudah di-debit saat receive
3. Setelah payment, phantom AP liability Rp 100jt tetap mengambang di neraca

**Seharusnya**: Jika bill linked ke PO yang sudah received, `postBill()` hanya perlu mem-match accrual (atau skip journal karena sudah diakui saat receive).

---

### 2.3 ⛔ P0-BUG: No PPN Masukan (1140) Journal

TaxService.php — `applyPPNToBill()`:

```php
public function applyPPNToBill(SupplierBill $bill, ?float $rate = null): void
{
    $bill->update([
        'dpp'        => $dpp,
        'tax_rate'   => $rate,
        'tax_amount' => $ppn,
        'total'      => round($dpp + $ppn, 2),
    ]);
    // ← No journal to PPN Masukan (1140) account!
}
```

`postBill()` juga **tidak** memisahkan DPP dan PPN ke akun terpisah — seluruh `$bill->total` (termasuk PPN) masuk ke satu baris debit Expense(5000).

**Seharusnya saat bill posting (PKP supplier):**

```
Dr Inventory/Expense    : DPP amount
Dr PPN Masukan (1140)   : PPN amount
    Cr Accounts Payable : DPP + PPN
```

**Dampak**: PPN Masukan hanya muncul di SPT report (dari `getSptMasaPPN()` query) tapi **tidak pernah masuk di General Ledger**. Neraca tidak ada saldo akun 1140. Audit pajak akan menemukan inkonsistensi antara GL dan SPT.

---

## 3. VENDOR MANAGEMENT & PRICING

### 3.1 Supplier Model — Minimal

Supplier.php:


| Field                           | Ada? | Keterangan                               |
| ------------------------------- | ---- | ---------------------------------------- |
| `name`, `email`, `phone`        | ✅   | Basic contact                            |
| `npwp`, `is_pkp`, `tax_address` | ✅   | Tax compliance                           |
| `payment_terms`                 | ❌   | Hardcoded 30 days di`createBillFromPO()` |
| `credit_limit`                  | ❌   | No vendor credit limit                   |
| `lead_time`                     | ❌   | No delivery performance tracking         |
| `rating` / `score`              | ❌   | No vendor evaluation                     |
| `currency_id`                   | ❌   | No default currency per vendor           |
| `bank_account`                  | ❌   | Manual entry setiap payment              |

### 3.2 No Purchase Price Variance (PPV)

Jika harga di SupplierBill berbeda dari harga PO, **tidak ada mekanisme** untuk menangkap dan menjurnal selisihnya. `createBillFromPO()` AccountsPayableService.php meng-copy `$item->price` dari PO.

Jika user manual mengedit harga di bill, selisih dari PO **tidak di-track** dan tidak ada PPV journal.

### 3.3 No 3-Way Matching

Tidak ada GRN (Goods Receipt Note) model. Receiving dilakukan inline di controller. Tanpa GRN terpisah, **3-way matching** (PO qty/price vs GRN qty vs Invoice qty/price) **tidak mungkin**.

---

## 4. LANDED COST & TOTAL COST OF OWNERSHIP

### 4.1 Landed Cost — **Tidak Diimplementasi**

Tidak ada:

- Model `LandedCost` atau tabel terkait
- Alokasi freight/customs/insurance ke HPP per item
- Integrasi dengan `StockValuationService::recordIncoming()`

Semua pembelian menggunakan **unit_price dari PO** sebagai cost basis. Untuk barang impor, HPP akan **understated** karena tidak memasukkan:

- Bea Masuk
- Freight & Insurance
- Handling charges
- Customs clearance fee

### 4.2 `createBillFromPO()` — Qty Mismatch

AccountsPayableService.php:

```php
$items = $po->items->map(function ($item) {
    return [
        'quantity' => $item->quantity,  // ← PO ordered qty, NOT received qty!
        'price'    => $item->price,
    ];
});
```

Jika PO 100 unit tapi baru diterima 60 unit (partial), `createBillFromPO()` akan membuat bill untuk **100 unit**. Ini akan meng-overstate payable dan expense.

---

## 5. ADDITIONAL FINDINGS

### 5.1 `received` → No Cancel Allowed, But No Return Path

PurchaseOrder.php:

```php
'received' => [], // Terminal state — no transitions
```

Setelah PO fully received, tidak ada jalur untuk return. Tidak ada model `PurchaseReturn`, tidak ada `return()` method di controller. **Goods yang defective setelah full receipt tidak bisa di-handle**.

### 5.2 Hard-coded Due Date

```php
'due_date' => now()->addDays(30)->format('Y-m-d'), // Always 30 days
```

Tidak sourced dari supplier payment terms atau PO terms.

### 5.3 No Duplicate Payment Protection

`AccountsPayableService::recordPayment()` memang cek `$amount > $bill->outstanding`, tapi **tidak ada** `lockForUpdate()` di bill query. Race condition bisa menyebabkan double payment jika 2 request concurrent meng-pay bill yang sama.

### 5.4 Test Coverage — **ALMOST ZERO**

Hanya PurchaseRequestWorkflowTest.php yang ada — ini test untuk **Purchase Request**, bukan PO. **Tidak ada test untuk:**

- PO confirm
- PO receive (full & partial)
- PO cancel (with stock reversal)
- Journal creation (receive & cancel)
- WAC calculation on receive
- WAC recalculation on cancel
- Bill creation from PO
- Bill posting journal
- Bill payment journal
- AP aging report
- PPN Masukan calculation

---

## 6. SWOT ANALYSIS

### Strengths (S)

- ✅ Partial receiving support — bisa receive sebagian dan track remaining
- ✅ WAC valuation layer terintegrasi — setiap receive terhitung ke avg cost
- ✅ bcmath digunakan untuk perhitungan moneter — presisi desimal terjaga
- ✅ State machine pattern konsisten dengan modul lain
- ✅ AP aging report sudah tersedia dengan buckets (current, 1-30, 31-60, 61-90, 90+)
- ✅ SupplierBill model robust: has faktur_pajak, soft delete, status helpers

### Weaknesses (W)

- ❌ **receive() & cancel() tidak atomic** — no DB::transaction
- ❌ **AP double-counting** — PO receive dan bill post keduanya credit AP
- ❌ **Silent journal failure** — purchase journals return void tanpa exception
- ❌ **No PPN Masukan journal** — GL tidak match SPT
- ❌ **No GRN** — receiving inline di controller, 3-way matching impossible
- ❌ **No Purchase Return** — received state terminal tanpa return path
- ❌ **createBillFromPO uses ordered qty, not received qty**
- ❌ **Zero test coverage** untuk PO features

### Opportunities (O)

- 🔧 Landed Cost allocation → akurasi HPP naik signifikan
- 🔧 Vendor performance dashboard → lead time, on-time %, quality rate
- 🔧 Purchase Price Variance (PPV) tracking → visibility terhadap cost creep
- 🔧 Auto-reorder dari safety stock / reorder point
- 🔧 Approval workflow untuk PO high-value
- 🔧 GRN model → enable 3-way matching → tightened AP control

### Threats (T)

- ⚠️ **Neraca salah** — AP over-stated 2x jika ada PO receive + bill post flow
- ⚠️ **Audit pajak risk** — PPN Masukan ada di SPT tapi tidak di GL
- ⚠️ **Data corruption pada concurrent access** — non-atomic operations
- ⚠️ **HPP understated** untuk barang impor tanpa landed cost
- ⚠️ **Double payment** risk tanpa row locking

---

## 7. PRIORITIZED RECOMMENDATIONS


| Priority | Issue                                       | Fix                                                                        |
| -------- | ------------------------------------------- | -------------------------------------------------------------------------- |
| **P0**   | `receive()` & `cancel()` no DB::transaction | Wrap dalam`DB::transaction`                                                |
| **P0**   | `journalPurchaseReceive/Cancel` silent fail | Throw`RuntimeException` seperti `journalSalesCogs`                         |
| **P0**   | AP double-counting (bill vs PO receive)     | `postBill()` harus detect linked PO — jika sudah accrued, skip atau match |
| **P0**   | No PPN Masukan journal                      | Split bill posting: Dr Inventory + Dr PPN Masukan(1140) / Cr AP            |
| **P1**   | `createBillFromPO()` uses ordered qty       | Use`received_quantity` instead of `quantity`                               |
| **P1**   | No payment row locking                      | Add`lockForUpdate()` di `recordPayment()` bill query                       |
| **P1**   | No Purchase Return                          | Add model + controller + stock reversal from received state                |
| **P1**   | Zero test coverage                          | Comprehensive PurchaseOrderTest suite                                      |
| **P2**   | No Landed Cost                              | LandedCost model + allocation engine                                       |
| **P2**   | No vendor performance                       | Add tracking fields to Supplier model                                      |
| **P2**   | Hard-coded payment terms                    | Source from Supplier.payment_terms                                         |
| **P2**   | No PPV tracking                             | Variance detection + journal on bill vs PO price diff                      |

---

**Ringkasan**: Modul Purchase memiliki **4 bug P0** yang langsung memengaruhi integritas data keuangan — AP double-counting, silent journal failure, non-atomic operations, dan missing PPN Masukan journal. Tanpa fix, neraca perusahaan **tidak bisa diandalkan** untuk audit keuangan.

Apakah Anda ingin saya lanjutkan ke implementasi "**The Purchase Integrity Patch**"?
