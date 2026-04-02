<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── A: Recurring Journals (extend journal_templates) ────────────
        Schema::table('journal_templates', function (Blueprint $table) {
            $table->boolean('is_recurring')->default(false)->after('is_active');
            $table->string('frequency')->nullable()->after('is_recurring'); // daily, weekly, monthly, quarterly, yearly
            $table->date('next_run_date')->nullable()->after('frequency');
            $table->date('last_run_date')->nullable()->after('next_run_date');
            $table->date('end_date')->nullable()->after('last_run_date');
        });

        // ── B: Financial Ratios — no schema needed, computed from existing data ──

        // ── C: Bank Reconciliation ──────────────────────────────────────
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('account_number')->nullable();
            $table->string('bank_name');
            $table->decimal('opening_balance', 20, 2)->default(0);
            $table->decimal('current_balance', 20, 2)->default(0);
            $table->foreignId('coa_id')->nullable()->constrained('chart_of_accounts')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('bank_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_account_id')->constrained()->cascadeOnDelete();
            $table->date('transaction_date');
            $table->string('description');
            $table->decimal('amount', 20, 2);
            $table->enum('type', ['debit', 'credit']); // debit = money in, credit = money out
            $table->string('reference')->nullable();
            $table->foreignId('journal_entry_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_reconciled')->default(false);
            $table->foreignId('reconciliation_id')->nullable();
            $table->timestamps();
        });

        Schema::create('bank_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_account_id')->constrained()->cascadeOnDelete();
            $table->date('statement_date');
            $table->decimal('statement_balance', 20, 2);
            $table->decimal('book_balance', 20, 2);
            $table->decimal('difference', 20, 2)->default(0);
            $table->enum('status', ['draft', 'completed'])->default('draft');
            $table->foreignId('reconciled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reconciled_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // ── D: Budget Management ────────────────────────────────────────
        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->year('fiscal_year');
            $table->enum('status', ['draft', 'approved', 'closed'])->default('draft');
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('budget_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('chart_of_accounts')->cascadeOnDelete();
            $table->decimal('jan', 20, 2)->default(0);
            $table->decimal('feb', 20, 2)->default(0);
            $table->decimal('mar', 20, 2)->default(0);
            $table->decimal('apr', 20, 2)->default(0);
            $table->decimal('may', 20, 2)->default(0);
            $table->decimal('jun', 20, 2)->default(0);
            $table->decimal('jul', 20, 2)->default(0);
            $table->decimal('aug', 20, 2)->default(0);
            $table->decimal('sep', 20, 2)->default(0);
            $table->decimal('oct', 20, 2)->default(0);
            $table->decimal('nov', 20, 2)->default(0);
            $table->decimal('dec', 20, 2)->default(0);
            $table->timestamps();

            $table->unique(['budget_id', 'account_id']);
        });

        // ── E: Fixed Assets & Depreciation ──────────────────────────────
        Schema::create('fixed_assets', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('category'); // building, vehicle, equipment, furniture, other
            $table->text('description')->nullable();
            $table->date('purchase_date');
            $table->decimal('purchase_cost', 20, 2);
            $table->integer('useful_life_months');
            $table->decimal('salvage_value', 20, 2)->default(0);
            $table->string('depreciation_method')->default('straight_line'); // straight_line, declining_balance
            $table->decimal('accumulated_depreciation', 20, 2)->default(0);
            $table->decimal('book_value', 20, 2)->default(0);
            $table->enum('status', ['active', 'fully_depreciated', 'disposed', 'sold'])->default('active');
            $table->string('location')->nullable();
            $table->foreignId('coa_asset_id')->nullable()->constrained('chart_of_accounts')->nullOnDelete();
            $table->foreignId('coa_depreciation_id')->nullable()->constrained('chart_of_accounts')->nullOnDelete();
            $table->foreignId('coa_expense_id')->nullable()->constrained('chart_of_accounts')->nullOnDelete();
            $table->date('disposed_date')->nullable();
            $table->decimal('disposal_amount', 20, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('depreciation_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fixed_asset_id')->constrained()->cascadeOnDelete();
            $table->date('period_date');
            $table->decimal('depreciation_amount', 20, 2);
            $table->decimal('accumulated_amount', 20, 2);
            $table->decimal('book_value', 20, 2);
            $table->foreignId('journal_entry_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        // ── F: Credit Notes / Debit Notes ───────────────────────────────
        Schema::create('credit_notes', function (Blueprint $table) {
            $table->id();
            $table->string('credit_note_number')->unique();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->decimal('amount', 20, 2);
            $table->decimal('tax_amount', 20, 2)->default(0);
            $table->decimal('total_amount', 20, 2);
            $table->string('reason');
            $table->text('notes')->nullable();
            $table->enum('status', ['draft', 'approved', 'applied'])->default('draft');
            $table->foreignId('journal_entry_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('debit_notes', function (Blueprint $table) {
            $table->id();
            $table->string('debit_note_number')->unique();
            $table->foreignId('supplier_bill_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->decimal('amount', 20, 2);
            $table->decimal('tax_amount', 20, 2)->default(0);
            $table->decimal('total_amount', 20, 2);
            $table->string('reason');
            $table->text('notes')->nullable();
            $table->enum('status', ['draft', 'approved', 'applied'])->default('draft');
            $table->foreignId('journal_entry_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('debit_notes');
        Schema::dropIfExists('credit_notes');
        Schema::dropIfExists('depreciation_entries');
        Schema::dropIfExists('fixed_assets');
        Schema::dropIfExists('budget_lines');
        Schema::dropIfExists('budgets');
        Schema::dropIfExists('bank_reconciliations');
        Schema::dropIfExists('bank_transactions');
        Schema::dropIfExists('bank_accounts');

        Schema::table('journal_templates', function (Blueprint $table) {
            $table->dropColumn(['is_recurring', 'frequency', 'next_run_date', 'last_run_date', 'end_date']);
        });
    }
};
