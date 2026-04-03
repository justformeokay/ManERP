<?php

namespace App\Models;

use App\Traits\HasStateMachine;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PayrollPeriod extends Model
{
    use SoftDeletes, HasStateMachine;

    protected $fillable = [
        'month', 'year', 'status',
        'total_gross', 'total_deductions', 'total_net',
        'created_by', 'approved_by', 'approved_at', 'posted_at',
    ];

    protected function casts(): array
    {
        return [
            'month'            => 'integer',
            'year'             => 'integer',
            'total_gross'      => 'decimal:2',
            'total_deductions' => 'decimal:2',
            'total_net'        => 'decimal:2',
            'approved_at'      => 'datetime',
            'posted_at'        => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (PayrollPeriod $period) {
            if (empty($period->created_by)) {
                $period->created_by = auth()->id();
            }
        });
    }

    // ── Relationships ────────────────────────────────────────

    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // ── State Machine ────────────────────────────────────────

    public static function statusOptions(): array
    {
        return ['draft', 'approved', 'posted'];
    }

    public static function statusTransitions(): array
    {
        return [
            'draft'    => ['approved'],
            'approved' => ['posted', 'draft'],
            'posted'   => [],
        ];
    }

    public static function statusColors(): array
    {
        return [
            'draft'    => 'bg-gray-100 text-gray-700 ring-gray-300',
            'approved' => 'bg-blue-50 text-blue-700 ring-blue-300',
            'posted'   => 'bg-green-50 text-green-700 ring-green-300',
        ];
    }

    // ── Helpers ──────────────────────────────────────────────

    public function getPeriodLabelAttribute(): string
    {
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret',
            4 => 'April', 5 => 'Mei', 6 => 'Juni',
            7 => 'Juli', 8 => 'Agustus', 9 => 'September',
            10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        return ($months[$this->month] ?? $this->month) . ' ' . $this->year;
    }

    public function recalculateTotals(): void
    {
        $this->total_gross      = $this->payslips()->sum('gross_salary');
        $this->total_deductions = $this->payslips()->sum('total_deductions');
        $this->total_net        = $this->payslips()->sum('net_salary');
        $this->saveQuietly();
    }

    // ── Scopes ───────────────────────────────────────────────

    public function scopeSearch($query, ?string $term)
    {
        if (!$term) return $query;

        return $query->where(function ($q) use ($term) {
            $q->where('year', 'like', "%{$term}%")
              ->orWhere('month', 'like', "%{$term}%");
        });
    }
}
