<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chart_of_accounts', function (Blueprint $table) {
            $table->string('cash_flow_category', 20)->nullable()->after('type');
        });

        // Auto-map existing accounts based on account code conventions (PSAK 2 / IAS 7)
        $mappings = [
            // Cash & Bank → 'cash'
            ['prefix' => '110', 'category' => 'cash'],       // 1100, 1101, 1102, 1110
            ['prefix' => '111', 'category' => 'cash'],       // 1110-1119

            // Current Assets (non-cash) → 'operating'
            ['prefix' => '112', 'category' => 'operating'],  // Prepaid, etc.
            ['prefix' => '12',  'category' => 'operating'],  // Accounts Receivable
            ['prefix' => '13',  'category' => 'operating'],  // Inventory
            ['prefix' => '14',  'category' => 'operating'],  // Other Current Assets

            // Fixed Assets & Accumulated Depreciation → 'investing'
            ['prefix' => '15',  'category' => 'investing'],  // Fixed Assets
            ['prefix' => '16',  'category' => 'investing'],  // Intangible Assets
            ['prefix' => '17',  'category' => 'investing'],  // Long-term Investments

            // Current Liabilities → 'operating'
            ['prefix' => '20',  'category' => 'operating'],  // Accounts Payable
            ['prefix' => '21',  'category' => 'operating'],  // Tax Payable, Accrued Expenses

            // Long-term Liabilities → 'financing'
            ['prefix' => '22',  'category' => 'financing'],  // Long-term Debt
            ['prefix' => '23',  'category' => 'financing'],  // Bonds Payable
            ['prefix' => '24',  'category' => 'financing'],  // Other LT Liabilities

            // Equity → 'financing'
            ['prefix' => '30',  'category' => 'financing'],  // Equity / Capital
            ['prefix' => '31',  'category' => 'financing'],  // Retained Earnings
            ['prefix' => '32',  'category' => 'financing'],  // Other Equity

            // Revenue & Expenses → 'none' (handled via net income in indirect method)
            ['prefix' => '4',   'category' => 'none'],       // Revenue
            ['prefix' => '5',   'category' => 'none'],       // COGS
            ['prefix' => '6',   'category' => 'none'],       // Operating Expenses
            ['prefix' => '7',   'category' => 'none'],       // Other Income
            ['prefix' => '8',   'category' => 'none'],       // Other Expenses
            ['prefix' => '9',   'category' => 'none'],       // Tax Expense
        ];

        foreach ($mappings as $map) {
            DB::table('chart_of_accounts')
                ->where('code', 'like', $map['prefix'] . '%')
                ->whereNull('cash_flow_category')
                ->update(['cash_flow_category' => $map['category']]);
        }
    }

    public function down(): void
    {
        Schema::table('chart_of_accounts', function (Blueprint $table) {
            $table->dropColumn('cash_flow_category');
        });
    }
};
