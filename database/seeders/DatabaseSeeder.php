<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create admin user with all permissions
        User::updateOrCreate(
            ['email' => 'admin@manerp.com'],
            [
                'name' => 'Administrator',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'status' => 'active',
                'permissions' => [
                    'clients.view' => true, 'clients.create' => true, 'clients.edit' => true, 'clients.delete' => true,
                    'warehouses.view' => true, 'warehouses.create' => true, 'warehouses.edit' => true, 'warehouses.delete' => true,
                    'suppliers.view' => true, 'suppliers.create' => true, 'suppliers.edit' => true, 'suppliers.delete' => true,
                    'projects.view' => true, 'projects.create' => true, 'projects.edit' => true, 'projects.delete' => true,
                    'inventory.view' => true, 'inventory.create' => true, 'inventory.edit' => true, 'inventory.delete' => true,
                    'manufacturing.view' => true, 'manufacturing.create' => true, 'manufacturing.edit' => true, 'manufacturing.delete' => true,
                    'sales.view' => true, 'sales.create' => true, 'sales.edit' => true, 'sales.delete' => true,
                    'purchasing.view' => true, 'purchasing.create' => true, 'purchasing.edit' => true, 'purchasing.delete' => true,
                    'finance.view' => true, 'finance.create' => true, 'finance.edit' => true, 'finance.delete' => true,
                    'reports.view' => true,
                ],
            ]
        );
    }
}
