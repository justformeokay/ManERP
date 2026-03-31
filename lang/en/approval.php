<?php

return [
    // Page titles
    'approvals'         => 'Approvals',
    'approval_detail'   => 'Approval Detail',
    'pending_approvals' => 'Pending Approvals',
    'my_requests'       => 'My Requests',

    // Labels
    'module'          => 'Module',
    'reference_id'    => 'Reference ID',
    'requested_by'    => 'Requested By',
    'approved_at'     => 'Approved At',
    'rejected_at'     => 'Rejected At',
    'document_info'   => 'Document Information',
    'view_document'   => 'View Document',
    'take_action'     => 'Take Action',
    'approval_history'=> 'Approval History',
    'step'            => 'Step',
    'progress'        => 'Progress',

    // Actions
    'approve'         => 'Approve',
    'reject'          => 'Reject',
    'cancel'          => 'Cancel',
    'resubmit'        => 'Resubmit',
    'review'          => 'Review',

    // Form labels
    'notes_optional'    => 'Notes (optional)',
    'rejection_reason'  => 'Rejection reason (required)',

    // Messages
    'pending_count'          => ':count pending approval(s)',
    'no_pending'             => 'All caught up!',
    'no_pending_description' => 'You have no pending approvals to review.',
    'no_requests'            => 'No approval requests yet.',

    'approved_success'   => 'Approval has been approved successfully.',
    'rejected_success'   => 'Approval has been rejected.',
    'cancelled_success'  => 'Approval request has been cancelled.',
    'resubmitted_success'=> 'Approval has been resubmitted for review.',

    'cannot_approve'  => 'You cannot approve this request.',
    'cannot_reject'   => 'You cannot reject this request.',
    'cannot_cancel'   => 'You cannot cancel this request.',
    'cannot_resubmit' => 'You cannot resubmit this request.',

    'confirm_cancel' => 'Are you sure you want to cancel this approval request?',

    // Flow management
    'flows'           => 'Approval Flows',
    'flow_updated'    => 'Approval flow has been updated.',
    'roles'           => 'Approval Roles',
    'role_updated'    => 'Approval role has been updated.',

    // Modules
    'modules' => [
        'purchase_order' => 'Purchase Order',
        'invoice'        => 'Invoice',
        'supplier_bill'  => 'Supplier Bill',
        'payment'        => 'Payment',
        'sales_order'    => 'Sales Order',
    ],
];
