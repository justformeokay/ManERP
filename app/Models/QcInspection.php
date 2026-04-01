<?php

namespace App\Models;

use App\Traits\HasStateMachine;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class QcInspection extends Model
{
    use HasFactory, SoftDeletes, HasStateMachine;

    protected $fillable = [
        'number', 'inspection_type', 'reference_type', 'reference_id',
        'product_id', 'warehouse_id', 'inspected_quantity', 'passed_quantity',
        'failed_quantity', 'result', 'status', 'notes', 'inspector_id', 'inspected_at',
    ];

    protected function casts(): array
    {
        return [
            'inspected_quantity' => 'decimal:2',
            'passed_quantity'    => 'decimal:2',
            'failed_quantity'    => 'decimal:2',
            'inspected_at'       => 'datetime',
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

    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspector_id');
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function items(): HasMany
    {
        return $this->hasMany(QcInspectionItem::class);
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
        static::creating(function (QcInspection $inspection) {
            if (empty($inspection->number)) {
                $last = static::withTrashed()->max('id') ?? 0;
                $inspection->number = 'QC-' . str_pad($last + 1, 5, '0', STR_PAD_LEFT);
            }
        });
    }

    // Helpers
    public static function inspectionTypeOptions(): array
    {
        return ['incoming', 'in_process', 'final'];
    }

    public static function resultOptions(): array
    {
        return ['pending', 'passed', 'failed', 'partial'];
    }

    public static function statusOptions(): array
    {
        return ['draft', 'in_progress', 'completed'];
    }

    public static function statusColors(): array
    {
        return [
            'draft'       => 'bg-gray-100 text-gray-700 ring-gray-500/20',
            'in_progress' => 'bg-amber-50 text-amber-700 ring-amber-600/20',
            'completed'   => 'bg-green-50 text-green-700 ring-green-600/20',
        ];
    }

    public static function statusTransitions(): array
    {
        return [
            'draft'       => ['in_progress', 'completed'],
            'in_progress' => ['completed'],
            'completed'   => [],
        ];
    }

    public static function resultColors(): array
    {
        return [
            'pending' => 'bg-gray-100 text-gray-700 ring-gray-500/20',
            'passed'  => 'bg-green-50 text-green-700 ring-green-600/20',
            'failed'  => 'bg-red-50 text-red-700 ring-red-600/20',
            'partial' => 'bg-amber-50 text-amber-700 ring-amber-600/20',
        ];
    }

    public function passRate(): float
    {
        if ($this->inspected_quantity <= 0) return 0;
        return round(($this->passed_quantity / $this->inspected_quantity) * 100, 1);
    }
}
