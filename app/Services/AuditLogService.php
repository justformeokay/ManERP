<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Session;

class AuditLogService
{
    /**
     * Fields to exclude from field-level diff tracking.
     */
    private const EXCLUDED_FIELDS = [
        'id', 'created_at', 'updated_at', 'deleted_at',
        'remember_token', 'password', 'two_factor_secret',
        'two_factor_recovery_codes',
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

        $record = [
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
            'session_id'     => Session::getId() ?: null,
            'created_at'     => now(),
        ];

        // HMAC checksum for anti-tampering verification
        $record['checksum'] = static::computeChecksum($record);

        return ActivityLog::create($record);
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

    /**
     * Compute HMAC-SHA256 checksum for tamper detection.
     * Uses APP_KEY as secret — if a record is modified directly in DB,
     * the checksum will no longer match.
     */
    public static function computeChecksum(array $record): string
    {
        $payload = json_encode([
            $record['user_id'],
            $record['module'],
            $record['action'],
            $record['description'],
            $record['ip_address'],
            $record['created_at'] instanceof \DateTimeInterface
                ? $record['created_at']->toIso8601String()
                : (string) $record['created_at'],
        ]);

        return hash_hmac('sha256', $payload, config('app.key'));
    }

    /**
     * Verify integrity of an existing audit log record.
     */
    public static function verifyChecksum(ActivityLog $log): bool
    {
        if (! $log->checksum) {
            return false; // Legacy record without checksum
        }

        $payload = json_encode([
            $log->user_id,
            $log->module,
            $log->action,
            $log->description,
            $log->ip_address,
            $log->created_at->toIso8601String(),
        ]);

        return hash_equals($log->checksum, hash_hmac('sha256', $payload, config('app.key')));
    }
}
