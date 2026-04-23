<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Product;
use App\Models\PurchaseRequest;
use App\Models\Setting;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseRequestWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Department $department;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $this->department = Department::create(['name' => 'General', 'code' => 'GEN', 'is_active' => true]);

        // Ensure settings exist for format_currency helper
        if (class_exists(Setting::class) && method_exists(Setting::class, 'query')) {
            Setting::firstOrCreate(['key' => 'currency_symbol'], ['value' => 'Rp']);
            Setting::firstOrCreate(['key' => 'currency_code'], ['value' => 'IDR']);
        }
    }

    private function makeProduct(): Product
    {
        return Product::create([
            'sku'        => 'TST-' . uniqid(),
            'name'       => 'Test Product',
            'type'       => 'raw_material',
            'unit'       => 'pcs',
            'cost_price' => 100,
            'sell_price' => 150,
            'is_active'  => true,
        ]);
    }

    public function test_can_list_purchase_requests()
    {
        $response = $this->actingAs($this->user)->get(route('purchase-requests.index'));
        $response->assertStatus(200);
    }

    public function test_can_create_purchase_request()
    {
        $product = $this->makeProduct();

        $response = $this->actingAs($this->user)->post(route('purchase-requests.store'), [
            'priority'      => 'normal',
            'purchase_type' => 'operational',
            'department_id' => $this->department->id,
            'required_date' => now()->addDays(7)->format('Y-m-d'),
            'reason'        => 'Need raw materials for production',
            'items'         => [
                [
                    'product_id'      => $product->id,
                    'quantity'        => 10,
                    'estimated_price' => 100,
                    'specification'   => 'Grade A',
                ],
            ],
        ]);

        $response->assertRedirect(route('purchase-requests.index'));
        $this->assertDatabaseHas('purchase_requests', [
            'status'   => 'draft',
            'priority' => 'normal',
        ]);
        $this->assertDatabaseHas('purchase_request_items', [
            'product_id' => $product->id,
            'quantity'    => 10,
        ]);
    }

    public function test_can_submit_for_approval()
    {
        $pr = PurchaseRequest::create([
            'number'       => 'PR-99999',
            'requested_by' => $this->user->id,
            'status'       => 'draft',
            'priority'     => 'high',
        ]);

        $response = $this->actingAs($this->user)->post(route('purchase-requests.submit', $pr));

        $response->assertRedirect();
        $pr->refresh();
        $this->assertEquals('pending', $pr->status);
    }

    public function test_can_approve_request()
    {
        $pr = PurchaseRequest::create([
            'number'       => 'PR-99998',
            'requested_by' => $this->user->id,
            'status'       => 'pending',
            'priority'     => 'normal',
        ]);

        $response = $this->actingAs($this->user)->post(route('purchase-requests.approve', $pr));

        $response->assertRedirect();
        $pr->refresh();
        $this->assertEquals('approved', $pr->status);
        $this->assertEquals($this->user->id, $pr->approved_by);
    }

    public function test_can_reject_request()
    {
        $pr = PurchaseRequest::create([
            'number'       => 'PR-99997',
            'requested_by' => $this->user->id,
            'status'       => 'pending',
            'priority'     => 'normal',
        ]);

        $response = $this->actingAs($this->user)->post(route('purchase-requests.reject', $pr), [
            'rejection_reason' => 'Budget exceeded',
        ]);

        $response->assertRedirect();
        $pr->refresh();
        $this->assertEquals('rejected', $pr->status);
        $this->assertEquals('Budget exceeded', $pr->rejection_reason);
    }

    public function test_can_convert_approved_pr_to_po()
    {
        $product = $this->makeProduct();
        $supplier = Supplier::create([
            'name' => 'Test Supplier', 'status' => 'active',
            'email' => 'supplier@test.com',
        ]);
        $warehouse = Warehouse::create([
            'name' => 'Main Warehouse', 'code' => 'WH-MAIN', 'is_active' => true,
        ]);

        $pr = PurchaseRequest::create([
            'number'        => 'PR-99996',
            'requested_by'  => $this->user->id,
            'status'        => 'approved',
            'priority'      => 'normal',
            'purchase_type' => 'operational',
            'department_id' => $this->department->id,
            'reason'        => 'Approved request',
        ]);
        $pr->items()->create([
            'product_id'      => $product->id,
            'quantity'        => 5,
            'estimated_price' => 100,
            'total'           => 500,
        ]);

        $sig = PurchaseRequest::conversionHmac($pr->id);

        $response = $this->actingAs($this->user)->post(
            route('purchase-requests.store-conversion', $pr),
            [
                'supplier_id'    => $supplier->id,
                'warehouse_id'   => $warehouse->id,
                'department_id'  => $this->department->id,
                'payment_terms'  => 'net_30',
                'expected_date'  => now()->addDays(14)->format('Y-m-d'),
                'conversion_sig' => $sig,
            ]
        );

        $response->assertRedirect();
        $pr->refresh();
        $this->assertEquals('converted', $pr->status);
        $this->assertDatabaseHas('purchase_orders', [
            'purchase_request_id' => $pr->id,
            'supplier_id'         => $supplier->id,
        ]);
    }

    public function test_cannot_convert_non_approved_pr()
    {
        $pr = PurchaseRequest::create([
            'number'       => 'PR-99995',
            'requested_by' => $this->user->id,
            'status'       => 'draft',
            'priority'     => 'normal',
        ]);

        $response = $this->actingAs($this->user)->get(route('purchase-requests.convert', $pr));

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_pr_auto_generates_number()
    {
        $pr = PurchaseRequest::create([
            'requested_by' => $this->user->id,
            'status'       => 'draft',
            'priority'     => 'normal',
        ]);

        $this->assertStringStartsWith('PR-', $pr->number);
    }
}
