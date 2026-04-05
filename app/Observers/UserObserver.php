<?php

namespace App\Observers;

use App\Models\Employee;
use App\Models\User;
use Carbon\Carbon;

class UserObserver
{
    /**
     * When a user is created with a role other than 'admin',
     * automatically create a linked Employee record.
     */
    public function created(User $user): void
    {
        if ($user->role === User::ROLE_ADMIN) {
            return;
        }

        // Skip if an employee record already exists for this user
        if (Employee::where('user_id', $user->id)->exists()) {
            return;
        }

        Employee::create([
            'user_id'      => $user->id,
            'nik'          => 'EMP-' . str_pad((string) $user->id, 5, '0', STR_PAD_LEFT),
            'name'         => $user->name,
            'join_date'    => Carbon::today(),
            'ptkp_status'  => 'TK/0',
            'status'       => 'active',
        ]);
    }
}
