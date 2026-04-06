<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShiftSchedule extends Model
{
    protected $fillable = [
        'employee_id', 'shift_id',
        'start_date', 'end_date',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date'   => 'date',
        ];
    }

    // ── Relationships ────────────────────────────────────────

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    // ── Scopes ───────────────────────────────────────────────

    /**
     * Find the scheduled shift for an employee on a specific date.
     */
    public function scopeForDate($query, int $employeeId, string $date)
    {
        return $query->where('employee_id', $employeeId)
            ->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date);
    }
}
