<?php

namespace Tests\Feature;

use App\Models\ChartOfAccount;
use App\Models\Client;
use App\Models\CreditNote;
use App\Models\InventoryStock;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\FinanceService;
use App\Services\StockService;
use App\Services\StockValuationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Invoice Module Enhancement — Technical Audit & Enhancement Test Suite
 *
 * Validates:
 *  1. Partial invoicing & invoiced_quantity tracking
 *  2. Draft→Approve→Sent status flow with journal creation on approve
 *  3. PPN 11% calculation with DPP
 *  4. Anti-overclaim protection via max_creditable_amount
 *  5. Invoice numbering format INV/YYYY/MM/SEQ
 *  6. Cancel restores invoiced_quantity on SO items
 *  7. Summary stats on index page
 *  8. SO Items JSON API endpoint
 *  9. Due date validation (after_or_equal:invoice_date)
 * 10. CoA 2110 correctly mapped to PPN Keluaran
 */
class InvoiceModuleEnhancementTest extends TestCase
{
    use RefreshDatabase;

    private FinanceService $financeService;
    private StockService $stockService;
    private StockValuationService $valuationService;
    private User $admin;
    private Warehouse $warehouse;
    private ChartOfAccount $arAccount;
    private ChartOfAccount $revenueAccount;
    private ChartOfAccount $cashAccount;
    private ChartOfAccount $ppnAccount;
    private ChartOfAccount $inventoryAccount;
    private ChartOfAccount $cogsAccount;

    protected function setUp(): void
    {
        parent::setUp();

        $this->financeService = app(FinanceService::class);
        $this->stockService = app(StockService::class);
        $this->valuationService = app(StockValuationService::class);

        $this->admin = User::factory()->create([
            'role'   => 'admin',
            'status' => 'active',
        ]);

        $this->warehouse = Warehouse::create([
            'code' => 'WH-INV', 'name' => 'Invoice Test Warehouse', 'is_active' => true,
        ]);

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
            ['name' => 'PPN Keluaran', 'type' => 'liability', 'is_active' => true,
             'is_tax_account' => true, 'tax_type' => 'ppn_keluaran']
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

    private function createProduct(string $sku, string $name, float $price = 100000): Product
    {
        return Product::create([
            'sku' => $sku, 'name' => $name, 'type' => 'finished_good',
            'cost_price' => 0, 'avg_cost' => 0, 'sell_price' => $price,
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
            'name' => 'Test Client ' . mt_rand(),
            'status' => 'active',
        ]);
    }

    private function createSalesOrder(
        Product $product,
        float $qty,
        float $unitPrice,
        float $taxAmount = 0
    ): SalesOrder {
        $client = $this->createClient();
        $subtotal = round($qty * $unitPrice, 2);
        $total = round($subtotal + $taxAmount, 2);

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

    private function createMultiItemOrder(array $items): SalesOrder
    {
        $client = $this->createClient();
        $subtotal = 0;
        foreach ($items as $item) {
            $subtotal += round($item['qty'] * $item['price'], 2);
        }

        $order = SalesOrder::create([
            'client_id'    => $client->id,
            'warehouse_id' => $this->warehouse->id,
            'order_date'   => now()->toDateString(),
            'status'       => 'draft',
            'subtotal'     => $subtotal,
            'tax_amount'   => 0,
            'discount'     => 0,
            'total'        => $subtotal,
            'created_by'   => $this->admin->id,
        ]);

        foreach ($items as $item) {
            SalesOrderItem::create([
                'sales_order_id' => $order->id,
                'product_id'     => $item['product']->id,
                'quantity'       => $item['qty'],
                'unit_price'     => $item['price'],
                'discount'       => 0,
                'total'          => round($item['qty'] * $item['price'], 2),
            ]);
        }

        return $order;
    }

    private function confirmAndDeliver(SalesOrder $order): void
    {
        $this->actingAs($this->admin)->post(route('sales.confirm', $order));
        $this->actingAs($this->admin)->post(route('sales.deliver', $order->fresh()));
    }

    // ═══════════════════════════════════════════════════════════════
    // TUGAS 1: Partial Invoicing & invoiced_quantity
    // ═══════════════════════════════════════════════════════════════

    public function test_create_invoice_defaults_to_draft_status(): void
    {
        $product = $this->createProduct('INV-DRAFT', 'Draft Test');
        $this->seedStock($product, 20, 50000);

        $order = $this->createSalesOrder($product, 10, 100000);
        $this->confirmAndDeliver($order);

        $invoice = $this->financeService->createInvoiceFromSalesOrder($order->fresh());

        $this->assertEquals('draft', $invoice->status);
    }

    public function test_full_invoicing_creates_all_items(): void
    {
        $product = $this->createProduct('INV-FULL', 'Full Invoice');
        $this->seedStock($product, 20, 50000);

        $order = $this->createSalesOrder($product, 10, 100000);
        $this->confirmAndDeliver($order);

        $invoice = $this->financeService->createInvoiceFromSalesOrder($order->fresh());

        $this->assertEquals(1, $invoice->items()->count());
        $item = $invoice->items()->first();
        $this->assertEqualsWithDelta(10, (float) $item->quantity, 0.01);
        $this->assertEqualsWithDelta(1000000, (float) $invoice->subtotal, 0.01);
    }

    public function test_partial_invoicing_creates_subset_of_items(): void
    {
        $productA = $this->createProduct('INV-PA', 'Product A');
        $productB = $this->createProduct('INV-PB', 'Product B');
        $this->seedStock($productA, 20, 50000);
        $this->seedStock($productB, 20, 50000);

        $order = $this->createMultiItemOrder([
            ['product' => $productA, 'qty' => 10, 'price' => 100000],
            ['product' => $productB, 'qty' => 5,  'price' => 200000],
        ]);
        $this->confirmAndDeliver($order);

        $soItems = SalesOrderItem::where('sales_order_id', $order->id)->get();
        $itemA = $soItems->firstWhere('product_id', $productA->id);
        $itemB = $soItems->firstWhere('product_id', $productB->id);

        // Only invoice 3 of product A
        $invoice = $this->financeService->createInvoiceFromSalesOrder($order->fresh(), [
            'items' => [
                (string) $itemA->id => ['quantity' => 3],
            ],
        ]);

        $this->assertEquals(1, $invoice->items()->count());
        $this->assertEqualsWithDelta(300000, (float) $invoice->subtotal, 0.01);
    }

    public function test_invoiced_quantity_tracked_on_so_items(): void
    {
        $product = $this->createProduct('INV-TRACK', 'Track Qty');
        $this->seedStock($product, 20, 50000);

        $order = $this->createSalesOrder($product, 10, 100000);
        $this->confirmAndDeliver($order);

        $soItem = SalesOrderItem::where('sales_order_id', $order->id)->first();
        $this->assertEqualsWithDelta(0, (float) $soItem->invoiced_quantity, 0.01);

        // Invoice 4 out of 10
        $this->financeService->createInvoiceFromSalesOrder($order->fresh(), [
            'items' => [
                (string) $soItem->id => ['quantity' => 4],
            ],
        ]);

        $soItem->refresh();
        $this->assertEqualsWithDelta(4, (float) $soItem->invoiced_quantity, 0.01);
        $this->assertEqualsWithDelta(6, (float) $soItem->remaining_invoiceable, 0.01);
    }

    public function test_second_partial_invoice_uses_remaining_quantity(): void
    {
        $product = $this->createProduct('INV-2ND', 'Second Partial');
        $this->seedStock($product, 20, 50000);

        $order = $this->createSalesOrder($product, 10, 100000);
        $this->confirmAndDeliver($order);

        $soItem = SalesOrderItem::where('sales_order_id', $order->id)->first();

        // First invoice: 4 units
        $this->financeService->createInvoiceFromSalesOrder($order->fresh(), [
            'items' => [
                (string) $soItem->id => ['quantity' => 4],
            ],
        ]);

        // Second invoice: remaining 6 units
        $invoice2 = $this->financeService->createInvoiceFromSalesOrder($order->fresh());

        $soItem->refresh();
        $this->assertEqualsWithDelta(10, (float) $soItem->invoiced_quantity, 0.01);
        $this->assertEqualsWithDelta(0, (float) $soItem->remaining_invoiceable, 0.01);

        $item2 = $invoice2->items()->first();
        $this->assertEqualsWithDelta(6, (float) $item2->quantity, 0.01);
        $this->assertEqualsWithDelta(600000, (float) $invoice2->subtotal, 0.01);
    }

    public function test_partial_invoice_clamps_to_remaining(): void
    {
        $product = $this->createProduct('INV-CLAMP', 'Clamp Qty');
        $this->seedStock($product, 20, 50000);

        $order = $this->createSalesOrder($product, 10, 100000);
        $this->confirmAndDeliver($order);

        $soItem = SalesOrderItem::where('sales_order_id', $order->id)->first();

        // Try to invoice 15 — should be clamped to 10 (remaining)
        $invoice = $this->financeService->createInvoiceFromSalesOrder($order->fresh(), [
            'items' => [
                (string) $soItem->id => ['quantity' => 15],
            ],
        ]);

        $item = $invoice->items()->first();
        $this->assertEqualsWithDelta(10, (float) $item->quantity, 0.01);
    }

    // ═══════════════════════════════════════════════════════════════
    // TUGAS 2: Approve → Journal + PPN Calculation
    // ═══════════════════════════════════════════════════════════════

    public function test_no_journal_on_draft_create(): void
    {
        $product = $this->createProduct('INV-NOJOURNAL', 'No Journal');
        $this->seedStock($product, 20, 50000);

        $order = $this->createSalesOrder($product, 5, 100000);
        $this->confirmAndDeliver($order);

        $invoice = $this->financeService->createInvoiceFromSalesOrder($order->fresh());

        $journal = JournalEntry::where('reference', $invoice->invoice_number)->first();
        $this->assertNull($journal, 'Draft invoice must NOT have journal entry');
    }

    public function test_approve_creates_journal_with_ppn(): void
    {
        $product = $this->createProduct('INV-APPROVE', 'Approve PPN');
        $this->seedStock($product, 20, 50000);

        $order = $this->createSalesOrder($product, 5, 100000);
        $this->confirmAndDeliver($order);

        // 500,000 subtotal, PPN 11% = 55,000, total = 555,000
        $invoice = $this->financeService->createInvoiceFromSalesOrder($order->fresh(), [
            'tax_rate' => 11,
            'include_tax' => true,
        ]);

        $this->assertEqualsWithDelta(55000, (float) $invoice->tax_amount, 0.01);
        $this->assertEqualsWithDelta(555000, (float) $invoice->total_amount, 0.01);

        // Approve it — this creates the journal
        $this->financeService->approveInvoice($invoice);

        $invoice->refresh();
        $this->assertEquals('unpaid', $invoice->status);

        $journal = JournalEntry::where('reference', $invoice->invoice_number)->first();
        $this->assertNotNull($journal, 'Approved invoice must have journal entry');

        // Dr AR = 555,000
        $arDebit = (float) $journal->items()->where('account_id', $this->arAccount->id)->value('debit');
        $this->assertEqualsWithDelta(555000, $arDebit, 0.01);

        // Cr Revenue = 500,000
        $revCredit = (float) $journal->items()->where('account_id', $this->revenueAccount->id)->value('credit');
        $this->assertEqualsWithDelta(500000, $revCredit, 0.01);

        // Cr PPN = 55,000
        $ppnCredit = (float) $journal->items()->where('account_id', $this->ppnAccount->id)->value('credit');
        $this->assertEqualsWithDelta(55000, $ppnCredit, 0.01);

        // Balanced
        $totalDebit = (float) $journal->items()->sum('debit');
        $totalCredit = (float) $journal->items()->sum('credit');
        $this->assertEqualsWithDelta($totalDebit, $totalCredit, 0.01);
    }

    public function test_approve_without_ppn(): void
    {
        $product = $this->createProduct('INV-NOPPN', 'No PPN');
        $this->seedStock($product, 20, 50000);

        $order = $this->createSalesOrder($product, 5, 100000);
        $this->confirmAndDeliver($order);

        $invoice = $this->financeService->createInvoiceFromSalesOrder($order->fresh(), [
            'include_tax' => false,
        ]);

        $this->assertEqualsWithDelta(0, (float) $invoice->tax_amount, 0.01);
        $this->assertEqualsWithDelta(500000, (float) $invoice->total_amount, 0.01);

        $this->financeService->approveInvoice($invoice);

        $journal = JournalEntry::where('reference', $invoice->invoice_number)->first();
        $this->assertNotNull($journal);

        $ppnLine = $journal->items()->where('account_id', $this->ppnAccount->id)->first();
        $this->assertNull($ppnLine, 'No PPN line when tax_amount is zero');
    }

    public function test_ppn_rate_configurable(): void
    {
        $product = $this->createProduct('INV-RATE', 'Custom Rate');
        $this->seedStock($product, 20, 50000);

        $order = $this->createSalesOrder($product, 10, 100000);
        $this->confirmAndDeliver($order);

        // Use 12% PPN rate
        $invoice = $this->financeService->createInvoiceFromSalesOrder($order->fresh(), [
            'tax_rate' => 12,
            'include_tax' => true,
        ]);

        // Subtotal = 1,000,000, PPN 12% = 120,000, Total = 1,120,000
        $this->assertEqualsWithDelta(120000, (float) $invoice->tax_amount, 0.01);
        $this->assertEqualsWithDelta(1120000, (float) $invoice->total_amount, 0.01);
    }

    // ═══════════════════════════════════════════════════════════════
    // TUGAS 3: Status Flow & Send
    // ═══════════════════════════════════════════════════════════════

    public function test_send_invoice_updates_status_and_timestamp(): void
    {
        $product = $this->createProduct('INV-SEND', 'Send Test');
        $this->seedStock($product, 20, 50000);

        $order = $this->createSalesOrder($product, 5, 100000);
        $this->confirmAndDeliver($order);

        $invoice = $this->financeService->createInvoiceFromSalesOrder($order->fresh());
        $this->assertNull($invoice->sent_at);

        $this->financeService->sendInvoice($invoice);

        $invoice->refresh();
        $this->assertEquals('sent', $invoice->status);
        $this->assertNotNull($invoice->sent_at);
    }

    public function test_approve_via_controller_route(): void
    {
        $product = $this->createProduct('INV-ROUTE-A', 'Route Approve');
        $this->seedStock($product, 20, 50000);

        $order = $this->createSalesOrder($product, 5, 100000);
        $this->confirmAndDeliver($order);

        $invoice = $this->financeService->createInvoiceFromSalesOrder($order->fresh());

        $response = $this->actingAs($this->admin)->post(route('finance.invoices.approve', $invoice));
        $response->assertRedirect();

        $invoice->refresh();
        $this->assertEquals('unpaid', $invoice->status);
    }

    public function test_send_via_controller_route(): void
    {
        $product = $this->createProduct('INV-ROUTE-S', 'Route Send');
        $this->seedStock($product, 20, 50000);

        $order = $this->createSalesOrder($product, 5, 100000);
        $this->confirmAndDeliver($order);

        $invoice = $this->financeService->createInvoiceFromSalesOrder($order->fresh());

        $response = $this->actingAs($this->admin)->post(route('finance.invoices.send', $invoice));
        $response->assertRedirect();

        $invoice->refresh();
        $this->assertEquals('sent', $invoice->status);
    }

    public function test_approve_non_draft_returns_error(): void
    {
        $product = $this->createProduct('INV-NONDRAFT', 'Non Draft');
        $this->seedStock($product, 20, 50000);

        $order = $this->createSalesOrder($product, 5, 100000);
        $this->confirmAndDeliver($order);

        $invoice = $this->financeService->createInvoiceFromSalesOrder($order->fresh());
        $this->financeService->approveInvoice($invoice);

        // Now try to approve again (status=unpaid, not draft)
        $response = $this->actingAs($this->admin)->post(route('finance.invoices.approve', $invoice));
        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    // ═══════════════════════════════════════════════════════════════
    // TUGAS 3b: Anti-Overclaim & Credit Note Linkage
    // ═══════════════════════════════════════════════════════════════

    public function test_max_creditable_amount_calculation(): void
    {
        $client = $this->createClient();
        $so = SalesOrder::create([
            'client_id' => $client->id, 'warehouse_id' => $this->warehouse->id,
            'order_date' => now()->toDateString(), 'status' => 'draft',
            'subtotal' => 1000000, 'tax_amount' => 0, 'discount' => 0,
            'total' => 1000000, 'created_by' => $this->admin->id,
        ]);

        $invoice = Invoice::create([
            'sales_order_id' => $so->id, 'client_id' => $client->id,
            'invoice_date' => now(), 'due_date' => now()->addDays(30),
            'subtotal' => 1000000, 'tax_amount' => 0, 'discount' => 0,
            'total_amount' => 1000000, 'status' => 'unpaid',
        ]);

        $this->assertEqualsWithDelta(1000000, $invoice->max_creditable_amount, 0.01);

        // Create a credit note for 300,000
        CreditNote::create([
            'invoice_id' => $invoice->id,
            'client_id' => $client->id,
            'credit_note_number' => 'CN-TEST-001',
            'date' => now()->toDateString(),
            'amount' => 300000,
            'total_amount' => 300000,
            'reason' => 'Test overclaim',
            'status' => 'approved',
        ]);

        $invoice->refresh();
        $this->assertEqualsWithDelta(700000, $invoice->max_creditable_amount, 0.01);
    }

    public function test_cancelled_credit_note_excluded_from_overclaim(): void
    {
        $client = $this->createClient();
        $so = SalesOrder::create([
            'client_id' => $client->id, 'warehouse_id' => $this->warehouse->id,
            'order_date' => now()->toDateString(), 'status' => 'draft',
            'subtotal' => 1000000, 'tax_amount' => 0, 'discount' => 0,
            'total' => 1000000, 'created_by' => $this->admin->id,
        ]);

        $invoice = Invoice::create([
            'sales_order_id' => $so->id, 'client_id' => $client->id,
            'invoice_date' => now(), 'due_date' => now()->addDays(30),
            'subtotal' => 1000000, 'tax_amount' => 0, 'discount' => 0,
            'total_amount' => 1000000, 'status' => 'unpaid',
        ]);

        // Draft CN should still be counted (only cancelled is excluded)
        CreditNote::create([
            'invoice_id' => $invoice->id,
            'client_id' => $client->id,
            'credit_note_number' => 'CN-TEST-002',
            'date' => now()->toDateString(),
            'amount' => 500000,
            'total_amount' => 500000,
            'reason' => 'Test draft CN',
            'status' => 'draft',
        ]);

        $invoice->refresh();
        // Draft CN is not cancelled, so it reduces creditable amount
        $this->assertEqualsWithDelta(500000, $invoice->max_creditable_amount, 0.01);

        // Approved CN also reduces
        CreditNote::create([
            'invoice_id' => $invoice->id,
            'client_id' => $client->id,
            'credit_note_number' => 'CN-TEST-003',
            'date' => now()->toDateString(),
            'amount' => 200000,
            'total_amount' => 200000,
            'reason' => 'Test approved CN',
            'status' => 'approved',
        ]);

        $invoice->refresh();
        $this->assertEqualsWithDelta(300000, $invoice->max_creditable_amount, 0.01);
    }

    // ═══════════════════════════════════════════════════════════════
    // TUGAS 4: Cancel Restores invoiced_quantity
    // ═══════════════════════════════════════════════════════════════

    public function test_cancel_invoice_restores_invoiced_quantity(): void
    {
        $product = $this->createProduct('INV-CANCEL', 'Cancel Restore');
        $this->seedStock($product, 20, 50000);

        $order = $this->createSalesOrder($product, 10, 100000);
        $this->confirmAndDeliver($order);

        $soItem = SalesOrderItem::where('sales_order_id', $order->id)->first();

        // Invoice 6 units
        $invoice = $this->financeService->createInvoiceFromSalesOrder($order->fresh(), [
            'items' => [(string) $soItem->id => ['quantity' => 6]],
        ]);
        $this->financeService->approveInvoice($invoice);

        $soItem->refresh();
        $this->assertEqualsWithDelta(6, (float) $soItem->invoiced_quantity, 0.01);

        // Cancel via controller (restores invoiced_quantity)
        $response = $this->actingAs($this->admin)->post(route('finance.invoices.cancel', $invoice));
        $response->assertRedirect();

        $soItem->refresh();
        $this->assertEqualsWithDelta(0, (float) $soItem->invoiced_quantity, 0.01);
    }

    public function test_cancel_paid_invoice_returns_error(): void
    {
        $product = $this->createProduct('INV-PAID', 'Paid No Cancel');
        $this->seedStock($product, 20, 50000);

        $order = $this->createSalesOrder($product, 5, 100000);
        $this->confirmAndDeliver($order);

        $invoice = $this->financeService->createInvoiceFromSalesOrder($order->fresh(), [
            'include_tax' => false,
        ]);
        $this->financeService->approveInvoice($invoice);

        $this->financeService->recordPayment($invoice, [
            'amount' => $invoice->total_amount,
            'payment_date' => now()->toDateString(),
            'payment_method' => 'bank_transfer',
        ]);

        $invoice->refresh();
        $this->assertEquals('paid', $invoice->status);

        $response = $this->actingAs($this->admin)->post(route('finance.invoices.cancel', $invoice));
        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    // ═══════════════════════════════════════════════════════════════
    // Invoice Numbering
    // ═══════════════════════════════════════════════════════════════

    public function test_invoice_number_format(): void
    {
        $product = $this->createProduct('INV-NUM', 'Number Format');
        $this->seedStock($product, 20, 50000);

        $order = $this->createSalesOrder($product, 5, 100000);
        $this->confirmAndDeliver($order);

        $invoice = $this->financeService->createInvoiceFromSalesOrder($order->fresh());

        $year = now()->year;
        $month = str_pad(now()->month, 2, '0', STR_PAD_LEFT);

        $this->assertMatchesRegularExpression(
            "/^INV\/{$year}\/{$month}\/\d{5}$/",
            $invoice->invoice_number,
            'Invoice number must follow INV/YYYY/MM/NNNNN format'
        );
    }

    // ═══════════════════════════════════════════════════════════════
    // SO Items JSON API
    // ═══════════════════════════════════════════════════════════════

    public function test_so_items_api_returns_items_with_remaining(): void
    {
        $product = $this->createProduct('INV-API', 'API Test');
        $this->seedStock($product, 20, 50000);

        $order = $this->createSalesOrder($product, 10, 100000);
        $this->confirmAndDeliver($order);

        $response = $this->actingAs($this->admin)
            ->getJson(route('finance.invoices.so-items', ['sales_order_id' => $order->id]));

        $response->assertOk();
        $response->assertJsonStructure(['items', 'client_name']);

        $items = $response->json('items');
        $this->assertCount(1, $items);
        $this->assertEqualsWithDelta(10, (float) $items[0]['remaining'], 0.01);
    }

    public function test_so_items_api_reflects_partial_invoice(): void
    {
        $product = $this->createProduct('INV-API2', 'API Partial');
        $this->seedStock($product, 20, 50000);

        $order = $this->createSalesOrder($product, 10, 100000);
        $this->confirmAndDeliver($order);

        $soItem = SalesOrderItem::where('sales_order_id', $order->id)->first();

        // Invoice 3 of 10
        $this->financeService->createInvoiceFromSalesOrder($order->fresh(), [
            'items' => [(string) $soItem->id => ['quantity' => 3]],
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson(route('finance.invoices.so-items', ['sales_order_id' => $order->id]));

        $items = $response->json('items');
        $this->assertEqualsWithDelta(7, (float) $items[0]['remaining'], 0.01);
    }

    // ═══════════════════════════════════════════════════════════════
    // Index Summary Widgets
    // ═══════════════════════════════════════════════════════════════

    public function test_index_page_loads_with_summary(): void
    {
        $response = $this->actingAs($this->admin)->get(route('finance.invoices.index'));

        $response->assertOk();
        $response->assertViewHas('summary');

        $summary = $response->viewData('summary');
        $this->assertArrayHasKey('total_receivable', $summary);
        $this->assertArrayHasKey('overdue_count', $summary);
        $this->assertArrayHasKey('overdue_amount', $summary);
        $this->assertArrayHasKey('unpaid_count', $summary);
    }

    public function test_summary_counts_unpaid_invoices(): void
    {
        $client = $this->createClient();
        $so = SalesOrder::create([
            'client_id' => $client->id, 'warehouse_id' => $this->warehouse->id,
            'order_date' => now()->toDateString(), 'status' => 'draft',
            'subtotal' => 500000, 'tax_amount' => 0, 'discount' => 0,
            'total' => 500000, 'created_by' => $this->admin->id,
        ]);

        Invoice::create([
            'sales_order_id' => $so->id, 'client_id' => $client->id,
            'invoice_date' => now(), 'due_date' => now()->addDays(30),
            'subtotal' => 500000, 'tax_amount' => 0, 'discount' => 0,
            'total_amount' => 500000, 'paid_amount' => 0, 'status' => 'unpaid',
        ]);

        $response = $this->actingAs($this->admin)->get(route('finance.invoices.index'));
        $summary = $response->viewData('summary');

        $this->assertEquals(1, $summary['unpaid_count']);
        $this->assertEqualsWithDelta(500000, (float) $summary['total_receivable'], 0.01);
    }

    // ═══════════════════════════════════════════════════════════════
    // Due Date Validation
    // ═══════════════════════════════════════════════════════════════

    public function test_due_date_before_invoice_date_rejected(): void
    {
        $product = $this->createProduct('INV-DUEDATE', 'Due Date');
        $this->seedStock($product, 20, 50000);

        $order = $this->createSalesOrder($product, 5, 100000);
        $this->confirmAndDeliver($order);

        $response = $this->actingAs($this->admin)->post(route('finance.invoices.store'), [
            'sales_order_id' => $order->id,
            'invoice_date'   => '2025-03-15',
            'due_date'       => '2025-03-10', // before invoice_date
            'items'          => [],
        ]);

        $response->assertSessionHasErrors('due_date');
    }

    // ═══════════════════════════════════════════════════════════════
    // Model Helpers
    // ═══════════════════════════════════════════════════════════════

    public function test_is_editable_only_for_draft(): void
    {
        $client = $this->createClient();
        $so = SalesOrder::create([
            'client_id' => $client->id, 'warehouse_id' => $this->warehouse->id,
            'order_date' => now()->toDateString(), 'status' => 'draft',
            'subtotal' => 100000, 'tax_amount' => 0, 'discount' => 0,
            'total' => 100000, 'created_by' => $this->admin->id,
        ]);

        $invoice = Invoice::create([
            'sales_order_id' => $so->id, 'client_id' => $client->id,
            'invoice_date' => now(), 'due_date' => now()->addDays(30),
            'subtotal' => 100000, 'tax_amount' => 0, 'discount' => 0,
            'total_amount' => 100000, 'status' => 'draft',
        ]);

        $this->assertTrue($invoice->isEditable());

        $invoice->update(['status' => 'unpaid']);
        $this->assertFalse($invoice->isEditable());
    }

    public function test_is_cancellable_for_non_paid_non_cancelled(): void
    {
        $client = $this->createClient();
        $so = SalesOrder::create([
            'client_id' => $client->id, 'warehouse_id' => $this->warehouse->id,
            'order_date' => now()->toDateString(), 'status' => 'draft',
            'subtotal' => 100000, 'tax_amount' => 0, 'discount' => 0,
            'total' => 100000, 'created_by' => $this->admin->id,
        ]);

        $invoice = Invoice::create([
            'sales_order_id' => $so->id, 'client_id' => $client->id,
            'invoice_date' => now(), 'due_date' => now()->addDays(30),
            'subtotal' => 100000, 'tax_amount' => 0, 'discount' => 0,
            'total_amount' => 100000, 'status' => 'draft',
        ]);

        $this->assertTrue($invoice->isCancellable());

        $invoice->update(['status' => 'paid']);
        $this->assertFalse($invoice->isCancellable());

        $invoice->update(['status' => 'cancelled']);
        $this->assertFalse($invoice->isCancellable());
    }

    // ═══════════════════════════════════════════════════════════════
    // PPN CoA 2110 = PPN Keluaran (not Payroll)
    // ═══════════════════════════════════════════════════════════════

    public function test_coa_2110_is_ppn_keluaran(): void
    {
        $account = ChartOfAccount::where('code', '2110')->first();

        $this->assertNotNull($account);
        $this->assertEquals('PPN Keluaran', $account->name);
    }

    // ═══════════════════════════════════════════════════════════════
    // SalesOrderItem remaining_invoiceable Accessor
    // ═══════════════════════════════════════════════════════════════

    public function test_remaining_invoiceable_accessor(): void
    {
        $product = $this->createProduct('INV-REM', 'Remaining');
        $this->seedStock($product, 20, 50000);

        $order = $this->createSalesOrder($product, 10, 100000);

        $soItem = SalesOrderItem::where('sales_order_id', $order->id)->first();

        $this->assertEqualsWithDelta(10, $soItem->remaining_invoiceable, 0.01);

        $soItem->update(['invoiced_quantity' => 7]);
        $soItem->refresh();

        $this->assertEqualsWithDelta(3, $soItem->remaining_invoiceable, 0.01);
    }

    // ═══════════════════════════════════════════════════════════════
    // i18n Keys Exist
    // ═══════════════════════════════════════════════════════════════

    public function test_i18n_keys_exist_in_all_languages(): void
    {
        $requiredKeys = [
            'inv_create', 'inv_approve', 'inv_send', 'inv_cancel',
            'inv_ppn', 'inv_dpp', 'inv_total_receivable', 'inv_credit_notes',
            'inv_max_creditable', 'inv_grand_total',
        ];

        foreach (['en', 'id', 'zh', 'ko'] as $locale) {
            foreach ($requiredKeys as $key) {
                $translation = __("messages.{$key}", [], $locale);
                $this->assertNotEquals(
                    "messages.{$key}",
                    $translation,
                    "Missing i18n key '{$key}' in locale '{$locale}'"
                );
            }
        }
    }

    // ═══════════════════════════════════════════════════════════════
    // Watermark for Sent status
    // ═══════════════════════════════════════════════════════════════

    public function test_pdf_service_watermark_for_sent_status(): void
    {
        $pdfService = app(\App\Services\PDFService::class);

        // Access the protected method via reflection
        $method = new \ReflectionMethod($pdfService, 'getWatermark');
        $method->setAccessible(true);

        config(['pdf.watermark.enabled' => true]);

        $this->assertEquals('SENT', $method->invoke($pdfService, 'sent'));
        $this->assertEquals('DRAFT', $method->invoke($pdfService, 'draft'));
        $this->assertEquals('PAID', $method->invoke($pdfService, 'paid'));
        $this->assertEquals('CANCELLED', $method->invoke($pdfService, 'cancelled'));
    }

    // ═══════════════════════════════════════════════════════════════
    // Full Lifecycle: Create → Approve → Send → Pay
    // ═══════════════════════════════════════════════════════════════

    public function test_full_lifecycle_draft_approve_send_pay(): void
    {
        $product = $this->createProduct('INV-LIFECYCLE', 'Full Lifecycle');
        $this->seedStock($product, 20, 50000);

        $order = $this->createSalesOrder($product, 10, 100000, 0);
        $this->confirmAndDeliver($order);

        // Step 1: Create (draft, no PPN for simpler assertion)
        $invoice = $this->financeService->createInvoiceFromSalesOrder($order->fresh(), [
            'include_tax' => false,
        ]);
        $this->assertEquals('draft', $invoice->status);

        // Step 2: Approve (unpaid + journal)
        $this->financeService->approveInvoice($invoice);
        $invoice->refresh();
        $this->assertEquals('unpaid', $invoice->status);

        // Step 3: Send
        $this->financeService->sendInvoice($invoice);
        $invoice->refresh();
        $this->assertEquals('sent', $invoice->status);
        $this->assertNotNull($invoice->sent_at);

        // Step 4: Pay full amount
        $this->financeService->recordPayment($invoice, [
            'amount'         => $invoice->total_amount,
            'payment_date'   => now()->toDateString(),
            'payment_method' => 'bank_transfer',
        ]);
        $invoice->refresh();
        $this->assertEquals('paid', $invoice->status);
    }
}
