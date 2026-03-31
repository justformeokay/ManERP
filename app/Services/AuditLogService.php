<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditLogService
{
    public static function log(
        string $module,
        string $action,
        string $description,
        ?array $oldData = null,
        ?array $newData = null
    ): ActivityLog {
        return ActivityLog::create([
            'user_id'    => Auth::id(),
            'module'     => $module,
            'action'     => $action,
            'description'=> $description,
            'old_data'   => $oldData,
            'new_data'   => $newData,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'created_at' => now(),
        ]);
    }
}
