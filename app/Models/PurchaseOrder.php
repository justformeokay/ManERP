<?php

namespace App\Models;

use App\Traits\HasStateMachine;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrder extends Model
{
    use HasFactory, SoftDeletes, HasStateMachine;

    protected $fillable = [
        'supplier_id', 'warehouse_id', 'project_id', 'purchase_request_id',
        'purchase_type', 'department_id', 'priority', 'justification',
        'status', 'order_date', 'expected_date', 'subtotal', 'tax_amount',
        'total', 'notes', 'payment_terms', 'shipping_address', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'order_date'    => 'date',
            'expected_date' => 'date',
            'subtotal'      => 'decimal:2',
            'tax_amount'    => 'decimal:2',
            'total'         => 'decimal:2',
        ];
    }

    // Relationships
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function purchaseRequest(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequest::class);
    }

    // Scopes
    public function scopeSearch($query, ?string $term)
    {
        if (!$term) return $query;

        return $query->where(function ($q) use ($term) {
            $q->where('number', 'like', "%{$term}%")
              ->orWhereHas('supplier', fn($s) => $s->where('name', 'like', "%{$term}%"));
        });
    }

    // Auto-generate order number
    protected static function booted(): void
    {
        static::creating(function (PurchaseOrder $order) {
            if (empty($order->number)) {
                $last = static::withTrashed()->max('id') ?? 0;
                $order->number = 'PO-' . str_pad($last + 1, 5, '0', STR_PAD_LEFT);
            }
        });
    }

    // Helpers
    public static function statusOptions(): array
    {
        return ['draft', 'confirmed', 'partial', 'received', 'cancelled'];
    }

    public static function statusColors(): array
    {
        return [
            'draft'     => 'bg-gray-100 text-gray-700 ring-gray-500/20',
            'confirmed' => 'bg-primary-50 text-primary-700 ring-primary-600/20',
            'partial'   => 'bg-amber-50 text-amber-700 ring-amber-600/20',
            'received'  => 'bg-green-50 text-green-700 ring-green-600/20',
            'cancelled' => 'bg-red-50 text-red-700 ring-red-600/20',
        ];
    }

    public static function statusTransitions(): array
    {
        return [
            'draft'     => ['confirmed', 'cancelled'],
            'confirmed' => ['partial', 'received', 'cancelled'],
            'partial'   => ['received', 'cancelled'],
            'received'  => [],
            'cancelled' => [],
        ];
    }

    public function recalculateTotals(): void
    {
        $this->subtotal = $this->items->sum('total');
        $this->total = $this->subtotal + $this->tax_amount;
        $this->saveQuietly();
    }

    public function isFullyReceived(): bool
    {
        return $this->items->every(fn($item) => $item->received_quantity >= $item->quantity);
    }

    // Purchase Type
    public static function purchaseTypeOptions(): array
    {
        return ['operational', 'project_sales', 'project_capex'];
    }

    public static function purchaseTypeColors(): array
    {
        return [
            'operational'   => 'bg-gray-50 text-gray-700 ring-gray-500/20',
            'project_sales' => 'bg-blue-50 text-blue-700 ring-blue-600/20',
            'project_capex' => 'bg-violet-50 text-violet-700 ring-violet-600/20',
        ];
    }

    public function purchaseTypeLabel(): string
    {
        return __('messages.po_purchase_type_' . $this->purchase_type);
    }

    public static function priorityOptions(): array
    {
        return ['low', 'normal', 'urgent'];
    }

    public static function priorityColors(): array
    {
        return [
            'low'    => 'bg-gray-50 text-gray-600 ring-gray-500/20',
            'normal' => 'bg-blue-50 text-blue-600 ring-blue-500/20',
            'urgent' => 'bg-red-50 text-red-700 ring-red-600/20',
        ];
    }

    /**
     * Generate HMAC signature for project ID (F-14 audit compliance).
     */
    public static function projectHmac(int $projectId): string
    {
        return hash_hmac('sha256', 'po-project:' . $projectId, config('app.key'));
    }

    public static function paymentTermsOptions(): array
    {
        return ['cash', 'cod', 'net_15', 'net_30', 'net_60', 'net_90'];
    }
}
