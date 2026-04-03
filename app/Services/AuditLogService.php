<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditLogService
{
    /**
     * Fields to exclude from field-level diff tracking.
     */
    private const EXCLUDED_FIELDS = [
        'id', 'created_at', 'updated_at', 'deleted_at',
        'remember_token', 'password',
    ];

    public static function log(
        string $module,
        string $action,
        string $description,
        ?array $oldData = null,
        ?array $newData = null,
        ?Model $model = null
    ): ActivityLog {
        $changes = null;

        // Compute field-level changes for updates
        if ($oldData && $newData) {
            $changes = static::computeChanges($oldData, $newData);
        }

        return ActivityLog::create([
            'user_id'        => Auth::id(),
            'module'         => $module,
            'action'         => $action,
            'auditable_type' => $model ? $model->getMorphClass() : null,
            'auditable_id'   => $model?->getKey(),
            'description'    => $description,
            'old_data'       => $oldData,
            'new_data'       => $newData,
            'changes'        => $changes,
            'ip_address'     => Request::ip(),
            'user_agent'     => Request::userAgent(),
            'created_at'     => now(),
        ]);
    }

    /**
     * Compute field-level diffs between old and new data.
     *
     * Returns: [{"field": "status", "old": "draft", "new": "posted"}, ...]
     */
    public static function computeChanges(array $oldData, array $newData): array
    {
        $changes = [];
        $allKeys = array_unique(array_merge(array_keys($oldData), array_keys($newData)));

        foreach ($allKeys as $key) {
            if (in_array($key, self::EXCLUDED_FIELDS, true)) {
                continue;
            }

            $oldVal = $oldData[$key] ?? null;
            $newVal = $newData[$key] ?? null;

            // Normalize for comparison (cast to string to handle type mismatches)
            if (static::valuesAreDifferent($oldVal, $newVal)) {
                $changes[] = [
                    'field' => $key,
                    'old'   => $oldVal,
                    'new'   => $newVal,
                ];
            }
        }

        return $changes;
    }

    /**
     * Compare two values, handling type coercion for numbers/strings.
     */
    private static function valuesAreDifferent($old, $new): bool
    {
        // Both null — no change
        if (is_null($old) && is_null($new)) {
            return false;
        }

        // Skip nested arrays/objects (covered by old_data/new_data JSON blobs)
        if (is_array($old) || is_array($new)) {
            return false;
        }

        return (string) $old !== (string) $new;
    }
}
