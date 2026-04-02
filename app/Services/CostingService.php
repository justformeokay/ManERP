<?php

namespace App\Services;

use App\Models\BillOfMaterial;
use App\Models\ManufacturingOrder;
use App\Models\Product;
use App\Models\ProductionCost;
use Illuminate\Support\Facades\DB;

class CostingService
{
    /**
     * Calculate BOM cost breakdown (material only).
     */
    public function calculateBomCost(BillOfMaterial $bom, ?float $quantity = null): array
    {
        $qty = $quantity ?? $bom->output_quantity;
        $materials = $bom->getFlattenedMaterials($qty);

        $breakdown = [];
        $totalMaterial = 0;

        foreach ($materials as $mat) {
            $lineCost = $mat['quantity'] * $mat['unit_cost'];
            $totalMaterial += $lineCost;

            $breakdown[] = [
                'product_id'   => $mat['product_id'],
                'product_name' => $mat['product']->name ?? 'Unknown',
                'sku'          => $mat['product']->sku ?? '',
                'quantity'     => round($mat['quantity'], 4),
                'unit_cost'    => round($mat['unit_cost'], 2),
                'line_cost'    => round($lineCost, 2),
                'level'        => $mat['level'],
            ];
        }

        return [
            'materials'      => $breakdown,
            'material_cost'  => round($totalMaterial, 2),
            'output_quantity' => $qty,
            'cost_per_unit'  => $qty > 0 ? round($totalMaterial / $qty, 4) : 0,
        ];
    }

    /**
     * Calculate the full production cost (HPP) for a manufacturing order.
     * Includes material + labor + overhead.
     */
    public function calculateProductionCost(ManufacturingOrder $mo): ProductionCost
    {
        return DB::transaction(function () use ($mo) {
            $mo->loadMissing('bom.items.product', 'bom.items.subBom.items.product', 'product');

            $producedQty = $mo->produced_quantity ?: $mo->planned_quantity;
            $bomCost = $this->calculateBomCost($mo->bom, $producedQty);
            $materialCost = $bomCost['material_cost'];

            // Labor and overhead from the product's settings
            $laborCost = ($mo->product->labor_cost ?? 0) * $producedQty;
            $overheadCost = ($mo->product->overhead_cost ?? 0) * $producedQty;
            $totalCost = $materialCost + $laborCost + $overheadCost;
            $costPerUnit = $producedQty > 0 ? $totalCost / $producedQty : 0;

            return ProductionCost::updateOrCreate(
                ['manufacturing_order_id' => $mo->id],
                [
                    'product_id'        => $mo->product_id,
                    'material_cost'     => round($materialCost, 2),
                    'labor_cost'        => round($laborCost, 2),
                    'overhead_cost'     => round($overheadCost, 2),
                    'total_cost'        => round($totalCost, 2),
                    'cost_per_unit'     => round($costPerUnit, 4),
                    'produced_quantity' => $producedQty,
                    'cost_breakdown'    => $bomCost['materials'],
                ]
            );
        });
    }

    /**
     * Update a product's standard cost based on its active BOM.
     */
    public function updateStandardCost(Product $product): void
    {
        $bom = BillOfMaterial::where('product_id', $product->id)->active()->latest()->first();
        if (!$bom) return;

        $bomCost = $this->calculateBomCost($bom, 1);
        $materialPerUnit = $bomCost['cost_per_unit'];
        $standardCost = $materialPerUnit + ($product->labor_cost ?? 0) + ($product->overhead_cost ?? 0);

        $product->update(['standard_cost' => round($standardCost, 2)]);
    }

    /**
     * Calculate cost variance: standard vs actual for a manufacturing order.
     */
    public function getCostVariance(ManufacturingOrder $mo): array
    {
        $productionCost = ProductionCost::where('manufacturing_order_id', $mo->id)->first();
        if (!$productionCost) {
            $productionCost = $this->calculateProductionCost($mo);
        }

        $standardCost = ($mo->product->standard_cost ?? 0) * $productionCost->produced_quantity;

        return [
            'standard_cost'   => round($standardCost, 2),
            'actual_cost'     => round($productionCost->total_cost, 2),
            'variance'        => round($productionCost->total_cost - $standardCost, 2),
            'variance_pct'    => $standardCost > 0
                ? round((($productionCost->total_cost - $standardCost) / $standardCost) * 100, 2)
                : 0,
            'cost_per_unit'   => $productionCost->cost_per_unit,
            'produced_qty'    => $productionCost->produced_quantity,
        ];
    }
}
