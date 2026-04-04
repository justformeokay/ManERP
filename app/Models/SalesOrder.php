<?php

namespace App\Models;

use App\Traits\HasStateMachine;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesOrder extends Model
{
    use HasFactory, SoftDeletes, HasStateMachine;

    protected $fillable = [
        'client_id', 'warehouse_id', 'project_id', 'status',
        'order_date', 'delivery_date', 'subtotal', 'tax_amount',
        'discount', 'total', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'order_date'    => 'date',
            'delivery_date' => 'date',
            'subtotal'      => 'decimal:2',
            'tax_amount'    => 'decimal:2',
            'discount'      => 'decimal:2',
            'total'         => 'decimal:2',
        ];
    }

    // Relationships
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
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

    public function items(): HasMany
    {
        return $this->hasMany(SalesOrderItem::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    // Scopes
    public function scopeSearch($query, ?string $term)
    {
        if (!$term) return $query;

        return $query->where(function ($q) use ($term) {
            $q->where('number', 'like', "%{$term}%")
              ->orWhereHas('client', fn($c) => $c->where('name', 'like', "%{$term}%"));
        });
    }

    // Auto-generate order number
    protected static function booted(): void
    {
        static::creating(function (SalesOrder $order) {
            if (empty($order->number)) {
                $last = static::withTrashed()->max('id') ?? 0;
                $order->number = 'SLO-' . str_pad($last + 1, 5, '0', STR_PAD_LEFT);
            }
        });
    }

    // Helpers
    public static function statusOptions(): array
    {
        return ['draft', 'confirmed', 'processing', 'partial', 'shipped', 'completed', 'cancelled'];
    }

    public static function statusColors(): array
    {
        return [
            'draft'      => 'bg-gray-100 text-gray-700 ring-gray-500/20',
            'confirmed'  => 'bg-primary-50 text-primary-700 ring-primary-600/20',
            'processing' => 'bg-indigo-50 text-indigo-700 ring-indigo-600/20',
            'partial'    => 'bg-amber-50 text-amber-700 ring-amber-600/20',
            'shipped'    => 'bg-sky-50 text-sky-700 ring-sky-600/20',
            'completed'  => 'bg-green-50 text-green-700 ring-green-600/20',
            'cancelled'  => 'bg-red-50 text-red-700 ring-red-600/20',
        ];
    }

    public static function statusTransitions(): array
    {
        return [
            'draft'      => ['confirmed', 'cancelled'],
            'confirmed'  => ['processing', 'shipped', 'cancelled'],
            'processing' => ['shipped', 'cancelled'],
            'partial'    => ['shipped', 'cancelled'],
            'shipped'    => ['completed', 'cancelled'],
            'completed'  => [],
            'cancelled'  => [],
        ];
    }

    public function recalculateTotals(): void
    {
        $this->subtotal = $this->items->sum('total');
        $this->total = $this->subtotal + $this->tax_amount - $this->discount;
        $this->saveQuietly();
    }
}
