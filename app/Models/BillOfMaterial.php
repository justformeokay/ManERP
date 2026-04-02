<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BillOfMaterial extends Model
{
    use HasFactory;

    protected $table = 'bill_of_materials';

    protected $fillable = [
        'product_id', 'parent_bom_id', 'name', 'description',
        'output_quantity', 'version', 'level', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'output_quantity' => 'decimal:2',
            'version'         => 'integer',
            'level'           => 'integer',
            'is_active'       => 'boolean',
        ];
    }

    // ── Relationships ──

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(BomItem::class, 'bom_id');
    }

    public function manufacturingOrders(): HasMany
    {
        return $this->hasMany(ManufacturingOrder::class, 'bom_id');
    }

    public function parentBom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_bom_id');
    }

    public function childBoms(): HasMany
    {
        return $this->hasMany(self::class, 'parent_bom_id');
    }

    // ── Multi-level BOM helpers ──

    /**
     * Get the flattened material list recursively.
     * Returns a collection of ['product_id', 'product', 'quantity', 'unit_cost', 'level'].
     */
    public function getFlattenedMaterials(float $parentQty = 1, int $depth = 0, array &$visited = []): array
    {
        if (in_array($this->id, $visited)) {
            return []; // prevent circular reference
        }
        $visited[] = $this->id;

        $materials = [];
        $this->loadMissing('items.product', 'items.subBom.items.product');

        foreach ($this->items as $item) {
            $requiredQty = ($item->quantity / $this->output_quantity) * $parentQty;

            if ($item->sub_bom_id && $item->subBom) {
                // Recurse into sub-BOM
                $subMaterials = $item->subBom->getFlattenedMaterials($requiredQty, $depth + 1, $visited);
                $materials = array_merge($materials, $subMaterials);
            } else {
                $materials[] = [
                    'product_id' => $item->product_id,
                    'product'    => $item->product,
                    'quantity'   => $requiredQty,
                    'unit_cost'  => $item->unit_cost ?: ($item->product->cost_price ?? 0),
                    'level'      => $depth,
                ];
            }
        }

        return $materials;
    }

    /**
     * Calculate the total material cost for this BOM.
     */
    public function calculateTotalCost(?float $quantity = null): float
    {
        $qty = $quantity ?? $this->output_quantity;
        $materials = $this->getFlattenedMaterials($qty);

        return collect($materials)->sum(fn($m) => $m['quantity'] * $m['unit_cost']);
    }

    /**
     * Get the maximum depth of the BOM tree.
     */
    public function getMaxDepth(array &$visited = []): int
    {
        if (in_array($this->id, $visited)) {
            return 0;
        }
        $visited[] = $this->id;

        $this->loadMissing('items.subBom');
        $maxChild = 0;

        foreach ($this->items as $item) {
            if ($item->sub_bom_id && $item->subBom) {
                $childDepth = $item->subBom->getMaxDepth($visited);
                $maxChild = max($maxChild, $childDepth + 1);
            }
        }

        return $maxChild;
    }

    /**
     * Create a new version of this BOM.
     */
    public function createNewVersion(): self
    {
        $newBom = $this->replicate(['version']);
        $newBom->version = $this->version + 1;
        $newBom->save();

        foreach ($this->items as $item) {
            $newBom->items()->create($item->only(['product_id', 'sub_bom_id', 'quantity', 'unit_cost', 'line_cost', 'notes']));
        }

        return $newBom;
    }

    // ── Scopes ──

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeTopLevel($query)
    {
        return $query->whereNull('parent_bom_id');
    }

    public function scopeSearch($query, ?string $term)
    {
        if (!$term) return $query;

        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhereHas('product', fn($p) => $p->where('name', 'like', "%{$term}%"));
        });
    }
}
