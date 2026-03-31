<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApprovalStep extends Model
{
    protected $fillable = [
        'approval_flow_id',
        'step_order',
        'approval_role_id',
        'min_amount',
        'max_amount',
        'is_required',
        'timeout_hours',
    ];

    protected function casts(): array
    {
        return [
            'step_order'    => 'integer',
            'min_amount'    => 'decimal:2',
            'max_amount'    => 'decimal:2',
            'is_required'   => 'boolean',
            'timeout_hours' => 'integer',
        ];
    }

    // ── Relationships ───────────────────────────────────────────────

    public function flow(): BelongsTo
    {
        return $this->belongsTo(ApprovalFlow::class, 'approval_flow_id');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(ApprovalRole::class, 'approval_role_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ApprovalLog::class, 'step_order', 'step_order');
    }

    // ── Helpers ─────────────────────────────────────────────────────

    /**
     * Check if amount falls within this step's range.
     */
    public function appliesToAmount(float $amount): bool
    {
        $minOk = $this->min_amount === null || $amount >= $this->min_amount;
        $maxOk = $this->max_amount === null || $amount <= $this->max_amount;

        return $minOk && $maxOk;
    }

    /**
     * Get users who can approve this step.
     */
    public function getApprovers(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->role->users()->active()->get();
    }

    /**
     * Get amount range description.
     */
    public function getAmountRangeAttribute(): string
    {
        if ($this->min_amount === null && $this->max_amount === null) {
            return 'All amounts';
        }

        if ($this->min_amount !== null && $this->max_amount !== null) {
            return number_format((float) $this->min_amount) . ' - ' . number_format((float) $this->max_amount);
        }

        if ($this->min_amount !== null) {
            return '>= ' . number_format((float) $this->min_amount);
        }

        return '<= ' . number_format((float) $this->max_amount);
    }
}
