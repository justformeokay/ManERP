<?php

namespace App\Services;

use App\Models\InventoryStock;
use App\Models\StockMovement;
use App\Models\StockTransfer;
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

    /**
     * Execute a stock transfer between two warehouses.
     */
    public function executeTransfer(StockTransfer $transfer): void
    {
        DB::transaction(function () use ($transfer) {
            // OUT from source warehouse
            $this->processMovement([
                'product_id'     => $transfer->product_id,
                'warehouse_id'   => $transfer->from_warehouse_id,
                'type'           => 'out',
                'quantity'       => $transfer->quantity,
                'reference_type' => 'stock_transfer',
                'reference_id'   => $transfer->id,
                'notes'          => "Transfer out → {$transfer->toWarehouse->name} ({$transfer->number})",
                'created_by'     => $transfer->created_by,
            ]);

            // IN to destination warehouse
            $this->processMovement([
                'product_id'     => $transfer->product_id,
                'warehouse_id'   => $transfer->to_warehouse_id,
                'type'           => 'in',
                'quantity'       => $transfer->quantity,
                'reference_type' => 'stock_transfer',
                'reference_id'   => $transfer->id,
                'notes'          => "Transfer in ← {$transfer->fromWarehouse->name} ({$transfer->number})",
                'created_by'     => $transfer->created_by,
            ]);

            $transfer->update([
                'status'       => 'completed',
                'completed_at' => now(),
            ]);
        });
    }

    /**
     * Reverse a completed transfer (for cancellation).
     */
    public function reverseTransfer(StockTransfer $transfer): void
    {
        DB::transaction(function () use ($transfer) {
            // IN back to source
            $this->processMovement([
                'product_id'     => $transfer->product_id,
                'warehouse_id'   => $transfer->from_warehouse_id,
                'type'           => 'in',
                'quantity'       => $transfer->quantity,
                'reference_type' => 'stock_transfer_cancel',
                'reference_id'   => $transfer->id,
                'notes'          => "Transfer reversed — {$transfer->number}",
                'created_by'     => auth()->id(),
            ]);

            // OUT from destination
            $this->processMovement([
                'product_id'     => $transfer->product_id,
                'warehouse_id'   => $transfer->to_warehouse_id,
                'type'           => 'out',
                'quantity'       => $transfer->quantity,
                'reference_type' => 'stock_transfer_cancel',
                'reference_id'   => $transfer->id,
                'notes'          => "Transfer reversed — {$transfer->number}",
                'created_by'     => auth()->id(),
            ]);

            $transfer->update(['status' => 'cancelled']);
        });
    }
}
