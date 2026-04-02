<?php

namespace App\Models;

use App\Traits\HasStateMachine;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use SoftDeletes, HasStateMachine;

    protected $fillable = [
        'invoice_number', 'sales_order_id', 'client_id', 'invoice_date', 'due_date',
        'subtotal', 'tax_amount', 'tax_rate', 'dpp', 'faktur_pajak_number',
        'discount', 'total_amount', 'paid_amount',
        'status', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'due_date' => 'date',
            'subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'discount' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'dpp' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Invoice $invoice) {
            if (empty($invoice->invoice_number)) {
                $year = now()->year;
                $last = static::withTrashed()
                    ->where('invoice_number', 'like', "INV-{$year}-%")
                    ->orderByDesc('invoice_number')
                    ->value('invoice_number');

                $sequence = $last ? (int) substr($last, -5) + 1 : 1;
                $invoice->invoice_number = sprintf("INV-%s-%05d", $year, $sequence);
            }

            if (empty($invoice->created_by)) {
                $invoice->created_by = auth()->id();
            }
        });
    }

    // ── Relationships ───────────────────────────────────────────

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    // ── Helpers ─────────────────────────────────────────────────

    public function getRemainingBalanceAttribute(): float
    {
        return round((float) $this->total_amount - (float) $this->paid_amount, 2);
    }

    public function recalculateStatus(): void
    {
        $paid = (float) $this->paid_amount;
        $total = (float) $this->total_amount;

        if ($paid >= $total) {
            $this->status = 'paid';
        } elseif ($paid > 0) {
            $this->status = 'partial';
        } else {
            $this->status = 'unpaid';
        }

        $this->saveQuietly();
    }

    public static function statusOptions(): array
    {
        return ['draft', 'unpaid', 'partial', 'paid', 'cancelled'];
    }

    public static function statusColors(): array
    {
        return [
            'draft'     => 'bg-gray-100 text-gray-700 ring-gray-300',
            'unpaid'    => 'bg-red-50 text-red-700 ring-red-300',
            'partial'   => 'bg-amber-50 text-amber-700 ring-amber-300',
            'paid'      => 'bg-green-50 text-green-700 ring-green-300',
            'cancelled' => 'bg-red-100 text-red-800 ring-red-400',
        ];
    }

    public static function statusTransitions(): array
    {
        return [
            'draft'     => ['unpaid', 'cancelled'],
            'unpaid'    => ['partial', 'paid', 'cancelled'],
            'partial'   => ['paid', 'cancelled'],
            'paid'      => [],
            'cancelled' => [],
        ];
    }

    // ── Scopes ──────────────────────────────────────────────────

    public function scopeSearch($query, ?string $term)
    {
        if (!$term) return $query;

        return $query->where(function ($q) use ($term) {
            $q->where('invoice_number', 'like', "%{$term}%")
              ->orWhereHas('client', fn($q) => $q->where('name', 'like', "%{$term}%")->orWhere('company', 'like', "%{$term}%"))
              ->orWhereHas('salesOrder', fn($q) => $q->where('number', 'like', "%{$term}%"));
        });
    }
}
