<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayslipItem extends Model
{
    protected $fillable = [
        'payslip_id', 'type', 'label', 'amount', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'amount'     => 'decimal:2',
            'sort_order' => 'integer',
        ];
    }

    // ── Relationships ────────────────────────────────────────

    public function payslip(): BelongsTo
    {
        return $this->belongsTo(Payslip::class);
    }

    // ── Constants ────────────────────────────────────────────

    public static function typeOptions(): array
    {
        return ['earning', 'deduction'];
    }
}
