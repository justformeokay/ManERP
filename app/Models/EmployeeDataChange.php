<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeDataChange extends Model
{
    public const STATUS_PENDING  = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    public const EDITABLE_FIELDS = [
        'phone', 'bank_id', 'bank_account_number', 'bank_account_name',
        'bpjs_tk_number', 'bpjs_kes_number',
    ];

    public const FIELD_LABELS = [
        'phone'               => 'Phone Number',
        'bank_id'             => 'Bank Name',
        'bank_account_number' => 'Bank Account Number',
        'bank_account_name'   => 'Bank Account Name',
        'bpjs_tk_number'      => 'BPJS TK Number',
        'bpjs_kes_number'     => 'BPJS Kesehatan Number',
    ];

    protected $fillable = [
        'employee_id', 'requested_by', 'requested_changes',
        'original_data', 'status', 'rejection_reason',
        'reviewed_by', 'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'requested_changes' => 'array',
            'original_data'     => 'array',
            'reviewed_at'       => 'datetime',
        ];
    }

    // ── Relationships ─────────────────────────────────────────

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    // ── Helpers ───────────────────────────────────────────────

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
}
