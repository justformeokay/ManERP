<?php

namespace App\Services;

use App\Models\Approval;
use App\Models\ApprovalFlow;
use App\Models\ApprovalLog;
use App\Models\ApprovalRole;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ApprovalService
{
    /**
     * Start a new approval workflow for a document.
     *
     * @param string $module Module key (purchase_order, invoice, etc.)
     * @param int $referenceId The ID of the document
     * @param float $amount The amount to determine approval steps
     * @param int $requestedBy User ID who initiated the request
     * @return Approval|null Returns null if no approval is required
     */
    public function startApproval(string $module, int $referenceId, float $amount, int $requestedBy): ?Approval
    {
        // Get the active flow for this module
        $flow = ApprovalFlow::where('module', $module)
            ->where('is_active', true)
            ->first();

        if (!$flow) {
            return null;
        }

        // Get applicable steps based on amount
        $steps = $flow->getApplicableSteps($amount);

        if ($steps->isEmpty()) {
            return null; // No approval required
        }

        return DB::transaction(function () use ($module, $referenceId, $amount, $requestedBy, $flow, $steps) {
            // Create the approval record
            $approval = Approval::create([
                'module'         => $module,
                'reference_id'   => $referenceId,
                'approval_flow_id' => $flow->id,
                'amount'         => $amount,
                'requested_by'   => $requestedBy,
                'current_step'   => 1,
                'status'         => Approval::STATUS_PENDING,
            ]);

            // Create log entries for each step
            foreach ($steps as $index => $step) {
                ApprovalLog::create([
                    'approval_id'      => $approval->id,
                    'step_order'       => $index + 1,
                    'approval_role_id' => $step->approval_role_id,
                    'action'           => ApprovalLog::ACTION_PENDING,
                ]);
            }

            return $approval;
        });
    }

    /**
     * Approve the current step of an approval.
     *
     * @param Approval $approval
     * @param User $user
     * @param string|null $notes
     * @return bool
     */
    public function approve(Approval $approval, User $user, ?string $notes = null): bool
    {
        if (!$approval->canBeApprovedBy($user)) {
            return false;
        }

        if ($approval->status !== Approval::STATUS_PENDING) {
            return false;
        }

        return DB::transaction(function () use ($approval, $user, $notes) {
            // Update the current log entry
            $log = ApprovalLog::where('approval_id', $approval->id)
                ->where('step_order', $approval->current_step)
                ->first();

            if ($log) {
                $log->update([
                    'acted_by' => $user->id,
                    'action'   => ApprovalLog::ACTION_APPROVED,
                    'notes'    => $notes,
                ]);
            }

            // Check if there are more steps
            $nextStep = ApprovalLog::where('approval_id', $approval->id)
                ->where('step_order', $approval->current_step + 1)
                ->where('action', ApprovalLog::ACTION_PENDING)
                ->exists();

            if ($nextStep) {
                // Move to next step
                $approval->update([
                    'current_step' => $approval->current_step + 1,
                ]);
            } else {
                // All steps completed - mark as approved
                $approval->update([
                    'status'      => Approval::STATUS_APPROVED,
                    'approved_at' => now(),
                    'approved_by' => $user->id,
                ]);

                // Fire event for document status update
                $this->onApprovalComplete($approval);
            }

            return true;
        });
    }

    /**
     * Reject an approval.
     *
     * @param Approval $approval
     * @param User $user
     * @param string|null $reason
     * @return bool
     */
    public function reject(Approval $approval, User $user, ?string $reason = null): bool
    {
        if (!$approval->canBeApprovedBy($user)) {
            return false;
        }

        if ($approval->status !== Approval::STATUS_PENDING) {
            return false;
        }

        return DB::transaction(function () use ($approval, $user, $reason) {
            // Update the current log entry
            $log = ApprovalLog::where('approval_id', $approval->id)
                ->where('step_order', $approval->current_step)
                ->first();

            if ($log) {
                $log->update([
                    'acted_by' => $user->id,
                    'action'   => ApprovalLog::ACTION_REJECTED,
                    'notes'    => $reason,
                ]);
            }

            // Update approval status
            $approval->update([
                'status'      => Approval::STATUS_REJECTED,
                'rejected_at' => now(),
                'rejected_by' => $user->id,
            ]);

            // Fire event for document status update
            $this->onApprovalRejected($approval);

            return true;
        });
    }

    /**
     * Cancel an approval request.
     *
     * @param Approval $approval
     * @param User $user Only the requester can cancel
     * @return bool
     */
    public function cancel(Approval $approval, User $user): bool
    {
        if ($approval->requested_by !== $user->id && !$user->isAdmin()) {
            return false;
        }

        if ($approval->status !== Approval::STATUS_PENDING) {
            return false;
        }

        return DB::transaction(function () use ($approval) {
            $approval->update([
                'status' => Approval::STATUS_CANCELLED,
            ]);

            // Mark pending logs as skipped
            ApprovalLog::where('approval_id', $approval->id)
                ->where('action', ApprovalLog::ACTION_PENDING)
                ->update(['action' => ApprovalLog::ACTION_SKIPPED]);

            return true;
        });
    }

    /**
     * Get pending approvals for a user.
     *
     * @param User $user
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getPendingApprovals(User $user)
    {
        $roleIds = $user->getApprovalRoleIds();

        if (empty($roleIds)) {
            return collect();
        }

        return Approval::pending()
            ->whereHas('logs', function ($query) use ($roleIds) {
                $query->whereIn('approval_role_id', $roleIds)
                    ->where('action', ApprovalLog::ACTION_PENDING)
                    ->whereColumn('step_order', 'approvals.current_step');
            })
            ->with(['flow', 'requester', 'logs.role'])
            ->latest()
            ->get();
    }

    /**
     * Get all approvals for a user (requested by or can approve).
     *
     * @param User $user
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUserApprovals(User $user)
    {
        $roleIds = $user->getApprovalRoleIds();

        return Approval::where(function ($query) use ($user, $roleIds) {
                $query->where('requested_by', $user->id);

                if (!empty($roleIds)) {
                    $query->orWhereHas('logs', function ($q) use ($roleIds) {
                        $q->whereIn('approval_role_id', $roleIds);
                    });
                }
            })
            ->with(['flow', 'requester', 'logs.role', 'logs.actor'])
            ->latest()
            ->get();
    }

    /**
     * Get approval history for a document.
     *
     * @param string $module
     * @param int $referenceId
     * @return Approval|null
     */
    public function getDocumentApproval(string $module, int $referenceId): ?Approval
    {
        return Approval::forModule($module)
            ->forReference($referenceId)
            ->with(['flow', 'requester', 'logs.role', 'logs.actor'])
            ->first();
    }

    /**
     * Check if a document requires approval.
     *
     * @param string $module
     * @param float $amount
     * @return bool
     */
    public function requiresApproval(string $module, float $amount): bool
    {
        $flow = ApprovalFlow::where('module', $module)
            ->where('is_active', true)
            ->first();

        if (!$flow) {
            return false;
        }

        return $flow->requiresApproval($amount);
    }

    /**
     * Get approval status for a document.
     *
     * @param string $module
     * @param int $referenceId
     * @return string|null
     */
    public function getDocumentStatus(string $module, int $referenceId): ?string
    {
        $approval = Approval::forModule($module)
            ->forReference($referenceId)
            ->first();

        return $approval?->status;
    }

    /**
     * Re-submit a rejected approval.
     *
     * @param Approval $approval
     * @param User $user
     * @return bool
     */
    public function resubmit(Approval $approval, User $user): bool
    {
        if ($approval->requested_by !== $user->id) {
            return false;
        }

        if ($approval->status !== Approval::STATUS_REJECTED) {
            return false;
        }

        return DB::transaction(function () use ($approval) {
            // Reset the approval
            $approval->update([
                'status'       => Approval::STATUS_PENDING,
                'current_step' => 1,
                'approved_at'  => null,
                'approved_by'  => null,
                'rejected_at'  => null,
                'rejected_by'  => null,
            ]);

            // Reset all log entries to pending
            ApprovalLog::where('approval_id', $approval->id)
                ->update([
                    'action'   => ApprovalLog::ACTION_PENDING,
                    'acted_by' => null,
                    'notes'    => null,
                ]);

            return true;
        });
    }

    // ── Hooks for Document Updates ──────────────────────────────────

    /**
     * Called when approval is complete - update document status.
     */
    protected function onApprovalComplete(Approval $approval): void
    {
        $document = $approval->getReferencedModel();

        if ($document && method_exists($document, 'onApprovalComplete')) {
            $document->onApprovalComplete();
        }

        // Alternatively, fire an event
        // event(new ApprovalCompleted($approval));
    }

    /**
     * Called when approval is rejected - update document status.
     */
    protected function onApprovalRejected(Approval $approval): void
    {
        $document = $approval->getReferencedModel();

        if ($document && method_exists($document, 'onApprovalRejected')) {
            $document->onApprovalRejected();
        }

        // Alternatively, fire an event
        // event(new ApprovalRejected($approval));
    }
}
