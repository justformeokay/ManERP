<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Approval extends Model
{
    protected $fillable = [
        'module',
        'reference_id',
        'amount',
        'current_step',
        'total_steps',
        'status',
        'requested_by',
        'approved_at',
        'rejected_at',
    ];

    protected function casts(): array
    {
        return [
            'amount'       => 'decimal:2',
            'current_step' => 'integer',
            'total_steps'  => 'integer',
            'approved_at'  => 'datetime',
            'rejected_at'  => 'datetime',
        ];
    }

    // ── Status Constants ────────────────────────────────────────────

    public const STATUS_PENDING   = 'pending';
    public const STATUS_APPROVED  = 'approved';
    public const STATUS_REJECTED  = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';

    // ── Relationships ───────────────────────────────────────────────

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ApprovalLog::class)->orderBy('step_order');
    }

    /**
     * Get the referenced model (PO, Invoice, etc.).
     */
    public function getReferencedModel(): ?Model
    {
        return match ($this->module) {
            'purchase_order' => PurchaseOrder::find($this->reference_id),
            'invoice'        => Invoice::find($this->reference_id),
            'supplier_bill'  => SupplierBill::find($this->reference_id),
            'sales_order'    => SalesOrder::find($this->reference_id),
            default          => null,
        };
    }

    /**
     * Get the approval flow for this module.
     */
    public function getFlow(): ?ApprovalFlow
    {
        return ApprovalFlow::forModule($this->module);
    }

    /**
     * Get current approval step configuration.
     */
    public function getCurrentStep(): ?ApprovalStep
    {
        $flow = $this->getFlow();
        if (!$flow) return null;

        return $flow->steps()
            ->where('step_order', $this->current_step)
            ->first();
    }

    // ── Scopes ──────────────────────────────────────────────────────

    public function scopeForModule($query, string $module)
    {
        return $query->where('module', $module);
    }

    public function scopeForReference($query, int $referenceId)
    {
        return $query->where('reference_id', $referenceId);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    /**
     * Scope to approvals that the given user can action.
     */
    public function scopeForApprover($query, User $user)
    {
        if ($user->isAdmin()) {
            return $query; // Admins see all
        }

        $roleIds = $user->getApprovalRoleIds();
        if (empty($roleIds)) {
            return $query->whereRaw('1 = 0'); // No roles = no approvals
        }

        return $query->where('status', self::STATUS_PENDING)
            ->whereExists(function ($subquery) use ($roleIds) {
                $subquery->selectRaw(1)
                    ->from('approval_flows')
                    ->join('approval_steps', 'approval_flows.id', '=', 'approval_steps.approval_flow_id')
                    ->whereColumn('approval_flows.module', 'approvals.module')
                    ->whereColumn('approval_steps.step_order', 'approvals.current_step')
                    ->whereIn('approval_steps.approval_role_id', $roleIds);
            });
    }

    // ── Helpers ─────────────────────────────────────────────────────

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /**
     * Check if user can approve current step.
     */
    public function canBeApprovedBy(User $user): bool
    {
        if (!$this->isPending()) {
            return false;
        }

        $currentStep = $this->getCurrentStep();
        if (!$currentStep) {
            return false;
        }

        return $user->hasApprovalRole($currentStep->approval_role_id);
    }

    /**
     * Get module label.
     */
    public function getModuleLabelAttribute(): string
    {
        return ApprovalFlow::getModuleLabel($this->module);
    }

    /**
     * Get reference number/identifier.
     */
    public function getReferenceNumberAttribute(): string
    {
        $model = $this->getReferencedModel();
        if (!$model) return "#{$this->reference_id}";

        return match ($this->module) {
            'purchase_order' => $model->number ?? "PO-{$this->reference_id}",
            'invoice'        => $model->invoice_number ?? "INV-{$this->reference_id}",
            'supplier_bill'  => $model->bill_number ?? "BILL-{$this->reference_id}",
            'sales_order'    => $model->number ?? "SO-{$this->reference_id}",
            default          => "#{$this->reference_id}",
        };
    }

    /**
     * Get status badge color.
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_APPROVED  => 'green',
            self::STATUS_REJECTED  => 'red',
            self::STATUS_CANCELLED => 'gray',
            default                => 'amber',
        };
    }

    /**
     * Get progress percentage.
     */
    public function getProgressAttribute(): int
    {
        if ($this->total_steps === 0) return 100;
        if ($this->isApproved()) return 100;

        $completedSteps = $this->logs()->where('action', 'approved')->count();
        return (int) round(($completedSteps / $this->total_steps) * 100);
    }

    /**
     * Get module badge color.
     */
    public function getModuleColor(): string
    {
        return match ($this->module) {
            'purchase_order' => 'blue',
            'invoice'        => 'purple',
            'supplier_bill'  => 'orange',
            'payment'        => 'green',
            'sales_order'    => 'indigo',
            default          => 'gray',
        };
    }

    /**
     * Get document URL for viewing.
     */
    public function getDocumentUrl(): string
    {
        return match ($this->module) {
            'purchase_order' => route('purchasing.purchase-orders.show', $this->reference_id),
            'invoice'        => route('finance.invoices.show', $this->reference_id),
            'supplier_bill'  => route('ap.bills.show', $this->reference_id),
            'sales_order'    => route('sales.orders.show', $this->reference_id),
            default          => '#',
        };
    }
}
