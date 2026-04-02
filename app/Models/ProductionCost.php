<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionCost extends Model
{
    use HasFactory;

    protected $fillable = [
        'manufacturing_order_id', 'product_id',
        'material_cost', 'labor_cost', 'overhead_cost',
        'total_cost', 'cost_per_unit', 'produced_quantity',
        'cost_breakdown', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'material_cost'    => 'decimal:2',
            'labor_cost'       => 'decimal:2',
            'overhead_cost'    => 'decimal:2',
            'total_cost'       => 'decimal:2',
            'cost_per_unit'    => 'decimal:4',
            'produced_quantity' => 'integer',
            'cost_breakdown'   => 'array',
        ];
    }

    public function manufacturingOrder(): BelongsTo
    {
        return $this->belongsTo(ManufacturingOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
