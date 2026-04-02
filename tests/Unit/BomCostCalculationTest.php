<?php

namespace Tests\Unit;

use App\Models\BillOfMaterial;
use App\Models\BomItem;
use App\Models\Product;
use App\Services\CostingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BomCostCalculationTest extends TestCase
{
    use RefreshDatabase;

    private CostingService $costingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->costingService = new CostingService();
    }

    private function makeProduct(array $attrs = []): Product
    {
        return Product::create(array_merge([
            'sku'        => 'PRD-' . uniqid(),
            'name'       => 'Test Product',
            'type'       => 'raw_material',
            'unit'       => 'pcs',
            'cost_price' => 100.00,
            'sell_price' => 150.00,
            'is_active'  => true,
        ], $attrs));
    }

    public function test_single_level_bom_cost_calculation()
    {
        $finished = $this->makeProduct(['name' => 'Finished Good', 'type' => 'finished_good', 'cost_price' => 0]);
        $materialA = $this->makeProduct(['name' => 'Material A', 'cost_price' => 50.00]);
        $materialB = $this->makeProduct(['name' => 'Material B', 'cost_price' => 30.00]);

        $bom = BillOfMaterial::create([
            'product_id'      => $finished->id,
            'name'            => 'Test BOM',
            'output_quantity' => 1,
            'is_active'       => true,
        ]);

        BomItem::create(['bom_id' => $bom->id, 'product_id' => $materialA->id, 'quantity' => 2, 'unit_cost' => 50, 'line_cost' => 100]);
        BomItem::create(['bom_id' => $bom->id, 'product_id' => $materialB->id, 'quantity' => 3, 'unit_cost' => 30, 'line_cost' => 90]);

        $result = $this->costingService->calculateBomCost($bom);

        $this->assertEquals(190.00, $result['material_cost']);
        $this->assertCount(2, $result['materials']);
        $this->assertEquals(1, $result['output_quantity']);
        $this->assertEquals(190.00, $result['cost_per_unit']);
    }

    public function test_bom_cost_scales_with_quantity()
    {
        $finished = $this->makeProduct(['name' => 'Finished Good', 'cost_price' => 0]);
        $material = $this->makeProduct(['name' => 'Material', 'cost_price' => 25.00]);

        $bom = BillOfMaterial::create([
            'product_id'      => $finished->id,
            'name'            => 'Scaled BOM',
            'output_quantity' => 1,
            'is_active'       => true,
        ]);

        BomItem::create(['bom_id' => $bom->id, 'product_id' => $material->id, 'quantity' => 4, 'unit_cost' => 25, 'line_cost' => 100]);

        $result = $this->costingService->calculateBomCost($bom, 10);

        $this->assertEquals(1000.00, $result['material_cost']); // 4 * 25 * 10
        $this->assertEquals(10, $result['output_quantity']);
        $this->assertEquals(100.00, $result['cost_per_unit']); // 1000 / 10
    }

    public function test_multi_level_bom_cost_calculation()
    {
        $finishedProduct = $this->makeProduct(['name' => 'Final Assembly', 'cost_price' => 0]);
        $subAssemblyProduct = $this->makeProduct(['name' => 'Sub-Assembly', 'cost_price' => 0]);
        $rawA = $this->makeProduct(['name' => 'Raw A', 'cost_price' => 10.00]);
        $rawB = $this->makeProduct(['name' => 'Raw B', 'cost_price' => 20.00]);
        $rawC = $this->makeProduct(['name' => 'Raw C', 'cost_price' => 5.00]);

        // Sub-BOM: 2x Raw A + 1x Raw B = 2*10 + 1*20 = 40 per sub-assembly
        $subBom = BillOfMaterial::create([
            'product_id'      => $subAssemblyProduct->id,
            'name'            => 'Sub-Assembly BOM',
            'output_quantity' => 1,
            'is_active'       => true,
        ]);
        BomItem::create(['bom_id' => $subBom->id, 'product_id' => $rawA->id, 'quantity' => 2, 'unit_cost' => 10, 'line_cost' => 20]);
        BomItem::create(['bom_id' => $subBom->id, 'product_id' => $rawB->id, 'quantity' => 1, 'unit_cost' => 20, 'line_cost' => 20]);

        // Main BOM: 1x Sub-Assembly + 3x Raw C = 40 + 3*5 = 55 per unit
        $mainBom = BillOfMaterial::create([
            'product_id'      => $finishedProduct->id,
            'name'            => 'Main BOM',
            'output_quantity' => 1,
            'is_active'       => true,
        ]);
        BomItem::create(['bom_id' => $mainBom->id, 'product_id' => $subAssemblyProduct->id, 'sub_bom_id' => $subBom->id, 'quantity' => 1, 'unit_cost' => 0, 'line_cost' => 0]);
        BomItem::create(['bom_id' => $mainBom->id, 'product_id' => $rawC->id, 'quantity' => 3, 'unit_cost' => 5, 'line_cost' => 15]);

        $result = $this->costingService->calculateBomCost($mainBom);

        // Flattened: 2x Raw A @ 10 = 20, 1x Raw B @ 20 = 20, 3x Raw C @ 5 = 15 => total = 55
        $this->assertEquals(55.00, $result['material_cost']);
        $this->assertCount(3, $result['materials']); // 3 raw materials flattened
    }

    public function test_flattened_materials_includes_depth_info()
    {
        $finishedProduct = $this->makeProduct(['name' => 'Assembly', 'cost_price' => 0]);
        $subProduct = $this->makeProduct(['name' => 'Sub Part', 'cost_price' => 0]);
        $raw = $this->makeProduct(['name' => 'Raw', 'cost_price' => 15.00]);

        $subBom = BillOfMaterial::create([
            'product_id' => $subProduct->id, 'name' => 'Sub BOM', 'output_quantity' => 1, 'is_active' => true,
        ]);
        BomItem::create(['bom_id' => $subBom->id, 'product_id' => $raw->id, 'quantity' => 1, 'unit_cost' => 15, 'line_cost' => 15]);

        $mainBom = BillOfMaterial::create([
            'product_id' => $finishedProduct->id, 'name' => 'Main BOM', 'output_quantity' => 1, 'is_active' => true,
        ]);
        BomItem::create(['bom_id' => $mainBom->id, 'product_id' => $subProduct->id, 'sub_bom_id' => $subBom->id, 'quantity' => 1, 'unit_cost' => 0, 'line_cost' => 0]);

        $flattened = $mainBom->getFlattenedMaterials();

        $this->assertCount(1, $flattened);
        $this->assertEquals(1, $flattened[0]['level']); // depth 1 because it's from sub-bom
    }

    public function test_max_depth_calculation()
    {
        $p1 = $this->makeProduct(['name' => 'L0']);
        $p2 = $this->makeProduct(['name' => 'L1']);
        $p3 = $this->makeProduct(['name' => 'L2 Raw', 'cost_price' => 10]);

        $bom2 = BillOfMaterial::create(['product_id' => $p2->id, 'name' => 'Level 2', 'output_quantity' => 1, 'is_active' => true]);
        BomItem::create(['bom_id' => $bom2->id, 'product_id' => $p3->id, 'quantity' => 1, 'unit_cost' => 10, 'line_cost' => 10]);

        $bom1 = BillOfMaterial::create(['product_id' => $p2->id, 'name' => 'Level 1', 'output_quantity' => 1, 'is_active' => true]);
        BomItem::create(['bom_id' => $bom1->id, 'product_id' => $p2->id, 'sub_bom_id' => $bom2->id, 'quantity' => 1, 'unit_cost' => 0, 'line_cost' => 0]);

        $bom0 = BillOfMaterial::create(['product_id' => $p1->id, 'name' => 'Level 0', 'output_quantity' => 1, 'is_active' => true]);
        BomItem::create(['bom_id' => $bom0->id, 'product_id' => $p2->id, 'sub_bom_id' => $bom1->id, 'quantity' => 1, 'unit_cost' => 0, 'line_cost' => 0]);

        $this->assertEquals(2, $bom0->getMaxDepth());
        $this->assertEquals(1, $bom1->getMaxDepth());
        $this->assertEquals(0, $bom2->getMaxDepth());
    }

    public function test_bom_version_creation()
    {
        $product = $this->makeProduct(['name' => 'Versioned']);
        $raw = $this->makeProduct(['name' => 'Raw Material', 'cost_price' => 20]);

        $bom = BillOfMaterial::create([
            'product_id' => $product->id, 'name' => 'Original BOM', 'output_quantity' => 1,
            'is_active' => true, 'version' => 1,
        ]);
        BomItem::create(['bom_id' => $bom->id, 'product_id' => $raw->id, 'quantity' => 5, 'unit_cost' => 20, 'line_cost' => 100]);

        $newBom = $bom->createNewVersion();

        $this->assertEquals(2, $newBom->version);
        $this->assertEquals($bom->name, $newBom->name);
        $this->assertEquals($bom->product_id, $newBom->product_id);
        $this->assertCount(1, $newBom->items);
        $this->assertEquals(5, $newBom->items->first()->quantity);
        $this->assertNotEquals($bom->id, $newBom->id);
    }

    public function test_circular_reference_protection()
    {
        $p = $this->makeProduct(['name' => 'Circular']);

        $bom = BillOfMaterial::create(['product_id' => $p->id, 'name' => 'Circular BOM', 'output_quantity' => 1, 'is_active' => true]);
        // This item points back to its own BOM (circular reference)
        BomItem::create(['bom_id' => $bom->id, 'product_id' => $p->id, 'sub_bom_id' => $bom->id, 'quantity' => 1, 'unit_cost' => 0, 'line_cost' => 0]);

        // Should not infinite loop
        $materials = $bom->getFlattenedMaterials();
        $this->assertIsArray($materials);
    }

    public function test_bom_output_quantity_division()
    {
        $finished = $this->makeProduct(['name' => 'Batch Product', 'cost_price' => 0]);
        $raw = $this->makeProduct(['name' => 'Raw', 'cost_price' => 100.00]);

        // BOM produces 10 units using 5 raw materials
        $bom = BillOfMaterial::create([
            'product_id' => $finished->id, 'name' => 'Batch BOM', 'output_quantity' => 10, 'is_active' => true,
        ]);
        BomItem::create(['bom_id' => $bom->id, 'product_id' => $raw->id, 'quantity' => 5, 'unit_cost' => 100, 'line_cost' => 500]);

        $result = $this->costingService->calculateBomCost($bom, 1);

        // For 1 unit: 5/10 * 1 = 0.5 raw => 0.5 * 100 = 50
        $this->assertEquals(50.00, $result['material_cost']);
        $this->assertEquals(50.00, $result['cost_per_unit']);
    }
}
