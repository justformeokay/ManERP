<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::query()
            ->search($request->search)
            ->when($request->role, fn($q, $v) => $q->where('role', $v))
            ->when($request->status, fn($q, $v) => $q->where('status', $v))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('users.index', compact('users'));
    }

    public function create()
    {
        return view('users.form', [
            'user' => new User(['role' => User::ROLE_STAFF, 'status' => User::STATUS_ACTIVE])
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Password::defaults()],
            'role' => ['required', Rule::in(User::roleOptions())],
            'phone' => 'nullable|string|max:30',
            'status' => ['required', Rule::in(User::statusOptions())],
        ]);

        User::create($validated);

        return redirect()->route('settings.users.index')->with('success', 'User created successfully.');
    }

    public function edit(User $user)
    {
        return view('users.form', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'role' => ['required', Rule::in(User::roleOptions())],
            'phone' => 'nullable|string|max:30',
            'status' => ['required', Rule::in(User::statusOptions())],
        ]);

        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        // Prevent admin from deactivating themselves
        if ($user->id === auth()->id() && $validated['status'] !== User::STATUS_ACTIVE) {
            return back()->with('error', 'You cannot deactivate your own account.');
        }

        $user->update($validated);

        return redirect()->route('settings.users.index')->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        if ($user->id === 1) {
            return back()->with('error', 'Cannot delete the primary admin user.');
        }
        
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return redirect()->route('settings.users.index')->with('success', 'User deleted successfully.');
    }
}
