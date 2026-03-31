<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalLog extends Model
{
    protected $fillable = [
        'approval_id',
        'step_order',
        'approval_role_id',
        'acted_by',
        'action',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'step_order' => 'integer',
        ];
    }

    // ── Action Constants ────────────────────────────────────────────

    public const ACTION_PENDING   = 'pending';
    public const ACTION_APPROVED  = 'approved';
    public const ACTION_REJECTED  = 'rejected';
    public const ACTION_SKIPPED   = 'skipped';
    public const ACTION_ESCALATED = 'escalated';

    // ── Relationships ───────────────────────────────────────────────

    public function approval(): BelongsTo
    {
        return $this->belongsTo(Approval::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(ApprovalRole::class, 'approval_role_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acted_by');
    }

    // ── Helpers ─────────────────────────────────────────────────────

    public function isApproved(): bool
    {
        return $this->action === self::ACTION_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->action === self::ACTION_REJECTED;
    }

    public function isPending(): bool
    {
        return $this->action === self::ACTION_PENDING;
    }

    /**
     * Get action badge color.
     */
    public function getActionColorAttribute(): string
    {
        return match ($this->action) {
            self::ACTION_APPROVED  => 'green',
            self::ACTION_REJECTED  => 'red',
            self::ACTION_SKIPPED   => 'gray',
            self::ACTION_ESCALATED => 'amber',
            default                => 'blue',
        };
    }

    /**
     * Get action label.
     */
    public function getActionLabelAttribute(): string
    {
        return ucfirst($this->action);
    }
}
