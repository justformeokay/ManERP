<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'nik', 'name', 'position', 'department',
        'join_date', 'resign_date',
        'npwp', 'bpjs_tk_number', 'bpjs_kes_number',
        'ptkp_status', 'ter_category',
        'bank_name', 'bank_account_number', 'bank_account_name',
        'status', 'user_id',
    ];

    protected function casts(): array
    {
        return [
            'join_date'   => 'date',
            'resign_date' => 'date',
        ];
    }

    // ── Constants ────────────────────────────────────────────

    public const PTKP_OPTIONS = [
        'TK/0' => 'Tidak Kawin / Tanpa Tanggungan',
        'TK/1' => 'Tidak Kawin / 1 Tanggungan',
        'TK/2' => 'Tidak Kawin / 2 Tanggungan',
        'TK/3' => 'Tidak Kawin / 3 Tanggungan',
        'K/0'  => 'Kawin / Tanpa Tanggungan',
        'K/1'  => 'Kawin / 1 Tanggungan',
        'K/2'  => 'Kawin / 2 Tanggungan',
        'K/3'  => 'Kawin / 3 Tanggungan',
    ];

    public const TER_CATEGORIES = ['A', 'B', 'C'];

    /**
     * Mapping PTKP status → TER category per PMK 168/2023.
     *
     * Category A: TK/0, TK/1
     * Category B: TK/2, TK/3, K/0, K/1
     * Category C: K/2, K/3
     */
    public const PTKP_TO_TER = [
        'TK/0' => 'A',
        'TK/1' => 'A',
        'TK/2' => 'B',
        'TK/3' => 'B',
        'K/0'  => 'B',
        'K/1'  => 'B',
        'K/2'  => 'C',
        'K/3'  => 'C',
    ];

    /**
     * Derive TER category from PTKP status per PMK 168/2023.
     */
    public static function deriveTerCategory(string $ptkpStatus): string
    {
        return self::PTKP_TO_TER[$ptkpStatus] ?? 'A';
    }

    /**
     * PTKP annual amounts (PP 101/2016 – still effective).
     */
    public const PTKP_AMOUNTS = [
        'TK/0' => 54000000,
        'TK/1' => 58500000,
        'TK/2' => 63000000,
        'TK/3' => 67500000,
        'K/0'  => 58500000,
        'K/1'  => 63000000,
        'K/2'  => 67500000,
        'K/3'  => 72000000,
    ];

    // ── Relationships ────────────────────────────────────────

    protected static function booted(): void
    {
        static::saving(function (Employee $employee) {
            if ($employee->ptkp_status) {
                $employee->ter_category = self::deriveTerCategory($employee->ptkp_status);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function salaryStructures(): HasMany
    {
        return $this->hasMany(SalaryStructure::class);
    }

    public function activeSalary(): HasOne
    {
        return $this->hasOne(SalaryStructure::class)
            ->where('is_active', true)
            ->latest('effective_date');
    }

    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function leaveBalances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class);
    }

    // ── Helpers ──────────────────────────────────────────────

    public function getActiveSalaryStructure(): ?SalaryStructure
    {
        return $this->salaryStructures()
            ->where('is_active', true)
            ->where('effective_date', '<=', now()->toDateString())
            ->latest('effective_date')
            ->first();
    }

    public static function ptkpOptions(): array
    {
        return array_keys(self::PTKP_OPTIONS);
    }

    public static function statusOptions(): array
    {
        return ['active', 'inactive'];
    }

    // ── Scopes ───────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeSearch($query, ?string $term)
    {
        if (!$term) return $query;

        return $query->where(function ($q) use ($term) {
            $q->where('nik', 'like', "%{$term}%")
              ->orWhere('name', 'like', "%{$term}%")
              ->orWhere('department', 'like', "%{$term}%")
              ->orWhere('position', 'like', "%{$term}%");
        });
    }
}
