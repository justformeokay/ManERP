<?php

namespace App\Services;

use App\Models\InventoryStock;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class StockService
{
    /**
     * Process a stock movement and update inventory accordingly.
     */
    public function processMovement(array $data): StockMovement
    {
        return DB::transaction(function () use ($data) {
            $stock = InventoryStock::firstOrCreate(
                [
                    'product_id'   => $data['product_id'],
                    'warehouse_id' => $data['warehouse_id'],
                ],
                ['quantity' => 0, 'reserved_quantity' => 0]
            );

            // Lock the row for update
            $stock = InventoryStock::where('id', $stock->id)->lockForUpdate()->first();

            $newQuantity = match ($data['type']) {
                'in'         => $stock->quantity + $data['quantity'],
                'out'        => $stock->quantity - $data['quantity'],
                'adjustment' => $data['quantity'], // Set to exact value
                default      => throw new InvalidArgumentException("Invalid movement type: {$data['type']}"),
            };

            if ($newQuantity < 0) {
                throw new InvalidArgumentException(
                    "Insufficient stock. Available: {$stock->quantity}, requested out: {$data['quantity']}"
                );
            }

            $stock->update(['quantity' => $newQuantity]);

            return StockMovement::create([
                'product_id'     => $data['product_id'],
                'warehouse_id'   => $data['warehouse_id'],
                'type'           => $data['type'],
                'quantity'       => $data['quantity'],
                'balance_after'  => $newQuantity,
                'reference_type' => $data['reference_type'] ?? null,
                'reference_id'   => $data['reference_id'] ?? null,
                'notes'          => $data['notes'] ?? null,
                'created_by'     => $data['created_by'] ?? null,
            ]);
        });
    }
}
