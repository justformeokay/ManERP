<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryStock extends Model
{
    use HasFactory;

    protected $fillable = ['product_id', 'warehouse_id', 'quantity', 'reserved_quantity'];

    protected function casts(): array
    {
        return [
            'quantity'          => 'decimal:2',
            'reserved_quantity' => 'decimal:2',
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

    // Helpers
    public function availableQuantity(): float
    {
        return max(0, $this->quantity - $this->reserved_quantity);
    }

    public function isLow(): bool
    {
        return $this->product
            && $this->product->min_stock > 0
            && $this->quantity <= $this->product->min_stock;
    }
}
