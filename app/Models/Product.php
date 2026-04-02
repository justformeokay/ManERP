<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'sku', 'name', 'description', 'category_id', 'type',
        'unit', 'cost_price', 'overhead_cost', 'labor_cost', 'standard_cost',
        'sell_price', 'min_stock', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'cost_price'    => 'decimal:2',
            'overhead_cost' => 'decimal:2',
            'labor_cost'    => 'decimal:2',
            'standard_cost' => 'decimal:2',
            'sell_price'    => 'decimal:2',
            'min_stock'     => 'integer',
            'is_active'     => 'boolean',
        ];
    }

    // Relationships
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function inventoryStocks(): HasMany
    {
        return $this->hasMany(InventoryStock::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSearch($query, ?string $term)
    {
        if (!$term) return $query;

        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('sku', 'like', "%{$term}%");
        });
    }

    // Helpers
    public function totalStock(): float
    {
        return $this->inventoryStocks->sum('quantity');
    }

    public function isLowStock(): bool
    {
        return $this->min_stock > 0 && $this->totalStock() <= $this->min_stock;
    }

    // Auto-generate SKU
    protected static function booted(): void
    {
        static::creating(function (Product $product) {
            if (empty($product->sku)) {
                $last = static::withTrashed()->max('id') ?? 0;
                $product->sku = 'PRD-' . str_pad($last + 1, 5, '0', STR_PAD_LEFT);
            }
        });
    }

    public static function typeOptions(): array
    {
        return ['raw_material', 'semi_finished', 'finished_good', 'consumable'];
    }

    public static function unitOptions(): array
    {
        return ['pcs', 'kg', 'ltr', 'mtr', 'box', 'set', 'roll', 'unit'];
    }
}
