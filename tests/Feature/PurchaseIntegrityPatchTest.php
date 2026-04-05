<?php

namespace Tests\Feature;

use App\Models\ChartOfAccount;
use App\Models\InventoryStock;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Setting;
use App\Models\Supplier;
use App\Models\SupplierBill;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\AccountsPayableService;
use App\Services\StockService;
use App\Services\StockValuationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseIntegrityPatchTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Warehouse $warehouse;
    private Supplier $supplier;
    private StockService $stockService;
    private StockValuationService $valuationService;
    private AccountsPayableService $apService;

    private ChartOfAccount $inventoryAccount;
    private ChartOfAccount $apAccount;
    private ChartOfAccount $expenseAccount;
    private ChartOfAccount $cashAccount;
    private ChartOfAccount $ppnMasukanAccount;
    private ChartOfAccount $ppvAccount;

    protected function setUp(): void
    {
        parent::setUp();

        $this->stockService     = app(StockService::class);
        $this->valuationService = app(StockValuationService::class);
        $this->apService        = app(AccountsPayableService::class);

        $this->admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);

        if (class_exists(Setting::class) && method_exists(Setting::class, 'query')) {
            Setting::firstOrCreate(['key' => 'currency_symbol'], ['value' => 'Rp']);
            Setting::firstOrCreate(['key' => 'currency_code'], ['value' => 'IDR']);
        }

        $this->warehouse = Warehouse::create([
            'code' => 'WH-PI', 'name' => 'Purchase Integrity WH', 'is_active' => true,
        ]);

        $this->supplier = Supplier::create([
            'name' => 'Test Supplier', 'status' => 'active', 'is_pkp' => true,
        ]);

        // Seed required CoA accounts
        $this->inventoryAccount  = ChartOfAccount::create(['code' => '1300', 'name' => 'Inventory', 'type' => 'asset', 'is_active' => true]);
        $this->apAccount         = ChartOfAccount::create(['code' => '2000', 'name' => 'Accounts Payable', 'type' => 'liability', 'is_active' => true]);
        $this->expenseAccount    = ChartOfAccount::create(['code' => '5000', 'name' => 'COGS', 'type' => 'expense', 'is_active' => true]);
        $this->cashAccount       = ChartOfAccount::create(['code' => '1100', 'name' => 'Cash & Bank', 'type' => 'asset', 'is_active' => true]);
        $this->ppnMasukanAccount = ChartOfAccount::create(['code' => '1140', 'name' => 'PPN Masukan', 'type' => 'asset', 'is_active' => true]);
        $this->ppvAccount        = ChartOfAccount::create(['code' => '5101', 'name' => 'Purchase Price Variance', 'type' => 'expense', 'is_active' => true]);
    }

    // ═══════════════════════════════════════════════════════════════
    // Helpers
    // ═══════════════════════════════════════════════════════════════

    private function createProduct(float $costPrice = 10000): Product
    {
        static $counter = 0;
        $counter++;

        return Product::create([
            'sku'        => 'PI-PROD-' . $counter,
            'name'       => 'Test Product ' . $counter,
            'type'       => 'raw_material',
            'cost_price' => $costPrice,
            'avg_cost'   => 0,
            'sell_price' => $costPrice * 2,
            'is_active'  => true,
        ]);
    }

    private function createPO(Product $product, float $qty = 10, float $unitPrice = 10000, float $taxAmount = 0): PurchaseOrder
    {
        $order = PurchaseOrder::create([
            'supplier_id'  => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'order_date'   => now(),
            'tax_amount'   => $taxAmount,
            'status'       => 'draft',
            'created_by'   => $this->admin->id,
        ]);

        $order->items()->create([
            'product_id'        => $product->id,
            'quantity'          => $qty,
            'received_quantity' => 0,
            'unit_price'        => $unitPrice,
            'total'             => bcmul((string) $qty, (string) $unitPrice, 2),
        ]);

        $order->recalculateTotals();

        return $order->fresh(['items']);
    }

    private function confirmPO(PurchaseOrder $order): void
    {
        $order->transitionToAndSave('confirmed');
    }

    private function receivePO(PurchaseOrder $order, ?float $qty = null): void
    {
        $order->load('items');
        $item = $order->items->first();
        $receiveQty = $qty ?? $item->quantity;

        $response = $this->actingAs($this->admin)
            ->post(route('purchasing.receive', $order), [
                'receive' => [
                    ['item_id' => $item->id, 'quantity' => $receiveQty],
                ],
            ]);

        $this->assertTrue(
            $response->getSession()->has('success'),
            'Receive failed: ' . $response->getSession()->get('error', 'no error message')
        );
    }

    private function getAccountBalance(string $code): array
    {
        $account = ChartOfAccount::where('code', $code)->first();
        if (!$account) return ['debit' => 0, 'credit' => 0, 'balance' => 0];

        $items = JournalItem::where('account_id', $account->id)->get();
        $debit  = (float) $items->sum('debit');
        $credit = (float) $items->sum('credit');

        return [
            'debit'   => $debit,
            'credit'  => $credit,
            'balance' => $debit - $credit,
        ];
    }

    // ═══════════════════════════════════════════════════════════════
    // TEST 1: Receive is atomic — rollback on journal failure
    // ═══════════════════════════════════════════════════════════════

    public function test_receive_is_atomic_on_failure(): void
    {
        $product = $this->createProduct(50000);
        $order   = $this->createPO($product, 10, 50000);
        $this->confirmPO($order);

        // Delete the CoA accounts to force RuntimeException during journal creation
        ChartOfAccount::where('code', '1300')->delete();

        $item = $order->items->first();

        // Attempt to receive — should fail and rollback
        $response = $this->actingAs($this->admin)->post(route('purchasing.receive', $order), [
            'receive' => [
                ['item_id' => $item->id, 'quantity' => 10],
            ],
        ]);

        // Stock should NOT have increased (rollback)
        $stockQty = (float) InventoryStock::where('product_id', $product->id)
            ->where('warehouse_id', $this->warehouse->id)
            ->value('quantity');
        $this->assertEquals(0, $stockQty, 'Stock should be 0 after failed receive (atomic rollback)');

        // received_quantity should NOT have changed
        $item->refresh();
        $this->assertEquals(0, (float) $item->received_quantity, 'received_quantity should remain 0 after rollback');

        // PO status should still be confirmed
        $order->refresh();
        $this->assertEquals('confirmed', $order->status, 'PO status should remain confirmed after rollback');
    }

    // ═══════════════════════════════════════════════════════════════
    // TEST 2: Cancel is atomic — rollback on journal failure
    // ═══════════════════════════════════════════════════════════════

    public function test_cancel_is_atomic_on_failure(): void
    {
        $product = $this->createProduct(50000);
        $order   = $this->createPO($product, 10, 50000);
        $this->confirmPO($order);
        $this->receivePO($order, 10);

        $order->refresh();
        $this->assertEquals('received', $order->status);

        // Verify stock is 10
        $stockBefore = (float) InventoryStock::where('product_id', $product->id)
            ->where('warehouse_id', $this->warehouse->id)
            ->value('quantity');
        $this->assertEquals(10, $stockBefore);

        // Delete CoA to force RuntimeException during cancel journal
        // Must remove dependent journal items first (receive created them)
        $apAccountId = ChartOfAccount::where('code', '2000')->value('id');
        JournalItem::where('account_id', $apAccountId)->delete();
        ChartOfAccount::where('code', '2000')->delete();

        // Attempt cancel — should fail and rollback
        $response = $this->actingAs($this->admin)->post(route('purchasing.cancel', $order));

        // Stock should still be 10 (rollback)
        $stockAfter = (float) InventoryStock::where('product_id', $product->id)
            ->where('warehouse_id', $this->warehouse->id)
            ->value('quantity');
        $this->assertEquals(10, $stockAfter, 'Stock should remain 10 after failed cancel (atomic rollback)');

        // PO status should NOT be cancelled
        $order->refresh();
        $this->assertNotEquals('cancelled', $order->status, 'PO should not be cancelled after rollback');
    }

    // ═══════════════════════════════════════════════════════════════
    // TEST 3: Bill creation uses received_quantity, not ordered
    // ═══════════════════════════════════════════════════════════════

    public function test_bill_creation_uses_received_quantity(): void
    {
        $product = $this->createProduct(10000);
        $order   = $this->createPO($product, 100, 10000);
        $this->confirmPO($order);

        // Receive only 60 out of 100
        $this->receivePO($order, 60);

        $order->refresh()->load('items');
        $item = $order->items->first();
        $this->assertEquals(60, (float) $item->received_quantity);

        // Create bill from PO
        $bill = $this->apService->createBillFromPO($order);

        // Bill should be for 60 units, not 100
        $billItem = $bill->items->first();
        $this->assertEquals(60, (float) $billItem->quantity, 'Bill quantity must match received_quantity, not ordered_quantity');

        // Bill subtotal should be 60 * 10000 = 600,000
        $expectedSubtotal = bcmul('60', '10000', 2);
        $this->assertEquals((float) $expectedSubtotal, (float) $bill->subtotal, 'Bill subtotal should reflect received qty');
    }

    // ═══════════════════════════════════════════════════════════════
    // TEST 4: Bill from PO with zero received → exception
    // ═══════════════════════════════════════════════════════════════

    public function test_bill_from_po_with_no_received_items_throws(): void
    {
        $product = $this->createProduct(10000);
        $order   = $this->createPO($product, 100, 10000);
        $this->confirmPO($order);

        // No items received yet — bill creation should fail
        $this->expectException(\InvalidArgumentException::class);
        $this->apService->createBillFromPO($order);
    }

    // ═══════════════════════════════════════════════════════════════
    // TEST 5: No double-counting on AP (PO-linked bill)
    // ═══════════════════════════════════════════════════════════════

    public function test_no_double_counting_on_ap(): void
    {
        $product = $this->createProduct(50000);
        $order   = $this->createPO($product, 10, 50000);
        $this->confirmPO($order);
        $this->receivePO($order, 10);

        // After receive: AP should have credit of 500,000 (10 * 50,000)
        $apAfterReceive = $this->getAccountBalance('2000');
        $this->assertEquals(500000, $apAfterReceive['credit'], 'AP credit after receive = 500,000');

        // Create & post bill for same PO (same price → no variance)
        $order->refresh();
        $bill = $this->apService->createBillFromPO($order);
        $this->apService->postBill($bill);

        // After bill post: AP credit should still be 500,000 (NOT 1,000,000)
        // Because bill is linked to PO, no additional AP credit for DPP
        $apAfterBill = $this->getAccountBalance('2000');
        $this->assertEquals(500000, $apAfterBill['credit'], 'AP credit after bill post must NOT double (still 500,000)');
    }

    // ═══════════════════════════════════════════════════════════════
    // TEST 6: PPN Masukan journal entry on bill post
    // ═══════════════════════════════════════════════════════════════

    public function test_ppn_masukan_journal_entry(): void
    {
        $product = $this->createProduct(100000);
        $order   = $this->createPO($product, 10, 100000, 110000); // tax = 110,000 (11% of 1,000,000)
        $this->confirmPO($order);
        $this->receivePO($order, 10);

        // Create bill with PPN
        $order->refresh();
        $bill = $this->apService->createBillFromPO($order, [
            'tax_amount' => 110000,
        ]);

        // Manually set dpp for clarity
        $bill->update(['dpp' => 1000000, 'total' => 1110000]);

        $this->apService->postBill($bill);

        // PPN Masukan (1140) should have debit of 110,000
        $ppnBalance = $this->getAccountBalance('1140');
        $this->assertEquals(110000, $ppnBalance['debit'], 'PPN Masukan debit should be 110,000');

        // AP should have additional credit of 110,000 (for PPN only)
        // Total AP credit = 1,000,000 (from receive) + 110,000 (from bill PPN) = 1,110,000
        $apBalance = $this->getAccountBalance('2000');
        $this->assertEquals(1110000, $apBalance['credit'], 'AP credit should be 1,110,000 (receive DPP + bill PPN)');
    }

    // ═══════════════════════════════════════════════════════════════
    // TEST 7: Purchase Price Variance journal (bill price > PO price)
    // ═══════════════════════════════════════════════════════════════

    public function test_purchase_price_variance_unfavorable(): void
    {
        $product = $this->createProduct(50000);
        $order   = $this->createPO($product, 10, 50000); // PO @ 50,000/unit
        $this->confirmPO($order);
        $this->receivePO($order, 10);

        // AP accrued = 10 * 50,000 = 500,000
        $apAfterReceive = $this->getAccountBalance('2000');
        $this->assertEquals(500000, $apAfterReceive['credit']);

        // Create bill with HIGHER price: DPP = 550,000 (variance = 50,000 unfavorable)
        $order->refresh();
        $bill = $this->apService->createBillFromPO($order);
        // Override bill items to reflect higher price
        $bill->items()->delete();
        $bill->items()->create([
            'product_id'  => $product->id,
            'description' => $product->name,
            'quantity'    => 10,
            'price'       => 55000,
        ]);
        $bill->update([
            'subtotal'   => 550000,
            'dpp'        => 550000,
            'tax_amount' => 0,
            'total'      => 550000,
        ]);

        $this->apService->postBill($bill);

        // PPV (5101) should have debit of 50,000 (unfavorable)
        $ppvBalance = $this->getAccountBalance('5101');
        $this->assertEquals(50000, $ppvBalance['debit'], 'PPV debit = 50,000 (unfavorable variance)');

        // AP total credit = 500,000 (receive) + 50,000 (variance portion in bill) = 550,000
        $apAfterBill = $this->getAccountBalance('2000');
        $this->assertEquals(550000, $apAfterBill['credit'], 'AP credit = 550,000 (receive + variance)');
    }

    // ═══════════════════════════════════════════════════════════════
    // TEST 8: Purchase Price Variance journal (bill price < PO price)
    // ═══════════════════════════════════════════════════════════════

    public function test_purchase_price_variance_favorable(): void
    {
        $product = $this->createProduct(50000);
        $order   = $this->createPO($product, 10, 50000);
        $this->confirmPO($order);
        $this->receivePO($order, 10);

        // Create bill with LOWER price: DPP = 450,000 (variance = -50,000 favorable)
        $order->refresh();
        $bill = $this->apService->createBillFromPO($order);
        $bill->items()->delete();
        $bill->items()->create([
            'product_id'  => $product->id,
            'description' => $product->name,
            'quantity'    => 10,
            'price'       => 45000,
        ]);
        $bill->update([
            'subtotal'   => 450000,
            'dpp'        => 450000,
            'tax_amount' => 0,
            'total'      => 450000,
        ]);

        $this->apService->postBill($bill);

        // PPV (5101) should have credit of 50,000 (favorable)
        $ppvBalance = $this->getAccountBalance('5101');
        $this->assertEquals(50000, $ppvBalance['credit'], 'PPV credit = 50,000 (favorable variance)');

        // AP total = receive credit 500,000 MINUS favorable debit 50,000 = net 450,000
        // The favorable PPV journals: Dr AP 50,000 / Cr PPV 50,000
        $apAfterBill = $this->getAccountBalance('2000');
        $netApCredit = $apAfterBill['credit'] - $apAfterBill['debit'];
        $this->assertEquals(450000, $netApCredit, 'AP net credit = 450,000 (receive - favorable variance)');
    }

    // ═══════════════════════════════════════════════════════════════
    // TEST 9: Non-PO bill posts full expense + PPN Masukan
    // ═══════════════════════════════════════════════════════════════

    public function test_non_po_bill_posts_expense_and_ppn(): void
    {
        // Create a service bill (no PO)
        $bill = $this->apService->createBill([
            'supplier_id' => $this->supplier->id,
            'bill_date'   => now()->format('Y-m-d'),
            'due_date'    => now()->addDays(30)->format('Y-m-d'),
            'tax_amount'  => 55000,
            'items'       => [
                ['description' => 'Consulting Service', 'quantity' => 1, 'price' => 500000],
            ],
        ]);
        $bill->update(['dpp' => 500000, 'total' => 555000]);

        $this->apService->postBill($bill);

        // Expense (5000) should have debit of 500,000
        $expenseBalance = $this->getAccountBalance('5000');
        $this->assertEquals(500000, $expenseBalance['debit'], 'Expense debit = 500,000 (DPP)');

        // PPN Masukan (1140) should have debit of 55,000
        $ppnBalance = $this->getAccountBalance('1140');
        $this->assertEquals(55000, $ppnBalance['debit'], 'PPN Masukan debit = 55,000');

        // AP (2000) should have credit of 555,000
        $apBalance = $this->getAccountBalance('2000');
        $this->assertEquals(555000, $apBalance['credit'], 'AP credit = 555,000 (DPP + PPN)');
    }

    // ═══════════════════════════════════════════════════════════════
    // TEST 10: RuntimeException when CoA 1300 missing (journal fail)
    // ═══════════════════════════════════════════════════════════════

    public function test_journal_purchase_receive_throws_on_missing_coa(): void
    {
        ChartOfAccount::where('code', '1300')->delete();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Required CoA accounts for Purchase Receive not found');

        $this->valuationService->journalPurchaseReceive(
            'PO-TEST',
            now()->toDateString(),
            100000,
            'Test receive'
        );
    }

    // ═══════════════════════════════════════════════════════════════
    // TEST 11: RuntimeException when CoA 2000 missing (cancel journal)
    // ═══════════════════════════════════════════════════════════════

    public function test_journal_purchase_cancel_throws_on_missing_coa(): void
    {
        ChartOfAccount::where('code', '2000')->delete();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Required CoA accounts for Purchase Cancel not found');

        $this->valuationService->journalPurchaseCancel(
            'PO-CANCEL',
            now()->toDateString(),
            100000,
            'Test cancel'
        );
    }

    // ═══════════════════════════════════════════════════════════════
    // TEST 12: Successful PO receive + status transition
    // ═══════════════════════════════════════════════════════════════

    public function test_successful_receive_creates_stock_and_journal(): void
    {
        $product = $this->createProduct(25000);
        $order   = $this->createPO($product, 20, 25000);
        $this->confirmPO($order);

        // Partial receive: 15 out of 20
        $this->receivePO($order, 15);

        $order->refresh();
        $this->assertEquals('partial', $order->status);

        // Stock should be 15
        $stockQty = (float) InventoryStock::where('product_id', $product->id)
            ->where('warehouse_id', $this->warehouse->id)
            ->value('quantity');
        $this->assertEquals(15, $stockQty);

        // Inventory (1300) debit = 15 * 25,000 = 375,000
        $invBalance = $this->getAccountBalance('1300');
        $this->assertEquals(375000, $invBalance['debit']);

        // AP (2000) credit = 375,000
        $apBalance = $this->getAccountBalance('2000');
        $this->assertEquals(375000, $apBalance['credit']);

        // Receive remaining 5
        $order->load('items');
        $item = $order->items->first();
        $this->actingAs($this->admin)->post(route('purchasing.receive', $order), [
            'receive' => [['item_id' => $item->id, 'quantity' => 5]],
        ]);

        $order->refresh();
        $this->assertEquals('received', $order->status);

        // Total inventory debit = 500,000
        $invBalance2 = $this->getAccountBalance('1300');
        $this->assertEquals(500000, $invBalance2['debit']);
    }
}
