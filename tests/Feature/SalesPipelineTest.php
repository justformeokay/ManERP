<?php

namespace Tests\Feature;

use App\Http\Controllers\ClientController;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\SalesOrder;
use App\Models\User;
use App\Models\Warehouse;
use App\Observers\InvoiceObserver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesPipelineTest extends TestCase
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
            'code' => 'WH-PL', 'name' => 'Pipeline Warehouse', 'is_active' => true,
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

    private function createInvoice(Client $client, float $total, float $paid = 0, string $status = 'unpaid'): Invoice
    {
        $order = $this->createSalesOrder($client, $total);

        return Invoice::create([
            'invoice_number' => 'INV-' . mt_rand(10000, 99999),
            'sales_order_id' => $order->id,
            'client_id'      => $client->id,
            'invoice_date'   => now()->toDateString(),
            'due_date'       => now()->addDays(30)->toDateString(),
            'subtotal'       => $total,
            'tax_amount'     => 0,
            'discount'       => 0,
            'total_amount'   => $total,
            'paid_amount'    => $paid,
            'status'         => $status,
            'created_by'     => $this->admin->id,
        ]);
    }

    private function createStaffUser(string $role = User::ROLE_STAFF, array $permissions = []): User
    {
        return User::factory()->create([
            'role'        => $role,
            'status'      => User::STATUS_ACTIVE,
            'permissions' => $permissions,
        ]);
    }

    // ═══════════════════════════════════════════════════════════════
    // TUGAS 1: Pipeline View — Data & View Toggle
    // ═══════════════════════════════════════════════════════════════

    public function test_index_returns_pipeline_clients_grouped_by_type(): void
    {
        $this->createClient(['name' => 'Lead A', 'type' => 'lead']);
        $this->createClient(['name' => 'Prospect A', 'type' => 'prospect']);
        $this->createClient(['name' => 'Customer A', 'type' => 'customer']);
        $this->createClient(['name' => 'Inactive', 'type' => 'customer', 'status' => 'inactive']);

        $response = $this->actingAs($this->admin)->get(route('clients.index'));

        $response->assertOk();
        $pipeline = $response->viewData('pipelineClients');
        $this->assertArrayHasKey('lead', $pipeline->toArray());
        $this->assertArrayHasKey('prospect', $pipeline->toArray());
        $this->assertArrayHasKey('customer', $pipeline->toArray());
        // Inactive clients excluded
        $this->assertCount(1, $pipeline['customer']);
    }

    public function test_index_returns_conversion_rate(): void
    {
        $response = $this->actingAs($this->admin)->get(route('clients.index'));

        $response->assertOk();
        $conversionRate = $response->viewData('conversionRate');
        $this->assertIsFloat($conversionRate);
    }

    public function test_index_view_shows_view_toggle_buttons(): void
    {
        $this->createClient();

        $response = $this->actingAs($this->admin)->get(route('clients.index'));

        $response->assertOk();
        $response->assertSee(__('messages.table_view'));
        $response->assertSee(__('messages.pipeline_view'));
    }

    public function test_index_view_shows_pipeline_kanban_columns(): void
    {
        $this->createClient(['name' => 'Lead Co', 'type' => 'lead']);
        $this->createClient(['name' => 'Prospect Co', 'type' => 'prospect']);
        $this->createClient(['name' => 'Customer Co', 'type' => 'customer']);

        $response = $this->actingAs($this->admin)->get(route('clients.index'));

        $response->assertOk();
        $response->assertSee('Lead Co');
        $response->assertSee('Prospect Co');
        $response->assertSee('Customer Co');
    }

    public function test_index_pipeline_cards_carry_hmac_attributes(): void
    {
        $client = $this->createClient(['type' => 'lead']);

        $response = $this->actingAs($this->admin)->get(route('clients.index'));

        $response->assertOk();
        $expectedHmac = ClientController::pipelineHmac($client->id, 'prospect');
        $response->assertSee($expectedHmac, false);
    }

    public function test_index_shows_conversion_rate_banner(): void
    {
        $this->createClient();

        $response = $this->actingAs($this->admin)->get(route('clients.index'));

        $response->assertOk();
        $response->assertSee(__('messages.conversion_rate'));
    }

    // ═══════════════════════════════════════════════════════════════
    // TUGAS 2: Enhanced Form — Tooltip & Conditional Fields
    // ═══════════════════════════════════════════════════════════════

    public function test_create_form_shows_type_tooltip(): void
    {
        $response = $this->actingAs($this->admin)->get(route('clients.create'));

        $response->assertOk();
        $response->assertSee(__('messages.tooltip_lead'));
        $response->assertSee(__('messages.tooltip_prospect'));
        $response->assertSee(__('messages.tooltip_customer'));
    }

    public function test_create_form_shows_color_coded_type_options(): void
    {
        $response = $this->actingAs($this->admin)->get(route('clients.create'));

        $response->assertOk();
        // Emoji indicators in select options
        $response->assertSee('🟡', false);
        $response->assertSee('🟣', false);
        $response->assertSee('🔵', false);
    }

    public function test_create_form_has_alpine_client_type_binding(): void
    {
        $response = $this->actingAs($this->admin)->get(route('clients.create'));

        $response->assertOk();
        $response->assertSee('x-model="clientType"', false);
        $response->assertSee("clientType !== 'lead'", false);
    }

    // ═══════════════════════════════════════════════════════════════
    // TUGAS 3: Invoice Observer — Auto Conversion
    // ═══════════════════════════════════════════════════════════════

    public function test_observer_converts_prospect_to_customer_on_invoice_paid(): void
    {
        $client = $this->createClient(['type' => 'prospect']);
        $invoice = $this->createInvoice($client, 500000, 0, 'unpaid');

        // Simulate payment → status changes to paid
        $invoice->update([
            'status'      => 'paid',
            'paid_amount' => 500000,
        ]);

        $client->refresh();
        $this->assertEquals('customer', $client->type);
    }

    public function test_observer_ignores_non_prospect_clients(): void
    {
        $client = $this->createClient(['type' => 'lead']);
        $invoice = $this->createInvoice($client, 500000, 0, 'unpaid');

        $invoice->update([
            'status'      => 'paid',
            'paid_amount' => 500000,
        ]);

        $client->refresh();
        $this->assertEquals('lead', $client->type);
    }

    public function test_observer_ignores_non_paid_status_changes(): void
    {
        $client = $this->createClient(['type' => 'prospect']);
        $invoice = $this->createInvoice($client, 500000, 100000, 'unpaid');

        // Change to partial — not paid
        $invoice->update([
            'status'      => 'partial',
            'paid_amount' => 100000,
        ]);

        $client->refresh();
        $this->assertEquals('prospect', $client->type);
    }

    public function test_observer_does_not_affect_already_customer_type(): void
    {
        $client = $this->createClient(['type' => 'customer']);
        $invoice = $this->createInvoice($client, 500000, 0, 'unpaid');

        $invoice->update([
            'status'      => 'paid',
            'paid_amount' => 500000,
        ]);

        $client->refresh();
        $this->assertEquals('customer', $client->type);
    }

    public function test_observer_creates_audit_log_on_conversion(): void
    {
        $client = $this->createClient(['type' => 'prospect']);
        $invoice = $this->createInvoice($client, 500000, 0, 'unpaid');

        $invoice->update([
            'status'      => 'paid',
            'paid_amount' => 500000,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'module' => 'clients',
            'action' => 'update',
            'auditable_id' => $client->id,
        ]);
    }

    // ═══════════════════════════════════════════════════════════════
    // TUGAS 4: HMAC Security & Permission Checks
    // ═══════════════════════════════════════════════════════════════

    public function test_pipeline_hmac_is_deterministic(): void
    {
        $hmac1 = ClientController::pipelineHmac(42, 'prospect');
        $hmac2 = ClientController::pipelineHmac(42, 'prospect');

        $this->assertEquals($hmac1, $hmac2);
        $this->assertNotEmpty($hmac1);
    }

    public function test_pipeline_hmac_differs_by_type(): void
    {
        $hmac1 = ClientController::pipelineHmac(1, 'lead');
        $hmac2 = ClientController::pipelineHmac(1, 'prospect');

        $this->assertNotEquals($hmac1, $hmac2);
    }

    public function test_pipeline_hmac_differs_by_client(): void
    {
        $hmac1 = ClientController::pipelineHmac(1, 'prospect');
        $hmac2 = ClientController::pipelineHmac(2, 'prospect');

        $this->assertNotEquals($hmac1, $hmac2);
    }

    public function test_update_type_with_valid_hmac_succeeds(): void
    {
        $client = $this->createClient(['type' => 'lead']);
        $sig = ClientController::pipelineHmac($client->id, 'prospect');

        $response = $this->actingAs($this->admin)->patchJson(
            route('clients.updateType', $client),
            ['type' => 'prospect', 'sig' => $sig]
        );

        $response->assertOk();
        $response->assertJson(['success' => true]);
        $client->refresh();
        $this->assertEquals('prospect', $client->type);
    }

    public function test_update_type_with_invalid_hmac_returns_403(): void
    {
        $client = $this->createClient(['type' => 'lead']);

        $response = $this->actingAs($this->admin)->patchJson(
            route('clients.updateType', $client),
            ['type' => 'prospect', 'sig' => 'invalid-signature']
        );

        $response->assertStatus(403);
        $client->refresh();
        $this->assertEquals('lead', $client->type);
    }

    public function test_update_type_validates_type_field(): void
    {
        $client = $this->createClient(['type' => 'lead']);
        $sig = ClientController::pipelineHmac($client->id, 'invalid');

        $response = $this->actingAs($this->admin)->patchJson(
            route('clients.updateType', $client),
            ['type' => 'invalid', 'sig' => $sig]
        );

        $response->assertStatus(422);
    }

    public function test_update_type_requires_sig_field(): void
    {
        $client = $this->createClient(['type' => 'lead']);

        $response = $this->actingAs($this->admin)->patchJson(
            route('clients.updateType', $client),
            ['type' => 'prospect']
        );

        $response->assertStatus(422);
    }

    public function test_customer_downgrade_denied_for_regular_staff(): void
    {
        $staff = $this->createStaffUser(User::ROLE_STAFF, ['clients.view', 'clients.edit']);
        $client = $this->createClient(['type' => 'customer']);
        $sig = ClientController::pipelineHmac($client->id, 'prospect');

        $response = $this->actingAs($staff)->patchJson(
            route('clients.updateType', $client),
            ['type' => 'prospect', 'sig' => $sig]
        );

        $response->assertStatus(403);
        $response->assertJson(['error' => __('messages.pipeline_downgrade_denied')]);
        $client->refresh();
        $this->assertEquals('customer', $client->type);
    }

    public function test_customer_downgrade_allowed_for_admin(): void
    {
        $client = $this->createClient(['type' => 'customer']);
        $sig = ClientController::pipelineHmac($client->id, 'lead');

        $response = $this->actingAs($this->admin)->patchJson(
            route('clients.updateType', $client),
            ['type' => 'lead', 'sig' => $sig]
        );

        $response->assertOk();
        $response->assertJson(['success' => true]);
        $client->refresh();
        $this->assertEquals('lead', $client->type);
    }

    public function test_customer_downgrade_allowed_for_sales_manager(): void
    {
        $manager = $this->createStaffUser('sales_manager', ['clients.view', 'clients.edit']);
        $client = $this->createClient(['type' => 'customer']);
        $sig = ClientController::pipelineHmac($client->id, 'prospect');

        $response = $this->actingAs($manager)->patchJson(
            route('clients.updateType', $client),
            ['type' => 'prospect', 'sig' => $sig]
        );

        $response->assertOk();
        $client->refresh();
        $this->assertEquals('prospect', $client->type);
    }

    public function test_non_customer_type_change_allowed_for_staff(): void
    {
        $staff = $this->createStaffUser(User::ROLE_STAFF, ['clients.view', 'clients.edit']);
        $client = $this->createClient(['type' => 'lead']);
        $sig = ClientController::pipelineHmac($client->id, 'prospect');

        $response = $this->actingAs($staff)->patchJson(
            route('clients.updateType', $client),
            ['type' => 'prospect', 'sig' => $sig]
        );

        $response->assertOk();
        $client->refresh();
        $this->assertEquals('prospect', $client->type);
    }

    public function test_update_type_creates_audit_log(): void
    {
        $client = $this->createClient(['type' => 'lead']);
        $sig = ClientController::pipelineHmac($client->id, 'prospect');

        $this->actingAs($this->admin)->patchJson(
            route('clients.updateType', $client),
            ['type' => 'prospect', 'sig' => $sig]
        );

        $this->assertDatabaseHas('activity_logs', [
            'module'       => 'clients',
            'action'       => 'update',
            'auditable_id' => $client->id,
        ]);
    }

    public function test_update_type_returns_localized_message(): void
    {
        $client = $this->createClient(['name' => 'Acme Corp', 'type' => 'lead']);
        $sig = ClientController::pipelineHmac($client->id, 'prospect');

        $response = $this->actingAs($this->admin)->patchJson(
            route('clients.updateType', $client),
            ['type' => 'prospect', 'sig' => $sig]
        );

        $response->assertOk();
        $json = $response->json();
        $this->assertStringContainsString('Acme Corp', $json['message']);
    }

    // ═══════════════════════════════════════════════════════════════
    // Translation Keys
    // ═══════════════════════════════════════════════════════════════

    public function test_pipeline_translation_keys_exist(): void
    {
        $keys = [
            'table_view',
            'pipeline_view',
            'conversion_rate',
            'conversion_rate_desc',
            'pipeline_type_updated',
            'pipeline_downgrade_denied',
            'auto_conversion_notification',
            'tooltip_lead',
            'tooltip_prospect',
            'tooltip_customer',
        ];

        foreach ($keys as $key) {
            $translated = __('messages.' . $key);
            $this->assertNotEquals('messages.' . $key, $translated, "Translation key 'messages.{$key}' is missing.");
        }
    }
}
