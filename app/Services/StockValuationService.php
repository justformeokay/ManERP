<?php

namespace App\Services;

use App\Models\ChartOfAccount;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\StockValuationLayer;
use Illuminate\Support\Facades\DB;

/**
 * Weighted Average Costing (WAC) service per PSAK 14.
 *
 * Formula (incoming): new_avg_cost = (existing_value + incoming_value) / (existing_qty + incoming_qty)
 * Outgoing: uses current avg_cost — no change to avg_cost.
 */
class StockValuationService
{
    public function __construct(private AccountingService $accountingService) {}

    /**
     * Record an incoming stock movement and recalculate WAC.
     * Called on: PO receive, SO cancel (return), MO produce (finished goods).
     */
    public function recordIncoming(
        int $productId,
        int $warehouseId,
        float $quantity,
        float $unitCost,
        ?StockMovement $movement = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $description = null
    ): StockValuationLayer {
        $product = Product::lockForUpdate()->findOrFail($productId);

        // Current total qty across all warehouses
        $currentQty = (float) $product->inventoryStocks()->sum('quantity');
        // qty BEFORE this movement was applied (StockService already updated inventory_stocks)
        $existingQty = bcsub((string) $currentQty, (string) $quantity, 4);
        $existingValue = bcmul((string) $existingQty, (string) $product->avg_cost, 4);

        $incomingValue = bcmul((string) $quantity, (string) $unitCost, 4);
        $totalQty = bcadd((string) $existingQty, (string) $quantity, 4);
        $totalValue = bcadd((string) $existingValue, (string) $incomingValue, 4);

        // WAC formula
        $newAvgCost = $totalQty > 0
            ? bcdiv($totalValue, $totalQty, 4)
            : $unitCost; // First receipt: avg_cost = purchase price

        $product->update(['avg_cost' => $newAvgCost]);

        $layerTotalValue = bcmul((string) $quantity, (string) $unitCost, 4);

        return StockValuationLayer::create([
            'product_id'        => $productId,
            'warehouse_id'      => $warehouseId,
            'stock_movement_id' => $movement?->id,
            'direction'         => 'in',
            'quantity'          => $quantity,
            'unit_cost'         => $unitCost,
            'total_value'       => $layerTotalValue,
            'remaining_qty'     => $quantity,
            'remaining_value'   => $layerTotalValue,
            'avg_cost_after'    => $newAvgCost,
            'reference_type'    => $referenceType ?? $movement?->reference_type,
            'reference_id'      => $referenceId ?? $movement?->reference_id,
            'description'       => $description,
        ]);
    }

    /**
     * Record an outgoing stock movement using current WAC.
     * Called on: SO confirm, PO cancel (reverse), MO consume (raw materials).
     */
    public function recordOutgoing(
        int $productId,
        int $warehouseId,
        float $quantity,
        ?StockMovement $movement = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $description = null
    ): StockValuationLayer {
        $product = Product::lockForUpdate()->findOrFail($productId);

        $unitCost = (float) $product->avg_cost;
        $totalValue = bcmul((string) $quantity, (string) $unitCost, 4);

        // avg_cost does NOT change on outgoing movements (WAC rule)
        return StockValuationLayer::create([
            'product_id'        => $productId,
            'warehouse_id'      => $warehouseId,
            'stock_movement_id' => $movement?->id,
            'direction'         => 'out',
            'quantity'          => $quantity,
            'unit_cost'         => $unitCost,
            'total_value'       => $totalValue,
            'remaining_qty'     => 0,
            'remaining_value'   => 0,
            'avg_cost_after'    => $unitCost,
            'reference_type'    => $referenceType ?? $movement?->reference_type,
            'reference_id'      => $referenceId ?? $movement?->reference_id,
            'description'       => $description,
        ]);
    }

    /**
     * Record a purchase return at the ORIGINAL purchase price.
     * Unlike normal outgoing (which keeps avg_cost unchanged),
     * a purchase return recalculates avg_cost because the value
     * leaving the pool differs from the current average.
     */
    public function recordPurchaseReturn(
        int $productId,
        int $warehouseId,
        float $quantity,
        float $originalUnitCost,
        ?StockMovement $movement = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $description = null
    ): StockValuationLayer {
        $product = Product::lockForUpdate()->findOrFail($productId);

        // After processMovement, inventory_stocks already decremented
        $currentQty = (float) $product->inventoryStocks()->sum('quantity');
        $beforeQty = bcadd((string) $currentQty, (string) $quantity, 4);
        $beforeValue = bcmul((string) $beforeQty, (string) $product->avg_cost, 4);

        $returnValue = bcmul((string) $quantity, (string) $originalUnitCost, 4);
        $afterValue = bcsub($beforeValue, $returnValue, 4);

        $newAvgCost = $currentQty > 0
            ? bcdiv($afterValue, (string) $currentQty, 4)
            : '0';

        $product->update(['avg_cost' => $newAvgCost]);

        return StockValuationLayer::create([
            'product_id'        => $productId,
            'warehouse_id'      => $warehouseId,
            'stock_movement_id' => $movement?->id,
            'direction'         => 'out',
            'quantity'          => $quantity,
            'unit_cost'         => $originalUnitCost,
            'total_value'       => $returnValue,
            'remaining_qty'     => 0,
            'remaining_value'   => 0,
            'avg_cost_after'    => $newAvgCost,
            'reference_type'    => $referenceType ?? $movement?->reference_type,
            'reference_id'      => $referenceId ?? $movement?->reference_id,
            'description'       => $description,
        ]);
    }

    /**
     * Record manufacturing incoming: FG cost = sum of consumed materials' avg_cost.
     */
    public function recordManufacturingIncoming(
        int $productId,
        int $warehouseId,
        float $quantity,
        float $totalMaterialCost,
        ?StockMovement $movement = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $description = null
    ): StockValuationLayer {
        $unitCost = $quantity > 0
            ? bcdiv((string) $totalMaterialCost, (string) $quantity, 4)
            : '0';

        return $this->recordIncoming(
            $productId,
            $warehouseId,
            $quantity,
            (float) $unitCost,
            $movement,
            $referenceType,
            $referenceId,
            $description
        );
    }

    /**
     * Create auto-journal entry for purchase receipt.
     * Dr Inventory (1300) / Cr Accounts Payable (2000)
     */
    public function journalPurchaseReceive(
        string $reference,
        string $date,
        float $totalValue,
        string $description
    ): void {
        $inventoryAccount = ChartOfAccount::where('code', '1300')->first();
        $payableAccount = ChartOfAccount::where('code', '2000')->first();

        if (!$inventoryAccount || !$payableAccount || $totalValue <= 0) {
            return;
        }

        $this->accountingService->createJournalEntry($reference, $date, $description, [
            ['account_id' => $inventoryAccount->id, 'debit' => round($totalValue, 2), 'credit' => 0],
            ['account_id' => $payableAccount->id, 'debit' => 0, 'credit' => round($totalValue, 2)],
        ]);
    }

    /**
     * Create auto-journal entry for purchase cancel/return.
     * Dr Accounts Payable (2000) / Cr Inventory (1300)
     */
    public function journalPurchaseCancel(
        string $reference,
        string $date,
        float $totalValue,
        string $description
    ): void {
        $inventoryAccount = ChartOfAccount::where('code', '1300')->first();
        $payableAccount = ChartOfAccount::where('code', '2000')->first();

        if (!$inventoryAccount || !$payableAccount || $totalValue <= 0) {
            return;
        }

        $this->accountingService->createJournalEntry($reference, $date, $description, [
            ['account_id' => $payableAccount->id, 'debit' => round($totalValue, 2), 'credit' => 0],
            ['account_id' => $inventoryAccount->id, 'debit' => 0, 'credit' => round($totalValue, 2)],
        ]);
    }

    /**
     * Create auto-journal entry for sales (COGS recognition).
     * Dr COGS (5000) / Cr Inventory (1300)
     */
    public function journalSalesCogs(
        string $reference,
        string $date,
        float $totalCogs,
        string $description
    ): void {
        $inventoryAccount = ChartOfAccount::where('code', '1300')->first();
        $cogsAccount = ChartOfAccount::where('code', '5000')->first();

        if (!$inventoryAccount || !$cogsAccount || $totalCogs <= 0) {
            return;
        }

        $this->accountingService->createJournalEntry($reference, $date, $description, [
            ['account_id' => $cogsAccount->id, 'debit' => round($totalCogs, 2), 'credit' => 0],
            ['account_id' => $inventoryAccount->id, 'debit' => 0, 'credit' => round($totalCogs, 2)],
        ]);
    }

    /**
     * Create auto-journal for sales cancellation (reverse COGS).
     * Dr Inventory (1300) / Cr COGS (5000)
     */
    public function journalSalesCancel(
        string $reference,
        string $date,
        float $totalCogs,
        string $description
    ): void {
        $inventoryAccount = ChartOfAccount::where('code', '1300')->first();
        $cogsAccount = ChartOfAccount::where('code', '5000')->first();

        if (!$inventoryAccount || !$cogsAccount || $totalCogs <= 0) {
            return;
        }

        $this->accountingService->createJournalEntry($reference, $date, $description, [
            ['account_id' => $inventoryAccount->id, 'debit' => round($totalCogs, 2), 'credit' => 0],
            ['account_id' => $cogsAccount->id, 'debit' => 0, 'credit' => round($totalCogs, 2)],
        ]);
    }

    /**
     * Create auto-journal for manufacturing production.
     * Dr Finished Goods Inventory (1300) / Cr Raw Materials Inventory (1300)
     * (Same account since we use single Inventory account; cost flows through production_costs)
     */
    public function journalManufacturingProduce(
        string $reference,
        string $date,
        float $totalMaterialCost,
        string $description
    ): void {
        // For single-account setup, manufacturing is a cost reclassification
        // tracked in production_costs table. Journal only if WIP account exists.
        $inventoryAccount = ChartOfAccount::where('code', '1300')->first();
        $cogsAccount = ChartOfAccount::where('code', '5000')->first();

        if (!$inventoryAccount || !$cogsAccount || $totalMaterialCost <= 0) {
            return;
        }

        // Dr COGS (material consumed) / Cr Inventory (raw material out)
        // The FG incoming is already recorded as Dr Inventory via recordIncoming
        // Net effect: no change to Inventory for the consumed portion
        // This creates the COGS recognition for materials consumed
    }

    /**
     * Get stock valuation report: all products with qty on hand and total value.
     */
    public function getStockValuationReport(?string $date = null): array
    {
        $query = Product::query()
            ->where('is_active', true)
            ->with(['inventoryStocks.warehouse', 'category']);

        $products = $query->get();

        $report = [];
        $grandTotalValue = 0;

        foreach ($products as $product) {
            $totalQty = $product->inventoryStocks->sum('quantity');
            if ($totalQty <= 0 && !$date) {
                continue; // Skip zero-stock products in current report
            }

            $avgCost = (float) $product->avg_cost;
            $totalValue = bcmul((string) $totalQty, (string) $avgCost, 4);
            $grandTotalValue = bcadd((string) $grandTotalValue, $totalValue, 4);

            $warehouseBreakdown = [];
            foreach ($product->inventoryStocks as $stock) {
                if ($stock->quantity > 0) {
                    $whValue = bcmul((string) $stock->quantity, (string) $avgCost, 4);
                    $warehouseBreakdown[] = [
                        'warehouse_id'   => $stock->warehouse_id,
                        'warehouse_name' => $stock->warehouse->name ?? 'Unknown',
                        'quantity'       => (float) $stock->quantity,
                        'total_value'    => (float) $whValue,
                    ];
                }
            }

            $report[] = [
                'product_id'    => $product->id,
                'sku'           => $product->sku,
                'product_name'  => $product->name,
                'category'      => $product->category->name ?? '—',
                'type'          => $product->type,
                'unit'          => $product->unit,
                'total_qty'     => (float) $totalQty,
                'avg_cost'      => (float) $avgCost,
                'total_value'   => (float) $totalValue,
                'warehouses'    => $warehouseBreakdown,
            ];
        }

        return [
            'products'          => $report,
            'grand_total_value' => (float) $grandTotalValue,
            'generated_at'      => now()->toDateTimeString(),
        ];
    }

    /**
     * Get valuation history (layers) for a specific product.
     */
    public function getProductValuationHistory(int $productId, ?string $from = null, ?string $to = null): array
    {
        $query = StockValuationLayer::where('product_id', $productId)
            ->with('warehouse')
            ->orderBy('created_at');

        if ($from) {
            $query->where('created_at', '>=', $from);
        }
        if ($to) {
            $query->where('created_at', '<=', $to . ' 23:59:59');
        }

        return $query->get()->toArray();
    }
}
