<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierBillItem extends Model
{
    protected $fillable = [
        'supplier_bill_id',
        'product_id',
        'description',
        'quantity',
        'price',
        'total',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'price'    => 'decimal:2',
            'total'    => 'decimal:2',
        ];
    }

    // ── Auto-calculate total ────────────────────────────────────────

    protected static function booted(): void
    {
        static::saving(function (SupplierBillItem $item) {
            $item->total = round($item->quantity * $item->price, 2);
        });

        // Recalculate bill totals when items change
        static::saved(function (SupplierBillItem $item) {
            $item->supplierBill->recalculateTotals();
        });

        static::deleted(function (SupplierBillItem $item) {
            $item->supplierBill->recalculateTotals();
        });
    }

    // ── Relationships ───────────────────────────────────────────────

    public function supplierBill(): BelongsTo
    {
        return $this->belongsTo(SupplierBill::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
