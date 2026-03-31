<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
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
}
