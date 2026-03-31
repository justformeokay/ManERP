<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ApprovalWorkflowSeeder extends Seeder
{
    public function run(): void
    {
        // Create Approval Roles
        $roles = [
            ['name' => 'Staff', 'slug' => 'staff', 'level' => 1],
            ['name' => 'Supervisor', 'slug' => 'supervisor', 'level' => 2],
            ['name' => 'Manager', 'slug' => 'manager', 'level' => 3],
            ['name' => 'Finance Manager', 'slug' => 'finance_manager', 'level' => 3],
            ['name' => 'Director', 'slug' => 'director', 'level' => 4],
            ['name' => 'CEO', 'slug' => 'ceo', 'level' => 5],
        ];

        foreach ($roles as $role) {
            DB::table('approval_roles')->updateOrInsert(
                ['slug' => $role['slug']],
                array_merge($role, ['created_at' => now(), 'updated_at' => now()])
            );
        }

        // Get role IDs
        $managerRole = DB::table('approval_roles')->where('slug', 'manager')->value('id');
        $directorRole = DB::table('approval_roles')->where('slug', 'director')->value('id');
        $financeRole = DB::table('approval_roles')->where('slug', 'finance_manager')->value('id');

        // Create Approval Flows
        $flows = [
            [
                'module' => 'purchase_order',
                'name' => 'Purchase Order Approval',
                'description' => 'Multi-level approval for purchase orders based on amount',
                'steps' => [
                    // Auto-approve < 10 million (no steps needed, handled by service)
                    ['step_order' => 1, 'approval_role_id' => $managerRole, 'min_amount' => 10000000, 'max_amount' => 50000000],
                    ['step_order' => 2, 'approval_role_id' => $directorRole, 'min_amount' => 50000000, 'max_amount' => null],
                ],
            ],
            [
                'module' => 'invoice',
                'name' => 'Invoice Approval',
                'description' => 'Approval for customer invoices',
                'steps' => [
                    ['step_order' => 1, 'approval_role_id' => $financeRole, 'min_amount' => 0, 'max_amount' => null],
                ],
            ],
            [
                'module' => 'supplier_bill',
                'name' => 'Supplier Bill Approval',
                'description' => 'Approval for supplier bills before posting',
                'steps' => [
                    ['step_order' => 1, 'approval_role_id' => $managerRole, 'min_amount' => 5000000, 'max_amount' => 25000000],
                    ['step_order' => 2, 'approval_role_id' => $financeRole, 'min_amount' => 25000000, 'max_amount' => null],
                ],
            ],
            [
                'module' => 'payment',
                'name' => 'Payment Approval',
                'description' => 'Approval for outgoing payments',
                'steps' => [
                    ['step_order' => 1, 'approval_role_id' => $financeRole, 'min_amount' => 0, 'max_amount' => 100000000],
                    ['step_order' => 2, 'approval_role_id' => $directorRole, 'min_amount' => 100000000, 'max_amount' => null],
                ],
            ],
        ];

        foreach ($flows as $flowData) {
            $steps = $flowData['steps'];
            unset($flowData['steps']);

            $flowId = DB::table('approval_flows')->updateOrInsert(
                ['module' => $flowData['module']],
                array_merge($flowData, ['created_at' => now(), 'updated_at' => now()])
            );

            $flowId = DB::table('approval_flows')->where('module', $flowData['module'])->value('id');

            // Clear existing steps
            DB::table('approval_steps')->where('approval_flow_id', $flowId)->delete();

            // Insert steps
            foreach ($steps as $step) {
                DB::table('approval_steps')->insert(array_merge($step, [
                    'approval_flow_id' => $flowId,
                    'is_required' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }

        // Assign admin user to Manager role for testing
        $adminUser = DB::table('users')->where('role', 'admin')->first();
        if ($adminUser && $managerRole) {
            DB::table('approval_role_user')->updateOrInsert(
                ['user_id' => $adminUser->id, 'approval_role_id' => $managerRole],
                ['created_at' => now(), 'updated_at' => now()]
            );
            DB::table('approval_role_user')->updateOrInsert(
                ['user_id' => $adminUser->id, 'approval_role_id' => $directorRole],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }
    }
}
