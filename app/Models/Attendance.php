<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    protected $fillable = [
        'employee_id', 'date', 'clock_in', 'clock_out',
        'latitude', 'longitude',
        'status', 'late_minutes', 'overtime_hours', 'notes', 'source', 'shift_id',
    ];

    protected function casts(): array
    {
        return [
            'date'           => 'date',
            'clock_in'       => 'datetime',
            'clock_out'      => 'datetime',
            'overtime_hours' => 'decimal:2',
            'late_minutes'   => 'integer',
            'latitude'       => 'decimal:7',
            'longitude'      => 'decimal:7',
        ];
    }

    public const STATUS_OPTIONS = ['present', 'absent', 'late', 'half_day', 'leave'];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function scopeForPeriod($query, int $month, int $year)
    {
        $start = sprintf('%04d-%02d-01', $year, $month);
        $end = \Carbon\Carbon::create($year, $month, 1)->endOfMonth()->toDateString();

        return $query->whereBetween('date', [$start, $end]);
    }
}
