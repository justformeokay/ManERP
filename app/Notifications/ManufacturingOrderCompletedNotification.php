<?php

namespace App\Notifications;

use App\Models\ManufacturingOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ManufacturingOrderCompletedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public ManufacturingOrder $order,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'manufacturing_order_completed',
            'title' => 'Manufacturing Order Completed',
            'message' => "Manufacturing Order {$this->order->number} has been completed. Product: {$this->order->product->name}. Quantity: {$this->order->produced_quantity}",
            'order_id' => $this->order->id,
            'order_number' => $this->order->number,
            'product_name' => $this->order->product->name,
            'produced_quantity' => $this->order->produced_quantity,
        ];
    }
}
