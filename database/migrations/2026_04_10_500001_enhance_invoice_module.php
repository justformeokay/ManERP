<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Invoice Module Technical Audit Enhancement
 *
 * 1. Add 'sent' status to invoices enum
 * 2. Add 'invoiced_quantity' to sales_order_items for partial invoicing tracking
 * 3. Add 'sent_at' timestamp for tracking when invoice was sent
 * 4. Seed PPN Keluaran CoA (2110) properly as liability/tax account
 * 5. Fix invoice_number format to INV/YYYY/MM/SEQUENCE
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Add sent status to invoices (MySQL only — SQLite stores enum as string, no change needed)
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE invoices MODIFY COLUMN status ENUM('draft','sent','unpaid','partial','paid','cancelled') NOT NULL DEFAULT 'draft'");
        }

        // 2. Add sent_at timestamp
        if (!Schema::hasColumn('invoices', 'sent_at')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->timestamp('sent_at')->nullable();
            });
        }

        // 3. Add invoiced_quantity to sales_order_items for partial invoicing
        if (!Schema::hasColumn('sales_order_items', 'invoiced_quantity')) {
            Schema::table('sales_order_items', function (Blueprint $table) {
                $table->decimal('invoiced_quantity', 12, 2)->default(0);
            });
        }

        // 4. Seed PPN Keluaran as proper tax account (fix collision with Payroll)
        // The HR migration seeds 2110 as 'Utang Gaji' — we need it as PPN Keluaran
        DB::table('chart_of_accounts')->updateOrInsert(
            ['code' => '2110'],
            [
                'name' => 'PPN Keluaran',
                'type' => 'liability',
                'is_active' => true,
                'is_system_account' => true,
                'is_tax_account' => true,
                'tax_type' => 'ppn_keluaran',
                'liquidity_classification' => 'current',
                'updated_at' => now(),
            ]
        );

        // Move Payroll Payable to 2150 to resolve collision
        DB::table('chart_of_accounts')->updateOrInsert(
            ['code' => '2150'],
            [
                'name' => 'Utang Gaji (Payroll Payable)',
                'type' => 'liability',
                'is_active' => true,
                'is_system_account' => true,
                'liquidity_classification' => 'current',
                'updated_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE invoices MODIFY COLUMN status ENUM('draft','unpaid','partial','paid','cancelled') NOT NULL DEFAULT 'draft'");
        }

        if (Schema::hasColumn('invoices', 'sent_at')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->dropColumn('sent_at');
            });
        }

        if (Schema::hasColumn('sales_order_items', 'invoiced_quantity')) {
            Schema::table('sales_order_items', function (Blueprint $table) {
                $table->dropColumn('invoiced_quantity');
            });
        }
    }
};
