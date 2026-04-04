<?php

namespace Tests\Feature;

use App\Models\ChartOfAccount;
use App\Models\Client;
use App\Models\InventoryStock;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\StockValuationLayer;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\FinanceService;
use App\Services\StockService;
use App\Services\StockValuationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sales Integrity & Tax Compliance Patch — Verification Test Suite
 *
 * Validates P0/P1 fixes:
 *  1. Invoice journal splits PPN (Dr AR / Cr Revenue / Cr PPN Keluaran 2110)
 *  2. Cancel invoice creates reversing journal (AR & Revenue zeroed)
 *  3. Cancel shipped SO is atomic (DB::transaction)
 *  4. Missing CoA throws RuntimeException (no silent skip)
 *  5. delivered_quantity updated on deliver
 *  6. Admin notification uses role='admin' (not is_admin)
 */
class SalesIntegrityPatchTest extends TestCase
{
    use RefreshDatabase;

    private StockService $stockService;
    private StockValuationService $valuationService;
    private FinanceService $financeService;
    private Warehouse $warehouse;
    private User $admin;
    private ChartOfAccount $arAccount;
    private ChartOfAccount $revenueAccount;
    private ChartOfAccount $cashAccount;
    private ChartOfAccount $ppnAccount;
    private ChartOfAccount $inventoryAccount;
    private ChartOfAccount $cogsAccount;

    protected function setUp(): void
    {
        parent::setUp();

        $this->stockService = app(StockService::class);
        $this->valuationService = app(StockValuationService::class);
        $this->financeService = app(FinanceService::class);

        $this->admin = User::factory()->create([
            'role'   => 'admin',
            'status' => 'active',
        ]);

        $this->warehouse = Warehouse::create([
            'code' => 'WH-SALES', 'name' => 'Sales Warehouse', 'is_active' => true,
        ]);

        // Chart of Accounts — full set (firstOrCreate to avoid conflict with migration seeds)
        $this->cashAccount = ChartOfAccount::firstOrCreate(
            ['code' => '1100'],
            ['name' => 'Cash & Bank', 'type' => 'asset', 'is_active' => true]
        );
        $this->arAccount = ChartOfAccount::firstOrCreate(
            ['code' => '1200'],
            ['name' => 'Accounts Receivable', 'type' => 'asset', 'is_active' => true]
        );
        $this->inventoryAccount = ChartOfAccount::firstOrCreate(
            ['code' => '1300'],
            ['name' => 'Inventory', 'type' => 'asset', 'is_active' => true]
        );
        $this->ppnAccount = ChartOfAccount::firstOrCreate(
            ['code' => '2110'],
            ['name' => 'PPN Keluaran', 'type' => 'liability', 'is_active' => true]
        );
        $this->revenueAccount = ChartOfAccount::firstOrCreate(
            ['code' => '4000'],
            ['name' => 'Revenue', 'type' => 'revenue', 'is_active' => true]
        );
        $this->cogsAccount = ChartOfAccount::firstOrCreate(
            ['code' => '5000'],
            ['name' => 'COGS', 'type' => 'expense', 'is_active' => true]
        );
    }

    // ═══════════════════════════════════════════════════════════════
    // Helpers
    // ═══════════════════════════════════════════════════════════════

    private function createProduct(string $sku, string $name): Product
    {
        return Product::create([
            'sku' => $sku, 'name' => $name, 'type' => 'finished_good',
            'cost_price' => 0, 'avg_cost' => 0, 'sell_price' => 100000,
            'is_active' => true,
        ]);
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

    private function createClient(): Client
    {
        return Client::create([
            'code' => 'CLI-' . mt_rand(1000, 9999),
            'name' => 'Test Client',
            'status' => 'active',
        ]);
    }

    /**
     * Create a sales order with a single item line.
     */
    private function createSalesOrder(
        Product $product,
        float $qty,
        float $unitPrice,
        float $taxAmount = 0
    ): SalesOrder {
        $client = $this->createClient();
        $subtotal = (float) bcmul((string) $qty, (string) $unitPrice, 2);
        $total = (float) bcadd((string) $subtotal, (string) $taxAmount, 2);

        $order = SalesOrder::create([
            'client_id'    => $client->id,
            'warehouse_id' => $this->warehouse->id,
            'order_date'   => now()->toDateString(),
            'status'       => 'draft',
            'subtotal'     => $subtotal,
            'tax_amount'   => $taxAmount,
            'discount'     => 0,
            'total'        => $total,
            'created_by'   => $this->admin->id,
        ]);

        SalesOrderItem::create([
            'sales_order_id' => $order->id,
            'product_id'     => $product->id,
            'quantity'       => $qty,
            'unit_price'     => $unitPrice,
            'discount'       => 0,
            'total'          => $subtotal,
        ]);

        return $order;
    }

    /**
     * Confirm → Deliver → Invoice a sales order through the full lifecycle.
     * Returns the created Invoice.
     */
    private function fullLifecycle(SalesOrder $order): Invoice
    {
        // Confirm
        $this->actingAs($this->admin)->post(route('sales.confirm', $order));
        $order->refresh();

        // Deliver
        $this->actingAs($this->admin)->post(route('sales.deliver', $order));
        $order->refresh();

        // Create invoice via FinanceService
        $invoice = $this->financeService->createInvoiceFromSalesOrder($order);

        return $invoice;
    }

    // ═══════════════════════════════════════════════════════════════
    // TUGAS 1: Invoice Journal Splits VAT
    // ═══════════════════════════════════════════════════════════════

    public function test_invoice_journal_splits_vat_correctly(): void
    {
        $product = $this->createProduct('FG-VAT', 'VAT Product');
        $this->seedStock($product, 10, 50000);

        // Subtotal = 5 × 100,000 = 500,000; PPN = 55,000; Total = 555,000
        $order = $this->createSalesOrder($product, 5, 100000, 55000);

        $invoice = $this->fullLifecycle($order);

        // Verify journal was created with the invoice number
        $journal = JournalEntry::where('reference', $invoice->invoice_number)->first();
        $this->assertNotNull($journal, 'Invoice journal must be created');

        // Dr AR = 555,000 (total_amount)
        $arDebit = (float) $journal->items()
            ->where('account_id', $this->arAccount->id)
            ->value('debit');
        $this->assertEqualsWithDelta(555000, $arDebit, 0.01, 'AR debit must equal total_amount');

        // Cr Revenue = 500,000 (total_amount - tax_amount)
        $revenueCredit = (float) $journal->items()
            ->where('account_id', $this->revenueAccount->id)
            ->value('credit');
        $this->assertEqualsWithDelta(500000, $revenueCredit, 0.01, 'Revenue credit must equal subtotal (excl. tax)');

        // Cr PPN Keluaran = 55,000
        $ppnCredit = (float) $journal->items()
            ->where('account_id', $this->ppnAccount->id)
            ->value('credit');
        $this->assertEqualsWithDelta(55000, $ppnCredit, 0.01, 'PPN Keluaran credit must equal tax_amount');

        // Journal must be balanced
        $totalDebit = (float) $journal->items()->sum('debit');
        $totalCredit = (float) $journal->items()->sum('credit');
        $this->assertEqualsWithDelta($totalDebit, $totalCredit, 0.01, 'Journal must be balanced');
    }

    public function test_invoice_journal_no_ppn_line_when_zero_tax(): void
    {
        $product = $this->createProduct('FG-NOTAX', 'No Tax Product');
        $this->seedStock($product, 10, 50000);

        // No tax
        $order = $this->createSalesOrder($product, 5, 100000, 0);
        $invoice = $this->fullLifecycle($order);

        $journal = JournalEntry::where('reference', $invoice->invoice_number)->first();
        $this->assertNotNull($journal);

        // Should only have 2 lines (AR + Revenue), no PPN line
        $ppnLine = $journal->items()->where('account_id', $this->ppnAccount->id)->first();
        $this->assertNull($ppnLine, 'No PPN line when tax_amount is zero');

        // Revenue = total_amount (since tax=0)
        $revenueCredit = (float) $journal->items()
            ->where('account_id', $this->revenueAccount->id)
            ->value('credit');
        $this->assertEqualsWithDelta(500000, $revenueCredit, 0.01);
    }

    // ═══════════════════════════════════════════════════════════════
    // TUGAS 2: Cancel Invoice Generates Reversing Journal
    // ═══════════════════════════════════════════════════════════════

    public function test_cancel_invoice_generates_reversing_journal(): void
    {
        $product = $this->createProduct('FG-REV', 'Reversible Product');
        $this->seedStock($product, 10, 50000);

        $order = $this->createSalesOrder($product, 5, 100000, 55000);
        $invoice = $this->fullLifecycle($order);

        // Cancel the invoice
        $this->financeService->cancelInvoice($invoice);

        // Verify reversing journal exists
        $revJournal = JournalEntry::where('reference', 'REV-' . $invoice->invoice_number)->first();
        $this->assertNotNull($revJournal, 'Reversing journal must be created on invoice cancel');

        // Dr Revenue = 500,000 (reversal)
        $revenueDebit = (float) $revJournal->items()
            ->where('account_id', $this->revenueAccount->id)
            ->value('debit');
        $this->assertEqualsWithDelta(500000, $revenueDebit, 0.01, 'Revenue must be debited in reversal');

        // Cr AR = 555,000 (reversal)
        $arCredit = (float) $revJournal->items()
            ->where('account_id', $this->arAccount->id)
            ->value('credit');
        $this->assertEqualsWithDelta(555000, $arCredit, 0.01, 'AR must be credited in reversal');

        // Dr PPN Keluaran = 55,000 (reversal)
        $ppnDebit = (float) $revJournal->items()
            ->where('account_id', $this->ppnAccount->id)
            ->value('debit');
        $this->assertEqualsWithDelta(55000, $ppnDebit, 0.01, 'PPN Keluaran must be debited in reversal');

        // Net AR effect = original debit - reversal credit = 0
        $totalArDebit = (float) JournalEntry::join('journal_items', 'journal_entries.id', '=', 'journal_items.journal_entry_id')
            ->where('journal_items.account_id', $this->arAccount->id)
            ->whereIn('journal_entries.reference', [$invoice->invoice_number, 'REV-' . $invoice->invoice_number])
            ->sum('journal_items.debit');
        $totalArCredit = (float) JournalEntry::join('journal_items', 'journal_entries.id', '=', 'journal_items.journal_entry_id')
            ->where('journal_items.account_id', $this->arAccount->id)
            ->whereIn('journal_entries.reference', [$invoice->invoice_number, 'REV-' . $invoice->invoice_number])
            ->sum('journal_items.credit');

        $this->assertEqualsWithDelta($totalArDebit, $totalArCredit, 0.01,
            'Net AR must be zero after invoice + reversal');
    }

    public function test_cancel_invoice_updates_status_and_clears_payments(): void
    {
        $product = $this->createProduct('FG-CANCEL', 'Cancel Product');
        $this->seedStock($product, 10, 50000);

        $order = $this->createSalesOrder($product, 5, 100000, 0);
        $invoice = $this->fullLifecycle($order);

        // Record a partial payment first
        $this->financeService->recordPayment($invoice, [
            'amount'         => 200000,
            'payment_date'   => now()->toDateString(),
            'payment_method' => 'bank_transfer',
        ]);

        $invoice->refresh();
        $this->assertEquals('partial', $invoice->status);

        // Cancel
        $this->financeService->cancelInvoice($invoice);

        $invoice->refresh();
        $this->assertEquals('cancelled', $invoice->status);
        $this->assertEquals(0, (float) $invoice->paid_amount);
        $this->assertEquals(0, $invoice->payments()->count());
    }

    // ═══════════════════════════════════════════════════════════════
    // TUGAS 3: Atomic Cancel for Shipped Orders
    // ═══════════════════════════════════════════════════════════════

    public function test_transaction_rollback_on_failed_cancel(): void
    {
        $product = $this->createProduct('FG-ATOM', 'Atomic Product');
        $this->seedStock($product, 20, 50000);

        $order = $this->createSalesOrder($product, 10, 100000, 0);

        // Confirm + Deliver
        $this->actingAs($this->admin)->post(route('sales.confirm', $order));
        $this->actingAs($this->admin)->post(route('sales.deliver', $order->fresh()));

        $order->refresh();
        $this->assertEquals('shipped', $order->status);

        // Record stock before cancel attempt
        $stockBefore = (float) InventoryStock::where('product_id', $product->id)
            ->where('warehouse_id', $this->warehouse->id)
            ->value('quantity');

        // Rename COGS account code so resolveAccount('5000') fails → RuntimeException
        \Illuminate\Support\Facades\DB::table('chart_of_accounts')
            ->where('id', $this->cogsAccount->id)
            ->update(['code' => '5000_DISABLED']);

        // Cancel should fail (COGS journal will throw)
        $response = $this->actingAs($this->admin)->post(route('sales.cancel', $order));
        $response->assertStatus(500);

        // Verify stock was NOT restored (transaction rolled back)
        $stockAfter = (float) InventoryStock::where('product_id', $product->id)
            ->where('warehouse_id', $this->warehouse->id)
            ->value('quantity');

        $this->assertEquals($stockBefore, $stockAfter,
            'Stock must NOT change when cancel transaction fails');

        // Order must still be shipped
        $order->refresh();
        $this->assertEquals('shipped', $order->status);
    }

    public function test_cancel_shipped_restores_stock_atomically(): void
    {
        $product = $this->createProduct('FG-RESTORE', 'Restorable Product');
        $this->seedStock($product, 20, 50000);

        $order = $this->createSalesOrder($product, 10, 100000, 0);

        $this->actingAs($this->admin)->post(route('sales.confirm', $order));
        $this->actingAs($this->admin)->post(route('sales.deliver', $order->fresh()));

        // Stock should be 10 after delivering 10
        $stockAfterDeliver = (float) InventoryStock::where('product_id', $product->id)
            ->where('warehouse_id', $this->warehouse->id)
            ->value('quantity');
        $this->assertEquals(10, $stockAfterDeliver);

        // Cancel
        $this->actingAs($this->admin)->post(route('sales.cancel', $order->fresh()));

        // Stock should be restored to 20
        $stockAfterCancel = (float) InventoryStock::where('product_id', $product->id)
            ->where('warehouse_id', $this->warehouse->id)
            ->value('quantity');
        $this->assertEquals(20, $stockAfterCancel);

        // COGS reversal journal must exist
        $cogsRev = JournalEntry::where('reference', $order->fresh()->number . '-COGS-REV')->first();
        $this->assertNotNull($cogsRev, 'COGS reversal journal must be created');
    }

    // ═══════════════════════════════════════════════════════════════
    // TUGAS 4: Exception on Missing CoA
    // ═══════════════════════════════════════════════════════════════

    public function test_exception_on_missing_coa(): void
    {
        $product = $this->createProduct('FG-COA', 'CoA Product');
        $this->seedStock($product, 10, 50000);

        $order = $this->createSalesOrder($product, 5, 100000, 0);

        // Confirm + Deliver first (needs CoA intact for COGS)
        $this->actingAs($this->admin)->post(route('sales.confirm', $order));
        $this->actingAs($this->admin)->post(route('sales.deliver', $order->fresh()));

        // Rename AR account code so resolveAccount('1200') fails → RuntimeException
        \Illuminate\Support\Facades\DB::table('chart_of_accounts')
            ->where('id', $this->arAccount->id)
            ->update(['code' => '1200_DISABLED']);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Required Chart of Account '1200' not found");

        $this->financeService->createInvoiceFromSalesOrder($order->fresh());
    }

    public function test_exception_on_missing_ppn_account(): void
    {
        $product = $this->createProduct('FG-PPN', 'PPN Product');
        $this->seedStock($product, 10, 50000);

        // Order WITH tax — needs PPN account
        $order = $this->createSalesOrder($product, 5, 100000, 55000);

        $this->actingAs($this->admin)->post(route('sales.confirm', $order));
        $this->actingAs($this->admin)->post(route('sales.deliver', $order->fresh()));

        // Rename PPN account code so resolveAccount('2110') fails → RuntimeException
        \Illuminate\Support\Facades\DB::table('chart_of_accounts')
            ->where('id', $this->ppnAccount->id)
            ->update(['code' => '2110_DISABLED']);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Required Chart of Account '2110' not found");

        $this->financeService->createInvoiceFromSalesOrder($order->fresh());
    }

    // ═══════════════════════════════════════════════════════════════
    // TUGAS 5: delivered_quantity Updated on Deliver
    // ═══════════════════════════════════════════════════════════════

    public function test_deliver_updates_delivered_quantity(): void
    {
        $product = $this->createProduct('FG-DELQ', 'Delivery Qty Product');
        $this->seedStock($product, 50, 50000);

        $order = $this->createSalesOrder($product, 15, 100000, 0);

        // Confirm
        $this->actingAs($this->admin)->post(route('sales.confirm', $order));

        // Deliver
        $this->actingAs($this->admin)->post(route('sales.deliver', $order->fresh()));

        // Check delivered_quantity updated
        $item = SalesOrderItem::where('sales_order_id', $order->id)->first();
        $this->assertEqualsWithDelta(15, (float) $item->delivered_quantity, 0.01,
            'delivered_quantity must be updated to match delivered amount');
    }

    // ═══════════════════════════════════════════════════════════════
    // TUGAS 4b: Admin Notification Uses role='admin'
    // ═══════════════════════════════════════════════════════════════

    public function test_confirm_sends_notification_to_admin_by_role(): void
    {
        \Illuminate\Support\Facades\Notification::fake();

        $product = $this->createProduct('FG-NOTIF', 'Notify Product');
        $this->seedStock($product, 50, 50000);

        $order = $this->createSalesOrder($product, 5, 100000, 0);

        // Create a non-admin who should NOT receive notification
        $staff = User::factory()->create(['role' => 'staff', 'status' => 'active']);

        $this->actingAs($this->admin)->post(route('sales.confirm', $order));

        \Illuminate\Support\Facades\Notification::assertSentTo(
            $this->admin,
            \App\Notifications\SalesOrderConfirmedNotification::class
        );

        \Illuminate\Support\Facades\Notification::assertNotSentTo(
            $staff,
            \App\Notifications\SalesOrderConfirmedNotification::class
        );
    }

    // ═══════════════════════════════════════════════════════════════
    // Integration: Full Sales Lifecycle with PPN
    // ═══════════════════════════════════════════════════════════════

    public function test_full_lifecycle_with_ppn_produces_correct_gl(): void
    {
        $product = $this->createProduct('FG-FULL', 'Full Lifecycle Product');
        $this->seedStock($product, 20, 50000);

        // Subtotal = 10 × 100,000 = 1,000,000; PPN = 110,000; Total = 1,110,000
        $order = $this->createSalesOrder($product, 10, 100000, 110000);

        // Full lifecycle
        $invoice = $this->fullLifecycle($order);

        // Record payment
        $this->financeService->recordPayment($invoice, [
            'amount'         => 1110000,
            'payment_date'   => now()->toDateString(),
            'payment_method' => 'bank_transfer',
        ]);

        $invoice->refresh();
        $this->assertEquals('paid', $invoice->status);

        // Verify GL entries:
        // 1. Invoice: Dr AR 1,110,000 / Cr Revenue 1,000,000 / Cr PPN 110,000
        // 2. Payment: Dr Cash 1,110,000 / Cr AR 1,110,000
        // 3. COGS: Dr COGS xxx / Cr Inventory xxx

        // Net AR should be zero (invoice debit = cancel reversal + payment credit)
        $arDebit = (float) JournalEntry::join('journal_items', 'journal_entries.id', '=', 'journal_items.journal_entry_id')
            ->where('journal_items.account_id', $this->arAccount->id)
            ->sum('journal_items.debit');
        $arCredit = (float) JournalEntry::join('journal_items', 'journal_entries.id', '=', 'journal_items.journal_entry_id')
            ->where('journal_items.account_id', $this->arAccount->id)
            ->sum('journal_items.credit');

        $this->assertEqualsWithDelta($arDebit, $arCredit, 0.01,
            'AR must net to zero after full payment');

        // Revenue should be 1,000,000 (NOT 1,110,000 — PPN excluded)
        $revenueCredit = (float) JournalEntry::join('journal_items', 'journal_entries.id', '=', 'journal_items.journal_entry_id')
            ->where('journal_items.account_id', $this->revenueAccount->id)
            ->sum('journal_items.credit');

        $this->assertEqualsWithDelta(1000000, $revenueCredit, 0.01,
            'Revenue must exclude PPN');

        // PPN Keluaran should be 110,000
        $ppnCredit = (float) JournalEntry::join('journal_items', 'journal_entries.id', '=', 'journal_items.journal_entry_id')
            ->where('journal_items.account_id', $this->ppnAccount->id)
            ->sum('journal_items.credit');

        $this->assertEqualsWithDelta(110000, $ppnCredit, 0.01,
            'PPN Keluaran must equal tax_amount');
    }
}
