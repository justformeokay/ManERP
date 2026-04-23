<?php

namespace Database\Seeders;

use App\Models\ChartOfAccount;
use App\Models\SystemLicense;
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
                    'accounting.view' => true, 'accounting.create' => true, 'accounting.edit' => true, 'accounting.delete' => true,
                    'hr.view' => true, 'hr.create' => true, 'hr.edit' => true, 'hr.delete' => true,
                    'reports.view' => true,
                ],
            ]
        );

        // Seed default Chart of Accounts
        $accounts = [
            ['code' => '1100', 'name' => 'Cash & Bank', 'type' => 'asset', 'is_system_account' => false, 'liquidity_classification' => 'current'],
            ['code' => '1200', 'name' => 'Accounts Receivable', 'type' => 'asset', 'is_system_account' => true, 'liquidity_classification' => 'current'],
            ['code' => '1300', 'name' => 'Inventory', 'type' => 'asset', 'is_system_account' => true, 'liquidity_classification' => 'current'],
            ['code' => '2000', 'name' => 'Accounts Payable', 'type' => 'liability', 'is_system_account' => true, 'liquidity_classification' => 'current'],
            ['code' => '3000', 'name' => "Owner's Equity", 'type' => 'equity', 'is_system_account' => false, 'liquidity_classification' => null],
            ['code' => '4000', 'name' => 'Revenue', 'type' => 'revenue', 'is_system_account' => false, 'liquidity_classification' => null],
            ['code' => '5000', 'name' => 'Cost of Goods Sold', 'type' => 'expense', 'is_system_account' => false, 'liquidity_classification' => null],
            ['code' => '5102', 'name' => 'Inventory Adjustment Variance', 'type' => 'expense', 'is_system_account' => true, 'liquidity_classification' => null],
            ['code' => '6000', 'name' => 'Operating Expenses', 'type' => 'expense', 'is_system_account' => false, 'liquidity_classification' => null],
        ];

        foreach ($accounts as $acc) {
            ChartOfAccount::firstOrCreate(
                ['code' => $acc['code']],
                [
                    'name' => $acc['name'],
                    'type' => $acc['type'],
                    'is_active' => true,
                    'is_system_account' => $acc['is_system_account'],
                    'liquidity_classification' => $acc['liquidity_classification'],
                ]
            );
        }

        // Create default trial license
        SystemLicense::firstOrCreate(
            ['plan_name' => 'Trial'],
            [
                'license_key' => 'TRIAL-' . now()->format('Ymd'),
                'license_type' => SystemLicense::TYPE_SUBSCRIPTION,
                'user_limit' => 2,
                'features_enabled' => ['sales', 'purchasing', 'inventory', 'accounting'],
                'starts_at' => now(),
                'expires_at' => now()->addDays(14),
                'is_active' => true,
            ]
        );

        $this->call([
            BankSeeder::class,
            DepartmentSeeder::class,
            PositionSeeder::class,
            EducationArticleSeeder::class,
        ]);
    }
}
