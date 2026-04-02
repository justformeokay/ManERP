<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── A: Cash Flow — tag journal entries with cash-flow category ──
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->string('entry_type', 30)->default('manual')->after('is_posted');
            // operating, investing, financing, or null for auto-detect
            $table->string('cash_flow_category', 20)->nullable()->after('entry_type');
            // For reversing/adjusting entries link to the original journal
            $table->foreignId('reversed_entry_id')->nullable()->after('cash_flow_category')
                  ->constrained('journal_entries')->nullOnDelete();
        });

        // ── B: PPN / Tax Management — tax detail on invoices & bills ──
        Schema::table('invoices', function (Blueprint $table) {
            $table->decimal('tax_rate', 5, 2)->default(0)->after('tax_amount');
            $table->decimal('dpp', 14, 2)->default(0)->after('tax_rate');        // Dasar Pengenaan Pajak
            $table->string('faktur_pajak_number', 50)->nullable()->after('dpp');
        });

        Schema::table('supplier_bills', function (Blueprint $table) {
            $table->decimal('tax_rate', 5, 2)->default(0)->after('tax_amount');
            $table->decimal('dpp', 14, 2)->default(0)->after('tax_rate');
            $table->string('faktur_pajak_number', 50)->nullable()->after('dpp');
        });

        // Tax account mapping in chart_of_accounts
        Schema::table('chart_of_accounts', function (Blueprint $table) {
            $table->boolean('is_tax_account')->default(false)->after('is_active');
            $table->string('tax_type', 30)->nullable()->after('is_tax_account');  // ppn_keluaran, ppn_masukan, pph21, pph23, etc.
        });

        // ── D: Fiscal Period / Closing ──
        Schema::create('fiscal_periods', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);                            // e.g. "January 2026", "Q1 2026"
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status', ['open', 'closed'])->default('open');
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->text('closing_notes')->nullable();
            $table->foreignId('closing_journal_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->timestamps();

            $table->unique(['start_date', 'end_date']);
            $table->index('status');
        });

        // ── E: Recurring journal templates ──
        Schema::create('journal_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->json('items');  // [{account_id, debit, credit, description}]
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // ── F: NPWP / Tax data on clients & suppliers ──
        Schema::table('clients', function (Blueprint $table) {
            $table->string('npwp', 30)->nullable()->after('tax_id');
            $table->text('tax_address')->nullable()->after('npwp');
            $table->boolean('is_pkp')->default(false)->after('tax_address');
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->string('npwp', 30)->nullable()->after('tax_id');
            $table->text('tax_address')->nullable()->after('npwp');
            $table->boolean('is_pkp')->default(false)->after('tax_address');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_templates');
        Schema::dropIfExists('fiscal_periods');

        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn(['npwp', 'tax_address', 'is_pkp']);
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['npwp', 'tax_address', 'is_pkp']);
        });

        Schema::table('chart_of_accounts', function (Blueprint $table) {
            $table->dropColumn(['is_tax_account', 'tax_type']);
        });

        Schema::table('supplier_bills', function (Blueprint $table) {
            $table->dropColumn(['tax_rate', 'dpp', 'faktur_pajak_number']);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['tax_rate', 'dpp', 'faktur_pajak_number']);
        });

        Schema::table('journal_entries', function (Blueprint $table) {
            $table->dropForeign(['reversed_entry_id']);
            $table->dropColumn(['entry_type', 'cash_flow_category', 'reversed_entry_id']);
        });
    }
};
