<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // Create default admin user if not exists
        User::firstOrCreate(
            ['email' => 'admin@manerp.test'],
            [
                'name' => 'Administrator',
                'password' => Hash::make('password'),
                'role' => User::ROLE_ADMIN,
                'status' => User::STATUS_ACTIVE,
            ]
        );

        // Create a sample staff user
        User::firstOrCreate(
            ['email' => 'staff@manerp.test'],
            [
                'name' => 'Staff User',
                'password' => Hash::make('password'),
                'role' => User::ROLE_STAFF,
                'status' => User::STATUS_ACTIVE,
            ]
        );
    }
}
