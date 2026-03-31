<?php

namespace App\Notifications;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LowStockNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Product $product,
        public string $warehouseName,
        public float $currentQuantity,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'low_stock',
            'title' => 'Low Stock Alert',
            'message' => "{$this->product->name} ({$this->product->sku}) is low in {$this->warehouseName}. Current: {$this->currentQuantity}, Min: {$this->product->min_stock}",
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'product_sku' => $this->product->sku,
            'warehouse' => $this->warehouseName,
            'current_quantity' => $this->currentQuantity,
            'min_stock' => $this->product->min_stock,
        ];
    }
}
