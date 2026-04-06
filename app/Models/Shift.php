<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shift extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'start_time', 'end_time',
        'grace_period', 'is_night_shift',
        'night_shift_bonus', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'grace_period'      => 'integer',
            'is_night_shift'    => 'boolean',
            'night_shift_bonus' => 'decimal:2',
            'is_active'         => 'boolean',
        ];
    }

    // ── Relationships ────────────────────────────────────────

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(ShiftSchedule::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    // ── Scopes ───────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Check if this is a cross-day shift (end_time < start_time, e.g. 22:00–06:00).
     */
    public function isCrossDay(): bool
    {
        return $this->end_time < $this->start_time;
    }
}
