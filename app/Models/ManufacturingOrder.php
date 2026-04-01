<?php

namespace App\Models;

use App\Traits\HasStateMachine;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ManufacturingOrder extends Model
{
    use HasFactory, SoftDeletes, HasStateMachine;

    protected $fillable = [
        'number', 'bom_id', 'product_id', 'warehouse_id', 'project_id',
        'planned_quantity', 'produced_quantity', 'status', 'planned_start',
        'planned_end', 'actual_start', 'actual_end', 'priority', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'planned_quantity'  => 'decimal:2',
            'produced_quantity' => 'decimal:2',
            'planned_start'     => 'date',
            'planned_end'       => 'date',
            'actual_start'      => 'date',
            'actual_end'        => 'date',
        ];
    }

    // Relationships
    public function bom(): BelongsTo
    {
        return $this->belongsTo(BillOfMaterial::class, 'bom_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
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
              ->orWhereHas('product', fn($p) => $p->where('name', 'like', "%{$term}%"));
        });
    }

    // Auto-generate number
    protected static function booted(): void
    {
        static::creating(function (ManufacturingOrder $mo) {
            if (empty($mo->number)) {
                $last = static::withTrashed()->max('id') ?? 0;
                $mo->number = 'MO-' . str_pad($last + 1, 5, '0', STR_PAD_LEFT);
            }
        });
    }

    // Helpers
    public function progressPercent(): float
    {
        if ($this->planned_quantity <= 0) return 0;
        return min(100, round(($this->produced_quantity / $this->planned_quantity) * 100, 1));
    }

    public static function statusOptions(): array
    {
        return ['draft', 'confirmed', 'in_progress', 'done', 'cancelled'];
    }

    public static function statusColors(): array
    {
        return [
            'draft'       => 'bg-gray-100 text-gray-700 ring-gray-500/20',
            'confirmed'   => 'bg-primary-50 text-primary-700 ring-primary-600/20',
            'in_progress' => 'bg-amber-50 text-amber-700 ring-amber-600/20',
            'done'        => 'bg-green-50 text-green-700 ring-green-600/20',
            'cancelled'   => 'bg-red-50 text-red-700 ring-red-600/20',
        ];
    }

    public static function statusTransitions(): array
    {
        return [
            'draft'       => ['confirmed', 'cancelled'],
            'confirmed'   => ['in_progress', 'cancelled'],
            'in_progress' => ['done', 'cancelled'],
            'done'        => [],
            'cancelled'   => [],
        ];
    }

    public static function priorityOptions(): array
    {
        return ['low', 'normal', 'high', 'urgent'];
    }

    public static function priorityColors(): array
    {
        return [
            'low'    => 'bg-gray-100 text-gray-600 ring-gray-500/20',
            'normal' => 'bg-primary-50 text-primary-700 ring-primary-600/20',
            'high'   => 'bg-amber-50 text-amber-700 ring-amber-600/20',
            'urgent' => 'bg-red-50 text-red-700 ring-red-600/20',
        ];
    }
}
