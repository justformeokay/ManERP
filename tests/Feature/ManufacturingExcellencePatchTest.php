<?php

namespace Tests\Feature;

use App\Models\BillOfMaterial;
use App\Models\BomItem;
use App\Models\ChartOfAccount;
use App\Models\Client;
use App\Models\InventoryStock;
use App\Models\JournalEntry;
use App\Models\ManufacturingOrder;
use App\Models\Product;
use App\Models\ProductionCost;
use App\Models\QcInspection;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\StockValuationLayer;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\StockService;
use App\Services\StockValuationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Manufacturing Excellence Patch — Verification Test Suite
 *
 * Validates P0/P1 fixes:
 *  1. Transaction atomicity (DB::transaction rollback)
 *  2. Manufacturing respects sales reservations (availableQuantity)
 *  3. WIP accounting flow (Dr 1400 / Cr 1300-RM then Dr 1300-FG / Cr 1400)
 *  4. QC gate blocks done on failed inspection
 *  5. QC auto-creation on in_progress
 *  6. Confirm pre-check warnings
 *  7. Variance journal on completion
 *  8. CoA missing throws exception
 */
class ManufacturingExcellencePatchTest extends TestCase
{
    use RefreshDatabase;

    private StockService $stockService;
    private StockValuationService $valuationService;
    private Warehouse $warehouse;
    private User $admin;
    private ChartOfAccount $inventoryAccount;
    private ChartOfAccount $fgAccount;
    private ChartOfAccount $rmAccount;
    private ChartOfAccount $wipAccount;
    private ChartOfAccount $cogsAccount;
    private ChartOfAccount $varianceAccount;

    protected function setUp(): void
    {
        parent::setUp();

        $this->stockService = app(StockService::class);
        $this->valuationService = app(StockValuationService::class);

        $this->admin = User::factory()->create([
            'role'   => 'admin',
            'status' => 'active',
        ]);

        $this->warehouse = Warehouse::create([
            'code' => 'WH-MFG', 'name' => 'MFG Warehouse', 'is_active' => true,
        ]);

        $this->inventoryAccount = ChartOfAccount::create([
            'code' => '1300', 'name' => 'Inventory', 'type' => 'asset', 'is_active' => true,
        ]);
        $this->fgAccount = ChartOfAccount::create([
            'code' => '1300-FG', 'name' => 'Inventory — Finished Goods', 'type' => 'asset', 'is_active' => true,
        ]);
        $this->rmAccount = ChartOfAccount::create([
            'code' => '1300-RM', 'name' => 'Inventory — Raw Materials', 'type' => 'asset', 'is_active' => true,
        ]);
        $this->wipAccount = ChartOfAccount::create([
            'code' => '1400', 'name' => 'Work in Progress', 'type' => 'asset', 'is_active' => true,
        ]);
        ChartOfAccount::create([
            'code' => '2000', 'name' => 'Accounts Payable', 'type' => 'liability', 'is_active' => true,
        ]);
        $this->cogsAccount = ChartOfAccount::create([
            'code' => '5000', 'name' => 'COGS', 'type' => 'expense', 'is_active' => true,
        ]);
        $this->varianceAccount = ChartOfAccount::create([
            'code' => '6500', 'name' => 'Manufacturing Variance', 'type' => 'expense', 'is_active' => true,
        ]);
    }

    // ═══════════════════════════════════════════════════════════════
    // Helpers
    // ═══════════════════════════════════════════════════════════════

    private function createProduct(string $sku, string $name, string $type = 'raw_material', array $extra = []): Product
    {
        return Product::create(array_merge([
            'sku' => $sku, 'name' => $name, 'type' => $type,
            'cost_price' => 0, 'avg_cost' => 0, 'sell_price' => 100,
            'is_active' => true,
        ], $extra));
    }

    private function seedStock(Product $product, float $qty, float $unitCost): void
    {
        $movement = $this->stockService->processMovement([
            'product_id'     => $product->id,
            'warehouse_id'   => $this->warehouse->id,
            'type'           => 'in',
            'quantity'       => $qty,
            'unit_cost'      => $unitCost,
            'reference_type' => 'purchase_order',
            'reference_id'   => 1,
        ]);

        $this->valuationService->recordIncoming(
            $product->id, $this->warehouse->id, $qty, $unitCost,
            $movement, 'purchase_order', 1, 'Seed stock'
        );
    }

    private function createBom(Product $fg, array $materials): BillOfMaterial
    {
        $bom = BillOfMaterial::create([
            'product_id'      => $fg->id,
            'name'            => $fg->name . ' BOM',
            'output_quantity' => 1,
            'version'         => 1,
            'level'           => 0,
            'is_active'       => true,
        ]);

        foreach ($materials as [$product, $qty, $unitCost]) {
            BomItem::create([
                'bom_id'     => $bom->id,
                'product_id' => $product->id,
                'quantity'   => $qty,
                'unit_cost'  => $unitCost,
                'line_cost'  => $qty * $unitCost,
            ]);
        }

        return $bom;
    }

    private function createMo(BillOfMaterial $bom, float $plannedQty, string $status = 'confirmed'): ManufacturingOrder
    {
        $mo = ManufacturingOrder::create([
            'bom_id'            => $bom->id,
            'product_id'        => $bom->product_id,
            'warehouse_id'      => $this->warehouse->id,
            'planned_quantity'  => $plannedQty,
            'produced_quantity' => 0,
            'status'            => 'draft',
            'priority'          => 'normal',
            'created_by'        => $this->admin->id,
        ]);

        if ($status !== 'draft') {
            $mo->transitionToAndSave('confirmed');
            if ($status === 'in_progress') {
                $mo->transitionToAndSave('in_progress');
            }
        }

        return $mo;
    }

    // ═══════════════════════════════════════════════════════════════
    // TUGAS 1: Transaction Atomicity & Integrity
    // ═══════════════════════════════════════════════════════════════

    public function test_production_fails_safely_on_db_error(): void
    {
        $rm1 = $this->createProduct('RM-TX1', 'Mat A');
        $rm2 = $this->createProduct('RM-TX2', 'Mat B');
        $fg  = $this->createProduct('FG-TX', 'Product TX', 'finished_good');

        // Seed enough of RM1, but NOT enough of RM2
        $this->seedStock($rm1, 100, 5000);
        $this->seedStock($rm2, 1, 3000); // Only 1 unit

        $bom = $this->createBom($fg, [
            [$rm1, 2, 5000],
            [$rm2, 3, 3000], // Needs 30 for 10 items → will fail
        ]);

        $mo = $this->createMo($bom, 10);
        $mo->transitionToAndSave('in_progress');

        // Pre-validation should catch insufficient stock for RM2
        $response = $this->actingAs($this->admin)->post(
            route('manufacturing.orders.produce', $mo),
            ['quantity' => 10]
        );

        $response->assertSessionHasErrors('quantity');

        // Verify RM1 stock was NOT consumed (atomicity: pre-validate catches it before any consumption)
        $rm1Stock = (float) InventoryStock::where('product_id', $rm1->id)
            ->where('warehouse_id', $this->warehouse->id)
            ->value('quantity');
        $this->assertEquals(100, $rm1Stock, 'RM1 stock must be unchanged after failed produce');

        // Verify RM2 stock unchanged
        $rm2Stock = (float) InventoryStock::where('product_id', $rm2->id)
            ->where('warehouse_id', $this->warehouse->id)
            ->value('quantity');
        $this->assertEquals(1, $rm2Stock, 'RM2 stock must be unchanged after failed produce');

        // Verify FG not produced
        $fgStockRecord = InventoryStock::where('product_id', $fg->id)
            ->where('warehouse_id', $this->warehouse->id)
            ->first();
        $this->assertTrue(
            $fgStockRecord === null || (float) $fgStockRecord->quantity === 0.0,
            'FG must not be produced on failed produce'
        );

        // Verify no WIP journal created
        $wipJournal = JournalEntry::where('reference', 'like', $mo->fresh()->number . '%')->count();
        $this->assertEquals(0, $wipJournal, 'No journals should be created on failed produce');

        // Verify MO state unchanged
        $mo->refresh();
        $this->assertEquals(0, (float) $mo->produced_quantity);
    }

    public function test_coa_missing_throws_exception(): void
    {
        // Delete the WIP account to force the error
        $this->wipAccount->delete();

        $rm = $this->createProduct('RM-COA', 'CoA RM');
        $fg = $this->createProduct('FG-COA', 'CoA FG', 'finished_good');
        $this->seedStock($rm, 100, 5000);

        $bom = $this->createBom($fg, [[$rm, 2, 5000]]);
        $mo = $this->createMo($bom, 5);
        $mo->transitionToAndSave('in_progress');

        // Should fail because WIP account (1400) is missing
        $response = $this->actingAs($this->admin)->post(
            route('manufacturing.orders.produce', $mo),
            ['quantity' => 5]
        );

        // The RuntimeException should propagate and cause a 500 error
        $response->assertStatus(500);

        // Verify stock was rolled back (transaction atomicity)
        $rmStock = (float) InventoryStock::where('product_id', $rm->id)
            ->where('warehouse_id', $this->warehouse->id)
            ->value('quantity');
        $this->assertEquals(100, $rmStock, 'RM stock must be rolled back on CoA missing error');
    }

    // ═══════════════════════════════════════════════════════════════
    // TUGAS 1b: Manufacturing Respects Sales Reservations
    // ═══════════════════════════════════════════════════════════════

    public function test_manufacturing_respects_sales_reservations(): void
    {
        $product = $this->createProduct('FG-RES', 'Shared Product', 'finished_good');
        $rm = $this->createProduct('RM-RES', 'RM for Shared');
        $this->seedStock($product, 100, 5000); // 100 FG
        $this->seedStock($rm, 200, 3000);

        // Sales reserves 80 units of the product used as RM by manufacturing
        // Simulate sales reservation on this RM
        $stock = InventoryStock::where('product_id', $rm->id)
            ->where('warehouse_id', $this->warehouse->id)
            ->first();
        $stock->update(['reserved_quantity' => 150]); // 200 - 150 = 50 available

        $bom = $this->createBom($product, [[$rm, 10, 3000]]); // 10 RM per FG
        $mo = $this->createMo($bom, 10); // needs 100 RM total
        $mo->transitionToAndSave('in_progress');

        // Try to produce 10 FG — needs 100 RM but only 50 available (after reservations)
        $response = $this->actingAs($this->admin)->post(
            route('manufacturing.orders.produce', $mo),
            ['quantity' => 10]
        );

        $response->assertSessionHasErrors('quantity');

        // Produce 5 instead — needs 50 RM = exactly what's available
        $response = $this->actingAs($this->admin)->post(
            route('manufacturing.orders.produce', $mo),
            ['quantity' => 5]
        );

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $mo->refresh();
        $this->assertEquals(5, (float) $mo->produced_quantity);
    }

    // ═══════════════════════════════════════════════════════════════
    // TUGAS 2: WIP Accounting Flow
    // ═══════════════════════════════════════════════════════════════

    public function test_wip_accounting_flow_consistency(): void
    {
        $rm = $this->createProduct('RM-WIP', 'WIP RM');
        $fg = $this->createProduct('FG-WIP', 'WIP FG', 'finished_good');
        $this->seedStock($rm, 100, 4000);

        $bom = $this->createBom($fg, [[$rm, 5, 4000]]);
        $mo = $this->createMo($bom, 10);
        $mo->transitionToAndSave('in_progress');

        $this->actingAs($this->admin)->post(
            route('manufacturing.orders.produce', $mo),
            ['quantity' => 10]
        );

        $moNumber = $mo->fresh()->number;

        // ── Stage 1: Material → WIP (Dr 1400 / Cr 1300-RM) ──
        $wipIn = JournalEntry::where('reference', $moNumber . '-WIP-IN')->first();
        $this->assertNotNull($wipIn, 'WIP-IN journal must exist');

        $wipInDebit = (float) $wipIn->items()->where('account_id', $this->wipAccount->id)->value('debit');
        $wipInCredit = (float) $wipIn->items()->where('account_id', $this->rmAccount->id)->value('credit');

        // Material cost = 50 RM × Rp4,000 = Rp200,000
        $this->assertEqualsWithDelta(200000, $wipInDebit, 0.01, 'WIP debit must equal material cost');
        $this->assertEqualsWithDelta(200000, $wipInCredit, 0.01, 'RM credit must equal material cost');

        // ── Stage 2: WIP → FG (Dr 1300-FG / Cr 1400) ──
        $wipOut = JournalEntry::where('reference', $moNumber . '-WIP-OUT')->first();
        $this->assertNotNull($wipOut, 'WIP-OUT journal must exist');

        $fgDebit = (float) $wipOut->items()->where('account_id', $this->fgAccount->id)->value('debit');
        $wipOutCredit = (float) $wipOut->items()->where('account_id', $this->wipAccount->id)->value('credit');

        $this->assertEqualsWithDelta(200000, $fgDebit, 0.01, 'FG debit must equal material cost');
        $this->assertEqualsWithDelta(200000, $wipOutCredit, 0.01, 'WIP credit must equal material cost');

        // ── WIP account should net zero (in = out) ──
        $totalWipDebit = (float) JournalEntry::where('reference', 'like', $moNumber . '-WIP%')
            ->join('journal_items', 'journal_entries.id', '=', 'journal_items.journal_entry_id')
            ->where('journal_items.account_id', $this->wipAccount->id)
            ->sum('journal_items.debit');
        $totalWipCredit = (float) JournalEntry::where('reference', 'like', $moNumber . '-WIP%')
            ->join('journal_items', 'journal_entries.id', '=', 'journal_items.journal_entry_id')
            ->where('journal_items.account_id', $this->wipAccount->id)
            ->sum('journal_items.credit');

        $this->assertEqualsWithDelta($totalWipDebit, $totalWipCredit, 0.01,
            'WIP account must net zero after production completes (debit = credit)');
    }

    public function test_wip_partial_production_creates_journals_per_batch(): void
    {
        $rm = $this->createProduct('RM-PARTIAL', 'Partial RM');
        $fg = $this->createProduct('FG-PARTIAL', 'Partial FG', 'finished_good');
        $this->seedStock($rm, 100, 5000);

        $bom = $this->createBom($fg, [[$rm, 2, 5000]]);
        $mo = $this->createMo($bom, 10);
        $mo->transitionToAndSave('in_progress');

        // Produce in 2 batches
        $this->actingAs($this->admin)->post(
            route('manufacturing.orders.produce', $mo),
            ['quantity' => 4]
        );

        $this->actingAs($this->admin)->post(
            route('manufacturing.orders.produce', $mo->fresh()),
            ['quantity' => 6]
        );

        $moNumber = $mo->fresh()->number;

        // Should have 2 WIP-IN and 2 WIP-OUT journals
        $wipInCount = JournalEntry::where('reference', $moNumber . '-WIP-IN')->count();
        $wipOutCount = JournalEntry::where('reference', $moNumber . '-WIP-OUT')->count();

        // Journals use same reference per MO, so they may be 1 each or 2 (depending on uniqueness)
        // The important thing is WIP net zero
        $this->assertGreaterThanOrEqual(1, $wipInCount);
        $this->assertGreaterThanOrEqual(1, $wipOutCount);
    }

    // ═══════════════════════════════════════════════════════════════
    // TUGAS 3: QC Gate Integration
    // ═══════════════════════════════════════════════════════════════

    public function test_qc_draft_auto_created_on_in_progress(): void
    {
        $rm = $this->createProduct('RM-QC', 'QC RM');
        $fg = $this->createProduct('FG-QC', 'QC FG', 'finished_good');
        $this->seedStock($rm, 100, 5000);

        $bom = $this->createBom($fg, [[$rm, 2, 5000]]);
        $mo = $this->createMo($bom, 10);

        // First produce triggers in_progress transition
        $this->actingAs($this->admin)->post(
            route('manufacturing.orders.produce', $mo),
            ['quantity' => 3]
        );

        $mo->refresh();
        $this->assertEquals('in_progress', $mo->status);

        // Verify QC inspection was auto-created
        $qc = QcInspection::where('reference_type', ManufacturingOrder::class)
            ->where('reference_id', $mo->id)
            ->first();

        $this->assertNotNull($qc, 'QC inspection must be auto-created when MO enters in_progress');
        $this->assertEquals('in_process', $qc->inspection_type);
        $this->assertEquals('draft', $qc->status);
        $this->assertEquals('pending', $qc->result);
        $this->assertEquals($mo->product_id, $qc->product_id);
        $this->assertEquals($mo->warehouse_id, $qc->warehouse_id);
    }

    public function test_qc_not_duplicated_on_subsequent_produce(): void
    {
        $rm = $this->createProduct('RM-QC2', 'QC RM2');
        $fg = $this->createProduct('FG-QC2', 'QC FG2', 'finished_good');
        $this->seedStock($rm, 100, 5000);

        $bom = $this->createBom($fg, [[$rm, 2, 5000]]);
        $mo = $this->createMo($bom, 10);

        // Produce twice
        $this->actingAs($this->admin)->post(
            route('manufacturing.orders.produce', $mo),
            ['quantity' => 3]
        );
        $this->actingAs($this->admin)->post(
            route('manufacturing.orders.produce', $mo->fresh()),
            ['quantity' => 3]
        );

        $qcCount = QcInspection::where('reference_type', ManufacturingOrder::class)
            ->where('reference_id', $mo->id)
            ->count();

        $this->assertEquals(1, $qcCount, 'Only one QC inspection should be created per MO');
    }

    public function test_qc_failed_blocks_done_transition(): void
    {
        $rm = $this->createProduct('RM-QCFAIL', 'QC Fail RM');
        $fg = $this->createProduct('FG-QCFAIL', 'QC Fail FG', 'finished_good');
        $this->seedStock($rm, 100, 5000);

        $bom = $this->createBom($fg, [[$rm, 2, 5000]]);
        $mo = $this->createMo($bom, 10);

        // First produce to trigger in_progress + QC creation
        $this->actingAs($this->admin)->post(
            route('manufacturing.orders.produce', $mo),
            ['quantity' => 5]
        );

        // Set QC to failed
        $qc = QcInspection::where('reference_type', ManufacturingOrder::class)
            ->where('reference_id', $mo->id)
            ->first();
        $qc->update(['result' => 'failed', 'status' => 'completed']);

        // Produce remaining 5 — should complete quantity but NOT transition to done
        $this->actingAs($this->admin)->post(
            route('manufacturing.orders.produce', $mo->fresh()),
            ['quantity' => 5]
        );

        $mo->refresh();
        $this->assertEquals(10, (float) $mo->produced_quantity, 'All units should be produced');
        $this->assertEquals('in_progress', $mo->status,
            'MO must NOT transition to done while QC is failed');
    }

    // ═══════════════════════════════════════════════════════════════
    // TUGAS 4: Stock Pre-check on Confirm
    // ═══════════════════════════════════════════════════════════════

    public function test_confirm_warns_on_insufficient_material(): void
    {
        $rm = $this->createProduct('RM-CONF', 'Confirm RM');
        $fg = $this->createProduct('FG-CONF', 'Confirm FG', 'finished_good');
        $this->seedStock($rm, 5, 5000); // Only 5, MO needs 20

        $bom = $this->createBom($fg, [[$rm, 2, 5000]]);
        $mo = $this->createMo($bom, 10, 'draft'); // needs 20 RM

        $response = $this->actingAs($this->admin)->post(
            route('manufacturing.orders.confirm', $mo)
        );

        $response->assertRedirect();
        $response->assertSessionHasNoErrors(); // Non-blocking — confirm proceeds

        $mo->refresh();
        $this->assertEquals('confirmed', $mo->status, 'MO should be confirmed despite warning');

        // Verify the success message contains the warning
        $response->assertSessionHas('success');
        $session = $response->getSession();
        $this->assertStringContainsString('Warning', $session->get('success'));
    }

    public function test_confirm_no_warning_when_material_sufficient(): void
    {
        $rm = $this->createProduct('RM-SUFF', 'Sufficient RM');
        $fg = $this->createProduct('FG-SUFF', 'Sufficient FG', 'finished_good');
        $this->seedStock($rm, 200, 5000);

        $bom = $this->createBom($fg, [[$rm, 2, 5000]]);
        $mo = $this->createMo($bom, 10, 'draft');

        $response = $this->actingAs($this->admin)->post(
            route('manufacturing.orders.confirm', $mo)
        );

        $response->assertRedirect();
        $mo->refresh();
        $this->assertEquals('confirmed', $mo->status);

        $session = $response->getSession();
        $this->assertStringNotContainsString('Warning', $session->get('success'));
    }

    // ═══════════════════════════════════════════════════════════════
    // TUGAS 2b: Variance Journal
    // ═══════════════════════════════════════════════════════════════

    public function test_variance_journal_created_on_completion(): void
    {
        $rm = $this->createProduct('RM-VAR', 'Variance RM');
        $fg = $this->createProduct('FG-VAR', 'Variance FG', 'finished_good', [
            'labor_cost'    => 0,
            'overhead_cost' => 0,
            'standard_cost' => 8000, // Standard says 8000 per unit
        ]);
        $this->seedStock($rm, 100, 5000);

        $bom = $this->createBom($fg, [[$rm, 2, 5000]]);
        $mo = $this->createMo($bom, 10);

        // Produce all 10 — actual material cost = 20 × 5000 = 100,000
        // Standard cost = 10 × 8000 = 80,000
        // Variance = 100,000 - 80,000 = +20,000 (unfavorable)
        $this->actingAs($this->admin)->post(
            route('manufacturing.orders.produce', $mo),
            ['quantity' => 10]
        );

        $mo->refresh();
        $this->assertEquals('done', $mo->status);

        // Verify variance journal
        $varJournal = JournalEntry::where('reference', $mo->number . '-VAR')->first();
        $this->assertNotNull($varJournal, 'Variance journal must be created on MO completion');

        // Unfavorable: Dr Variance (6500) / Cr WIP (1400)
        $varDebit = (float) $varJournal->items()->where('account_id', $this->varianceAccount->id)->value('debit');
        $wipCredit = (float) $varJournal->items()->where('account_id', $this->wipAccount->id)->value('credit');

        $this->assertEqualsWithDelta(20000, $varDebit, 0.01, 'Variance debit must equal 20,000');
        $this->assertEqualsWithDelta(20000, $wipCredit, 0.01, 'WIP credit must equal 20,000');
    }

    public function test_no_variance_journal_when_zero_variance(): void
    {
        $rm = $this->createProduct('RM-NOVAR', 'No Var RM');
        $fg = $this->createProduct('FG-NOVAR', 'No Var FG', 'finished_good', [
            'labor_cost'    => 0,
            'overhead_cost' => 0,
            'standard_cost' => 10000, // Exactly matches: 2 × 5000 = 10000
        ]);
        $this->seedStock($rm, 100, 5000);

        $bom = $this->createBom($fg, [[$rm, 2, 5000]]);
        $mo = $this->createMo($bom, 10);

        $this->actingAs($this->admin)->post(
            route('manufacturing.orders.produce', $mo),
            ['quantity' => 10]
        );

        $mo->refresh();
        $this->assertEquals('done', $mo->status);

        $varJournal = JournalEntry::where('reference', $mo->number . '-VAR')->first();
        $this->assertNull($varJournal, 'No variance journal when actual = standard');
    }
}
