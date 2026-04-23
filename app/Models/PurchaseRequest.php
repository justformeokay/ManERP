<?php

namespace App\Models;

use App\Traits\HasStateMachine;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseRequest extends Model
{
    use HasFactory, SoftDeletes, HasStateMachine;

    protected $fillable = [
        'number', 'requested_by', 'approved_by', 'project_id',
        'department_id', 'purchase_type',
        'status', 'priority', 'required_date', 'reason',
        'rejection_reason', 'approved_at', 'rejected_at',
    ];

    protected function casts(): array
    {
        return [
            'required_date' => 'date',
            'approved_at'   => 'datetime',
            'rejected_at'   => 'datetime',
        ];
    }

    // ── State Machine ──

    public function statusTransitions(): array
    {
        return [
            'draft'     => ['pending'],
            'pending'   => ['approved', 'rejected'],
            'approved'  => ['converted'],
            'rejected'  => ['draft'],       // can re-edit and resubmit
            'converted' => [],              // final
        ];
    }

    // ── Relationships ──

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseRequestItem::class);
    }

    public function purchaseOrder(): HasOne
    {
        return $this->hasOne(PurchaseOrder::class);
    }

    // ── Scopes ──

    public function scopeSearch($query, ?string $term)
    {
        if (!$term) return $query;

        return $query->where(function ($q) use ($term) {
            $q->where('number', 'like', "%{$term}%")
              ->orWhere('reason', 'like', "%{$term}%")
              ->orWhereHas('requester', fn($u) => $u->where('name', 'like', "%{$term}%"));
        });
    }

    // ── Helpers ──

    public function getEstimatedTotal(): float
    {
        return $this->items->sum('total');
    }

    public static function priorityOptions(): array
    {
        return ['low', 'normal', 'high', 'urgent'];
    }

    public static function purchaseTypeOptions(): array
    {
        return ['operational', 'project_sales', 'project_capex'];
    }

    /**
     * Generate HMAC signature for PR→PO conversion (F-14 audit compliance).
     */
    public static function conversionHmac(int $prId): string
    {
        return hash_hmac('sha256', 'pr-conversion:' . $prId, config('app.key'));
    }

    protected static function booted(): void
    {
        static::creating(function (self $pr) {
            if (empty($pr->number)) {
                $last = static::withTrashed()->max('id') ?? 0;
                $pr->number = 'PR-' . str_pad($last + 1, 5, '0', STR_PAD_LEFT);
            }
        });
    }
}
