<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveBalance extends Model
{
    protected $fillable = [
        'employee_id', 'year', 'type',
        'entitlement', 'used', 'balance',
    ];

    protected function casts(): array
    {
        return [
            'year'        => 'integer',
            'entitlement' => 'decimal:1',
            'used'        => 'decimal:1',
            'balance'     => 'decimal:1',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
