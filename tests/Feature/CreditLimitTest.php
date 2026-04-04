<?php

namespace Tests\Feature;

use App\Models\ChartOfAccount;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\ClientService;
use App\Services\FinanceService;
use App\Services\StockService;
use App\Services\StockValuationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreditLimitTest extends TestCase
{
    use RefreshDatabase;

    private ClientService $clientService;
    private StockService $stockService;
    private StockValuationService $valuationService;
    private FinanceService $financeService;
    private User $admin;
    private Warehouse $warehouse;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clientService = app(ClientService::class);
        $this->stockService = app(StockService::class);
        $this->valuationService = app(StockValuationService::class);
        $this->financeService = app(FinanceService::class);

        $this->admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);

        $this->warehouse = Warehouse::create([
            'code' => 'WH-CL', 'name' => 'Credit Limit Warehouse', 'is_active' => true,
        ]);

        // Seed required CoA accounts
        foreach ([
            ['code' => '1100', 'name' => 'Cash & Bank', 'type' => 'asset'],
            ['code' => '1200', 'name' => 'Accounts Receivable', 'type' => 'asset'],
            ['code' => '1300', 'name' => 'Inventory', 'type' => 'asset'],
            ['code' => '2110', 'name' => 'PPN Keluaran', 'type' => 'liability'],
            ['code' => '4000', 'name' => 'Revenue', 'type' => 'revenue'],
            ['code' => '5000', 'name' => 'COGS', 'type' => 'expense'],
        ] as $acct) {
            ChartOfAccount::firstOrCreate(['code' => $acct['code']], array_merge($acct, ['is_active' => true]));
        }
    }

    // ═══════════════════════════════════════════════════════════════
    // Helpers
    // ═══════════════════════════════════════════════════════════════

    private function createClient(float $creditLimit = 0, bool $blocked = false, int $paymentTerms = 30): Client
    {
        return Client::create([
            'code'              => 'CLI-' . mt_rand(1000, 9999),
            'name'              => 'Test Client',
            'status'            => 'active',
            'credit_limit'      => $creditLimit,
            'payment_terms'     => $paymentTerms,
            'is_credit_blocked' => $blocked,
        ]);
    }

    private function createProduct(): Product
    {
        static $counter = 0;
        $counter++;
        return Product::create([
            'sku'        => 'CL-FG-' . $counter,
            'name'       => 'Credit Test Product ' . $counter,
            'type'       => 'finished_good',
            'cost_price' => 0,
            'avg_cost'   => 50000,
            'sell_price' => 100000,
            'is_active'  => true,
        ]);
    }

    private function seedStock(Product $product, float $qty): void
    {
        $movement = $this->stockService->processMovement([
            'product_id'     => $product->id,
            'warehouse_id'   => $this->warehouse->id,
            'type'           => 'in',
            'quantity'       => $qty,
            'unit_cost'      => 50000,
            'reference_type' => 'purchase_order',
            'reference_id'   => 1,
        ]);

        $this->valuationService->recordIncoming(
            $product->id, $this->warehouse->id, $qty, 50000,
            $movement, 'purchase_order', 1, 'Seed stock'
        );
    }

    private function createSalesOrder(Client $client, float $total, ?Product $product = null, float $qty = 1): SalesOrder
    {
        $product = $product ?? $this->createProduct();
        $unitPrice = (float) bcdiv((string) $total, (string) $qty, 2);

        $order = SalesOrder::create([
            'client_id'    => $client->id,
            'warehouse_id' => $this->warehouse->id,
            'order_date'   => now()->toDateString(),
            'status'       => 'draft',
            'subtotal'     => $total,
            'tax_amount'   => 0,
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
            'total'          => $total,
        ]);

        return $order;
    }

    /**
     * Create an unpaid invoice directly for a client (simulates outstanding AR).
     * Must link to a real SO since sales_order_id is NOT NULL.
     */
    private function createOutstandingInvoice(Client $client, float $amount, ?string $dueDate = null): Invoice
    {
        // Create a minimal completed SO to satisfy the FK
        $product = $this->createProduct();
        $so = SalesOrder::create([
            'client_id'    => $client->id,
            'warehouse_id' => $this->warehouse->id,
            'order_date'   => now()->toDateString(),
            'status'       => 'completed',
            'subtotal'     => $amount,
            'tax_amount'   => 0,
            'discount'     => 0,
            'total'        => $amount,
            'created_by'   => $this->admin->id,
        ]);
        SalesOrderItem::create([
            'sales_order_id' => $so->id,
            'product_id'     => $product->id,
            'quantity'       => 1,
            'unit_price'     => $amount,
            'discount'       => 0,
            'total'          => $amount,
        ]);

        return Invoice::create([
            'sales_order_id' => $so->id,
            'client_id'      => $client->id,
            'invoice_date'   => now()->toDateString(),
            'due_date'       => $dueDate ?? now()->addDays(30)->toDateString(),
            'subtotal'       => $amount,
            'tax_amount'     => 0,
            'discount'       => 0,
            'total_amount'   => $amount,
            'paid_amount'    => 0,
            'status'         => 'unpaid',
            'created_by'     => $this->admin->id,
        ]);
    }

    // ═══════════════════════════════════════════════════════════════
    // Tests
    // ═══════════════════════════════════════════════════════════════

    public function test_cannot_confirm_order_when_credit_limit_exceeded(): void
    {
        // Client with 1,000,000 credit limit
        $client = $this->createClient(creditLimit: 1000000);

        // Existing exposure: 800,000 in unpaid invoices
        $this->createOutstandingInvoice($client, 800000);

        // New order worth 300,000 → total exposure 1,100,000 > limit 1,000,000
        $product = $this->createProduct();
        $this->seedStock($product, 100);
        $order = $this->createSalesOrder($client, 300000, $product, 3);

        $response = $this->actingAs($this->admin)->post(route('sales.confirm', $order));

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $order->refresh();
        $this->assertEquals('draft', $order->status, 'Order must remain draft when credit limit exceeded');

        // Verify the error message contains the exposure/limit information
        $errorMsg = session('error');
        $this->assertStringContainsString('800', $errorMsg);
        $this->assertStringContainsString('1,000,000', $errorMsg);
    }

    public function test_cannot_confirm_order_when_client_is_manually_blocked(): void
    {
        // Client is manually credit-blocked (even with no limit set)
        $client = $this->createClient(creditLimit: 0, blocked: true);

        $product = $this->createProduct();
        $this->seedStock($product, 100);
        $order = $this->createSalesOrder($client, 100000, $product, 1);

        $response = $this->actingAs($this->admin)->post(route('sales.confirm', $order));

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $order->refresh();
        $this->assertEquals('draft', $order->status, 'Order must remain draft when client is credit-blocked');
    }

    public function test_cannot_confirm_order_with_overdue_invoices(): void
    {
        // Client with generous limit but overdue invoices
        $client = $this->createClient(creditLimit: 10000000);

        // Invoice overdue by 15 days (> 7-day grace)
        $this->createOutstandingInvoice($client, 100000, now()->subDays(15)->toDateString());

        $product = $this->createProduct();
        $this->seedStock($product, 100);
        $order = $this->createSalesOrder($client, 50000, $product, 1);

        $response = $this->actingAs($this->admin)->post(route('sales.confirm', $order));

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $order->refresh();
        $this->assertEquals('draft', $order->status, 'Order must remain draft when client has overdue invoices');

        // Verify error mentions overdue
        $errorMsg = session('error');
        $this->assertStringContainsString('1', $errorMsg); // 1 overdue invoice
    }

    public function test_can_confirm_order_when_limit_is_zero_or_sufficient(): void
    {
        // Scenario 1: Credit limit = 0 (unlimited) → should confirm
        $client1 = $this->createClient(creditLimit: 0);
        $product1 = $this->createProduct();
        $this->seedStock($product1, 100);
        $order1 = $this->createSalesOrder($client1, 5000000, $product1, 5);

        $response1 = $this->actingAs($this->admin)->post(route('sales.confirm', $order1));

        $order1->refresh();
        $this->assertEquals('confirmed', $order1->status, 'Order should be confirmed when credit limit is 0 (unlimited)');

        // Scenario 2: Credit limit sufficient → should confirm
        $client2 = $this->createClient(creditLimit: 2000000);
        $product2 = $this->createProduct();
        $this->seedStock($product2, 100);
        $order2 = $this->createSalesOrder($client2, 500000, $product2, 5);

        $response2 = $this->actingAs($this->admin)->post(route('sales.confirm', $order2));

        $order2->refresh();
        $this->assertEquals('confirmed', $order2->status, 'Order should be confirmed when within credit limit');
    }

    // ═══════════════════════════════════════════════════════════════
    // Exposure Calculation Tests
    // ═══════════════════════════════════════════════════════════════

    public function test_exposure_includes_outstanding_ar_and_confirmed_orders(): void
    {
        $client = $this->createClient(creditLimit: 5000000);

        // 300,000 in unpaid invoices
        $this->createOutstandingInvoice($client, 200000);
        $this->createOutstandingInvoice($client, 100000);

        // 500,000 in confirmed (not yet invoiced) SO
        $product = $this->createProduct();
        $this->seedStock($product, 100);
        $confirmedOrder = $this->createSalesOrder($client, 500000, $product, 5);
        $this->actingAs($this->admin)->post(route('sales.confirm', $confirmedOrder));

        $exposure = $this->clientService->calculateTotalExposure($client->id);

        // Exposure = 300,000 (AR) + 500,000 (confirmed SO) = 800,000
        $this->assertEqualsWithDelta(800000, (float) $exposure, 0.01,
            'Exposure must include outstanding AR + confirmed SO totals');
    }

    public function test_overdue_check_respects_grace_period(): void
    {
        $client = $this->createClient();

        // Invoice due 5 days ago (within 7-day grace) → should NOT block
        $this->createOutstandingInvoice($client, 100000, now()->subDays(5)->toDateString());

        $overdue = $this->clientService->checkOverdueInvoices($client->id);
        $this->assertFalse($overdue['blocked'], 'Invoice within grace period should not block');

        // Invoice due 10 days ago (beyond 7-day grace) → should block
        $this->createOutstandingInvoice($client, 100000, now()->subDays(10)->toDateString());

        $overdue = $this->clientService->checkOverdueInvoices($client->id);
        $this->assertTrue($overdue['blocked'], 'Invoice beyond grace period should block');
        $this->assertEquals(1, $overdue['count']);
    }

    public function test_paid_invoices_do_not_count_in_exposure(): void
    {
        $client = $this->createClient(creditLimit: 500000);

        // Paid invoice — should NOT count (use helper to create with SO link, then mark paid)
        $invoice = $this->createOutstandingInvoice($client, 400000);
        $invoice->update(['paid_amount' => 400000, 'status' => 'paid']);

        $exposure = $this->clientService->calculateTotalExposure($client->id);
        $this->assertEqualsWithDelta(0, (float) $exposure, 0.01, 'Paid invoices should not count in exposure');
    }

    public function test_client_show_page_displays_credit_status(): void
    {
        $client = $this->createClient(creditLimit: 1000000);
        $this->createOutstandingInvoice($client, 300000);

        $response = $this->actingAs($this->admin)->get(route('clients.show', $client));

        $response->assertStatus(200);
        // number_format uses '.' as thousands separator in the blade
        $response->assertSee('1.000.000'); // credit limit
        $response->assertSee('300.000');   // exposure
    }
}
