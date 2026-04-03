<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockValuationLayer extends Model
{
    protected $fillable = [
        'product_id', 'warehouse_id', 'stock_movement_id',
        'direction', 'quantity', 'unit_cost', 'total_value',
        'remaining_qty', 'remaining_value', 'avg_cost_after',
        'reference_type', 'reference_id', 'description',
    ];

    protected function casts(): array
    {
        return [
            'quantity'        => 'decimal:2',
            'unit_cost'       => 'decimal:4',
            'total_value'     => 'decimal:4',
            'remaining_qty'   => 'decimal:2',
            'remaining_value' => 'decimal:4',
            'avg_cost_after'  => 'decimal:4',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function stockMovement(): BelongsTo
    {
        return $this->belongsTo(StockMovement::class);
    }
}
