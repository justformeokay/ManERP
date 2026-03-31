<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupplierBill extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'bill_number',
        'supplier_id',
        'purchase_order_id',
        'bill_date',
        'due_date',
        'subtotal',
        'tax_amount',
        'total',
        'paid_amount',
        'status',
        'notes',
        'created_by',
        'journal_entry_id',
    ];

    protected function casts(): array
    {
        return [
            'bill_date'   => 'date',
            'due_date'    => 'date',
            'subtotal'    => 'decimal:2',
            'tax_amount'  => 'decimal:2',
            'total'       => 'decimal:2',
            'paid_amount' => 'decimal:2',
        ];
    }

    // ── Auto-generate bill number ───────────────────────────────────

    protected static function booted(): void
    {
        static::creating(function (SupplierBill $bill) {
            if (empty($bill->bill_number)) {
                $year = now()->year;
                $last = static::withTrashed()
                    ->where('bill_number', 'like', "BILL-{$year}-%")
                    ->orderByDesc('bill_number')
                    ->value('bill_number');

                $sequence = $last ? (int) substr($last, -5) + 1 : 1;
                $bill->bill_number = sprintf("BILL-%s-%05d", $year, $sequence);
            }

            if (empty($bill->created_by)) {
                $bill->created_by = auth()->id();
            }
        });
    }

    // ── Relationships ───────────────────────────────────────────────

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SupplierBillItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SupplierPayment::class);
    }

    // ── Scopes ──────────────────────────────────────────────────────

    public function scopeSearch($query, ?string $term)
    {
        if (!$term) return $query;

        return $query->where(function ($q) use ($term) {
            $q->where('bill_number', 'like', "%{$term}%")
              ->orWhereHas('supplier', fn($s) => $s->where('name', 'like', "%{$term}%"));
        });
    }

    public function scopeStatus($query, ?string $status)
    {
        if (!$status) return $query;
        return $query->where('status', $status);
    }

    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now()->startOfDay())
                     ->whereNotIn('status', ['paid', 'cancelled']);
    }

    public function scopeUnpaid($query)
    {
        return $query->whereIn('status', ['posted', 'partial']);
    }

    // ── Helpers ─────────────────────────────────────────────────────

    public function getOutstandingAttribute(): float
    {
        return round($this->total - $this->paid_amount, 2);
    }

    public function isOverdue(): bool
    {
        return $this->due_date->lt(now()->startOfDay()) 
            && !in_array($this->status, ['paid', 'cancelled']);
    }

    public function canPost(): bool
    {
        return $this->status === 'draft' && $this->items()->count() > 0;
    }

    public function canPay(): bool
    {
        return in_array($this->status, ['posted', 'partial']) && $this->outstanding > 0;
    }

    public function canEdit(): bool
    {
        return $this->status === 'draft';
    }

    public function canCancel(): bool
    {
        return in_array($this->status, ['draft', 'posted']) && $this->paid_amount == 0;
    }

    /**
     * Calculate days until due or days overdue.
     */
    public function getDaysUntilDueAttribute(): int
    {
        return (int) now()->startOfDay()->diffInDays($this->due_date, false);
    }

    /**
     * Get aging bucket (0-30, 31-60, 61-90, 90+).
     */
    public function getAgingBucketAttribute(): string
    {
        if ($this->days_until_due >= 0) {
            return 'current';
        }

        $overdueDays = abs($this->days_until_due);

        if ($overdueDays <= 30) return '1-30';
        if ($overdueDays <= 60) return '31-60';
        if ($overdueDays <= 90) return '61-90';
        return '90+';
    }

    /**
     * Status options for forms.
     */
    public static function statusOptions(): array
    {
        return ['draft', 'posted', 'partial', 'paid', 'cancelled'];
    }

    /**
     * Recalculate totals from items.
     */
    public function recalculateTotals(): void
    {
        $subtotal = $this->items()->sum('total');
        $this->subtotal = $subtotal;
        $this->total = $subtotal + $this->tax_amount;
        $this->save();
    }
}
