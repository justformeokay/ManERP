<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveRequest extends Model
{
    protected $fillable = [
        'employee_id', 'type', 'start_date', 'end_date',
        'days', 'status', 'reason', 'approved_by', 'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'start_date'  => 'date',
            'end_date'    => 'date',
            'days'        => 'integer',
            'approved_at' => 'datetime',
        ];
    }

    public const TYPE_OPTIONS = ['annual', 'sick', 'maternity', 'unpaid', 'other'];
    public const STATUS_OPTIONS = ['pending', 'approved', 'rejected', 'cancelled'];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }
}
