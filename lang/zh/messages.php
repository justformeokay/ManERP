<?php

return [
    /*
    |--------------------------------------------------------------------------
    | 通用
    |--------------------------------------------------------------------------
    */
    'language_changed' => '语言切换成功。',
    'invalid_language' => '所选语言无效。',

    /*
    |--------------------------------------------------------------------------
    | 导航与菜单
    |--------------------------------------------------------------------------
    */
    'dashboard' => '仪表板',
    'main' => '主页',
    'operations' => '运营',
    'crm_clients' => 'CRM / 客户',
    'clients' => '客户',
    'projects' => '项目',

    // 库存
    'inventory' => '库存',
    'categories' => '分类',
    'products' => '产品',
    'warehouses' => '仓库',
    'stock_levels' => '库存水平',
    'stock_movements' => '库存移动',
    'stock_transfers' => '库存转移',

    // 制造
    'manufacturing' => '制造',
    'bill_of_materials' => '物料清单',
    'work_orders' => '工单',

    // 供应链
    'supply_chain' => '供应链',
    'suppliers' => '供应商',

    // 商务
    'commerce' => '商务',
    'sales' => '销售',
    'sales_orders' => '销售订单',
    'purchasing' => '采购',
    'purchase_orders' => '采购订单',

    // 财务
    'finance' => '财务',
    'invoices' => '发票',
    'payments' => '付款',

    // 会计
    'accounting' => '会计',
    'chart_of_accounts' => '科目表',
    'journal_entries' => '日记账',
    'general_ledger' => '总账',
    'trial_balance' => '试算平衡表',
    'balance_sheet' => '资产负债表',
    'profit_loss' => '损益表',

    // 分析与报告
    'analytics' => '分析',
    'reports' => '报告',

    // 管理
    'administration' => '管理',
    'settings' => '设置',
    'users' => '用户',
    'audit_logs' => '审计日志',

    /*
    |--------------------------------------------------------------------------
    | 导航栏
    |--------------------------------------------------------------------------
    */
    'search_placeholder' => '搜索...',
    'notifications' => '通知',
    'mark_all_read' => '全部标记为已读',
    'no_notifications' => '没有新通知',
    'view_all_notifications' => '查看所有通知',
    'profile_settings' => '个人设置',
    'system_settings' => '系统设置',
    'sign_out' => '退出登录',
    'language' => '语言',

    /*
    |--------------------------------------------------------------------------
    | 常用操作
    |--------------------------------------------------------------------------
    */
    'create' => '创建',
    'edit' => '编辑',
    'delete' => '删除',
    'save' => '保存',
    'cancel' => '取消',
    'back' => '返回',
    'view' => '查看',
    'search' => '搜索',
    'filter' => '筛选',
    'export' => '导出',
    'import' => '导入',
    'actions' => '操作',
    'confirm' => '确认',
    'submit' => '提交',
    'update' => '更新',
    'close' => '关闭',
    'yes' => '是',
    'no' => '否',
    'loading' => '加载中...',

    /*
    |--------------------------------------------------------------------------
    | 常用标签
    |--------------------------------------------------------------------------
    */
    'name' => '名称',
    'email' => '邮箱',
    'phone' => '电话',
    'address' => '地址',
    'status' => '状态',
    'date' => '日期',
    'created_at' => '创建时间',
    'updated_at' => '更新时间',
    'description' => '描述',
    'notes' => '备注',
    'total' => '总计',
    'subtotal' => '小计',
    'tax' => '税',
    'discount' => '折扣',
    'quantity' => '数量',
    'price' => '价格',
    'amount' => '金额',
    'active' => '活跃',
    'inactive' => '非活跃',
    'pending' => '待处理',
    'completed' => '已完成',
    'cancelled' => '已取消',

    /*
    |--------------------------------------------------------------------------
    | 消息
    |--------------------------------------------------------------------------
    */
    'created_successfully' => ':item 创建成功。',
    'updated_successfully' => ':item 更新成功。',
    'deleted_successfully' => ':item 删除成功。',
    'confirm_delete' => '您确定要删除此 :item 吗？',
    'no_data' => '没有可用数据。',
    'error_occurred' => '发生错误，请重试。',

    /*
    |--------------------------------------------------------------------------
    | 仪表板
    |--------------------------------------------------------------------------
    */
    'youre_logged_in' => '您已登录！',
    'welcome' => '欢迎',
    'welcome_back' => '欢迎回来，{name}！以下是您的业务摘要。',
    'total_clients' => '总客户',
    'total_products' => '总产品',
    'pending_manufacturing' => '待处理制造',
    'low_stock_items' => '库存紧张',
    'total_revenue' => '总收入',
    'this_month' => '本月',
    'pending_orders_count' => '待处理订单',
    'quick_actions' => '快速操作',
    'new_order' => '新订单',
    'new_product' => '新产品',
    'new_client' => '新客户',
    'view_reports' => '查看报告',
    'recent_sales_orders' => '最近销售订单',
    'view_all' => '查看全部',
    'no_sales_orders' => '暂无销售订单',
    'recent_purchase_orders' => '最近采购订单',
    'no_purchase_orders' => '暂无采购订单',
    'low_stock_warning' => '库存紧张警告',
    'manage_stock' => '管理库存',
    'sku' => 'SKU',
    'stock' => '库存',
    'min_stock' => '最小库存',
    'low_stock_badge' => '库存紧张',
    'product_column' => '产品',

    /*
    |--------------------------------------------------------------------------
    | 销售订单表单
    |--------------------------------------------------------------------------
    */
    'new_sales_order' => '新销售订单',
    'edit_sales_order' => '编辑 :number',
    'create_new_sales_order_for_client' => '为客户创建新销售订单。',
    'update_order_details' => '更新订单详情和项目。',
    'order_details' => '订单详情',
    'order_date' => '订单日期',
    'delivery_date' => '交货日期',
    'select_client' => '选择客户...',
    'select_warehouse' => '选择仓库...',
    'select_product' => '选择产品...',
    'none_option' => '— 无 —',
    'order_items' => '订单项目',
    'add_item' => '添加项目',
    'qty' => '数量',
    'unit_price' => '单价',
    'insufficient_stock' => '— 库存不足！',
    'line_total' => '行合计：',
    'no_items_added' => '尚未添加任何项目。点击"添加项目"开始。',
    'optional_order_notes' => '可选订单备注...',
    'summary' => '摘要',
    'grand_total' => '总计',
    'stock_warning_message' => '警告：部分项目超出可用库存。订单可以保存为草稿，但不能确认。',
    'create_order' => '创建订单',
    'update_order' => '更新订单',

    /*
    |--------------------------------------------------------------------------
    | 客户页面
    |--------------------------------------------------------------------------
    */
    'crm_clients_title' => 'CRM / 客户',
    'clients_heading' => '客户',
    'clients_subtitle' => '管理您的客户、潜在客户和候选客户。',
    'add_client' => '添加客户',
    'search_clients_placeholder' => '按姓名、公司、邮箱或代码搜索...',
    'all_status' => '所有状态',
    'all_types' => '所有类型',
    'filter' => '筛选',
    'clear' => '清除筛选',
    'client_column' => '客户',
    'contact' => '联系方式',
    'type' => '类型',
    'created' => '创建时间',
    'customer' => '客户',
    'lead' => '潜在客户',
    'prospect' => '候选客户',
    'no_clients_found' => '未找到客户。',
    'add_your_first_client' => '+ 添加您的第一个客户',
    'delete_client_confirm' => '删除客户 :name？',

    /*
    |--------------------------------------------------------------------------
    | 项目页面
    |--------------------------------------------------------------------------
    */
    'projects_title' => '项目',
    'projects_heading' => '项目',
    'projects_subtitle' => '跟踪和管理客户项目。',
    'new_project' => '新项目',
    'search_projects_placeholder' => '按项目名称、代码或客户搜索...',
    'all_clients' => '所有客户',
    'project_column' => '项目',
    'timeline' => '时间表',
    'budget' => '预算',
    'view' => '查看',
    'no_projects_found' => '未找到项目。',
    'create_your_first_project' => '+ 创建您的第一个项目',
    'delete_project_confirm' => '删除项目 :name？',

    /*
    |--------------------------------------------------------------------------
    | 客户表单
    |--------------------------------------------------------------------------
    */
    'add_client' => '添加客户',
    'edit_client' => '编辑客户',
    'fill_in_details_to_create_new_client' => '填写详信息以创建新客户。',
    'update_client_information' => '更新客户信息。',
    'basic_information' => '基本信息',
    'full_name' => '全名',
    'company_name' => '公司名称',
    'tax_id' => '税号',
    'tax_identification_number' => '税收识别号',
    'address_label' => '地址',
    'street_address' => '街道地址',
    'city' => '城市',
    'country' => '国家',
    'status_notes' => '状态与备注',
    'internal_notes' => '关于此客户的内部备注...',
    'cancel' => '取消',
    'create_client' => '创建客户',
    'update_client' => '更新客户',

    /*
    |--------------------------------------------------------------------------
    | 产品表单
    |--------------------------------------------------------------------------
    */
    'new_product' => '新产品',
    'edit_product' => '编辑产品',
    'add_new_product_to_catalog' => '向目录添加新产品。',
    'update_product_details' => '更新产品详情。',
    'product_details' => '产品详情',
    'pricing_stock' => '定价与库存',
    'no_category' => '无分类',
    'auto_generated_if_empty' => '如果为空则自动生成',
    'product_description_placeholder' => '产品描述...',
    'cost_price' => '成本价',
    'sell_price' => '售价',
    'min_stock_alert' => '最小库存警告',
    'active_product' => '活跃产品',
    'create_product' => '创建产品',
    'update_product' => '更新产品',
    /*
    |--------------------------------------------------------------------------
    | Stock Levels Page
    |--------------------------------------------------------------------------
    */
    'stock_levels_subtitle' => '所有仓库的当前库存水平。',
    'new_movement' => '新库存移动',
    'search_by_product_name_sku' => '按产品名称或 SKU 搜索...',
    'all_warehouses' => '所有仓库',
    'product_column' => '产品',
    'warehouse_column' => '仓库',
    'quantity_column' => '数量',
    'reserved_column' => '已预留',
    'available_column' => '可用',
    'status_column' => '状态',
    'low_stock' => '库存不足',
    'in_stock' => '有货',
    'out_of_stock' => '缺货',
    'no_inventory_records_found' => '未找到库存记录。',
    'record_first_stock_movement' => '+ 记录您的第一条库存移动',

    /*
    |--------------------------------------------------------------------------
    | Reports Dashboard
    |--------------------------------------------------------------------------
    */
    'reports_dashboard' => '报表仪表板',
    'reports_dashboard_subtitle' => '业务概览与关键指标',
    'last_7_days' => '最近 7 天',
    'last_30_days' => '最近 30 天',
    'last_90_days' => '最近 90 天',
    'last_1_year' => '最近 1 年',
    'custom_range' => '自定义范围',
    'apply' => '应用',
    'sales_report' => '销售报表',
    'purchasing_report' => '采购报表',
    'inventory_report' => '库存报表',
    'manufacturing_report' => '制造报表',
    'finance_report' => '财务报表',
    'total_sales' => '总销售额',
    'total_purchases' => '总采购额',
    'total_orders' => '总订单数',
    'sales_trend_last_7_days' => '销售趋势（最近 7 天）',
    'purchase_trend_last_6_months' => '采购趋势（最近 6 个月）',
    'export_csv' => '导出 CSV ↓',
    'top_selling_products' => '热销产品',
    'qty_sold' => '销售数量',
    'revenue' => '收入',
    'no_sales_data_period' => '此期间无销售数据',
    'low_stock_alerts' => '低库存警告',
    'stock_min' => '最低',
    'all_stock_levels_healthy' => '所有库存水平正常',

    /*
    |--------------------------------------------------------------------------
    | Settings Page
    |--------------------------------------------------------------------------
    */
    'settings_saved' => '设置已成功保存。',
    'settings_description' => '配置公司信息和系统偏好设置。',
    'company_information' => '公司信息',
    'system_preferences' => '系统偏好',
    'default_currency' => '默认货币',
    'timezone' => '时区',
    'save_settings' => '保存设置',

    /*
    |--------------------------------------------------------------------------
    | Category Form
    |--------------------------------------------------------------------------
    */
    'edit_category' => '编辑分类',
    'create_category' => '创建分类',
    'update_category_information' => '更新分类信息',
    'add_new_product_category' => '添加新产品分类',
    'basic_information' => '基本信息',
    'category_name' => '分类名称 *',
    'category_name_placeholder' => '例如，电子产品、原材料',
    'slug_label' => 'Slug（URL友好）',
    'slug_placeholder' => '从名称自动生成',
    'auto_generate_slug' => '留空以从分类名称自动生成',
    'description_label' => '描述',
    'description_placeholder' => '为此分类添加详细描述',
    'category_hierarchy' => '分类层次',
    'parent_category' => '父分类',
    'parent_category_none' => '无（顶级）',
    'parent_category_instruction' => '通过选择父分类来创建子分类',
    'update_category_btn' => '更新分类',
    'create_category_btn' => '创建分类',

    /*
    |--------------------------------------------------------------------------
    | Category Index
    |--------------------------------------------------------------------------
    */
    'products_categories' => '产品分类',
    'manage_categories' => '管理产品分类和层次',
    'add_category' => '添加分类',
    'search_categories_placeholder' => '搜索分类...',
    'category_name_header' => '分类名称',
    'parent_header' => '父类',
    'subcategories_header' => '子分类',
    'products_header' => '产品',
    'actions_header' => '操作',
    'edit_btn' => '编辑',
    'delete_btn' => '删除',
    'delete_category_confirm' => '你确定么？此操作无法撤销。请确保此分类中没有产品。',
    ];