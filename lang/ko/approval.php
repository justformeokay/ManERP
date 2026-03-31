<?php

return [
    // Page titles
    'approvals'         => '승인',
    'approval_detail'   => '승인 상세',
    'pending_approvals' => '대기 중인 승인',
    'my_requests'       => '내 요청',

    // Labels
    'module'          => '모듈',
    'reference_id'    => '참조 ID',
    'requested_by'    => '요청자',
    'approved_at'     => '승인 일시',
    'rejected_at'     => '거절 일시',
    'document_info'   => '문서 정보',
    'view_document'   => '문서 보기',
    'take_action'     => '조치 취하기',
    'approval_history'=> '승인 이력',
    'step'            => '단계',
    'progress'        => '진행률',

    // Actions
    'approve'         => '승인',
    'reject'          => '거절',
    'cancel'          => '취소',
    'resubmit'        => '재제출',
    'review'          => '검토',

    // Form labels
    'notes_optional'    => '메모 (선택 사항)',
    'rejection_reason'  => '거절 사유 (필수)',

    // Messages
    'pending_count'          => ':count건의 승인 대기',
    'no_pending'             => '모두 완료!',
    'no_pending_description' => '검토할 대기 중인 승인이 없습니다.',
    'no_requests'            => '아직 승인 요청이 없습니다.',

    'approved_success'   => '승인이 성공적으로 완료되었습니다.',
    'rejected_success'   => '승인이 거절되었습니다.',
    'cancelled_success'  => '승인 요청이 취소되었습니다.',
    'resubmitted_success'=> '승인이 재검토를 위해 재제출되었습니다.',

    'cannot_approve'  => '이 요청을 승인할 수 없습니다.',
    'cannot_reject'   => '이 요청을 거절할 수 없습니다.',
    'cannot_cancel'   => '이 요청을 취소할 수 없습니다.',
    'cannot_resubmit' => '이 요청을 재제출할 수 없습니다.',

    'confirm_cancel' => '이 승인 요청을 취소하시겠습니까?',

    // Flow management
    'flows'           => '승인 플로우',
    'flow_updated'    => '승인 플로우가 업데이트되었습니다.',
    'roles'           => '승인 역할',
    'role_updated'    => '승인 역할이 업데이트되었습니다.',

    // Modules
    'modules' => [
        'purchase_order' => '구매 주문',
        'invoice'        => '송장',
        'supplier_bill'  => '공급업체 청구서',
        'payment'        => '결제',
        'sales_order'    => '판매 주문',
    ],
];
