<?php

use App\Services\AuditLogService;

if (!function_exists('audit_log')) {
    function audit_log(
        string $module,
        string $action,
        string $description,
        ?array $oldData = null,
        ?array $newData = null
    ) {
        return AuditLogService::log($module, $action, $description, $oldData, $newData);
    }
}
