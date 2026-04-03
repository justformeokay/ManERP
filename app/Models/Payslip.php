<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payslip extends Model
{
    protected $fillable = [
        'payroll_period_id', 'employee_id',
        // Earnings
        'basic_salary', 'fixed_allowance',
        'meal_allowance', 'transport_allowance',
        'overtime_hours', 'overtime_amount',
        'other_earnings', 'gross_salary',
        // BPJS Company
        'bpjs_jht_company', 'bpjs_jkk_company', 'bpjs_jkm_company',
        'bpjs_jp_company', 'bpjs_kes_company',
        // BPJS Employee
        'bpjs_jht_employee', 'bpjs_jp_employee', 'bpjs_kes_employee',
        // PPh 21
        'pph21_amount',
        // Other Deductions
        'loan_deduction', 'absence_deduction', 'other_deductions',
        // Totals
        'total_deductions', 'net_salary',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'basic_salary'       => 'decimal:2',
            'fixed_allowance'    => 'decimal:2',
            'meal_allowance'     => 'decimal:2',
            'transport_allowance' => 'decimal:2',
            'overtime_hours'     => 'decimal:2',
            'overtime_amount'    => 'decimal:2',
            'other_earnings'     => 'decimal:2',
            'gross_salary'       => 'decimal:2',
            'bpjs_jht_company'   => 'decimal:2',
            'bpjs_jkk_company'   => 'decimal:2',
            'bpjs_jkm_company'   => 'decimal:2',
            'bpjs_jp_company'    => 'decimal:2',
            'bpjs_kes_company'   => 'decimal:2',
            'bpjs_jht_employee'  => 'decimal:2',
            'bpjs_jp_employee'   => 'decimal:2',
            'bpjs_kes_employee'  => 'decimal:2',
            'pph21_amount'       => 'decimal:2',
            'loan_deduction'     => 'decimal:2',
            'absence_deduction'  => 'decimal:2',
            'other_deductions'   => 'decimal:2',
            'total_deductions'   => 'decimal:2',
            'net_salary'         => 'decimal:2',
        ];
    }

    // ── Relationships ────────────────────────────────────────

    public function payrollPeriod(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PayslipItem::class)->orderBy('type')->orderBy('sort_order');
    }

    // ── Helpers ──────────────────────────────────────────────

    public function getTotalBpjsCompanyAttribute(): float
    {
        return round(
            (float) $this->bpjs_jht_company +
            (float) $this->bpjs_jkk_company +
            (float) $this->bpjs_jkm_company +
            (float) $this->bpjs_jp_company +
            (float) $this->bpjs_kes_company,
            2
        );
    }

    public function getTotalBpjsEmployeeAttribute(): float
    {
        return round(
            (float) $this->bpjs_jht_employee +
            (float) $this->bpjs_jp_employee +
            (float) $this->bpjs_kes_employee,
            2
        );
    }
}
