<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupplierPayment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'payment_number',
        'supplier_id',
        'supplier_bill_id',
        'payment_date',
        'amount',
        'payment_method',
        'reference_number',
        'notes',
        'created_by',
        'journal_entry_id',
    ];

    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'amount'       => 'decimal:2',
        ];
    }

    // ── Auto-generate payment number ────────────────────────────────

    protected static function booted(): void
    {
        static::creating(function (SupplierPayment $payment) {
            if (empty($payment->payment_number)) {
                $year = now()->year;
                $last = static::withTrashed()
                    ->where('payment_number', 'like', "PAY-{$year}-%")
                    ->orderByDesc('payment_number')
                    ->value('payment_number');

                $sequence = $last ? (int) substr($last, -5) + 1 : 1;
                $payment->payment_number = sprintf("PAY-%s-%05d", $year, $sequence);
            }

            if (empty($payment->created_by)) {
                $payment->created_by = auth()->id();
            }
        });
    }

    // ── Relationships ───────────────────────────────────────────────

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function supplierBill(): BelongsTo
    {
        return $this->belongsTo(SupplierBill::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    // ── Scopes ──────────────────────────────────────────────────────

    public function scopeSearch($query, ?string $term)
    {
        if (!$term) return $query;

        return $query->where(function ($q) use ($term) {
            $q->where('payment_number', 'like', "%{$term}%")
              ->orWhere('reference_number', 'like', "%{$term}%")
              ->orWhereHas('supplier', fn($s) => $s->where('name', 'like', "%{$term}%"));
        });
    }

    // ── Helpers ─────────────────────────────────────────────────────

    /**
     * Payment method options.
     */
    public static function paymentMethodOptions(): array
    {
        return [
            'cash'          => 'Cash',
            'bank_transfer' => 'Bank Transfer',
            'check'         => 'Check',
            'other'         => 'Other',
        ];
    }
}
