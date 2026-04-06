<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id', 'warehouse_id', 'type', 'quantity',
        'balance_after', 'unit_cost', 'total_value',
        'reference_type', 'reference_id', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'quantity'      => 'decimal:2',
            'balance_after' => 'decimal:2',
            'unit_cost'     => 'decimal:4',
            'total_value'   => 'decimal:4',
        ];
    }

    // Relationships
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Helpers
    public static function typeOptions(): array
    {
        return ['in', 'out', 'adjustment'];
    }

    public static function typeColors(): array
    {
        return [
            'in'         => 'bg-green-50 text-green-700 ring-green-600/20',
            'out'        => 'bg-red-50 text-red-700 ring-red-600/20',
            'adjustment' => 'bg-amber-50 text-amber-700 ring-amber-600/20',
            'transfer'   => 'bg-primary-50 text-primary-700 ring-primary-600/20',
        ];
    }

    public function getReferenceLabel(): string
    {
        if ($this->reference_type && $this->reference_id) {
            return $this->reference_type . ' #' . $this->reference_id;
        }
        return $this->reference_type ?? '—';
    }
}
