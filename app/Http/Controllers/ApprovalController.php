<?php

namespace App\Http\Controllers;

use App\Models\Approval;
use App\Models\ApprovalFlow;
use App\Models\ApprovalRole;
use App\Services\ApprovalService;
use Illuminate\Http\Request;

class ApprovalController extends Controller
{
    public function __construct(
        protected ApprovalService $approvalService
    ) {}

    /**
     * Display pending approvals for current user.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $pendingApprovals = $this->approvalService->getPendingApprovals($user);
        $myRequests = Approval::where('requested_by', $user->id)
            ->with(['flow', 'logs.role', 'logs.actor'])
            ->latest()
            ->paginate(10, ['*'], 'my_page');

        return view('approvals.index', compact('pendingApprovals', 'myRequests'));
    }

    /**
     * Display approval details.
     */
    public function show(Approval $approval)
    {
        $approval->load(['flow', 'requester', 'logs.role', 'logs.actor']);

        // Get the referenced document
        $document = $approval->getReferencedModel();

        return view('approvals.show', compact('approval', 'document'));
    }

    /**
     * Approve an approval request.
     */
    public function approve(Request $request, Approval $approval)
    {
        $request->validate([
            'notes' => 'nullable|string|max:500',
        ]);

        $user = $request->user();

        if ($this->approvalService->approve($approval, $user, $request->notes)) {
            return redirect()
                ->route('approvals.index')
                ->with('success', __('approval.approved_success'));
        }

        return back()->with('error', __('approval.cannot_approve'));
    }

    /**
     * Reject an approval request.
     */
    public function reject(Request $request, Approval $approval)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $user = $request->user();

        if ($this->approvalService->reject($approval, $user, $request->reason)) {
            return redirect()
                ->route('approvals.index')
                ->with('success', __('approval.rejected_success'));
        }

        return back()->with('error', __('approval.cannot_reject'));
    }

    /**
     * Cancel an approval request.
     */
    public function cancel(Request $request, Approval $approval)
    {
        $user = $request->user();

        if ($this->approvalService->cancel($approval, $user)) {
            return redirect()
                ->route('approvals.index')
                ->with('success', __('approval.cancelled_success'));
        }

        return back()->with('error', __('approval.cannot_cancel'));
    }

    /**
     * Re-submit a rejected approval.
     */
    public function resubmit(Request $request, Approval $approval)
    {
        $user = $request->user();

        if ($this->approvalService->resubmit($approval, $user)) {
            return redirect()
                ->route('approvals.show', $approval)
                ->with('success', __('approval.resubmitted_success'));
        }

        return back()->with('error', __('approval.cannot_resubmit'));
    }

    // ── Admin: Workflow Configuration ───────────────────────────────

    /**
     * Display workflow configuration list.
     */
    public function flows()
    {
        $flows = ApprovalFlow::with('steps.role')->get();
        $roles = ApprovalRole::orderBy('level')->get();

        return view('approvals.flows.index', compact('flows', 'roles'));
    }

    /**
     * Show flow edit form.
     */
    public function editFlow(ApprovalFlow $flow)
    {
        $flow->load('steps.role');
        $roles = ApprovalRole::orderBy('level')->get();

        return view('approvals.flows.edit', compact('flow', 'roles'));
    }

    /**
     * Update flow configuration.
     */
    public function updateFlow(Request $request, ApprovalFlow $flow)
    {

        $validated = $request->validate([
            'name'        => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'is_active'   => 'boolean',
            'steps'       => 'array',
            'steps.*.approval_role_id' => 'required|exists:approval_roles,id',
            'steps.*.min_amount'       => 'nullable|numeric|min:0',
            'steps.*.max_amount'       => 'nullable|numeric|min:0',
            'steps.*.is_required'      => 'boolean',
        ]);

        $flow->update([
            'name'        => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_active'   => $validated['is_active'] ?? false,
        ]);

        // Update steps
        if (isset($validated['steps'])) {
            // Delete old steps
            $flow->steps()->delete();

            // Create new steps
            foreach ($validated['steps'] as $order => $stepData) {
                $flow->steps()->create([
                    'step_order'       => $order + 1,
                    'approval_role_id' => $stepData['approval_role_id'],
                    'min_amount'       => $stepData['min_amount'] ?? null,
                    'max_amount'       => $stepData['max_amount'] ?? null,
                    'is_required'      => $stepData['is_required'] ?? true,
                ]);
            }
        }

        return redirect()
            ->route('approvals.flows')
            ->with('success', __('approval.flow_updated'));
    }

    // ── Admin: Role Management ──────────────────────────────────────

    /**
     * Display approval roles.
     */
    public function roles()
    {
        $roles = ApprovalRole::withCount('users')->orderBy('level')->get();

        return view('approvals.roles.index', compact('roles'));
    }

    /**
     * Show role edit form.
     */
    public function editRole(ApprovalRole $role)
    {
        $users = \App\Models\User::orderBy('name')->get();
        $assignedUserIds = $role->users->pluck('id')->toArray();

        return view('approvals.roles.edit', compact('role', 'users', 'assignedUserIds'));
    }

    /**
     * Update role users.
     */
    public function updateRole(Request $request, ApprovalRole $role)
    {

        $validated = $request->validate([
            'user_ids'   => 'array',
            'user_ids.*' => 'exists:users,id',
        ]);

        $role->users()->sync($validated['user_ids'] ?? []);

        return redirect()
            ->route('approvals.roles')
            ->with('success', __('approval.role_updated'));
    }
}
