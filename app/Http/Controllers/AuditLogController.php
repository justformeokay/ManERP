<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $logs = ActivityLog::with('user')
            ->when($request->module, fn($q, $v) => $q->where('module', $v))
            ->when($request->action, fn($q, $v) => $q->where('action', $v))
            ->when($request->user_id, fn($q, $v) => $q->where('user_id', $v))
            ->when($request->date_from, fn($q, $v) => $q->where('created_at', '>=', $v))
            ->when($request->date_to, fn($q, $v) => $q->where('created_at', '<=', $v . ' 23:59:59'))
            ->when($request->search, fn($q, $v) => $q->search($v))
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        $users = User::orderBy('name')->get(['id', 'name']);

        return view('audit-logs.index', compact('logs', 'users'));
    }

    public function show(ActivityLog $activityLog)
    {
        $activityLog->load('user');

        return view('audit-logs.show', ['log' => $activityLog]);
    }

    /**
     * Verify integrity of all audit log records.
     * Runs HMAC checksum validation and returns results as JSON.
     */
    public function verifyIntegrity(): JsonResponse
    {
        $total = ActivityLog::count();
        $tampered = [];
        $legacy = 0;

        // Process in chunks to avoid memory issues
        ActivityLog::orderBy('id')->chunk(500, function ($logs) use (&$tampered, &$legacy) {
            foreach ($logs as $log) {
                if (! $log->checksum) {
                    $legacy++;
                    continue;
                }

                if (! AuditLogService::verifyChecksum($log)) {
                    $tampered[] = [
                        'id'          => $log->id,
                        'created_at'  => $log->created_at->toIso8601String(),
                        'module'      => $log->module,
                        'action'      => $log->action,
                        'description' => $log->description,
                        'user'        => $log->user?->name ?? 'System',
                    ];
                }
            }
        });

        AuditLogService::log(
            'system',
            'integrity_check',
            'Audit log integrity verification: ' . count($tampered) . ' tampered of ' . $total . ' records',
        );

        return response()->json([
            'total'    => $total,
            'verified' => $total - $legacy - count($tampered),
            'legacy'   => $legacy,
            'tampered' => $tampered,
        ]);
    }
}
