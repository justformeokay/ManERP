<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Phase 7 — Industrial RBAC Matrix Seeder
 *
 * Seeds 8 industrial role templates with Segregation of Duties (SoD).
 * All access is permission-based — no hardcoded user IDs.
 */
class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $templates = self::roleTemplates();

        foreach ($templates as $template => $config) {
            $user = User::updateOrCreate(
                ['email' => $config['email']],
                [
                    'name'        => $config['name'],
                    'password'    => Hash::make($config['password'] ?? 'password'),
                    'role'        => $config['role'],
                    'status'      => User::STATUS_ACTIVE,
                    'permissions' => $config['permissions'],
                ]
            );

            AuditLogService::log(
                'admin',
                'seed',
                "Role template [{$template}] seeded for user #{$user->id} ({$user->email})",
                null,
                ['template' => $template, 'permissions_count' => count($config['permissions'])],
                $user
            );
        }
    }

    /**
     * Get all role templates with their permission matrices.
     *
     * @return array<string, array{name: string, email: string, role: string, permissions: list<string>}>
     */
    public static function roleTemplates(): array
    {
        return [
            'super_admin' => [
                'name'        => 'Super Admin',
                'email'       => 'admin@manerp.com',
                'role'        => User::ROLE_ADMIN,
                'permissions' => User::allPermissions(),
            ],

            'finance_manager' => [
                'name'        => 'Finance Manager',
                'email'       => 'finance.manager@manerp.com',
                'role'        => User::ROLE_STAFF,
                'permissions' => self::financeManagerPermissions(),
            ],

            'accounting_staff' => [
                'name'        => 'Accounting Staff',
                'email'       => 'accounting.staff@manerp.com',
                'role'        => User::ROLE_STAFF,
                'permissions' => self::accountingStaffPermissions(),
            ],

            'production_manager' => [
                'name'        => 'Production Manager',
                'email'       => 'production.manager@manerp.com',
                'role'        => User::ROLE_STAFF,
                'permissions' => self::productionManagerPermissions(),
            ],

            'warehouse_staff' => [
                'name'        => 'Warehouse Staff',
                'email'       => 'warehouse.staff@manerp.com',
                'role'        => User::ROLE_STAFF,
                'permissions' => self::warehouseStaffPermissions(),
            ],

            'purchasing' => [
                'name'        => 'Purchasing Officer',
                'email'       => 'purchasing@manerp.com',
                'role'        => User::ROLE_STAFF,
                'permissions' => self::purchasingPermissions(),
            ],

            'sales' => [
                'name'        => 'Sales Officer',
                'email'       => 'sales@manerp.com',
                'role'        => User::ROLE_STAFF,
                'permissions' => self::salesPermissions(),
            ],

            'hr_payroll' => [
                'name'        => 'HR & Payroll Manager',
                'email'       => 'hr.payroll@manerp.com',
                'role'        => User::ROLE_STAFF,
                'permissions' => self::hrPayrollPermissions(),
            ],
        ];
    }

    /**
     * Get permission array for a specific role template.
     */
    public static function getTemplatePermissions(string $template): array
    {
        $templates = self::roleTemplates();
        return $templates[$template]['permissions'] ?? [];
    }

    // ── Permission Matrices ─────────────────────────────────────

    private static function financeManagerPermissions(): array
    {
        return array_merge(
            // Full accounting & finance
            self::fullModule('accounting'),
            self::fullModule('finance'),
            // Special: can close periods, post to GL, view costs
            ['accounting.close_period', 'accounting.post_gl', 'inventory.view_cost'],
            // Read access to operational modules for financial oversight
            self::viewModule('sales'),
            self::viewModule('purchasing'),
            self::viewModule('inventory'),
            self::viewModule('products'),
            self::viewModule('manufacturing'),
            self::viewModule('hr'),
            self::viewModule('clients'),
            self::viewModule('suppliers'),
            // Reports
            self::fullModule('reports'),
        );
    }

    private static function accountingStaffPermissions(): array
    {
        return array_merge(
            // Accounting: view, create, edit (NO delete, NO close_period, NO post_gl)
            ['accounting.view', 'accounting.create', 'accounting.edit'],
            // Finance: view, create, edit (NO delete)
            ['finance.view', 'finance.create', 'finance.edit'],
            // View cost prices for reconciliation
            ['inventory.view_cost'],
            // Read access to operational modules
            self::viewModule('sales'),
            self::viewModule('purchasing'),
            self::viewModule('inventory'),
            self::viewModule('products'),
            self::viewModule('clients'),
            self::viewModule('suppliers'),
            // Reports (view only)
            ['reports.view'],
        );
    }

    private static function productionManagerPermissions(): array
    {
        return array_merge(
            // Full manufacturing
            self::fullModule('manufacturing'),
            // Full inventory & products (manages production materials)
            self::fullModule('inventory'),
            self::fullModule('products'),
            self::fullModule('warehouses'),
            // Can view costs for costing analysis
            ['inventory.view_cost'],
            // Read access for supply chain visibility
            self::viewModule('purchasing'),
            self::viewModule('suppliers'),
            // Reports
            ['reports.view'],
        );
    }

    private static function warehouseStaffPermissions(): array
    {
        return array_merge(
            // Full inventory & warehouse management
            self::fullModule('inventory'),
            self::fullModule('warehouses'),
            // Products: view and edit (stock adjustments) — NO cost_price visibility
            ['products.view', 'products.edit'],
            // View purchasing for receiving
            self::viewModule('purchasing'),
            self::viewModule('suppliers'),
            // Reports
            ['reports.view'],
        );
    }

    private static function purchasingPermissions(): array
    {
        return array_merge(
            // Full purchasing & suppliers
            self::fullModule('purchasing'),
            self::fullModule('suppliers'),
            // Products: view for purchase specs
            ['products.view'],
            // Inventory: view for stock levels
            self::viewModule('inventory'),
            self::viewModule('warehouses'),
            // Can view costs for purchase price analysis
            ['inventory.view_cost'],
            // Finance: view AP bills
            ['finance.view'],
            // Reports
            ['reports.view'],
        );
    }

    private static function salesPermissions(): array
    {
        return array_merge(
            // Full sales, clients, projects
            self::fullModule('sales'),
            self::fullModule('clients'),
            self::fullModule('projects'),
            // Products: view for quotation/selling
            ['products.view'],
            // Inventory: view for availability checks
            self::viewModule('inventory'),
            self::viewModule('warehouses'),
            // Finance: view invoices
            ['finance.view'],
            // NO cost_price visibility — Segregation of Duties
            // Reports
            ['reports.view'],
        );
    }

    private static function hrPayrollPermissions(): array
    {
        return array_merge(
            // Full HR
            self::fullModule('hr'),
            // Special: approve and post payroll
            ['hr.approve_payroll', 'hr.post_payroll'],
            // Accounting view for payroll journal verification
            ['accounting.view'],
            // Reports
            ['reports.view'],
        );
    }

    // ── Helpers ──────────────────────────────────────────────────

    private static function fullModule(string $module): array
    {
        return array_map(fn($action) => "{$module}.{$action}", User::PERMISSION_ACTIONS);
    }

    private static function viewModule(string $module): array
    {
        return ["{$module}.view"];
    }
}
