<?php

namespace App\Services;

use App\Models\InventoryStock;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\User;
use App\Models\Warehouse;
use App\Notifications\LowStockNotification;
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

            // Check for low stock and notify admins
            $this->checkLowStock($data['product_id'], $data['warehouse_id'], $newQuantity);

            return StockMovement::create([
                'product_id'     => $data['product_id'],
                'warehouse_id'   => $data['warehouse_id'],
                'type'           => $data['type'],
                'quantity'       => $data['quantity'],
                'balance_after'  => $newQuantity,
                'unit_cost'      => $data['unit_cost'] ?? 0,
                'total_value'    => bcmul((string) ($data['unit_cost'] ?? 0), (string) $data['quantity'], 4),
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
            $product = Product::find($transfer->product_id);
            $unitCost = (float) ($product->avg_cost ?? 0);

            // OUT from source warehouse
            $this->processMovement([
                'product_id'     => $transfer->product_id,
                'warehouse_id'   => $transfer->from_warehouse_id,
                'type'           => 'out',
                'quantity'       => $transfer->quantity,
                'unit_cost'      => $unitCost,
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
                'unit_cost'      => $unitCost,
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
            $product = Product::find($transfer->product_id);
            $unitCost = (float) ($product->avg_cost ?? 0);

            // IN back to source
            $this->processMovement([
                'product_id'     => $transfer->product_id,
                'warehouse_id'   => $transfer->from_warehouse_id,
                'type'           => 'in',
                'quantity'       => $transfer->quantity,
                'unit_cost'      => $unitCost,
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
                'unit_cost'      => $unitCost,
                'reference_type' => 'stock_transfer_cancel',
                'reference_id'   => $transfer->id,
                'notes'          => "Transfer reversed — {$transfer->number}",
                'created_by'     => auth()->id(),
            ]);

            $transfer->update(['status' => 'cancelled']);
        });
    }

    /**
     * Check if stock is low after a movement and notify admin users.
     */
    private function checkLowStock(int $productId, int $warehouseId, float $newQuantity): void
    {
        $product = Product::find($productId);

        if (!$product || $product->min_stock <= 0 || $newQuantity > $product->min_stock) {
            return;
        }

        $warehouse = Warehouse::find($warehouseId);
        $warehouseName = $warehouse->name ?? 'Unknown';

        // Notify all active admin users
        $admins = User::where('role', User::ROLE_ADMIN)
            ->where('status', User::STATUS_ACTIVE)
            ->get();

        foreach ($admins as $admin) {
            // Avoid duplicate notifications for same product+warehouse within last hour
            $exists = $admin->notifications()
                ->where('type', LowStockNotification::class)
                ->where('created_at', '>=', now()->subHour())
                ->whereJsonContains('data->product_id', $product->id)
                ->whereJsonContains('data->warehouse', $warehouseName)
                ->exists();

            if (!$exists) {
                $admin->notify(new LowStockNotification($product, $warehouseName, $newQuantity));
            }
        }
    }
}
