<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryStructure extends Model
{
    protected $fillable = [
        'employee_id',
        'basic_salary', 'fixed_allowance',
        'meal_allowance', 'transport_allowance',
        'overtime_rate',
        'effective_date', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'basic_salary'      => 'decimal:2',
            'fixed_allowance'   => 'decimal:2',
            'meal_allowance'    => 'decimal:2',
            'transport_allowance' => 'decimal:2',
            'overtime_rate'     => 'decimal:2',
            'effective_date'    => 'date',
            'is_active'         => 'boolean',
        ];
    }

    // ── Relationships ────────────────────────────────────────

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    // ── Helpers ──────────────────────────────────────────────

    /**
     * Total monthly fixed earnings (for BPJS base calculation).
     */
    public function getMonthlyFixedAttribute(): float
    {
        return round(
            (float) $this->basic_salary + (float) $this->fixed_allowance,
            2
        );
    }
}
