<?php

namespace App\Notifications;

use App\Models\PurchaseOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PurchaseOrderReceivedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public PurchaseOrder $order,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'purchase_order_received',
            'title' => 'Purchase Order Received',
            'message' => "Purchase Order {$this->order->number} has been received. Supplier: {$this->order->supplier->name}. Total: " . number_format($this->order->total_amount, 2),
            'order_id' => $this->order->id,
            'order_number' => $this->order->number,
            'supplier_name' => $this->order->supplier->name,
            'total_amount' => $this->order->total_amount,
        ];
    }
}
