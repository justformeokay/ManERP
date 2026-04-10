<?php

namespace Tests\Feature;

use App\Http\Controllers\ClientController;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\SalesOrder;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrmClientsDashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Warehouse $warehouse;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role'   => User::ROLE_ADMIN,
            'status' => User::STATUS_ACTIVE,
        ]);

        $this->warehouse = Warehouse::create([
            'code' => 'WH-CRM', 'name' => 'CRM Warehouse', 'is_active' => true,
        ]);
    }

    // ═══════════════════════════════════════════════════════════════
    // HELPERS
    // ═══════════════════════════════════════════════════════════════

    private function createClient(array $overrides = []): Client
    {
        return Client::create(array_merge([
            'code'   => 'CLI-' . mt_rand(1000, 9999),
            'name'   => 'Test Client',
            'status' => 'active',
            'type'   => 'customer',
        ], $overrides));
    }

    private function createSalesOrder(Client $client, float $total, string $status = 'confirmed'): SalesOrder
    {
        return SalesOrder::create([
            'client_id'    => $client->id,
            'warehouse_id' => $this->warehouse->id,
            'order_date'   => now()->toDateString(),
            'status'       => $status,
            'subtotal'     => $total,
            'tax_amount'   => 0,
            'discount'     => 0,
            'total'        => $total,
            'created_by'   => $this->admin->id,
        ]);
    }

    private function createInvoice(Client $client, float $total, float $paid = 0, string $status = 'unpaid', ?string $date = null): Invoice
    {
        $order = $this->createSalesOrder($client, $total, 'confirmed');

        return Invoice::create([
            'invoice_number'  => 'INV-' . mt_rand(10000, 99999),
            'sales_order_id'  => $order->id,
            'client_id'       => $client->id,
            'invoice_date'    => $date ?? now()->toDateString(),
            'due_date'        => now()->addDays(30)->toDateString(),
            'subtotal'        => $total,
            'tax_amount'      => 0,
            'discount'        => 0,
            'total_amount'    => $total,
            'paid_amount'     => $paid,
            'status'          => $status,
            'created_by'      => $this->admin->id,
        ]);
    }

    // ═══════════════════════════════════════════════════════════════
    // TUGAS 1: Summary Cards
    // ═══════════════════════════════════════════════════════════════

    public function test_index_returns_summary_with_total_active_clients(): void
    {
        $this->createClient(['status' => 'active']);
        $this->createClient(['status' => 'active']);
        $this->createClient(['status' => 'inactive']);

        $response = $this->actingAs($this->admin)->get(route('clients.index'));

        $response->assertOk();
        $summary = $response->viewData('summary');
        $this->assertEquals(2, $summary['totalActiveClients']);
    }

    public function test_index_returns_summary_with_total_receivables(): void
    {
        $client = $this->createClient();
        $this->createInvoice($client, 500000, 100000, 'partial');
        $this->createInvoice($client, 300000, 0, 'unpaid');
        $this->createInvoice($client, 200000, 200000, 'paid');

        $response = $this->actingAs($this->admin)->get(route('clients.index'));

        $summary = $response->viewData('summary');
        // receivables = (500000-100000) + (300000-0) = 700000. Paid invoice excluded.
        $this->assertEquals(700000, (float) $summary['totalReceivables']);
    }

    public function test_index_returns_monthly_sales_growth(): void
    {
        $client = $this->createClient();

        // Previous month: 200000
        SalesOrder::create([
            'client_id'    => $client->id,
            'warehouse_id' => $this->warehouse->id,
            'order_date'   => now()->subMonth()->toDateString(),
            'status'       => 'confirmed',
            'subtotal'     => 200000,
            'tax_amount'   => 0,
            'discount'     => 0,
            'total'        => 200000,
            'created_by'   => $this->admin->id,
        ]);

        // Current month: 300000
        $this->createSalesOrder($client, 300000);

        $response = $this->actingAs($this->admin)->get(route('clients.index'));

        $summary = $response->viewData('summary');
        // Growth = ((300000 - 200000) / 200000) * 100 = 50%
        $this->assertEquals(50.0, $summary['monthlySalesGrowth']);
    }

    public function test_index_growth_100_when_no_previous_month(): void
    {
        $client = $this->createClient();
        $this->createSalesOrder($client, 100000);

        $response = $this->actingAs($this->admin)->get(route('clients.index'));

        $summary = $response->viewData('summary');
        $this->assertEquals(100.0, $summary['monthlySalesGrowth']);
    }

    public function test_index_growth_zero_when_no_sales_at_all(): void
    {
        $this->createClient();

        $response = $this->actingAs($this->admin)->get(route('clients.index'));

        $summary = $response->viewData('summary');
        $this->assertEquals(0.0, $summary['monthlySalesGrowth']);
    }

    public function test_index_returns_top_spender(): void
    {
        $small = $this->createClient(['name' => 'Small Buyer']);
        $big   = $this->createClient(['name' => 'Big Buyer']);

        $this->createSalesOrder($small, 100000);
        $this->createSalesOrder($big, 500000);
        $this->createSalesOrder($big, 300000);

        $response = $this->actingAs($this->admin)->get(route('clients.index'));

        $summary = $response->viewData('summary');
        $this->assertNotNull($summary['topSpender']);
        $this->assertEquals('Big Buyer', $summary['topSpender']->name);
        $this->assertEquals(800000, (float) $summary['topSpender']->lifetime_sales);
    }

    // ═══════════════════════════════════════════════════════════════
    // TUGAS 2: Eager-Loaded Table Data
    // ═══════════════════════════════════════════════════════════════

    public function test_index_client_has_sales_to_date_aggregate(): void
    {
        $client = $this->createClient();
        $this->createSalesOrder($client, 500000, 'confirmed');
        $this->createSalesOrder($client, 200000, 'completed');
        $this->createSalesOrder($client, 100000, 'cancelled'); // excluded
        $this->createSalesOrder($client, 50000, 'draft');      // excluded

        $response = $this->actingAs($this->admin)->get(route('clients.index'));

        $clients = $response->viewData('clients');
        $first = $clients->first();
        $this->assertEquals(700000, (float) $first->sales_to_date);
    }

    public function test_index_client_has_current_balance_aggregate(): void
    {
        $client = $this->createClient();
        $this->createInvoice($client, 500000, 100000, 'partial');
        $this->createInvoice($client, 300000, 0, 'unpaid');
        $this->createInvoice($client, 200000, 200000, 'paid'); // excluded

        $response = $this->actingAs($this->admin)->get(route('clients.index'));

        $clients = $response->viewData('clients');
        $first = $clients->first();
        // (500000-100000) + (300000-0) = 700000
        $this->assertEquals(700000, (float) $first->current_balance);
    }

    public function test_index_client_has_last_invoice_date(): void
    {
        $client = $this->createClient();
        $this->createInvoice($client, 100000, 0, 'unpaid', '2024-01-15');
        $this->createInvoice($client, 200000, 0, 'unpaid', '2024-06-20');

        $response = $this->actingAs($this->admin)->get(route('clients.index'));

        $clients = $response->viewData('clients');
        $first = $clients->first();
        $this->assertStringStartsWith('2024-06-20', $first->last_invoice_date);
    }

    // ═══════════════════════════════════════════════════════════════
    // TUGAS 3: HMAC Verification
    // ═══════════════════════════════════════════════════════════════

    public function test_client_hmac_is_deterministic(): void
    {
        $hmac1 = ClientController::clientHmac(42);
        $hmac2 = ClientController::clientHmac(42);

        $this->assertEquals($hmac1, $hmac2);
        $this->assertNotEmpty($hmac1);
    }

    public function test_client_hmac_differs_per_client(): void
    {
        $hmac1 = ClientController::clientHmac(1);
        $hmac2 = ClientController::clientHmac(2);

        $this->assertNotEquals($hmac1, $hmac2);
    }

    public function test_show_with_valid_hmac_succeeds(): void
    {
        $client = $this->createClient();
        $sig = ClientController::clientHmac($client->id);

        $response = $this->actingAs($this->admin)
            ->get(route('clients.show', $client) . '?sig=' . $sig);

        $response->assertOk();
    }

    public function test_show_with_invalid_hmac_returns_403(): void
    {
        $client = $this->createClient();

        $response = $this->actingAs($this->admin)
            ->get(route('clients.show', $client) . '?sig=invalid-signature');

        $response->assertForbidden();
    }

    public function test_show_without_sig_still_works(): void
    {
        $client = $this->createClient();

        $response = $this->actingAs($this->admin)
            ->get(route('clients.show', $client));

        $response->assertOk();
    }

    // ═══════════════════════════════════════════════════════════════
    // TUGAS 4: View Renders Color Indicators & Progress Bar
    // ═══════════════════════════════════════════════════════════════

    public function test_index_view_shows_over_limit_for_exceeded_credit(): void
    {
        $client = $this->createClient(['credit_limit' => 100000]);
        $this->createInvoice($client, 200000, 0, 'unpaid');

        $response = $this->actingAs($this->admin)->get(route('clients.index'));

        $response->assertOk();
        $response->assertSee(__('messages.over_limit'));
    }

    public function test_index_view_shows_credit_progress_bar(): void
    {
        $client = $this->createClient(['credit_limit' => 1000000]);
        $this->createInvoice($client, 500000, 0, 'unpaid');

        $response = $this->actingAs($this->admin)->get(route('clients.index'));

        $response->assertOk();
        // 50% usage → green bar
        $response->assertSee('bg-green-500');
        $response->assertSee('50%');
    }

    public function test_index_view_shows_amber_bar_above_75_percent(): void
    {
        $client = $this->createClient(['credit_limit' => 100000]);
        $this->createInvoice($client, 80000, 0, 'unpaid');

        $response = $this->actingAs($this->admin)->get(route('clients.index'));

        $response->assertOk();
        // 80% usage → amber bar
        $response->assertSee('bg-amber-500');
    }

    public function test_index_view_shows_red_bar_above_90_percent(): void
    {
        $client = $this->createClient(['credit_limit' => 100000]);
        $this->createInvoice($client, 95000, 0, 'unpaid');

        $response = $this->actingAs($this->admin)->get(route('clients.index'));

        $response->assertOk();
        // 95% usage → red bar
        $response->assertSee('bg-red-500');
    }

    public function test_index_view_shows_green_active_dot(): void
    {
        $client = $this->createClient(['status' => 'active']);
        $this->createSalesOrder($client, 100000);

        $response = $this->actingAs($this->admin)->get(route('clients.index'));

        $response->assertOk();
        // Active client with sales → green dot indicator
        $response->assertSee('bg-green-500 ring-2 ring-white');
    }

    public function test_index_view_shows_summary_cards(): void
    {
        $client = $this->createClient();
        $this->createSalesOrder($client, 250000);

        $response = $this->actingAs($this->admin)->get(route('clients.index'));

        $response->assertOk();
        $response->assertSee(__('messages.total_active_clients'));
        $response->assertSee(__('messages.total_receivables'));
        $response->assertSee(__('messages.monthly_sales_growth'));
        $response->assertSee(__('messages.top_spender'));
    }

    public function test_index_view_shows_hmac_signed_detail_links(): void
    {
        $client = $this->createClient();

        $response = $this->actingAs($this->admin)->get(route('clients.index'));

        $expectedSig = ClientController::clientHmac($client->id);
        $response->assertOk();
        $response->assertSee('sig=' . $expectedSig, false);
    }

    // ═══════════════════════════════════════════════════════════════
    // Filters
    // ═══════════════════════════════════════════════════════════════

    public function test_index_filters_by_status(): void
    {
        $this->createClient(['name' => 'Active Co', 'status' => 'active']);
        $this->createClient(['name' => 'Inactive Co', 'status' => 'inactive']);

        $response = $this->actingAs($this->admin)->get(route('clients.index', ['status' => 'active']));

        $response->assertOk();
        $clients = $response->viewData('clients');
        $this->assertCount(1, $clients);
        $this->assertEquals('Active Co', $clients->first()->name);
    }

    public function test_index_filters_by_type(): void
    {
        $this->createClient(['name' => 'Customer Co', 'type' => 'customer']);
        $this->createClient(['name' => 'Lead Co', 'type' => 'lead']);

        $response = $this->actingAs($this->admin)->get(route('clients.index', ['type' => 'lead']));

        $response->assertOk();
        $clients = $response->viewData('clients');
        $this->assertCount(1, $clients);
        $this->assertEquals('Lead Co', $clients->first()->name);
    }

    // ═══════════════════════════════════════════════════════════════
    // Translation Keys
    // ═══════════════════════════════════════════════════════════════

    public function test_new_translation_keys_exist(): void
    {
        $keys = [
            'total_active_clients',
            'total_receivables',
            'monthly_sales_growth',
            'growth_vs_last_month',
            'top_spender',
            'sales_to_date',
            'last_interaction',
            'no_transactions',
            'over_limit',
        ];

        foreach ($keys as $key) {
            $translated = __('messages.' . $key);
            $this->assertNotEquals('messages.' . $key, $translated, "Translation key 'messages.{$key}' is missing.");
        }
    }
}
