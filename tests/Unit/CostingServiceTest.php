<?php

namespace Tests\Unit;

use App\Models\BillOfMaterial;
use App\Models\BomItem;
use App\Models\ManufacturingOrder;
use App\Models\Product;
use App\Models\ProductionCost;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\CostingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CostingServiceTest extends TestCase
{
    use RefreshDatabase;

    private CostingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CostingService();
    }

    private function makeProduct(array $attrs = []): Product
    {
        return Product::create(array_merge([
            'sku'           => 'PRD-' . uniqid(),
            'name'          => 'Product',
            'type'          => 'raw_material',
            'unit'          => 'pcs',
            'cost_price'    => 100.00,
            'sell_price'    => 150.00,
            'labor_cost'    => 0,
            'overhead_cost' => 0,
            'standard_cost' => 0,
            'is_active'     => true,
        ], $attrs));
    }

    private function makeWarehouse(): Warehouse
    {
        return Warehouse::create([
            'name'      => 'Test Warehouse',
            'code'      => 'WH-' . uniqid(),
            'is_active' => true,
        ]);
    }

    public function test_calculate_production_cost_creates_record()
    {
        $finished = $this->makeProduct(['name' => 'Finished', 'labor_cost' => 10, 'overhead_cost' => 5]);
        $raw = $this->makeProduct(['name' => 'Raw', 'cost_price' => 50]);
        $warehouse = $this->makeWarehouse();
        $user = User::factory()->create();

        $bom = BillOfMaterial::create([
            'product_id' => $finished->id, 'name' => 'Test BOM',
            'output_quantity' => 1, 'is_active' => true,
        ]);
        BomItem::create(['bom_id' => $bom->id, 'product_id' => $raw->id, 'quantity' => 2, 'unit_cost' => 50, 'line_cost' => 100]);

        $mo = ManufacturingOrder::create([
            'product_id'       => $finished->id,
            'bom_id'           => $bom->id,
            'warehouse_id'     => $warehouse->id,
            'planned_quantity' => 10,
            'produced_quantity' => 10,
            'status'           => 'done',
            'created_by'       => $user->id,
        ]);

        $result = $this->service->calculateProductionCost($mo);

        $this->assertInstanceOf(ProductionCost::class, $result);
        $this->assertEquals($mo->id, $result->manufacturing_order_id);
        // Material: 2 * 50 * 10 = 1000
        $this->assertEquals(1000.00, $result->material_cost);
        // Labor: 10 * 10 = 100
        $this->assertEquals(100.00, $result->labor_cost);
        // Overhead: 5 * 10 = 50
        $this->assertEquals(50.00, $result->overhead_cost);
        // Total: 1000 + 100 + 50 = 1150
        $this->assertEquals(1150.00, $result->total_cost);
        // Per unit: 1150 / 10 = 115
        $this->assertEquals(115.00, round($result->cost_per_unit, 2));
    }

    public function test_update_standard_cost()
    {
        $product = $this->makeProduct(['name' => 'Std Cost Product', 'labor_cost' => 15, 'overhead_cost' => 10]);
        $raw = $this->makeProduct(['name' => 'Raw', 'cost_price' => 40]);

        $bom = BillOfMaterial::create([
            'product_id' => $product->id, 'name' => 'Standard BOM',
            'output_quantity' => 1, 'is_active' => true,
        ]);
        BomItem::create(['bom_id' => $bom->id, 'product_id' => $raw->id, 'quantity' => 3, 'unit_cost' => 40, 'line_cost' => 120]);

        $this->service->updateStandardCost($product);

        $product->refresh();
        // Material per unit: 3 * 40 / 1 = 120
        // Standard = 120 + 15 + 10 = 145
        $this->assertEquals(145.00, $product->standard_cost);
    }

    public function test_cost_variance_calculation()
    {
        $finished = $this->makeProduct([
            'name' => 'Variance Product', 'standard_cost' => 100,
            'labor_cost' => 10, 'overhead_cost' => 5,
        ]);
        $raw = $this->makeProduct(['name' => 'Raw', 'cost_price' => 80]);
        $warehouse = $this->makeWarehouse();
        $user = User::factory()->create();

        $bom = BillOfMaterial::create([
            'product_id' => $finished->id, 'name' => 'Variance BOM',
            'output_quantity' => 1, 'is_active' => true,
        ]);
        BomItem::create(['bom_id' => $bom->id, 'product_id' => $raw->id, 'quantity' => 1, 'unit_cost' => 80, 'line_cost' => 80]);

        $mo = ManufacturingOrder::create([
            'product_id'       => $finished->id,
            'bom_id'           => $bom->id,
            'warehouse_id'     => $warehouse->id,
            'planned_quantity' => 5,
            'produced_quantity' => 5,
            'status'           => 'done',
            'created_by'       => $user->id,
        ]);

        $variance = $this->service->getCostVariance($mo);

        // Standard: 100 * 5 = 500
        $this->assertEquals(500.00, $variance['standard_cost']);
        // Actual: material(80*5=400) + labor(10*5=50) + overhead(5*5=25) = 475
        $this->assertEquals(475.00, $variance['actual_cost']);
        // Variance: 475 - 500 = -25 (favorable)
        $this->assertEquals(-25.00, $variance['variance']);
    }

    public function test_production_cost_is_idempotent()
    {
        $finished = $this->makeProduct(['name' => 'Idempotent', 'labor_cost' => 0, 'overhead_cost' => 0]);
        $raw = $this->makeProduct(['name' => 'Raw', 'cost_price' => 100]);
        $warehouse = $this->makeWarehouse();
        $user = User::factory()->create();

        $bom = BillOfMaterial::create([
            'product_id' => $finished->id, 'name' => 'BOM',
            'output_quantity' => 1, 'is_active' => true,
        ]);
        BomItem::create(['bom_id' => $bom->id, 'product_id' => $raw->id, 'quantity' => 1, 'unit_cost' => 100, 'line_cost' => 100]);

        $mo = ManufacturingOrder::create([
            'product_id'       => $finished->id,
            'bom_id'           => $bom->id,
            'warehouse_id'     => $warehouse->id,
            'planned_quantity' => 1,
            'produced_quantity' => 1,
            'status'           => 'done',
            'created_by'       => $user->id,
        ]);

        // Calculate twice — should updateOrCreate, not duplicate
        $this->service->calculateProductionCost($mo);
        $this->service->calculateProductionCost($mo);

        $this->assertEquals(1, ProductionCost::where('manufacturing_order_id', $mo->id)->count());
    }
}
