<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'sales_order_id', 'product_id', 'quantity',
        'delivered_quantity', 'unit_price', 'discount', 'total',
    ];

    protected function casts(): array
    {
        return [
            'quantity'           => 'decimal:2',
            'delivered_quantity' => 'decimal:2',
            'unit_price'        => 'decimal:2',
            'discount'          => 'decimal:2',
            'total'             => 'decimal:2',
        ];
    }

    // Relationships
    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
