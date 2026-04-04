<?php

namespace Tests\Feature;

use App\Models\ChartOfAccount;
use App\Models\Product;
use App\Models\StockValuationLayer;
use App\Models\Warehouse;
use App\Services\StockService;
use App\Services\StockValuationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * TUGAS 3: Advanced WAC & Rounding Precision
 *
 * Tests fractional prices (e.g. Rp10.000,33) and edge cases
 * like zero-stock + sales-return to ensure no division-by-zero
 * and correct avg_cost behavior.
 */
class WacRoundingPrecisionTest extends TestCase
{
    use RefreshDatabase;

    private StockService $stockService;
    private StockValuationService $valuationService;
    private Warehouse $warehouse;
    private ChartOfAccount $inventoryAccount;
    private ChartOfAccount $apAccount;
    private ChartOfAccount $cogsAccount;

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
            'code' => 'WH-R', 'name' => 'Rounding Warehouse', 'is_active' => true,
        ]);
    }

    private function createProduct(string $sku, string $name): Product
    {
        return Product::create([
            'sku' => $sku, 'name' => $name, 'type' => 'raw_material',
            'cost_price' => 0, 'avg_cost' => 0, 'sell_price' => 0, 'is_active' => true,
        ]);
    }

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
            round($qty * $unitPrice, 2), "Goods received — {$ref}"
        );
    }

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
            $ref . '-COGS', now()->toDateString(), $totalCogs, "COGS — {$ref}"
        );

        return $totalCogs;
    }

    // ─── ROUNDING PRECISION ──────────────────────────────────────

    public function test_fractional_price_purchase_maintains_precision(): void
    {
        $product = $this->createProduct('RM-FRAC', 'Fractional Item');

        // Purchase 1: 100 units @ Rp10.000,33
        $this->purchaseStock($product, 100, 10000.33, 'PO-FRAC-1');

        $product->refresh();
        $this->assertEqualsWithDelta(10000.33, (float) $product->avg_cost, 0.01,
            'avg_cost should be exact after first purchase');

        // Total value = 100 × 10000.33 = 1,000,033.00
        $totalQty = (float) $product->inventoryStocks()->sum('quantity');
        $totalValue = $totalQty * (float) $product->avg_cost;
        $this->assertEqualsWithDelta(1000033.00, $totalValue, 0.01,
            'Total stock value must match to 2 decimal places');
        $this->assertEquals(100, $totalQty);
    }

    public function test_mixed_fractional_purchases_avg_cost_precision(): void
    {
        $product = $this->createProduct('RM-MIX', 'Mixed Fractions');

        // Purchase 1: 100 @ 10000.33
        $this->purchaseStock($product, 100, 10000.33, 'PO-MIX-1');

        // Purchase 2: 50 @ 13000.67
        $this->purchaseStock($product, 50, 13000.67, 'PO-MIX-2');

        $product->refresh();
        // Expected: (100×10000.33 + 50×13000.67) / 150
        // = (1000033 + 650033.50) / 150
        // = 1650066.50 / 150
        // = 11000.4433...
        $expectedAvg = (100 * 10000.33 + 50 * 13000.67) / 150;

        $this->assertEqualsWithDelta($expectedAvg, (float) $product->avg_cost, 0.01,
            'WAC with fractional prices must be precise to 2 decimal places');

        $totalQty = (float) $product->inventoryStocks()->sum('quantity');
        $this->assertEquals(150, $totalQty);

        // Total value cross-check
        $totalValue = $totalQty * (float) $product->avg_cost;
        $expectedTotalValue = 100 * 10000.33 + 50 * 13000.67;
        $this->assertEqualsWithDelta($expectedTotalValue, $totalValue, 0.02,
            'Total inventory value must stay in sync with individual layer values');
    }

    public function test_sell_after_fractional_purchase_cogs_is_precise(): void
    {
        $product = $this->createProduct('RM-SELL', 'Sell Precision');

        $this->purchaseStock($product, 100, 10000.33, 'PO-SP-1');
        $this->purchaseStock($product, 50, 13000.67, 'PO-SP-2');

        $product->refresh();
        $avgBefore = (float) $product->avg_cost;

        // Sell 30 units
        $cogs = $this->sellStock($product, 30, 'SO-SP-1');

        $product->refresh();
        $expectedCogs = round(30 * $avgBefore, 2);

        $this->assertEqualsWithDelta($expectedCogs, $cogs, 0.01,
            'COGS must use avg_cost with fractional precision');

        // avg_cost must NOT change after sale
        $this->assertEqualsWithDelta($avgBefore, (float) $product->avg_cost, 0.0001,
            'avg_cost must not change on outgoing');

        // Remaining = 120 units
        $remainingQty = (float) $product->inventoryStocks()->sum('quantity');
        $this->assertEquals(120, $remainingQty);
    }

    // ─── ZERO STOCK EDGE CASES ───────────────────────────────────

    public function test_sell_all_stock_then_purchase_resets_avg_cost(): void
    {
        $product = $this->createProduct('RM-ZERO', 'Zero Stock Test');

        // Purchase 10 @ 5000
        $this->purchaseStock($product, 10, 5000, 'PO-Z1');
        $product->refresh();
        $this->assertEqualsWithDelta(5000, (float) $product->avg_cost, 0.01);

        // Sell all 10
        $this->sellStock($product, 10, 'SO-Z1');
        $product->refresh();
        $remainingQty = (float) $product->inventoryStocks()->sum('quantity');
        $this->assertEquals(0, $remainingQty, 'Stock should be zero');

        // avg_cost remains at last value (not zeroed out)
        $this->assertEqualsWithDelta(5000, (float) $product->avg_cost, 0.01,
            'avg_cost should remain at last known value when stock reaches 0');

        // Now purchase again at different price
        $this->purchaseStock($product, 20, 7500, 'PO-Z2');
        $product->refresh();

        // With 0 existing stock, new avg = incoming price
        // Formula: (0 × 5000 + 20 × 7500) / 20 = 7500
        $this->assertEqualsWithDelta(7500, (float) $product->avg_cost, 0.01,
            'New purchase on zero stock: avg_cost = new purchase price');

        $totalQty = (float) $product->inventoryStocks()->sum('quantity');
        $this->assertEquals(20, $totalQty);
    }

    public function test_stock_adjustment_to_zero_no_division_by_zero(): void
    {
        $product = $this->createProduct('RM-ADJ0', 'Adjustment Zero');

        // Purchase 5 @ 8000
        $this->purchaseStock($product, 5, 8000, 'PO-ADJ0');
        $product->refresh();
        $this->assertEqualsWithDelta(8000, (float) $product->avg_cost, 0.01);

        // Write off everything
        $movement = $this->stockService->processMovement([
            'product_id'     => $product->id,
            'warehouse_id'   => $this->warehouse->id,
            'type'           => 'out',
            'quantity'       => 5,
            'unit_cost'      => (float) $product->avg_cost,
            'reference_type' => 'stock_adjustment',
            'reference_id'   => 1,
        ]);

        $this->valuationService->recordOutgoing(
            $product->id, $this->warehouse->id, 5,
            $movement, 'stock_adjustment', 1, 'Write off all stock'
        );

        $product->refresh();
        $qty = (float) $product->inventoryStocks()->sum('quantity');
        $this->assertEquals(0, $qty, 'Stock must be zero after full write-off');

        // avg_cost should still be a valid number (no NaN, no infinity, no exception)
        $this->assertIsNumeric($product->avg_cost, 'avg_cost must remain a valid number');
        $this->assertFalse(is_nan((float) $product->avg_cost), 'avg_cost must not be NaN');
        $this->assertFalse(is_infinite((float) $product->avg_cost), 'avg_cost must not be infinite');
    }

    public function test_purchase_after_zero_stock_from_adjustment_recalculates_correctly(): void
    {
        $product = $this->createProduct('RM-RCV0', 'Receive After Zero');

        // Purchase 3 @ 12000
        $this->purchaseStock($product, 3, 12000, 'PO-RCV0-1');

        // Sell all
        $this->sellStock($product, 3, 'SO-RCV0');
        $product->refresh();
        $this->assertEquals(0, (float) $product->inventoryStocks()->sum('quantity'));

        // New purchase at a very different price
        $this->purchaseStock($product, 7, 15000.55, 'PO-RCV0-2');
        $product->refresh();

        $totalQty = (float) $product->inventoryStocks()->sum('quantity');
        $this->assertEquals(7, $totalQty);
        // Since existing qty was 0, new avg = 15000.55
        $this->assertEqualsWithDelta(15000.55, (float) $product->avg_cost, 0.01,
            'After zero stock, avg_cost should equal new purchase price');

        $totalValue = $totalQty * (float) $product->avg_cost;
        $this->assertEqualsWithDelta(7 * 15000.55, $totalValue, 0.02,
            'Total value must be consistent');
    }

    // ─── REPEATING DECIMAL / THREE-WAY SPLIT ─────────────────────

    public function test_three_purchases_with_repeating_decimal_avg(): void
    {
        $product = $this->createProduct('RM-3WAY', 'Three Way Split');

        // Purchases that produce a repeating decimal:
        // 10 @ 100, 10 @ 200, 10 @ 300
        // avg = (1000 + 2000 + 3000) / 30 = 200
        $this->purchaseStock($product, 10, 100, 'PO-3-1');
        $this->purchaseStock($product, 10, 200, 'PO-3-2');
        $this->purchaseStock($product, 10, 300, 'PO-3-3');

        $product->refresh();
        $this->assertEqualsWithDelta(200, (float) $product->avg_cost, 0.01);
        $this->assertEquals(30, (float) $product->inventoryStocks()->sum('quantity'));

        // Now buy 7 @ 333.33 (produces repeating decimal in avg)
        // total = 30×200 + 7×333.33 = 6000 + 2333.31 = 8333.31
        // new avg = 8333.31 / 37 = 225.2246...
        $this->purchaseStock($product, 7, 333.33, 'PO-3-4');
        $product->refresh();

        $expectedAvg = (30 * 200 + 7 * 333.33) / 37;
        $this->assertEqualsWithDelta($expectedAvg, (float) $product->avg_cost, 0.01,
            'Repeating decimal avg_cost must be stored precisely');

        // Verify total value syncs with layers
        $layerTotal = StockValuationLayer::where('product_id', $product->id)
            ->where('direction', 'in')
            ->sum('total_value');
        $expectedLayerTotal = 10 * 100 + 10 * 200 + 10 * 300 + 7 * 333.33;
        $this->assertEqualsWithDelta($expectedLayerTotal, (float) $layerTotal, 0.02,
            'Sum of valuation layers must equal total incoming value');
    }
}
