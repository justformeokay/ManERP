<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\Setting;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrPoWorkflowSyncTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Department $department;
    private Supplier $supplier;
    private Warehouse $warehouse;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 'admin', 'status' => 'active']);

        if (class_exists(Setting::class) && method_exists(Setting::class, 'query')) {
            Setting::firstOrCreate(['key' => 'currency_symbol'], ['value' => 'Rp']);
            Setting::firstOrCreate(['key' => 'currency_code'], ['value' => 'IDR']);
        }

        $this->department = Department::create(['name' => 'Procurement', 'code' => 'PROC', 'is_active' => true]);
        $this->supplier = Supplier::create(['name' => 'Test Supplier', 'status' => 'active', 'email' => 'sup@test.com']);
        $this->warehouse = Warehouse::create(['name' => 'Main WH', 'code' => 'WH-01', 'is_active' => true]);
        $this->product = Product::create([
            'sku' => 'TST-' . uniqid(), 'name' => 'Test Product', 'type' => 'raw_material',
            'unit' => 'pcs', 'cost_price' => 100, 'sell_price' => 150, 'is_active' => true,
        ]);
    }

    private function makeApprovedPr(array $overrides = []): PurchaseRequest
    {
        $pr = PurchaseRequest::create(array_merge([
            'number'        => 'PR-' . uniqid(),
            'requested_by'  => $this->user->id,
            'status'        => 'approved',
            'priority'      => 'normal',
            'purchase_type' => 'operational',
            'department_id' => $this->department->id,
            'reason'        => 'Approved test request',
            'approved_by'   => $this->user->id,
            'approved_at'   => now(),
        ], $overrides));

        $pr->items()->create([
            'product_id'      => $this->product->id,
            'quantity'        => 10,
            'estimated_price' => 100,
            'total'           => 1000,
        ]);

        return $pr;
    }

    // ── TUGAS 2: PR Form Revision ──

    public function test_pr_create_page_shows_purchase_type_and_department()
    {
        $response = $this->actingAs($this->user)->get(route('purchase-requests.create'));
        $response->assertStatus(200);
        $response->assertSee('purchase_type');
        $response->assertSee($this->department->name);
    }

    public function test_pr_store_requires_department_and_purchase_type()
    {
        $response = $this->actingAs($this->user)->post(route('purchase-requests.store'), [
            'priority' => 'normal',
            'reason'   => 'Test',
            'items'    => [
                ['product_id' => $this->product->id, 'quantity' => 5, 'estimated_price' => 100],
            ],
        ]);

        $response->assertSessionHasErrors(['department_id', 'purchase_type']);
    }

    public function test_pr_store_requires_reason()
    {
        $response = $this->actingAs($this->user)->post(route('purchase-requests.store'), [
            'priority'      => 'normal',
            'purchase_type' => 'operational',
            'department_id' => $this->department->id,
            'items'         => [
                ['product_id' => $this->product->id, 'quantity' => 5, 'estimated_price' => 100],
            ],
        ]);

        $response->assertSessionHasErrors('reason');
    }

    public function test_pr_store_with_all_new_fields()
    {
        $response = $this->actingAs($this->user)->post(route('purchase-requests.store'), [
            'priority'      => 'high',
            'purchase_type' => 'project_capex',
            'department_id' => $this->department->id,
            'reason'        => 'Need CAPEX equipment',
            'items'         => [
                ['product_id' => $this->product->id, 'quantity' => 5, 'estimated_price' => 200],
            ],
        ]);

        $response->assertRedirect(route('purchase-requests.index'));
        $this->assertDatabaseHas('purchase_requests', [
            'purchase_type' => 'project_capex',
            'department_id' => $this->department->id,
            'reason'        => 'Need CAPEX equipment',
        ]);
    }

    public function test_pr_update_with_new_fields()
    {
        $pr = PurchaseRequest::create([
            'number'        => 'PR-UPD01',
            'requested_by'  => $this->user->id,
            'status'        => 'draft',
            'priority'      => 'normal',
            'purchase_type' => 'operational',
            'department_id' => $this->department->id,
            'reason'        => 'Original reason',
        ]);
        $pr->items()->create([
            'product_id' => $this->product->id, 'quantity' => 5,
            'estimated_price' => 100, 'total' => 500,
        ]);

        $response = $this->actingAs($this->user)->put(route('purchase-requests.update', $pr), [
            'priority'      => 'urgent',
            'purchase_type' => 'project_sales',
            'department_id' => $this->department->id,
            'reason'        => 'Updated justification',
            'items'         => [
                ['product_id' => $this->product->id, 'quantity' => 10, 'estimated_price' => 150],
            ],
        ]);

        $response->assertRedirect();
        $pr->refresh();
        $this->assertEquals('project_sales', $pr->purchase_type);
        $this->assertEquals('Updated justification', $pr->reason);
    }

    public function test_pr_store_rejects_invalid_purchase_type()
    {
        $response = $this->actingAs($this->user)->post(route('purchase-requests.store'), [
            'priority'      => 'normal',
            'purchase_type' => 'invalid_type',
            'department_id' => $this->department->id,
            'reason'        => 'Test',
            'items'         => [
                ['product_id' => $this->product->id, 'quantity' => 5, 'estimated_price' => 100],
            ],
        ]);

        $response->assertSessionHasErrors('purchase_type');
    }

    // ── TUGAS 1: PR→PO Conversion ──

    public function test_conversion_page_shows_enhanced_form()
    {
        $pr = $this->makeApprovedPr();

        $response = $this->actingAs($this->user)->get(route('purchase-requests.convert', $pr));

        $response->assertStatus(200);
        $response->assertSee('conversion_sig');
        $response->assertSee('payment_terms');
        $response->assertSee('shipping_address');
        $response->assertSee($this->department->name);
    }

    public function test_conversion_requires_hmac_signature()
    {
        $pr = $this->makeApprovedPr();

        $response = $this->actingAs($this->user)->post(route('purchase-requests.store-conversion', $pr), [
            'supplier_id'    => $this->supplier->id,
            'warehouse_id'   => $this->warehouse->id,
            'department_id'  => $this->department->id,
            'payment_terms'  => 'net_30',
            'conversion_sig' => 'invalid_signature',
        ]);

        $response->assertStatus(403);
    }

    public function test_successful_conversion_with_all_fields()
    {
        $pr = $this->makeApprovedPr(['purchase_type' => 'project_capex']);
        $sig = PurchaseRequest::conversionHmac($pr->id);

        $response = $this->actingAs($this->user)->post(route('purchase-requests.store-conversion', $pr), [
            'supplier_id'      => $this->supplier->id,
            'warehouse_id'     => $this->warehouse->id,
            'department_id'    => $this->department->id,
            'payment_terms'    => 'net_60',
            'shipping_address' => '123 Warehouse St',
            'expected_date'    => now()->addDays(14)->format('Y-m-d'),
            'conversion_sig'   => $sig,
        ]);

        $response->assertRedirect();

        $pr->refresh();
        $this->assertEquals('converted', $pr->status);

        $po = PurchaseOrder::where('purchase_request_id', $pr->id)->first();
        $this->assertNotNull($po);
        $this->assertEquals('project_capex', $po->purchase_type);
        $this->assertEquals($this->department->id, $po->department_id);
        $this->assertEquals('net_60', $po->payment_terms);
        $this->assertEquals('123 Warehouse St', $po->shipping_address);
        $this->assertEquals('normal', $po->priority);
        $this->assertEquals($pr->project_id, $po->project_id);
    }

    public function test_conversion_copies_items_to_po()
    {
        $pr = $this->makeApprovedPr();
        $sig = PurchaseRequest::conversionHmac($pr->id);

        $this->actingAs($this->user)->post(route('purchase-requests.store-conversion', $pr), [
            'supplier_id'    => $this->supplier->id,
            'warehouse_id'   => $this->warehouse->id,
            'department_id'  => $this->department->id,
            'payment_terms'  => 'net_30',
            'conversion_sig' => $sig,
        ]);

        $po = PurchaseOrder::where('purchase_request_id', $pr->id)->first();
        $this->assertCount(1, $po->items);
        $this->assertEquals($this->product->id, $po->items->first()->product_id);
        $this->assertEquals(10, $po->items->first()->quantity);
        $this->assertEquals(100, $po->items->first()->unit_price);
    }

    public function test_conversion_prevents_non_approved_pr()
    {
        $pr = PurchaseRequest::create([
            'number'        => 'PR-DRAFT1',
            'requested_by'  => $this->user->id,
            'status'        => 'draft',
            'priority'      => 'normal',
            'purchase_type' => 'operational',
            'department_id' => $this->department->id,
            'reason'        => 'Draft PR',
        ]);

        $response = $this->actingAs($this->user)->get(route('purchase-requests.convert', $pr));
        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_conversion_prevents_duplicate_already_converted()
    {
        $pr = $this->makeApprovedPr();
        $sig = PurchaseRequest::conversionHmac($pr->id);

        // First conversion
        $this->actingAs($this->user)->post(route('purchase-requests.store-conversion', $pr), [
            'supplier_id'    => $this->supplier->id,
            'warehouse_id'   => $this->warehouse->id,
            'department_id'  => $this->department->id,
            'payment_terms'  => 'net_30',
            'conversion_sig' => $sig,
        ]);

        $pr->refresh();
        $this->assertEquals('converted', $pr->status);

        // Try accessing convert page again
        $response = $this->actingAs($this->user)->get(route('purchase-requests.convert', $pr));
        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_conversion_requires_payment_terms()
    {
        $pr = $this->makeApprovedPr();
        $sig = PurchaseRequest::conversionHmac($pr->id);

        $response = $this->actingAs($this->user)->post(route('purchase-requests.store-conversion', $pr), [
            'supplier_id'    => $this->supplier->id,
            'warehouse_id'   => $this->warehouse->id,
            'department_id'  => $this->department->id,
            'conversion_sig' => $sig,
        ]);

        $response->assertSessionHasErrors('payment_terms');
    }

    public function test_conversion_requires_department()
    {
        $pr = $this->makeApprovedPr();
        $sig = PurchaseRequest::conversionHmac($pr->id);

        $response = $this->actingAs($this->user)->post(route('purchase-requests.store-conversion', $pr), [
            'supplier_id'    => $this->supplier->id,
            'warehouse_id'   => $this->warehouse->id,
            'payment_terms'  => 'net_30',
            'conversion_sig' => $sig,
        ]);

        $response->assertSessionHasErrors('department_id');
    }

    public function test_conversion_rejects_invalid_payment_terms()
    {
        $pr = $this->makeApprovedPr();
        $sig = PurchaseRequest::conversionHmac($pr->id);

        $response = $this->actingAs($this->user)->post(route('purchase-requests.store-conversion', $pr), [
            'supplier_id'    => $this->supplier->id,
            'warehouse_id'   => $this->warehouse->id,
            'department_id'  => $this->department->id,
            'payment_terms'  => 'invalid_terms',
            'conversion_sig' => $sig,
        ]);

        $response->assertSessionHasErrors('payment_terms');
    }

    // ── TUGAS 3: PO Form Revision ──

    public function test_po_show_displays_source_pr()
    {
        $pr = $this->makeApprovedPr();
        $sig = PurchaseRequest::conversionHmac($pr->id);

        $this->actingAs($this->user)->post(route('purchase-requests.store-conversion', $pr), [
            'supplier_id'    => $this->supplier->id,
            'warehouse_id'   => $this->warehouse->id,
            'department_id'  => $this->department->id,
            'payment_terms'  => 'net_30',
            'conversion_sig' => $sig,
        ]);

        $po = PurchaseOrder::where('purchase_request_id', $pr->id)->first();
        $response = $this->actingAs($this->user)->get(route('purchasing.show', $po));

        $response->assertStatus(200);
        $response->assertSee($pr->number);
    }

    public function test_po_show_displays_payment_terms()
    {
        $pr = $this->makeApprovedPr();
        $sig = PurchaseRequest::conversionHmac($pr->id);

        $this->actingAs($this->user)->post(route('purchase-requests.store-conversion', $pr), [
            'supplier_id'    => $this->supplier->id,
            'warehouse_id'   => $this->warehouse->id,
            'department_id'  => $this->department->id,
            'payment_terms'  => 'net_60',
            'conversion_sig' => $sig,
        ]);

        $po = PurchaseOrder::where('purchase_request_id', $pr->id)->first();
        $response = $this->actingAs($this->user)->get(route('purchasing.show', $po));

        $response->assertStatus(200);
        $response->assertSee('Net 60');
    }

    // ── TUGAS 4: Security & Integrity ──

    public function test_hmac_signature_is_valid()
    {
        $sig = PurchaseRequest::conversionHmac(42);
        $this->assertNotEmpty($sig);
        $this->assertEquals(64, strlen($sig)); // SHA-256 hex output
    }

    public function test_hmac_signature_is_deterministic()
    {
        $sig1 = PurchaseRequest::conversionHmac(42);
        $sig2 = PurchaseRequest::conversionHmac(42);
        $this->assertEquals($sig1, $sig2);
    }

    public function test_hmac_signature_differs_per_id()
    {
        $sig1 = PurchaseRequest::conversionHmac(1);
        $sig2 = PurchaseRequest::conversionHmac(2);
        $this->assertNotEquals($sig1, $sig2);
    }

    public function test_conversion_creates_audit_log()
    {
        $pr = $this->makeApprovedPr();
        $sig = PurchaseRequest::conversionHmac($pr->id);

        $this->actingAs($this->user)->post(route('purchase-requests.store-conversion', $pr), [
            'supplier_id'    => $this->supplier->id,
            'warehouse_id'   => $this->warehouse->id,
            'department_id'  => $this->department->id,
            'payment_terms'  => 'net_30',
            'conversion_sig' => $sig,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'convert_to_po',
        ]);
    }

    public function test_confirmed_po_cannot_be_edited()
    {
        $pr = $this->makeApprovedPr();
        $sig = PurchaseRequest::conversionHmac($pr->id);

        $this->actingAs($this->user)->post(route('purchase-requests.store-conversion', $pr), [
            'supplier_id'    => $this->supplier->id,
            'warehouse_id'   => $this->warehouse->id,
            'department_id'  => $this->department->id,
            'payment_terms'  => 'net_30',
            'conversion_sig' => $sig,
        ]);

        $po = PurchaseOrder::where('purchase_request_id', $pr->id)->first();

        // Confirm the PO
        $this->actingAs($this->user)->post(route('purchasing.confirm', $po));
        $po->refresh();
        $this->assertEquals('confirmed', $po->status);

        // Try to edit
        $response = $this->actingAs($this->user)->get(route('purchasing.edit', $po));
        $response->assertRedirect(route('purchasing.show', $po));
    }

    public function test_po_show_displays_lock_badge_for_confirmed()
    {
        $pr = $this->makeApprovedPr();
        $sig = PurchaseRequest::conversionHmac($pr->id);

        $this->actingAs($this->user)->post(route('purchase-requests.store-conversion', $pr), [
            'supplier_id'    => $this->supplier->id,
            'warehouse_id'   => $this->warehouse->id,
            'department_id'  => $this->department->id,
            'payment_terms'  => 'net_30',
            'conversion_sig' => $sig,
        ]);

        $po = PurchaseOrder::where('purchase_request_id', $pr->id)->first();
        $this->actingAs($this->user)->post(route('purchasing.confirm', $po));

        $response = $this->actingAs($this->user)->get(route('purchasing.show', $po));
        $response->assertStatus(200);
        $response->assertSee(__('messages.po_locked_label'));
    }

    // ── TUGAS 5: UI/UX Dashboard ──

    public function test_pr_index_shows_status_badges()
    {
        PurchaseRequest::create([
            'number'        => 'PR-BADGE1',
            'requested_by'  => $this->user->id,
            'status'        => 'draft',
            'priority'      => 'normal',
            'purchase_type' => 'operational',
            'department_id' => $this->department->id,
            'reason'        => 'Test',
        ]);

        $response = $this->actingAs($this->user)->get(route('purchase-requests.index'));
        $response->assertStatus(200);
        $response->assertSee(__('messages.pr_status_draft'));
    }

    public function test_pr_show_displays_department_and_purchase_type()
    {
        $pr = $this->makeApprovedPr(['purchase_type' => 'project_capex']);

        $response = $this->actingAs($this->user)->get(route('purchase-requests.show', $pr));
        $response->assertStatus(200);
        $response->assertSee($this->department->name);
        $response->assertSee(__('messages.po_purchase_type_project_capex'));
    }

    // ── Model Helpers ──

    public function test_purchase_request_purchase_type_options()
    {
        $options = PurchaseRequest::purchaseTypeOptions();
        $this->assertEquals(['operational', 'project_sales', 'project_capex'], $options);
    }

    public function test_purchase_order_payment_terms_options()
    {
        $options = PurchaseOrder::paymentTermsOptions();
        $this->assertContains('cash', $options);
        $this->assertContains('net_30', $options);
        $this->assertContains('net_90', $options);
        $this->assertCount(6, $options);
    }

    public function test_purchase_order_has_purchase_request_relationship()
    {
        $pr = $this->makeApprovedPr();
        $sig = PurchaseRequest::conversionHmac($pr->id);

        $this->actingAs($this->user)->post(route('purchase-requests.store-conversion', $pr), [
            'supplier_id'    => $this->supplier->id,
            'warehouse_id'   => $this->warehouse->id,
            'department_id'  => $this->department->id,
            'payment_terms'  => 'net_30',
            'conversion_sig' => $sig,
        ]);

        $po = PurchaseOrder::where('purchase_request_id', $pr->id)->first();
        $this->assertNotNull($po->purchaseRequest);
        $this->assertEquals($pr->id, $po->purchaseRequest->id);
    }

    public function test_purchase_request_has_department_relationship()
    {
        $pr = PurchaseRequest::create([
            'number'        => 'PR-DEPT01',
            'requested_by'  => $this->user->id,
            'status'        => 'draft',
            'priority'      => 'normal',
            'purchase_type' => 'operational',
            'department_id' => $this->department->id,
            'reason'        => 'Test',
        ]);

        $this->assertNotNull($pr->department);
        $this->assertEquals('Procurement', $pr->department->name);
    }

    // ── Conversion with different payment terms ──

    public function test_conversion_with_cash_payment_terms()
    {
        $pr = $this->makeApprovedPr();
        $sig = PurchaseRequest::conversionHmac($pr->id);

        $this->actingAs($this->user)->post(route('purchase-requests.store-conversion', $pr), [
            'supplier_id'    => $this->supplier->id,
            'warehouse_id'   => $this->warehouse->id,
            'department_id'  => $this->department->id,
            'payment_terms'  => 'cash',
            'conversion_sig' => $sig,
        ]);

        $po = PurchaseOrder::where('purchase_request_id', $pr->id)->first();
        $this->assertEquals('cash', $po->payment_terms);
    }

    public function test_conversion_with_cod_payment_terms()
    {
        $pr = $this->makeApprovedPr();
        $sig = PurchaseRequest::conversionHmac($pr->id);

        $this->actingAs($this->user)->post(route('purchase-requests.store-conversion', $pr), [
            'supplier_id'    => $this->supplier->id,
            'warehouse_id'   => $this->warehouse->id,
            'department_id'  => $this->department->id,
            'payment_terms'  => 'cod',
            'conversion_sig' => $sig,
        ]);

        $po = PurchaseOrder::where('purchase_request_id', $pr->id)->first();
        $this->assertEquals('cod', $po->payment_terms);
    }

    // ── Existing workflow tests with new field compatibility ──

    public function test_pr_full_workflow_draft_to_converted()
    {
        // Create
        $response = $this->actingAs($this->user)->post(route('purchase-requests.store'), [
            'priority'      => 'high',
            'purchase_type' => 'operational',
            'department_id' => $this->department->id,
            'reason'        => 'Full workflow test',
            'items'         => [
                ['product_id' => $this->product->id, 'quantity' => 10, 'estimated_price' => 100],
            ],
        ]);
        $response->assertRedirect();

        $pr = PurchaseRequest::latest()->first();
        $this->assertEquals('draft', $pr->status);

        // Submit
        $this->actingAs($this->user)->post(route('purchase-requests.submit', $pr));
        $pr->refresh();
        $this->assertEquals('pending', $pr->status);

        // Approve
        $this->actingAs($this->user)->post(route('purchase-requests.approve', $pr));
        $pr->refresh();
        $this->assertEquals('approved', $pr->status);

        // Convert
        $sig = PurchaseRequest::conversionHmac($pr->id);
        $this->actingAs($this->user)->post(route('purchase-requests.store-conversion', $pr), [
            'supplier_id'    => $this->supplier->id,
            'warehouse_id'   => $this->warehouse->id,
            'department_id'  => $this->department->id,
            'payment_terms'  => 'net_30',
            'conversion_sig' => $sig,
        ]);

        $pr->refresh();
        $this->assertEquals('converted', $pr->status);
        $this->assertDatabaseHas('purchase_orders', [
            'purchase_request_id' => $pr->id,
            'department_id'       => $this->department->id,
            'payment_terms'       => 'net_30',
        ]);
    }

    public function test_conversion_preserves_pr_priority_on_po()
    {
        $pr = $this->makeApprovedPr(['priority' => 'urgent']);
        $sig = PurchaseRequest::conversionHmac($pr->id);

        $this->actingAs($this->user)->post(route('purchase-requests.store-conversion', $pr), [
            'supplier_id'    => $this->supplier->id,
            'warehouse_id'   => $this->warehouse->id,
            'department_id'  => $this->department->id,
            'payment_terms'  => 'net_30',
            'conversion_sig' => $sig,
        ]);

        $po = PurchaseOrder::where('purchase_request_id', $pr->id)->first();
        $this->assertEquals('urgent', $po->priority);
    }

    public function test_po_recalculates_totals_after_conversion()
    {
        $pr = $this->makeApprovedPr();
        $sig = PurchaseRequest::conversionHmac($pr->id);

        $this->actingAs($this->user)->post(route('purchase-requests.store-conversion', $pr), [
            'supplier_id'    => $this->supplier->id,
            'warehouse_id'   => $this->warehouse->id,
            'department_id'  => $this->department->id,
            'payment_terms'  => 'net_30',
            'conversion_sig' => $sig,
        ]);

        $po = PurchaseOrder::where('purchase_request_id', $pr->id)->first();
        $this->assertEquals(1000, (float) $po->subtotal);
        $this->assertEquals(1000, (float) $po->total);
    }
}
