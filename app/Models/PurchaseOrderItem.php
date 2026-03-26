<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_order_id', 'product_id', 'quantity',
        'received_quantity', 'unit_price', 'total',
    ];

    protected function casts(): array
    {
        return [
            'quantity'          => 'decimal:2',
            'received_quantity' => 'decimal:2',
            'unit_price'        => 'decimal:2',
            'total'             => 'decimal:2',
        ];
    }

    // Relationships
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function remainingQuantity(): float
    {
        return $this->quantity - $this->received_quantity;
    }
}
