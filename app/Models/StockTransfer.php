<?php

namespace App\Models;

use App\Traits\HasStateMachine;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockTransfer extends Model
{
    use HasFactory, HasStateMachine;

    protected $fillable = [
        'from_warehouse_id', 'to_warehouse_id', 'product_id',
        'quantity', 'status', 'notes', 'created_by', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity'     => 'decimal:2',
            'completed_at' => 'datetime',
        ];
    }

    public function fromWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    public function toWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopeSearch($query, ?string $term)
    {
        if (!$term) return $query;

        return $query->where(function ($q) use ($term) {
            $q->where('number', 'like', "%{$term}%")
              ->orWhereHas('product', fn($p) => $p->where('name', 'like', "%{$term}%"))
              ->orWhereHas('fromWarehouse', fn($w) => $w->where('name', 'like', "%{$term}%"))
              ->orWhereHas('toWarehouse', fn($w) => $w->where('name', 'like', "%{$term}%"));
        });
    }

    // Auto-generate transfer number
    protected static function booted(): void
    {
        static::creating(function (StockTransfer $transfer) {
            if (empty($transfer->number)) {
                $last = static::max('id') ?? 0;
                $transfer->number = 'TRF-' . str_pad($last + 1, 5, '0', STR_PAD_LEFT);
            }
            if (empty($transfer->created_by) && auth()->check()) {
                $transfer->created_by = auth()->id();
            }
        });
    }

    // Helpers
    public static function statusOptions(): array
    {
        return ['pending', 'completed', 'cancelled'];
    }

    public static function statusColors(): array
    {
        return [
            'pending'   => 'bg-amber-50 text-amber-700 ring-amber-600/20',
            'completed' => 'bg-green-50 text-green-700 ring-green-600/20',
            'cancelled' => 'bg-red-50 text-red-700 ring-red-600/20',
        ];
    }

    public static function statusTransitions(): array
    {
        return [
            'pending'   => ['completed', 'cancelled'],
            'completed' => ['cancelled'],
            'cancelled' => [],
        ];
    }
}
