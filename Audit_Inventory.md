DOKUMEN AUDIT TEKNIS — MODUL INVENTORY ManERP

**Auditor:** ERP Solutions Architect & Senior Auditor Sistem Informasi
**Scope:** Inventory Module (Stock, Valuation, Manufacturing, Costing)
**Code Base:** Laravel 12 — 11 Models, 8 Controllers, 3 Services

---

## SECTION 1: WORKFLOW DEEP-DIVE

### 1A. Inbound Flow (Purchase Receive)

**Path:** `PurchaseOrderController::receive()` → `StockService::processMovement(type:'in')` → `StockValuationService::recordIncoming()` → `journalPurchaseReceive()`


| Step | Component             | Action                                                                                                                                               |
| ---- | --------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1    | Controller            | Validates each line item`received_quantity`, transitions PO status to `partial` or `received`                                                        |
| 2    | StockService          | `DB::transaction` + `lockForUpdate()` on `InventoryStock`; creates `StockMovement` with `balance_after`                                              |
| 3    | StockValuationService | WAC recalc:`new_avg = (old_qty × old_avg + new_qty × unit_cost) / (old_qty + new_qty)` via `bcmath`; creates `StockValuationLayer(direction:'in')` |
| 4    | Journal               | `Dr 1300 (Inventory) / Cr 2000 (AP)` — balanced double-entry via AccountingService                                                                  |

**Verdict:** Solid. Pessimistic locking prevents race conditions. WAC math is correct per PSAK 14. Partial receiving is supported.

### 1B. Outbound Flow (Sales Confirm)

**Path:** `SalesOrderController::confirm()` → `StockService::processMovement(type:'out')` → `StockValuationService::recordOutgoing()` → `journalSalesCogs()`


| Step | Component             | Action                                                                                                                            |
| ---- | --------------------- | --------------------------------------------------------------------------------------------------------------------------------- |
| 1    | Controller            | Pre-validates ALL stock availability before deducting any (atomic check pattern)                                                  |
| 2    | StockService          | Deducts quantity; throws`InvalidArgumentException` if `$newQuantity < 0` (negative stock prevented)                               |
| 3    | StockValuationService | Creates`StockValuationLayer(direction:'out')` using current `avg_cost` (no WAC change on outgoing — correct per WAC methodology) |
| 4    | Journal               | `Dr 5000 (COGS) / Cr 1300 (Inventory)`                                                                                            |

**Verdict:** Correct. All-or-nothing stock check before deduction is best practice.

### 1C. Internal Movement

**1C-i. Stock Transfer** (`StockTransferController::execute()` → `StockService::executeTransfer()`)

- Atomically: OUT from source warehouse + IN at destination warehouse
- Both legs use same `unit_cost` (product's `avg_cost`) — no valuation change
- Cancel via `reverseTransfer()` reverses both legs
- Uses `HasStateMachine` trait: `pending → completed → cancelled`

**1C-ii. Manufacturing Production** (`ManufacturingOrderController::produce()`)

- Pre-validates ALL raw material quantities before consuming any
- Consumes each BOM material: `processMovement(type:'out')` + `recordOutgoing()`
- Produces finished good: `processMovement(type:'in')` + `recordManufacturingIncoming(unit_cost = totalMaterialCost/qty)`
- State machine: `draft → confirmed → in_progress → done`

**Verdict:** Transfer logic is clean. Manufacturing has a critical journal gap (see Section 2).

### 1D. Stock Adjustment

**Path:** `StockMovementController::store()` → `StockService::processMovement(type:'adjustment')`

**Key behavior**: Adjustment type sets `quantity` **ABSOLUTELY** (full replacement), NOT as a delta:

```php
'adjustment' => $data['quantity']  // replaces, not adds
```

This means a stock opname of 50 units sets the stock to 50 regardless of current balance. A `StockMovement` is created with the new balance, providing a full audit trail.

**Verdict:** Functional but potentially confusing. No WAC valuation layer is created for adjustments — the avg_cost remains unchanged. This is acceptable for quantity-only corrections but problematic for value adjustments.

---

## SECTION 2: COSTING LOGIC AUDIT (WAC per PSAK 14)

### 2A. WAC Formula — `StockValuationService::recordIncoming()`

```
new_avg = (existing_qty × existing_avg + incoming_qty × unit_cost) / (existing_qty + incoming_qty)
```

Implemented with `bcmath` at scale 4 (`bcadd`, `bcmul`, `bcdiv`), stored in `decimal(15,4)`. **Mathematically correct.**

### 2B. Purchase Return — `recordPurchaseReturn()`

```
return_value = returned_qty × original_unit_cost
new_value = current_value - return_value  
new_avg = new_value / current_qty
```

**Correct.** Uses the original PO unit cost (not current avg) for return valuation.

### 2C. Sales Return / Cancellation — `SalesOrderController::cancel()`

```php
$this->valuationService->recordIncoming(
    $product, $item->quantity, $product->avg_cost, ...
);
```

**FINDING [MEDIUM RISK]:** Sales cancellation records incoming stock at `$product->avg_cost` (current average), NOT at the original sale's unit cost. If the WAC has shifted since the original sale, the return creates a circular reference:

- Sale at old avg → WAC changes → Cancel at new avg → WAC shifts again

This can cause **cumulative WAC drift** over time, especially with high-volume returns. Per PSAK 14, returns should restore at the original transaction cost.

### 2D. Negative Stock Prevention

```php
if ($newQuantity < 0) {
    throw new InvalidArgumentException("Insufficient stock ...");
}
```

**Verdict:** Strictly enforced at the StockService layer. No bypass exists.

### 2E. Rounding Precision Matrix


| Field                                   | Precision     | Source                |
| --------------------------------------- | ------------- | --------------------- |
| `products.avg_cost`                     | decimal(15,4) | Migration             |
| `stock_valuation_layers.unit_cost`      | decimal(15,4) | Migration             |
| `stock_valuation_layers.avg_cost_after` | decimal(15,4) | Migration             |
| `stock_movements.unit_cost`             | decimal(15,4) | Migration             |
| `inventory_stocks.quantity`             | decimal(12,2) | Migration             |
| `bom_items.quantity`                    | decimal(12,4) | Migration             |
| bcmath scale                            | 4 digits      | StockValuationService |

**Verdict:** Consistent 4-decimal precision for costs, 2-decimal for quantities. No truncation risk.

### 2F. Manufacturing Journal — CRITICAL FINDING

**`journalManufacturingProduce()` is a NO-OP:**

In StockValuationService.php:

```php
public function journalManufacturingProduce(...): void {
    $inventoryAccount = ChartOfAccount::where('code', '1300')->first();
    $cogsAccount = ChartOfAccount::where('code', '5000')->first();
    if (!$inventoryAccount || !$cogsAccount || $totalMaterialCost <= 0) {
        return;
    }
    // Comments about net effect... then NOTHING. No createJournalEntry() call.
}
```

**FINDING [HIGH RISK]:** When manufacturing consumes raw materials and produces finished goods:

- `recordOutgoing()` is called for each raw material (reduces Inventory via valuation layer)
- `recordIncoming()` is called for finished good (increases Inventory via valuation layer)
- **But no journal entry is created.** The GL never sees the material consumption or FG production.

**Impact:** The Inventory subledger (valuation layers) and the GL Account 1300 will **drift apart** with every production run. The COGS will be understated because material consumption is never journaled until the finished good is sold.

**Recommended fix:** Create a WIP (Work In Progress) account or, at minimum, journal:

```
Dr 1300-FG (Inventory – Finished Goods)
Cr 1300-RM (Inventory – Raw Materials)
```

for the `totalMaterialCost` amount.

---

## SECTION 3: DATA STRUCTURE & INTEGRITY REVIEW

### 3A. Table Relationships & Redundancy Check


| Relationship                   | Implementation                                        | Assessment                   |
| ------------------------------ | ----------------------------------------------------- | ---------------------------- |
| Product ↔ InventoryStock      | HasMany with unique(product_id, warehouse_id)         | Correct, prevents duplicates |
| Product ↔ StockMovement       | HasMany, polymorphic reference                        | Solid audit trail            |
| Product ↔ StockValuationLayer | HasMany                                               | Clean WAC tracking           |
| BOM ↔ BomItem                 | HasMany with sub_bom_id for sub-assemblies            | Multi-level supported        |
| BOM circular refs              | `getFlattenedMaterials(visited[])` tracks visited IDs | Protected                    |
| MO ↔ ProductionCost           | HasOne via`latestProductionCost()`                    | Clean                        |

**Potential redundancy:** `Product.avg_cost` is stored directly on the product AND also tracked in `StockValuationLayer.avg_cost_after`. These could diverge if one is updated without the other.

### 3B. HMAC / Tamper Protection

**FINDING [MEDIUM RISK]:** HMAC protection exists only in the `AuditLogService` (audit logs). The core stock tables — `inventory_stocks`, `stock_movements`, `stock_valuation_layers` — have **no integrity hash**.

A direct database edit to `stock_movements.quantity` or `inventory_stocks.quantity` would go undetected. For an ERP handling financial data, this is a compliance gap.

### 3C. Data Locking / Closing Period

**FINDING [HIGH RISK]:** The `EnsureOpenFiscalPeriod` middleware (`fiscal-lock`) is applied to:

- Invoice routes ✅
- Payment routes ✅
- AP Bill routes ✅
- Journal Entry routes ✅
- Bank Transaction routes ✅

**But NOT applied to:**

- `inventory.movements.store` ❌
- `inventory.transfers.execute` ❌
- `inventory.transfers.cancel` ❌
- `manufacturing.orders.produce` ❌
- `sales.*.confirm` ❌
- `sales.*.cancel` ❌
- `purchase-orders.*.receive` ❌
- `purchase-orders.*.cancel` ❌

**Impact:** A user can create stock movements, receive POs, confirm SOs, and run production in a **closed fiscal period**. The auto-generated journal entries from these operations would bypass the fiscal lock since they're created server-side (not via the journal routes).

### 3D. `reserved_quantity` — Dead Code

The `reserved_quantity` column exists in:

- Migration (2026_03_26_000006_create_warehouse_inventory_tables.php)
- Model (`InventoryStock.fillable`)
- Views (displayed in stock list)
- `availableQuantity()` helper uses it

But it is **NEVER incremented or decremented** anywhere in the codebase. ARCHITECTURE.md describes the intended behavior (reserve on SO confirm, release on delivery) but the implementation is missing.

**Impact:** `availableQuantity()` always equals `quantity` since `reserved_quantity` is always 0. This means **overselling** is possible: two concurrent SO confirmations for overlapping stock can both pass the availability check before either deducts.

---

## SECTION 4: SWOT ANALYSIS

### Strengths

1. **WAC precision with bcmath** — 4-decimal scale, no floating-point drift. Exceeds typical ERP implementations.
2. **Pessimistic locking** — `lockForUpdate()` on InventoryStock prevents race conditions during concurrent writes.
3. **Multi-level BOM** — Recursive `getFlattenedMaterials()` with circular reference protection and versioning.
4. **Atomic stock checks** — Both SO confirm and MO produce validate ALL lines before mutating any (prevent partial failures).
5. **Auto-journaling** — PO receive, PO cancel, SO confirm, SO cancel automatically create balanced double-entry journals.
6. **Comprehensive audit trail** — Every stock mutation creates a `StockMovement` with `balance_after` and polymorphic references.
7. **State machine** — `HasStateMachine` trait enforces valid status transitions on PO, SO, MO, and Transfer.
8. **Low stock notifications** — Automatic admin alerts deduped per hour per product/warehouse.
9. **BOM costing engine** — `CostingService` with variance analysis (actual vs standard cost with % deviation).

### Weaknesses

1. **Manufacturing journal NO-OP** — GL never records material consumption during production. Subledger-GL drift guaranteed.
2. **No fiscal-lock on inventory routes** — Closed-period protection completely bypassed for stock operations.
3. **`reserved_quantity` unimplemented** — Overselling risk in concurrent operations.
4. **Sales return WAC distortion** — Cancel records incoming at current avg, not original cost.
5. **Adjustment has no valuation layer** — Stock opname changes quantity but creates no `StockValuationLayer`, causing subledger-inventory mismatch.

### Opportunities

1. **Batch/Serial tracking** — Schema is ready for extension (polymorphic `reference_type` on StockMovement).
2. **Reorder point automation** — `min_stock` and `LowStockNotification` exist; extending to auto-generate Purchase Requests is straightforward.
3. **WIP accounting** — A WIP account (1400) would unlock proper manufacturing cost flow through GL.
4. **Inventory aging** — `StockValuationLayer.created_at` already enables FIFO/aging analysis with minimal new code.

### Threats (Logical Loopholes Between Inventory & GL)

1. **Subledger-GL divergence** — Manufacturing production changes Inventory valuation layers without GL journals. Over time, Account 1300 balance ≠ Σ(valuation layers).
2. **Closed-period bypass** — Stock operations in closed periods will create orphaned journal entries that violate the GL closure integrity.
3. **Concurrent overselling** — Without `reserved_quantity` enforcement, two staff can confirm SOs for the same stock simultaneously.
4. **Audit trail incompleteness** — Direct DB modifications to `inventory_stocks` are undetectable without HMAC.

---

## SECTION 5: ROADMAP TO EXCELLENCE

### 5A. Batch/Serial Number Tracking

**Current state:** Not implemented. No batch or serial columns exist.

**Recommended implementation:**

1. Create `product_lots` table: `id, product_id, lot_number, serial_number, expiry_date, manufactured_date, status`
2. Add `product_lot_id` (nullable FK) to `stock_movements` and `stock_valuation_layers`
3. Modify `StockService::processMovement()` to accept optional `lot_id`
4. For serialized items (`product.tracking_type = 'serial'`): enforce unique serial per movement
5. For batch items (`product.tracking_type = 'lot'`): allow quantity-based tracking with FEFO (First Expiry First Out) picking suggestion

**Effort:** Medium — schema change + service layer modifications.

### 5B. Reorder Point Automation

**Current state:** `Product.min_stock` exists. `StockService::checkLowStock()` sends `LowStockNotification` but takes no further action.

**Recommended implementation:**

1. Add to `Product`: `reorder_point (decimal)`, `reorder_quantity (decimal)`, `preferred_supplier_id (FK)`
2. Create `App\Jobs\AutoReorderJob` that checks stock levels daily
3. When `quantity <= reorder_point`: auto-generate a `PurchaseRequest` with `reorder_quantity` for `preferred_supplier_id`
4. Status: `auto_generated` (new PR status) → requires manual approval before converting to PO

**Effort:** Low — the PR module and notification infrastructure already exist.

### 5C. Backdated / Historical Valuation Report

**Current state:** `StockValuationService::getStockValuationReport()` returns current snapshot only. `getProductValuationHistory()` shows per-product layer history with date filter but doesn't reconstruct point-in-time portfolio valuation.

**Recommended implementation:**

1. Add `$asOfDate` parameter to `getStockValuationReport()`
2. For each product: sum `remaining_qty` from `StockValuationLayer` where `created_at <= $asOfDate`
3. Use `avg_cost_after` from the last layer before the cutoff date
4. Result: full inventory valuation as of any historical date

**Effort:** Low — all data already exists in `stock_valuation_layers`. Pure query logic change.

---

## PRIORITY REMEDIATION MATRIX


| # | Finding                                            | Risk        | Effort | Priority                          |
| - | -------------------------------------------------- | ----------- | ------ | --------------------------------- |
| 1 | `journalManufacturingProduce()` NO-OP              | HIGH        | Low    | **P0 — Fix immediately**         |
| 2 | No`fiscal-lock` on inventory/sales/purchase routes | HIGH        | Low    | **P0 — Fix immediately**         |
| 3 | `reserved_quantity` never updated                  | MEDIUM      | Medium | **P1 — Fix before production**   |
| 4 | Sales cancel uses current avg_cost                 | MEDIUM      | Low    | **P1 — Fix before production**   |
| 5 | No valuation layer for adjustments                 | MEDIUM      | Low    | **P1 — Fix before production**   |
| 6 | No HMAC on stock tables                            | MEDIUM      | Medium | **P2 — Plan for next iteration** |
| 7 | Batch/Serial tracking                              | Enhancement | Medium | **P2 — Roadmap item**            |
| 8 | Reorder automation                                 | Enhancement | Low    | **P3 — Nice to have**            |
| 9 | Historical valuation report                        | Enhancement | Low    | **P3 — Nice to have**            |

---

**Bottom Line:** The inventory module has a solid foundation — WAC math is correct, locking is proper, the BOM engine is well-designed, and the audit trail is comprehensive. However, the two P0 findings (manufacturing journal gap and fiscal-lock bypass) must be fixed before going live, as they will cause the GL to diverge from the inventory subledger in ways that are expensive to reconcile retroactively.
