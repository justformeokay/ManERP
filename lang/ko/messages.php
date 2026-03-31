<?php

return [
    /*
    |--------------------------------------------------------------------------
    | General
    |--------------------------------------------------------------------------
    */
    'language_changed' => '언어가 성공적으로 변경되었습니다.',
    'invalid_language' => '잘못된 언어가 선택되었습니다.',

    /*
    |--------------------------------------------------------------------------
    | Navigation & Menu
    |--------------------------------------------------------------------------
    */
    'dashboard' => '대시보드',
    'main' => '메인',
    'operations' => '운영',
    'crm_clients' => 'CRM / 고객',
    'clients' => '고객',
    'projects' => '프로젝트',

    // Inventory
    'inventory' => '재고',
    'categories' => '카테고리',
    'products' => '제품',
    'warehouses' => '창고',
    'stock_levels' => '재고 수준',
    'stock_movements' => '재고 이동',
    'stock_transfers' => '재고 이전',

    // Manufacturing
    'manufacturing' => '제조',
    'bill_of_materials' => '자재 명세서',
    'work_orders' => '작업 주문',

    // Supply Chain
    'supply_chain' => '공급망',
    'suppliers' => '공급업체',

    // Commerce
    'commerce' => '상거래',
    'sales' => '판매',
    'sales_orders' => '판매 주문',
    'purchasing' => '구매',
    'purchase_orders' => '구매 주문',

    // Finance
    'finance' => '재무',
    'invoices' => '송장',
    'payments' => '결제',

    // Accounting
    'accounting' => '회계',
    'chart_of_accounts' => '계정과목',
    'journal_entries' => '분개',
    'general_ledger' => '총계정원장',
    'trial_balance' => '시산표',
    'balance_sheet' => '대차대조표',
    'profit_loss' => '손익계산서',

    // Analytics & Reports
    'analytics' => '분석',
    'reports' => '보고서',

    // Administration
    'administration' => '관리',
    'settings' => '설정',
    'users' => '사용자',
    'audit_logs' => '감사 로그',

    /*
    |--------------------------------------------------------------------------
    | Navbar
    |--------------------------------------------------------------------------
    */
    'search_placeholder' => '검색...',
    'notifications' => '알림',
    'mark_all_read' => '모두 읽음 처리',
    'no_notifications' => '새 알림 없음',
    'view_all_notifications' => '모든 알림 보기',
    'profile_settings' => '프로필 설정',
    'system_settings' => '시스템 설정',
    'sign_out' => '로그아웃',
    'language' => '언어',

    /*
    |--------------------------------------------------------------------------
    | Common Actions
    |--------------------------------------------------------------------------
    */
    'create' => '생성',
    'edit' => '수정',
    'delete' => '삭제',
    'save' => '저장',
    'cancel' => '취소',
    'back' => '뒤로',
    'view' => '보기',
    'search' => '검색',
    'filter' => '필터',
    'export' => '내보내기',
    'import' => '가져오기',
    'actions' => '작업',
    'confirm' => '확인',
    'submit' => '제출',
    'update' => '업데이트',
    'close' => '닫기',
    'yes' => '예',
    'no' => '아니오',
    'loading' => '로딩 중...',

    /*
    |--------------------------------------------------------------------------
    | Common Labels
    |--------------------------------------------------------------------------
    */
    'name' => '이름',
    'email' => '이메일',
    'phone' => '전화번호',
    'address' => '주소',
    'status' => '상태',
    'date' => '날짜',
    'created_at' => '생성일',
    'updated_at' => '수정일',
    'description' => '설명',
    'notes' => '메모',
    'total' => '합계',
    'subtotal' => '소계',
    'tax' => '세금',
    'discount' => '할인',
    'quantity' => '수량',
    'price' => '가격',
    'amount' => '금액',
    'active' => '활성',
    'inactive' => '비활성',
    'pending' => '대기 중',
    'completed' => '완료',
    'cancelled' => '취소됨',

    /*
    |--------------------------------------------------------------------------
    | Messages
    |--------------------------------------------------------------------------
    */
    'created_successfully' => ':item이(가) 성공적으로 생성되었습니다.',
    'updated_successfully' => ':item이(가) 성공적으로 수정되었습니다.',
    'deleted_successfully' => ':item이(가) 성공적으로 삭제되었습니다.',
    'confirm_delete' => '이 :item을(를) 삭제하시겠습니까?',
    'no_data' => '데이터가 없습니다.',
    'error_occurred' => '오류가 발생했습니다. 다시 시도해 주세요.',

    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */
    'youre_logged_in' => '로그인되었습니다!',
    'welcome' => '환영합니다',
    'welcome_back' => '{name}님, 다시 오신 것을 환영합니다! 비즈니스 요약입니다.',
    'total_clients' => '총 고객 수',
    'total_products' => '총 제품 수',
    'pending_manufacturing' => '대기 중인 제조',
    'low_stock_items' => '재고 부족',
    'total_revenue' => '총 매출',
    'this_month' => '이번 달',
    'pending_orders_count' => '대기 주문',
    'quick_actions' => '빠른 작업',
    'new_order' => '새 주문',
    'new_product' => '새 제품',
    'new_client' => '새 고객',
    'view_reports' => '보고서 보기',
    'recent_sales_orders' => '최근 판매 주문',
    'view_all' => '전체 보기',
    'no_sales_orders' => '판매 주문이 없습니다',
    'recent_purchase_orders' => '최근 구매 주문',
    'no_purchase_orders' => '구매 주문이 없습니다',
    'low_stock_warning' => '재고 부족 경고',
    'manage_stock' => '재고 관리',
    'sku' => 'SKU',
    'stock' => '재고',
    'min_stock' => '최소 재고',
    'low_stock_badge' => '재고 부족',
    'product_column' => '제품',

    /*
    |--------------------------------------------------------------------------
    | Sales Order Form
    |--------------------------------------------------------------------------
    */
    'new_sales_order' => '새 판매 주문',
    'edit_sales_order' => ':number 수정',
    'create_new_sales_order_for_client' => '고객을 위한 새 판매 주문을 생성합니다.',
    'update_order_details' => '주문 세부 정보 및 항목을 업데이트합니다.',
    'order_details' => '주문 세부 정보',
    'order_date' => '주문일',
    'delivery_date' => '배송일',
    'select_client' => '고객 선택...',
    'select_warehouse' => '창고 선택...',
    'select_product' => '제품 선택...',
    'none_option' => '— 없음 —',
    'order_items' => '주문 항목',
    'add_item' => '항목 추가',
    'qty' => '수량',
    'unit_price' => '단가',
    'insufficient_stock' => '— 재고 부족!',
    'line_total' => '합계:',
    'no_items_added' => '아직 항목이 추가되지 않았습니다. "항목 추가"를 클릭하세요.',
    'optional_order_notes' => '선택적 주문 메모...',
    'summary' => '요약',
    'grand_total' => '총 합계',
    'stock_warning_message' => '경고: 일부 항목이 사용 가능한 재고를 초과합니다. 주문은 초안으로 저장할 수 있지만 확인할 수 없습니다.',
    'create_order' => '주문 생성',
    'update_order' => '주문 업데이트',

    /*
    |--------------------------------------------------------------------------
    | Clients Page
    |--------------------------------------------------------------------------
    */
    'crm_clients_title' => 'CRM / 고객',
    'clients_heading' => '고객',
    'clients_subtitle' => '고객, 리드 및 잠재 고객을 관리합니다.',
    'add_client' => '고객 추가',
    'search_clients_placeholder' => '이름, 회사, 이메일 또는 코드로 검색...',
    'all_status' => '모든 상태',
    'all_types' => '모든 유형',
    'clear' => '초기화',
    'client_column' => '고객',
    'contact' => '연락처',
    'type' => '유형',
    'created' => '생성일',
    'customer' => '고객',
    'lead' => '리드',
    'prospect' => '잠재 고객',
    'no_clients_found' => '고객을 찾을 수 없습니다.',
    'add_your_first_client' => '+ 첫 번째 고객 추가',
    'delete_client_confirm' => ':name 고객을 삭제하시겠습니까?',

    /*
    |--------------------------------------------------------------------------
    | Projects Page
    |--------------------------------------------------------------------------
    */
    'projects_title' => '프로젝트',
    'projects_heading' => '프로젝트',
    'projects_subtitle' => '고객 프로젝트를 추적하고 관리합니다.',
    'new_project' => '새 프로젝트',
    'search_projects_placeholder' => '프로젝트 이름, 코드 또는 고객으로 검색...',
    'all_clients' => '모든 고객',
    'project_column' => '프로젝트',
    'timeline' => '타임라인',
    'budget' => '예산',
    'no_projects_found' => '프로젝트를 찾을 수 없습니다.',
    'create_your_first_project' => '+ 첫 번째 프로젝트 만들기',
    'delete_project_confirm' => ':name 프로젝트를 삭제하시겠습니까?',

    /*
    |--------------------------------------------------------------------------
    | Clients Form
    |--------------------------------------------------------------------------
    */
    'edit_client' => '고객 수정',
    'fill_in_details_to_create_new_client' => '새 고객을 만들기 위한 세부 정보를 입력하세요.',
    'update_client_information' => '고객 정보를 업데이트합니다.',
    'basic_information' => '기본 정보',
    'full_name' => '전체 이름',
    'company_name' => '회사명',
    'tax_id' => '사업자번호',
    'tax_identification_number' => '사업자등록번호',
    'address_label' => '주소',
    'street_address' => '도로명 주소',
    'city' => '도시',
    'country' => '국가',
    'status_notes' => '상태 및 메모',
    'internal_notes' => '이 고객에 대한 내부 메모...',
    'create_client' => '고객 생성',
    'update_client' => '고객 업데이트',

    /*
    |--------------------------------------------------------------------------
    | Products Form
    |--------------------------------------------------------------------------
    */
    'edit_product' => '제품 수정',
    'add_new_product_to_catalog' => '카탈로그에 새 제품을 추가합니다.',
    'update_product_details' => '제품 세부 정보를 업데이트합니다.',
    'product_details' => '제품 세부 정보',
    'pricing_stock' => '가격 및 재고',
    'no_category' => '카테고리 없음',
    'auto_generated_if_empty' => '비어있으면 자동 생성',
    'product_description_placeholder' => '제품 설명...',
    'cost_price' => '원가',
    'sell_price' => '판매가',
    'min_stock_alert' => '최소 재고 알림',
    'active_product' => '활성 제품',
    'create_product' => '제품 생성',
    'update_product' => '제품 업데이트',

    /*
    |--------------------------------------------------------------------------
    | Stock Levels Page
    |--------------------------------------------------------------------------
    */
    'stock_levels_subtitle' => '모든 창고의 현재 재고 수준입니다.',
    'new_movement' => '새 이동',
    'search_by_product_name_sku' => '제품 이름 또는 SKU로 검색...',
    'all_warehouses' => '모든 창고',
    'warehouse_column' => '창고',
    'quantity_column' => '수량',
    'reserved_column' => '예약',
    'available_column' => '가용',
    'status_column' => '상태',
    'low_stock' => '재고 부족',
    'in_stock' => '재고 있음',
    'out_of_stock' => '품절',
    'no_inventory_records_found' => '재고 기록을 찾을 수 없습니다.',
    'record_first_stock_movement' => '+ 첫 번째 재고 이동 기록하기',

    /*
    |--------------------------------------------------------------------------
    | Reports Dashboard
    |--------------------------------------------------------------------------
    */
    'reports_dashboard' => '보고서 대시보드',
    'reports_dashboard_subtitle' => '비즈니스 개요 및 주요 지표',
    'last_7_days' => '최근 7일',
    'last_30_days' => '최근 30일',
    'last_90_days' => '최근 90일',
    'last_1_year' => '최근 1년',
    'custom_range' => '사용자 지정 범위',
    'apply' => '적용',
    'sales_report' => '판매 보고서',
    'purchasing_report' => '구매 보고서',
    'inventory_report' => '재고 보고서',
    'manufacturing_report' => '제조 보고서',
    'finance_report' => '재무 보고서',
    'total_sales' => '총 판매',
    'total_purchases' => '총 구매',
    'total_orders' => '총 주문',
    'sales_trend_last_7_days' => '판매 추세 (최근 7일)',
    'purchase_trend_last_6_months' => '구매 추세 (최근 6개월)',
    'export_csv' => 'CSV 내보내기 ↓',
    'top_selling_products' => '최고 판매 제품',
    'qty_sold' => '판매 수량',
    'revenue' => '매출',
    'no_sales_data_period' => '이 기간의 판매 데이터가 없습니다',
    'low_stock_alerts' => '재고 부족 알림',
    'stock_min' => '최소',
    'all_stock_levels_healthy' => '모든 재고 수준이 정상입니다',

    /*
    |--------------------------------------------------------------------------
    | Settings Page
    |--------------------------------------------------------------------------
    */
    'settings_saved' => '설정이 성공적으로 저장되었습니다.',
    'settings_description' => '회사 정보 및 시스템 환경설정을 구성합니다.',
    'company_information' => '회사 정보',
    'system_preferences' => '시스템 환경설정',
    'default_currency' => '기본 통화',
    'timezone' => '시간대',
    'save_settings' => '설정 저장',

    /*
    |--------------------------------------------------------------------------
    | Category Form
    |--------------------------------------------------------------------------
    */
    'edit_category' => '카테고리 수정',
    'create_category' => '카테고리 생성',
    'update_category_information' => '카테고리 정보를 업데이트합니다',
    'add_new_product_category' => '새 제품 카테고리를 추가합니다',
    'basic_information' => '기본 정보',
    'category_name' => '카테고리 이름 *',
    'category_name_placeholder' => '예: 전자제품, 원자재',
    'slug_label' => 'Slug (URL 친화적)',
    'slug_placeholder' => '이름에서 자동 생성됨',
    'auto_generate_slug' => '카테고리 이름에서 자동 생성하려면 비워두세요',
    'description_label' => '설명',
    'description_placeholder' => '이 카테고리에 대한 상세 설명을 추가합니다',
    'category_hierarchy' => '카테고리 계층',
    'parent_category' => '상위 카테고리',
    'parent_category_none' => '없음 (최상위)',
    'parent_category_instruction' => '상위 카테고리를 선택하여 하위 카테고리를 생성합니다',
    'update_category_btn' => '카테고리 업데이트',
    'create_category_btn' => '카테고리 생성',

    /*
    |--------------------------------------------------------------------------
    | Category Index
    |--------------------------------------------------------------------------
    */
    'products_categories' => '제품 카테고리',
    'manage_categories' => '제품 카테고리 및 계층 관리',
    'add_category' => '카테고리 추가',
    'search_categories_placeholder' => '카테고리 검색...',
    'category_name_header' => '카테고리 이름',
    'parent_header' => '상위',
    'subcategories_header' => '하위 카테고리',
    'products_header' => '제품',
    'actions_header' => '동작',
    'edit_btn' => '수정',
    'delete_btn' => '삭제',
    'delete_category_confirm' => '정말 볼력슶나요? 이 동작은 되돌릴 수 없습니다. 이 카테고리에 제품이 없는지 확인하세요.',
];
