<?php

namespace Tests\Feature;

use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\StockValuationLayer;
use App\Models\Warehouse;
use App\Services\StockService;
use App\Services\StockValuationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * UAT: Weighted Average Costing (WAC) — 5 Critical Scenarios per PSAK 14.
 *
 * Tests validate:
 *  - products.avg_cost is correctly recalculated
 *  - journal_entries / journal_items have correct debit & credit
 *  - stock_valuation_layers records are accurate
 */
class WeightedAverageCostingTest extends TestCase
{
    use RefreshDatabase;

    private StockService $stockService;
    private StockValuationService $valuationService;
    private Warehouse $warehouse;
    private ChartOfAccount $inventoryAccount;
    private ChartOfAccount $apAccount;
    private ChartOfAccount $cogsAccount;

    // —————————————————————————————————————————————————————————————————————
    // Setup
    // —————————————————————————————————————————————————————————————————————

    protected function setUp(): void
    {
        parent::setUp();

        $this->stockService = app(StockService::class);
        $this->valuationService = app(StockValuationService::class);

        $this->inventoryAccount = ChartOfAccount::create([
            'code' => '1300', 'name' => 'Inventory', 'type' => 'asset', 'is_active' => true,
        ]);
        $this->apAccount = ChartOfAccount::create([
            'code' => '2000', 'name' => 'Accounts Payable', 'type' => 'liability', 'is_active' => true,
        ]);
        $this->cogsAccount = ChartOfAccount::create([
            'code' => '5000', 'name' => 'Cost of Goods Sold', 'type' => 'expense', 'is_active' => true,
        ]);

        $this->warehouse = Warehouse::create([
            'code' => 'WH-01', 'name' => 'Main Warehouse', 'is_active' => true,
        ]);
    }

    // —————————————————————————————————————————————————————————————————————
    // Helpers
    // —————————————————————————————————————————————————————————————————————

    private function createProduct(string $sku, string $name, string $type = 'raw_material'): Product
    {
        return Product::create([
            'sku'        => $sku,
            'name'       => $name,
            'type'       => $type,
            'cost_price' => 0,
            'avg_cost'   => 0,
            'sell_price' => 0,
            'is_active'  => true,
        ]);
    }

    /**
     * Simulate purchase receipt → stock IN + WAC recalc + auto-journal.
     */
    private function purchaseStock(Product $product, float $qty, float $unitPrice, string $ref): void
    {
        $movement = $this->stockService->processMovement([
            'product_id'     => $product->id,
            'warehouse_id'   => $this->warehouse->id,
            'type'           => 'in',
            'quantity'       => $qty,
            'unit_cost'      => $unitPrice,
            'reference_type' => 'purchase_order',
            'reference_id'   => 1,
        ]);

        $this->valuationService->recordIncoming(
            $product->id, $this->warehouse->id, $qty, $unitPrice,
            $movement, 'purchase_order', 1, "Purchase {$ref}"
        );

        $this->valuationService->journalPurchaseReceive(
            $ref, now()->toDateString(),
            round($qty * $unitPrice, 2),
            "Goods received — {$ref}"
        );
    }

    /**
     * Simulate sales → stock OUT + COGS journal.  Returns total COGS.
     */
    private function sellStock(Product $product, float $qty, string $ref): float
    {
        $product->refresh();
        $unitCost = (float) $product->avg_cost;

        $movement = $this->stockService->processMovement([
            'product_id'     => $product->id,
            'warehouse_id'   => $this->warehouse->id,
            'type'           => 'out',
            'quantity'       => $qty,
            'unit_cost'      => $unitCost,
            'reference_type' => 'sales_order',
            'reference_id'   => 1,
        ]);

        $this->valuationService->recordOutgoing(
            $product->id, $this->warehouse->id, $qty,
            $movement, 'sales_order', 1, "Sale {$ref}"
        );

        $totalCogs = round($qty * $unitCost, 2);

        $this->valuationService->journalSalesCogs(
            $ref . '-COGS', now()->toDateString(),
            $totalCogs, "COGS — {$ref}"
        );

        return $totalCogs;
    }

    /**
     * Simulate purchase return at ORIGINAL price.  Recalculates avg_cost.
     */
    private function returnPurchase(Product $product, float $qty, float $originalPrice, string $ref): float
    {
        $movement = $this->stockService->processMovement([
            'product_id'     => $product->id,
            'warehouse_id'   => $this->warehouse->id,
            'type'           => 'out',
            'quantity'       => $qty,
            'unit_cost'      => $originalPrice,
            'reference_type' => 'purchase_return',
            'reference_id'   => 1,
        ]);

        $this->valuationService->recordPurchaseReturn(
            $product->id, $this->warehouse->id, $qty, $originalPrice,
            $movement, 'purchase_return', 1, "Return {$ref}"
        );

        $totalValue = round($qty * $originalPrice, 2);

        $this->valuationService->journalPurchaseCancel(
            $ref, now()->toDateString(),
            $totalValue, "Purchase return — {$ref}"
        );

        return $totalValue;
    }

    /**
     * Simulate stock adjustment (write-off) — avg_cost must not change.
     */
    private function adjustStockOut(Product $product, float $qty): void
    {
        $product->refresh();
        $unitCost = (float) $product->avg_cost;

        $movement = $this->stockService->processMovement([
            'product_id'     => $product->id,
            'warehouse_id'   => $this->warehouse->id,
            'type'           => 'out',
            'quantity'       => $qty,
            'unit_cost'      => $unitCost,
            'reference_type' => 'stock_adjustment',
            'reference_id'   => 1,
        ]);

        $this->valuationService->recordOutgoing(
            $product->id, $this->warehouse->id, $qty,
            $movement, 'stock_adjustment', 1, "Damaged goods — {$qty} units"
        );
    }

    /**
     * Assert a balanced journal entry exists with correct Dr / Cr amounts.
     */
    private function assertJournalEntry(string $reference, int $drAccountId, int $crAccountId, float $amount): void
    {
        $journal = JournalEntry::where('reference', $reference)->first();
        $this->assertNotNull($journal, "Journal '{$reference}' not found.");

        $dr = $journal->items()->where('account_id', $drAccountId)->first();
        $this->assertNotNull($dr, "Debit line missing in journal '{$reference}'.");
        $this->assertEqualsWithDelta($amount, (float) $dr->debit, 0.01,
            "Debit mismatch in '{$reference}': expected {$amount}, got {$dr->debit}");

        $cr = $journal->items()->where('account_id', $crAccountId)->first();
        $this->assertNotNull($cr, "Credit line missing in journal '{$reference}'.");
        $this->assertEqualsWithDelta($amount, (float) $cr->credit, 0.01,
            "Credit mismatch in '{$reference}': expected {$amount}, got {$cr->credit}");
    }

    /**
     * Setup Barang A with state through Scenario 1 (two purchases).
     *  100 @ 10,000 + 50 @ 13,000 → avg 11,000 — 150 units.
     */
    private function setupScenario1(): Product
    {
        $product = $this->createProduct('RM-A', 'Barang A');
        $this->purchaseStock($product, 100, 10000, 'PO-001');
        $this->purchaseStock($product, 50, 13000, 'PO-002');
        return $product;
    }

    /**
     * Setup through Scenario 2 (after sale of 60 units).
     *  90 units @ avg 11,000.
     */
    private function setupScenario2(): Product
    {
        $product = $this->setupScenario1();
        $this->sellStock($product, 60, 'SO-001');
        return $product;
    }

    /**
     * Setup through Scenario 3 (after purchase return of 10 @ 13,000).
     *  80 units @ avg 10,750.
     */
    private function setupScenario3(): Product
    {
        $product = $this->setupScenario2();
        $this->returnPurchase($product, 10, 13000, 'RET-001');
        return $product;
    }

    // =================================================================
    // SCENARIO 1 — Fluctuating Purchase
    // Beli 100@10.000 lalu 50@13.000 → avg_cost = 11.000
    // =================================================================

    public function test_scenario1_fluctuating_purchase_updates_weighted_average_cost(): void
    {
        $product = $this->createProduct('RM-A', 'Barang A');

        /* -------- Purchase 1: 100 units @ 10,000 -------- */
        $this->purchaseStock($product, 100, 10000, 'PO-001');

        $product->refresh();
        $this->assertEqualsWithDelta(10000, (float) $product->avg_cost, 0.0001,
            'avg_cost after 1st purchase');

        /* -------- Purchase 2: 50 units @ 13,000 -------- */
        $this->purchaseStock($product, 50, 13000, 'PO-002');

        $product->refresh();

        // avg = (100×10000 + 50×13000) / 150 = 1,650,000 / 150 = 11,000
        $this->assertEqualsWithDelta(11000, (float) $product->avg_cost, 0.0001,
            'avg_cost after 2nd purchase');

        // Total stock = 150
        $totalQty = (float) $product->inventoryStocks()->sum('quantity');
        $this->assertEquals(150, $totalQty);

        // Total value = 150 × 11,000 = 1,650,000
        $this->assertEqualsWithDelta(1650000, $totalQty * (float) $product->avg_cost, 0.01);

        /* -------- Valuation layers -------- */
        $layers = StockValuationLayer::where('product_id', $product->id)
            ->orderBy('id')->get();
        $this->assertCount(2, $layers);

        $this->assertEquals('in', $layers[0]->direction);
        $this->assertEqualsWithDelta(100, (float) $layers[0]->quantity, 0.01);
        $this->assertEqualsWithDelta(10000, (float) $layers[0]->unit_cost, 0.0001);
        $this->assertEqualsWithDelta(1000000, (float) $layers[0]->total_value, 0.01);
        $this->assertEqualsWithDelta(10000, (float) $layers[0]->avg_cost_after, 0.0001);

        $this->assertEquals('in', $layers[1]->direction);
        $this->assertEqualsWithDelta(50, (float) $layers[1]->quantity, 0.01);
        $this->assertEqualsWithDelta(13000, (float) $layers[1]->unit_cost, 0.0001);
        $this->assertEqualsWithDelta(650000, (float) $layers[1]->total_value, 0.01);
        $this->assertEqualsWithDelta(11000, (float) $layers[1]->avg_cost_after, 0.0001);

        /* -------- Purchase journals: Dr Inventory / Cr AP -------- */
        $this->assertJournalEntry('PO-001', $this->inventoryAccount->id, $this->apAccount->id, 1000000);
        $this->assertJournalEntry('PO-002', $this->inventoryAccount->id, $this->apAccount->id, 650000);
    }

    // =================================================================
    // SCENARIO 2 — Sales & COGS Accuracy
    // Jual 60 unit → COGS = 60 × 11,000 = 660,000
    // =================================================================

    public function test_scenario2_sales_cogs_uses_weighted_average_cost(): void
    {
        $product = $this->setupScenario1(); // 150 units @ avg 11,000

        /* -------- Sell 60 units -------- */
        $cogs = $this->sellStock($product, 60, 'SO-001');

        $product->refresh();

        // COGS = 60 × 11,000 = 660,000
        $this->assertEqualsWithDelta(660000, $cogs, 0.01, 'Total COGS');

        // Remaining: 90 units
        $remainingQty = (float) $product->inventoryStocks()->sum('quantity');
        $this->assertEquals(90, $remainingQty);

        // avg_cost unchanged after sale
        $this->assertEqualsWithDelta(11000, (float) $product->avg_cost, 0.0001,
            'avg_cost must not change on outgoing');

        // Remaining value = 90 × 11,000 = 990,000
        $this->assertEqualsWithDelta(990000, $remainingQty * (float) $product->avg_cost, 0.01);

        /* -------- Valuation layer -------- */
        $layer = StockValuationLayer::where('product_id', $product->id)
            ->where('direction', 'out')
            ->where('reference_type', 'sales_order')
            ->first();

        $this->assertNotNull($layer);
        $this->assertEqualsWithDelta(60, (float) $layer->quantity, 0.01);
        $this->assertEqualsWithDelta(11000, (float) $layer->unit_cost, 0.0001);
        $this->assertEqualsWithDelta(660000, (float) $layer->total_value, 0.01);
        $this->assertEqualsWithDelta(11000, (float) $layer->avg_cost_after, 0.0001);

        /* -------- COGS journal: Dr COGS (5000) / Cr Inventory (1300) -------- */
        $this->assertJournalEntry(
            'SO-001-COGS',
            $this->cogsAccount->id,
            $this->inventoryAccount->id,
            660000
        );
    }

    // =================================================================
    // SCENARIO 3 — Purchase Return (Retur ke Supplier)
    // Return 10 unit @13,000 → new avg = (990,000−130,000)/80 = 10,750
    // =================================================================

    public function test_scenario3_purchase_return_recalculates_average_cost(): void
    {
        $product = $this->setupScenario2(); // 90 units @ avg 11,000

        /* -------- Return 10 units at original price Rp13,000 -------- */
        $returnValue = $this->returnPurchase($product, 10, 13000, 'RET-001');

        $this->assertEqualsWithDelta(130000, $returnValue, 0.01);

        $product->refresh();

        // Remaining = 80 units
        $remainingQty = (float) $product->inventoryStocks()->sum('quantity');
        $this->assertEquals(80, $remainingQty);

        // New avg = (990,000 − 130,000) / 80 = 860,000 / 80 = 10,750
        $this->assertEqualsWithDelta(10750, (float) $product->avg_cost, 0.0001,
            'avg_cost must be recalculated after purchase return');

        // Remaining value = 80 × 10,750 = 860,000
        $this->assertEqualsWithDelta(860000, $remainingQty * (float) $product->avg_cost, 0.01);

        /* -------- Valuation layer -------- */
        $layer = StockValuationLayer::where('product_id', $product->id)
            ->where('reference_type', 'purchase_return')
            ->first();

        $this->assertNotNull($layer);
        $this->assertEquals('out', $layer->direction);
        $this->assertEqualsWithDelta(10, (float) $layer->quantity, 0.01);
        $this->assertEqualsWithDelta(13000, (float) $layer->unit_cost, 0.0001,
            'Layer must record original purchase price, not avg_cost');
        $this->assertEqualsWithDelta(130000, (float) $layer->total_value, 0.01);
        $this->assertEqualsWithDelta(10750, (float) $layer->avg_cost_after, 0.0001);

        /* -------- Return journal: Dr AP (2000) / Cr Inventory (1300) -------- */
        $this->assertJournalEntry(
            'RET-001',
            $this->apAccount->id,
            $this->inventoryAccount->id,
            130000
        );
    }

    // =================================================================
    // SCENARIO 4 — Manufacturing (BOM Consumption)
    // 2 unit Barang A → 1 unit Barang B
    // FG cost = 2 × 10,750 = 21,500
    // =================================================================

    public function test_scenario4_manufacturing_produces_finished_goods_at_accumulated_material_cost(): void
    {
        $productA = $this->setupScenario3(); // 80 units @ avg 10,750
        $productB = $this->createProduct('FG-B', 'Barang B', 'finished_good');

        $productA->refresh();
        $this->assertEqualsWithDelta(10750, (float) $productA->avg_cost, 0.0001);

        $consumeQty = 2;
        $materialUnitCost = (float) $productA->avg_cost; // 10,750

        /* -------- Step 1: Consume 2 units of raw material A -------- */
        $consumeMovement = $this->stockService->processMovement([
            'product_id'     => $productA->id,
            'warehouse_id'   => $this->warehouse->id,
            'type'           => 'out',
            'quantity'       => $consumeQty,
            'unit_cost'      => $materialUnitCost,
            'reference_type' => 'manufacturing_order',
            'reference_id'   => 1,
        ]);

        $this->valuationService->recordOutgoing(
            $productA->id, $this->warehouse->id, $consumeQty,
            $consumeMovement, 'manufacturing_order', 1,
            'MO-001 consume Barang A'
        );

        $totalMaterialCost = round($consumeQty * $materialUnitCost, 2);
        $this->assertEqualsWithDelta(21500, $totalMaterialCost, 0.01);

        /* -------- Step 2: Produce 1 unit of finished good B -------- */
        $produceQty = 1;
        $fgUnitCost = round($totalMaterialCost / $produceQty, 4);

        $produceMovement = $this->stockService->processMovement([
            'product_id'     => $productB->id,
            'warehouse_id'   => $this->warehouse->id,
            'type'           => 'in',
            'quantity'       => $produceQty,
            'unit_cost'      => $fgUnitCost,
            'reference_type' => 'manufacturing_order',
            'reference_id'   => 1,
        ]);

        $this->valuationService->recordManufacturingIncoming(
            $productB->id, $this->warehouse->id, $produceQty,
            $totalMaterialCost, $produceMovement,
            'manufacturing_order', 1,
            'MO-001 produce Barang B'
        );

        /* -------- Assertions: Barang A -------- */
        $productA->refresh();
        $this->assertEquals(78, (float) $productA->inventoryStocks()->sum('quantity'),
            'Barang A stock should be 80 − 2 = 78');
        $this->assertEqualsWithDelta(10750, (float) $productA->avg_cost, 0.0001,
            'Barang A avg_cost unchanged after consumption');

        /* -------- Assertions: Barang B -------- */
        $productB->refresh();
        $this->assertEquals(1, (float) $productB->inventoryStocks()->sum('quantity'));
        $this->assertEqualsWithDelta(21500, (float) $productB->avg_cost, 0.0001,
            'Barang B avg_cost = total material cost / qty produced');

        /* -------- Valuation layers -------- */
        // Material consumption layer (A out)
        $consumeLayer = StockValuationLayer::where('product_id', $productA->id)
            ->where('reference_type', 'manufacturing_order')
            ->where('direction', 'out')
            ->first();
        $this->assertNotNull($consumeLayer);
        $this->assertEqualsWithDelta(2, (float) $consumeLayer->quantity, 0.01);
        $this->assertEqualsWithDelta(10750, (float) $consumeLayer->unit_cost, 0.0001);
        $this->assertEqualsWithDelta(21500, (float) $consumeLayer->total_value, 0.01);

        // Finished goods layer (B in)
        $produceLayer = StockValuationLayer::where('product_id', $productB->id)
            ->where('reference_type', 'manufacturing_order')
            ->where('direction', 'in')
            ->first();
        $this->assertNotNull($produceLayer);
        $this->assertEqualsWithDelta(1, (float) $produceLayer->quantity, 0.01);
        $this->assertEqualsWithDelta(21500, (float) $produceLayer->unit_cost, 0.0001);
        $this->assertEqualsWithDelta(21500, (float) $produceLayer->total_value, 0.01);
        $this->assertEqualsWithDelta(21500, (float) $produceLayer->avg_cost_after, 0.0001);
    }

    // =================================================================
    // SCENARIO 5 — Stock Adjustment (Opname / Write-off)
    // Barang rusak 5 unit → avg_cost tetap 10,750
    // =================================================================

    public function test_scenario5_stock_adjustment_does_not_change_average_cost(): void
    {
        $product = $this->setupScenario3(); // 80 units @ avg 10,750

        $product->refresh();
        $this->assertEqualsWithDelta(10750, (float) $product->avg_cost, 0.0001);
        $this->assertEquals(80, (float) $product->inventoryStocks()->sum('quantity'));

        /* -------- Adjust: write off 5 damaged units -------- */
        $this->adjustStockOut($product, 5);

        $product->refresh();

        // Stock = 80 − 5 = 75
        $this->assertEquals(75, (float) $product->inventoryStocks()->sum('quantity'));

        // avg_cost MUST remain 10,750
        $this->assertEqualsWithDelta(10750, (float) $product->avg_cost, 0.0001,
            'Adjustment must NOT change avg_cost');

        // Value written off = 5 × 10,750 = 53,750
        $adjLayer = StockValuationLayer::where('product_id', $product->id)
            ->where('reference_type', 'stock_adjustment')
            ->first();

        $this->assertNotNull($adjLayer);
        $this->assertEquals('out', $adjLayer->direction);
        $this->assertEqualsWithDelta(5, (float) $adjLayer->quantity, 0.01);
        $this->assertEqualsWithDelta(10750, (float) $adjLayer->unit_cost, 0.0001);
        $this->assertEqualsWithDelta(53750, (float) $adjLayer->total_value, 0.01);
        $this->assertEqualsWithDelta(10750, (float) $adjLayer->avg_cost_after, 0.0001,
            'avg_cost_after should equal current avg_cost');

        // Remaining value = 75 × 10,750 = 806,250
        $remainingValue = 75 * (float) $product->avg_cost;
        $this->assertEqualsWithDelta(806250, $remainingValue, 0.01);
    }
}
