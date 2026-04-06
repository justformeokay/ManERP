<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\ActivityLog;
use App\Models\Bank;
use App\Models\EmployeeDataChange;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->user()->load('employee.documents', 'employee.dataChangeRequests');

        $recentActivity = ActivityLog::where('user_id', $user->id)
            ->latest('created_at')
            ->limit(5)
            ->get();

        $pendingChange = null;
        $changeHistory = collect();
        if ($user->employee) {
            $pendingChange = $user->employee->dataChangeRequests()
                ->where('status', EmployeeDataChange::STATUS_PENDING)
                ->first();
            $changeHistory = $user->employee->dataChangeRequests()
                ->latest()
                ->limit(5)
                ->get();
        }

        $banks = Bank::active()->orderBy('name')->get();

        return view('profile.edit', [
            'user'           => $user,
            'recentActivity' => $recentActivity,
            'pendingChange'  => $pendingChange,
            'changeHistory'  => $changeHistory,
            'banks'          => $banks,
        ]);
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $oldData = $user->only(['name', 'email']);

        $user->fill($request->validated());

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        AuditLogService::log(
            'users',
            'update',
            "User {$user->email} updated their profile",
            $oldData,
            $user->only(['name', 'email']),
            $user
        );

        return Redirect::route('profile.edit')
            ->with('status', 'profile-updated')
            ->with('flash_type', 'success');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        AuditLogService::log(
            'users',
            'delete',
            "User {$user->email} deleted their own account",
            null,
            null,
            $user
        );

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
