<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Phase 5 — Accounting Integrity & Control Account Patch
 *
 * 1. Adds is_system_account flag to chart_of_accounts
 * 2. Adds liquidity_classification for Balance Sheet reclassification
 * 3. Seeds is_system_account = true for control accounts
 * 4. Seeds liquidity_classification for all accounts
 * 5. Adds 5102 (Inventory Adjustment Variance) account
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chart_of_accounts', function (Blueprint $table) {
            $table->boolean('is_system_account')->default(false)->after('is_active');
            $table->string('liquidity_classification', 30)->nullable()->after('cash_flow_category');
        });

        // ── Mark control accounts as system accounts ──
        DB::table('chart_of_accounts')
            ->whereIn('code', ['1200', '1300', '1300-RM', '1300-FG', '1400', '2000', '3200'])
            ->update(['is_system_account' => true]);

        // ── Liquidity classification for Balance Sheet ──
        // Current Assets (codes 11xx, 12xx, 13xx, 14xx)
        DB::table('chart_of_accounts')
            ->where('type', 'asset')
            ->where(function ($q) {
                $q->where('code', 'like', '11%')
                  ->orWhere('code', 'like', '12%')
                  ->orWhere('code', 'like', '13%')
                  ->orWhere('code', 'like', '14%');
            })
            ->update(['liquidity_classification' => 'current']);

        // Non-Current Assets (codes 15xx, 16xx, 17xx, 18xx)
        DB::table('chart_of_accounts')
            ->where('type', 'asset')
            ->where(function ($q) {
                $q->where('code', 'like', '15%')
                  ->orWhere('code', 'like', '16%')
                  ->orWhere('code', 'like', '17%')
                  ->orWhere('code', 'like', '18%');
            })
            ->update(['liquidity_classification' => 'non_current']);

        // Current Liabilities (codes 20xx, 21xx)
        DB::table('chart_of_accounts')
            ->where('type', 'liability')
            ->where(function ($q) {
                $q->where('code', 'like', '20%')
                  ->orWhere('code', 'like', '21%');
            })
            ->update(['liquidity_classification' => 'current']);

        // Non-Current Liabilities (codes 22xx, 23xx, 24xx)
        DB::table('chart_of_accounts')
            ->where('type', 'liability')
            ->where(function ($q) {
                $q->where('code', 'like', '22%')
                  ->orWhere('code', 'like', '23%')
                  ->orWhere('code', 'like', '24%');
            })
            ->update(['liquidity_classification' => 'non_current']);

        // ── Seed Inventory Adjustment Variance account ──
        DB::table('chart_of_accounts')->insertOrIgnore([
            'code'                      => '5102',
            'name'                      => 'Inventory Adjustment Variance',
            'type'                      => 'expense',
            'cash_flow_category'        => 'none',
            'liquidity_classification'  => null,
            'is_active'                 => true,
            'is_system_account'         => true,
            'created_at'                => now(),
            'updated_at'                => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('chart_of_accounts')->where('code', '5102')->delete();

        Schema::table('chart_of_accounts', function (Blueprint $table) {
            $table->dropColumn(['is_system_account', 'liquidity_classification']);
        });
    }
};
