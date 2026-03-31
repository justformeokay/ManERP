<?php

namespace App\Notifications;

use App\Models\SalesOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SalesOrderConfirmedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public SalesOrder $order,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'sales_order_confirmed',
            'title' => 'Sales Order Confirmed',
            'message' => "Sales Order {$this->order->number} has been confirmed. Client: {$this->order->client->name}. Total: " . number_format($this->order->total_amount, 2),
            'order_id' => $this->order->id,
            'order_number' => $this->order->number,
            'client_name' => $this->order->client->name,
            'total_amount' => $this->order->total_amount,
        ];
    }
}
