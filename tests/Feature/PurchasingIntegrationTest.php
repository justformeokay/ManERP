<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Product;
use App\Models\Project;
use App\Models\PurchaseOrder;
use App\Models\Setting;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchasingIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Supplier $supplier;
    private Warehouse $warehouse;
    private Department $department;
    private Product $product;
    private Project $salesProject;
    private Project $capexProject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);

        Setting::firstOrCreate(['key' => 'currency_symbol'], ['value' => 'Rp']);
        Setting::firstOrCreate(['key' => 'currency_code'], ['value' => 'IDR']);

        $this->supplier = Supplier::create([
            'code' => 'SUP-PI', 'name' => 'PI Supplier', 'status' => 'active',
        ]);

        $this->warehouse = Warehouse::create([
            'code' => 'WH-PI', 'name' => 'PI Warehouse', 'is_active' => true,
        ]);

        $this->department = Department::create([
            'code' => 'DEPT-PI', 'name' => 'PI Department', 'is_active' => true,
        ]);

        $this->product = Product::create([
            'sku' => 'SKU-PI-001', 'name' => 'PI Product', 'unit' => 'pcs',
            'cost_price' => 10000, 'sell_price' => 15000, 'is_active' => true,
        ]);

        $this->salesProject = Project::create([
            'code' => 'PRJ-SAL-001', 'name' => 'Sales Project', 'type' => 'sales',
            'status' => 'active', 'budget' => 100000000,
        ]);

        $this->capexProject = Project::create([
            'code' => 'PRJ-CAP-001', 'name' => 'CAPEX Project', 'type' => 'internal_capex',
            'status' => 'active', 'budget' => 200000000,
        ]);
    }

    // ── Migration & Column Tests ────────────────────────────────

    public function test_migration_adds_purchase_type_column()
    {
        $this->assertTrue(
            \Schema::hasColumn('purchase_orders', 'purchase_type'),
            'purchase_type column should exist'
        );
    }

    public function test_migration_adds_department_id_column()
    {
        $this->assertTrue(
            \Schema::hasColumn('purchase_orders', 'department_id'),
            'department_id column should exist'
        );
    }

    public function test_migration_adds_priority_column()
    {
        $this->assertTrue(
            \Schema::hasColumn('purchase_orders', 'priority'),
            'priority column should exist'
        );
    }

    public function test_migration_adds_justification_column()
    {
        $this->assertTrue(
            \Schema::hasColumn('purchase_orders', 'justification'),
            'justification column should exist'
        );
    }

    // ── Model Helper Tests ──────────────────────────────────────

    public function test_purchase_type_options_returns_three_types()
    {
        $options = PurchaseOrder::purchaseTypeOptions();
        $this->assertCount(3, $options);
        $this->assertContains('operational', $options);
        $this->assertContains('project_sales', $options);
        $this->assertContains('project_capex', $options);
    }

    public function test_purchase_type_colors_returns_colors_for_each_type()
    {
        $colors = PurchaseOrder::purchaseTypeColors();
        foreach (PurchaseOrder::purchaseTypeOptions() as $type) {
            $this->assertArrayHasKey($type, $colors);
        }
    }

    public function test_priority_options_returns_three_levels()
    {
        $options = PurchaseOrder::priorityOptions();
        $this->assertCount(3, $options);
        $this->assertContains('low', $options);
        $this->assertContains('normal', $options);
        $this->assertContains('urgent', $options);
    }

    public function test_priority_colors_returns_colors_for_each_level()
    {
        $colors = PurchaseOrder::priorityColors();
        foreach (PurchaseOrder::priorityOptions() as $prio) {
            $this->assertArrayHasKey($prio, $colors);
        }
    }

    public function test_project_hmac_returns_consistent_signature()
    {
        $sig1 = PurchaseOrder::projectHmac(1);
        $sig2 = PurchaseOrder::projectHmac(1);
        $this->assertSame($sig1, $sig2);
        $this->assertNotSame($sig1, PurchaseOrder::projectHmac(2));
    }

    public function test_project_hmac_uses_sha256()
    {
        $sig = PurchaseOrder::projectHmac(99);
        $this->assertEquals(64, strlen($sig)); // SHA-256 = 64 hex chars
    }

    public function test_purchase_order_belongs_to_department()
    {
        $order = PurchaseOrder::create([
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'department_id' => $this->department->id,
            'purchase_type' => 'operational',
            'priority' => 'normal',
            'status' => 'draft',
            'order_date' => now(),
            'subtotal' => 0,
            'tax_amount' => 0,
            'total' => 0,
            'created_by' => $this->admin->id,
        ]);

        $this->assertNotNull($order->department);
        $this->assertEquals($this->department->id, $order->department->id);
    }

    public function test_project_has_many_purchase_orders()
    {
        PurchaseOrder::create([
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'department_id' => $this->department->id,
            'project_id' => $this->salesProject->id,
            'purchase_type' => 'project_sales',
            'priority' => 'normal',
            'status' => 'draft',
            'order_date' => now(),
            'subtotal' => 50000,
            'tax_amount' => 5000,
            'total' => 55000,
            'created_by' => $this->admin->id,
        ]);

        $this->assertTrue($this->salesProject->purchaseOrders()->exists());
        $this->assertEquals(1, $this->salesProject->purchaseOrders()->count());
    }

    public function test_purchase_type_label_returns_translated_string()
    {
        $order = new PurchaseOrder(['purchase_type' => 'operational']);
        $label = $order->purchaseTypeLabel();
        $this->assertIsString($label);
        $this->assertNotEmpty($label);
    }

    // ── Store – Operational PO ──────────────────────────────────

    public function test_store_operational_po_saves_without_project()
    {
        $response = $this->actingAs($this->admin)->post(route('purchasing.store'), [
            'purchase_type' => 'operational',
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'department_id' => $this->department->id,
            'priority' => 'normal',
            'order_date' => now()->format('Y-m-d'),
            'tax_amount' => 0,
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 10, 'unit_price' => 5000],
            ],
        ]);

        $response->assertRedirect();
        $order = PurchaseOrder::latest('id')->first();
        $this->assertEquals('operational', $order->purchase_type);
        $this->assertNull($order->project_id);
        $this->assertEquals('normal', $order->priority);
        $this->assertEquals($this->department->id, $order->department_id);
    }

    public function test_store_operational_po_ignores_project_id()
    {
        $this->actingAs($this->admin)->post(route('purchasing.store'), [
            'purchase_type' => 'operational',
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'department_id' => $this->department->id,
            'priority' => 'normal',
            'order_date' => now()->format('Y-m-d'),
            'project_id' => $this->salesProject->id,
            'project_sig' => PurchaseOrder::projectHmac($this->salesProject->id),
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 5, 'unit_price' => 1000],
            ],
        ]);

        $order = PurchaseOrder::latest('id')->first();
        $this->assertNull($order->project_id, 'Operational PO should have null project_id');
    }

    // ── Store – Project Sales PO ────────────────────────────────

    public function test_store_project_sales_po_with_valid_hmac()
    {
        $sig = PurchaseOrder::projectHmac($this->salesProject->id);

        $response = $this->actingAs($this->admin)->post(route('purchasing.store'), [
            'purchase_type' => 'project_sales',
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'department_id' => $this->department->id,
            'project_id' => $this->salesProject->id,
            'project_sig' => $sig,
            'priority' => 'urgent',
            'order_date' => now()->format('Y-m-d'),
            'justification' => 'Needed for client delivery',
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 20, 'unit_price' => 10000],
            ],
        ]);

        $response->assertRedirect();
        $order = PurchaseOrder::latest('id')->first();
        $this->assertEquals('project_sales', $order->purchase_type);
        $this->assertEquals($this->salesProject->id, $order->project_id);
        $this->assertEquals('urgent', $order->priority);
        $this->assertEquals('Needed for client delivery', $order->justification);
    }

    // ── Store – Project CAPEX PO ────────────────────────────────

    public function test_store_project_capex_po_with_valid_hmac()
    {
        $sig = PurchaseOrder::projectHmac($this->capexProject->id);

        $response = $this->actingAs($this->admin)->post(route('purchasing.store'), [
            'purchase_type' => 'project_capex',
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'department_id' => $this->department->id,
            'project_id' => $this->capexProject->id,
            'project_sig' => $sig,
            'priority' => 'low',
            'order_date' => now()->format('Y-m-d'),
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 5, 'unit_price' => 50000],
            ],
        ]);

        $response->assertRedirect();
        $order = PurchaseOrder::latest('id')->first();
        $this->assertEquals('project_capex', $order->purchase_type);
        $this->assertEquals($this->capexProject->id, $order->project_id);
        $this->assertEquals('low', $order->priority);
    }

    // ── HMAC Security ───────────────────────────────────────────

    public function test_store_with_invalid_hmac_returns_403()
    {
        $response = $this->actingAs($this->admin)->post(route('purchasing.store'), [
            'purchase_type' => 'project_sales',
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'department_id' => $this->department->id,
            'project_id' => $this->salesProject->id,
            'project_sig' => 'tampered-invalid-signature',
            'priority' => 'normal',
            'order_date' => now()->format('Y-m-d'),
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 1, 'unit_price' => 1000],
            ],
        ]);

        $response->assertStatus(403);
    }

    // ── Validation ──────────────────────────────────────────────

    public function test_store_project_type_requires_project_id()
    {
        $response = $this->actingAs($this->admin)->post(route('purchasing.store'), [
            'purchase_type' => 'project_sales',
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'department_id' => $this->department->id,
            'priority' => 'normal',
            'order_date' => now()->format('Y-m-d'),
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 1, 'unit_price' => 1000],
            ],
        ]);

        $response->assertSessionHasErrors('project_id');
    }

    public function test_store_project_capex_requires_project_id()
    {
        $response = $this->actingAs($this->admin)->post(route('purchasing.store'), [
            'purchase_type' => 'project_capex',
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'department_id' => $this->department->id,
            'priority' => 'normal',
            'order_date' => now()->format('Y-m-d'),
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 1, 'unit_price' => 1000],
            ],
        ]);

        $response->assertSessionHasErrors('project_id');
    }

    public function test_store_requires_department_id()
    {
        $response = $this->actingAs($this->admin)->post(route('purchasing.store'), [
            'purchase_type' => 'operational',
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'priority' => 'normal',
            'order_date' => now()->format('Y-m-d'),
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 1, 'unit_price' => 1000],
            ],
        ]);

        $response->assertSessionHasErrors('department_id');
    }

    public function test_store_validates_priority_value()
    {
        $response = $this->actingAs($this->admin)->post(route('purchasing.store'), [
            'purchase_type' => 'operational',
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'department_id' => $this->department->id,
            'priority' => 'critical', // invalid
            'order_date' => now()->format('Y-m-d'),
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 1, 'unit_price' => 1000],
            ],
        ]);

        $response->assertSessionHasErrors('priority');
    }

    public function test_store_validates_purchase_type_value()
    {
        $response = $this->actingAs($this->admin)->post(route('purchasing.store'), [
            'purchase_type' => 'invalid_type',
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'department_id' => $this->department->id,
            'priority' => 'normal',
            'order_date' => now()->format('Y-m-d'),
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 1, 'unit_price' => 1000],
            ],
        ]);

        $response->assertSessionHasErrors('purchase_type');
    }

    public function test_store_validates_justification_max_length()
    {
        $response = $this->actingAs($this->admin)->post(route('purchasing.store'), [
            'purchase_type' => 'operational',
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'department_id' => $this->department->id,
            'priority' => 'normal',
            'order_date' => now()->format('Y-m-d'),
            'justification' => str_repeat('A', 2001),
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 1, 'unit_price' => 1000],
            ],
        ]);

        $response->assertSessionHasErrors('justification');
    }

    public function test_store_requires_at_least_one_item()
    {
        $response = $this->actingAs($this->admin)->post(route('purchasing.store'), [
            'purchase_type' => 'operational',
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'department_id' => $this->department->id,
            'priority' => 'normal',
            'order_date' => now()->format('Y-m-d'),
            'items' => [],
        ]);

        $response->assertSessionHasErrors('items');
    }

    // ── Totals & Recalculation ──────────────────────────────────

    public function test_store_calculates_totals_correctly()
    {
        $this->actingAs($this->admin)->post(route('purchasing.store'), [
            'purchase_type' => 'operational',
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'department_id' => $this->department->id,
            'priority' => 'normal',
            'order_date' => now()->format('Y-m-d'),
            'tax_amount' => 5500,
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 10, 'unit_price' => 5000],
            ],
        ]);

        $order = PurchaseOrder::latest('id')->first();
        $this->assertEquals(50000, (float) $order->subtotal);
        $this->assertEquals(5500, (float) $order->tax_amount);
        $this->assertEquals(55500, (float) $order->total);
    }

    // ── Index – Purchase Type Filter ────────────────────────────

    public function test_index_page_loads_successfully()
    {
        $response = $this->actingAs($this->admin)->get(route('purchasing.index'));
        $response->assertOk();
        $response->assertViewIs('purchasing.index');
    }

    public function test_index_filters_by_purchase_type()
    {
        PurchaseOrder::create([
            'supplier_id' => $this->supplier->id, 'warehouse_id' => $this->warehouse->id,
            'department_id' => $this->department->id, 'purchase_type' => 'operational',
            'priority' => 'normal', 'status' => 'draft', 'order_date' => now(),
            'subtotal' => 0, 'tax_amount' => 0, 'total' => 0, 'created_by' => $this->admin->id,
        ]);

        PurchaseOrder::create([
            'supplier_id' => $this->supplier->id, 'warehouse_id' => $this->warehouse->id,
            'department_id' => $this->department->id, 'project_id' => $this->salesProject->id,
            'purchase_type' => 'project_sales', 'priority' => 'urgent', 'status' => 'draft',
            'order_date' => now(), 'subtotal' => 0, 'tax_amount' => 0, 'total' => 0,
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('purchasing.index', ['purchase_type' => 'operational']));
        $response->assertOk();

        $filteredOrders = $response->viewData('orders');
        $this->assertTrue($filteredOrders->every(fn($o) => $o->purchase_type === 'operational'));
    }

    // ── Show Page ───────────────────────────────────────────────

    public function test_show_page_loads_department_relation()
    {
        $order = PurchaseOrder::create([
            'supplier_id' => $this->supplier->id, 'warehouse_id' => $this->warehouse->id,
            'department_id' => $this->department->id, 'purchase_type' => 'operational',
            'priority' => 'urgent', 'status' => 'draft', 'order_date' => now(),
            'subtotal' => 0, 'tax_amount' => 0, 'total' => 0, 'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('purchasing.show', $order));
        $response->assertOk();
        $response->assertSee($this->department->name);
    }

    // ── Create Page ─────────────────────────────────────────────

    public function test_create_page_passes_project_lists_and_departments()
    {
        $response = $this->actingAs($this->admin)->get(route('purchasing.create'));
        $response->assertOk();

        $response->assertViewHas('salesProjects');
        $response->assertViewHas('capexProjects');
        $response->assertViewHas('departments');
    }

    public function test_create_page_filters_projects_by_type()
    {
        $response = $this->actingAs($this->admin)->get(route('purchasing.create'));

        $salesProjects = $response->viewData('salesProjects');
        $capexProjects = $response->viewData('capexProjects');

        $this->assertTrue($salesProjects->every(fn($p) => $p->type === 'sales'));
        $this->assertTrue($capexProjects->every(fn($p) => $p->type === 'internal_capex'));
    }

    // ── Project Show – Purchasing Tab ───────────────────────────

    public function test_project_show_displays_linked_purchase_orders()
    {
        $sig = PurchaseOrder::projectHmac($this->salesProject->id);

        $this->actingAs($this->admin)->post(route('purchasing.store'), [
            'purchase_type' => 'project_sales',
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'department_id' => $this->department->id,
            'project_id' => $this->salesProject->id,
            'project_sig' => $sig,
            'priority' => 'normal',
            'order_date' => now()->format('Y-m-d'),
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 10, 'unit_price' => 5000],
            ],
        ]);

        $response = $this->actingAs($this->admin)->get(route('projects.show', $this->salesProject));
        $response->assertOk();
        $response->assertViewHas('purchaseOrders');
        $response->assertViewHas('totalPurchased');

        $purchaseOrders = $response->viewData('purchaseOrders');
        $this->assertCount(1, $purchaseOrders);
    }

    public function test_project_show_excludes_cancelled_from_total()
    {
        PurchaseOrder::create([
            'supplier_id' => $this->supplier->id, 'warehouse_id' => $this->warehouse->id,
            'department_id' => $this->department->id, 'project_id' => $this->capexProject->id,
            'purchase_type' => 'project_capex', 'priority' => 'normal', 'status' => 'draft',
            'order_date' => now(), 'subtotal' => 100000, 'tax_amount' => 0, 'total' => 100000,
            'created_by' => $this->admin->id,
        ]);

        PurchaseOrder::create([
            'supplier_id' => $this->supplier->id, 'warehouse_id' => $this->warehouse->id,
            'department_id' => $this->department->id, 'project_id' => $this->capexProject->id,
            'purchase_type' => 'project_capex', 'priority' => 'normal', 'status' => 'cancelled',
            'order_date' => now(), 'subtotal' => 200000, 'tax_amount' => 0, 'total' => 200000,
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('projects.show', $this->capexProject));
        $totalPurchased = $response->viewData('totalPurchased');
        $this->assertEquals(100000, (float) $totalPurchased);
    }

    // ── Update ──────────────────────────────────────────────────

    public function test_update_changes_purchase_type_and_fields()
    {
        $order = PurchaseOrder::create([
            'supplier_id' => $this->supplier->id, 'warehouse_id' => $this->warehouse->id,
            'department_id' => $this->department->id, 'purchase_type' => 'operational',
            'priority' => 'normal', 'status' => 'draft', 'order_date' => now(),
            'subtotal' => 0, 'tax_amount' => 0, 'total' => 0, 'created_by' => $this->admin->id,
        ]);

        $order->items()->create([
            'product_id' => $this->product->id, 'quantity' => 1, 'unit_price' => 1000, 'total' => 1000,
        ]);

        $sig = PurchaseOrder::projectHmac($this->capexProject->id);

        $response = $this->actingAs($this->admin)->put(route('purchasing.update', $order), [
            'purchase_type' => 'project_capex',
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'department_id' => $this->department->id,
            'project_id' => $this->capexProject->id,
            'project_sig' => $sig,
            'priority' => 'urgent',
            'order_date' => now()->format('Y-m-d'),
            'justification' => 'Updated to CAPEX',
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 5, 'unit_price' => 2000],
            ],
        ]);

        $response->assertRedirect();
        $order->refresh();
        $this->assertEquals('project_capex', $order->purchase_type);
        $this->assertEquals($this->capexProject->id, $order->project_id);
        $this->assertEquals('urgent', $order->priority);
        $this->assertEquals('Updated to CAPEX', $order->justification);
    }

    public function test_update_with_invalid_hmac_returns_403()
    {
        $order = PurchaseOrder::create([
            'supplier_id' => $this->supplier->id, 'warehouse_id' => $this->warehouse->id,
            'department_id' => $this->department->id, 'purchase_type' => 'operational',
            'priority' => 'normal', 'status' => 'draft', 'order_date' => now(),
            'subtotal' => 0, 'tax_amount' => 0, 'total' => 0, 'created_by' => $this->admin->id,
        ]);

        $order->items()->create([
            'product_id' => $this->product->id, 'quantity' => 1, 'unit_price' => 1000, 'total' => 1000,
        ]);

        $response = $this->actingAs($this->admin)->put(route('purchasing.update', $order), [
            'purchase_type' => 'project_sales',
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'department_id' => $this->department->id,
            'project_id' => $this->salesProject->id,
            'project_sig' => 'wrong-hmac-sig',
            'priority' => 'normal',
            'order_date' => now()->format('Y-m-d'),
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 1, 'unit_price' => 1000],
            ],
        ]);

        $response->assertStatus(403);
    }

    // ── i18n ────────────────────────────────────────────────────

    public function test_english_i18n_keys_exist_for_purchasing_integration()
    {
        app()->setLocale('en');
        $keys = [
            'messages.po_purchase_type_label',
            'messages.po_purchase_type_operational',
            'messages.po_purchase_type_project_sales',
            'messages.po_purchase_type_project_capex',
            'messages.po_requesting_dept',
            'messages.po_priority',
            'messages.po_priority_low',
            'messages.po_priority_normal',
            'messages.po_priority_urgent',
            'messages.po_justification',
            'messages.po_purchasing_tab',
            'messages.po_total_spent',
            'messages.po_budget_utilization',
            'messages.po_no_linked_orders',
        ];

        foreach ($keys as $key) {
            $this->assertNotEquals(
                $key,
                __($key),
                "Missing EN translation for: {$key}"
            );
        }
    }

    public function test_indonesian_i18n_keys_exist_for_purchasing_integration()
    {
        app()->setLocale('id');
        $keys = [
            'messages.po_purchase_type_label',
            'messages.po_purchase_type_operational',
            'messages.po_purchase_type_project_sales',
            'messages.po_purchase_type_project_capex',
            'messages.po_requesting_dept',
            'messages.po_priority',
            'messages.po_priority_low',
            'messages.po_priority_normal',
            'messages.po_priority_urgent',
            'messages.po_justification',
            'messages.po_purchasing_tab',
            'messages.po_total_spent',
            'messages.po_budget_utilization',
            'messages.po_no_linked_orders',
        ];

        foreach ($keys as $key) {
            $this->assertNotEquals(
                $key,
                __($key),
                "Missing ID translation for: {$key}"
            );
        }
    }

    public function test_form_i18n_keys_exist_in_english()
    {
        app()->setLocale('en');
        $keys = [
            'messages.po_new_title',
            'messages.po_edit_title',
            'messages.po_create_subtitle',
            'messages.po_edit_subtitle',
            'messages.po_purchase_type_hint',
            'messages.po_type_operational_desc',
            'messages.po_type_sales_desc',
            'messages.po_type_capex_desc',
            'messages.po_project',
            'messages.po_select_project',
            'messages.po_order_details',
            'messages.po_supplier',
            'messages.po_select_supplier',
            'messages.po_warehouse',
            'messages.po_select_warehouse',
            'messages.po_select_department',
            'messages.po_order_date',
            'messages.po_expected_delivery',
            'messages.po_justification_hint',
            'messages.po_justification_placeholder',
            'messages.po_order_items',
            'messages.po_add_item',
            'messages.po_product',
            'messages.po_select_product',
            'messages.po_qty',
            'messages.po_unit_price',
            'messages.po_remove_item',
            'messages.po_line_total',
            'messages.po_no_items_yet',
            'messages.po_summary',
            'messages.po_subtotal',
            'messages.po_tax',
            'messages.po_grand_total',
            'messages.po_notes_placeholder',
            'messages.po_create_order',
            'messages.po_update_order',
            'messages.po_all_types_filter',
            'messages.po_linked_orders',
        ];

        foreach ($keys as $key) {
            $this->assertNotEquals(
                $key,
                __($key),
                "Missing EN translation for: {$key}"
            );
        }
    }

    // ── Default Values ──────────────────────────────────────────

    public function test_default_purchase_type_is_operational()
    {
        $order = PurchaseOrder::create([
            'supplier_id' => $this->supplier->id, 'warehouse_id' => $this->warehouse->id,
            'department_id' => $this->department->id, 'status' => 'draft',
            'order_date' => now(), 'subtotal' => 0, 'tax_amount' => 0, 'total' => 0,
            'created_by' => $this->admin->id,
        ]);

        $fresh = PurchaseOrder::find($order->id);
        $this->assertEquals('operational', $fresh->purchase_type);
    }

    public function test_default_priority_is_normal()
    {
        $order = PurchaseOrder::create([
            'supplier_id' => $this->supplier->id, 'warehouse_id' => $this->warehouse->id,
            'department_id' => $this->department->id, 'status' => 'draft',
            'order_date' => now(), 'subtotal' => 0, 'tax_amount' => 0, 'total' => 0,
            'created_by' => $this->admin->id,
        ]);

        $fresh = PurchaseOrder::find($order->id);
        $this->assertEquals('normal', $fresh->priority);
    }

    // ── Justification ───────────────────────────────────────────

    public function test_justification_is_stored_and_retrievable()
    {
        $this->actingAs($this->admin)->post(route('purchasing.store'), [
            'purchase_type' => 'operational',
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'department_id' => $this->department->id,
            'priority' => 'normal',
            'order_date' => now()->format('Y-m-d'),
            'justification' => 'Urgent maintenance parts needed for production line.',
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 1, 'unit_price' => 10000],
            ],
        ]);

        $order = PurchaseOrder::latest('id')->first();
        $this->assertEquals('Urgent maintenance parts needed for production line.', $order->justification);
    }

    public function test_justification_is_nullable()
    {
        $this->actingAs($this->admin)->post(route('purchasing.store'), [
            'purchase_type' => 'operational',
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'department_id' => $this->department->id,
            'priority' => 'normal',
            'order_date' => now()->format('Y-m-d'),
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 1, 'unit_price' => 1000],
            ],
        ]);

        $order = PurchaseOrder::latest('id')->first();
        $this->assertNull($order->justification);
    }
}
