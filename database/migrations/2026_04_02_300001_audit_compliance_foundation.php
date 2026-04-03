<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ══════════════════════════════════════════════════════════════
        // A: MULTI-CURRENCY SUPPORT
        // ══════════════════════════════════════════════════════════════

        // A1: Currencies table
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 3)->unique();          // ISO 4217: IDR, USD, EUR
            $table->string('name');                         // Indonesian Rupiah
            $table->string('symbol', 10);                   // Rp, $, €
            $table->unsignedTinyInteger('decimal_places')->default(2);
            $table->boolean('is_base')->default(false);     // Only one base currency
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // A2: Exchange rates table (daily rates)
        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('currency_id')->constrained()->cascadeOnDelete();
            $table->date('effective_date');
            $table->decimal('rate', 20, 6);                // 1 foreign = X base
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['currency_id', 'effective_date']);
            $table->index('effective_date');
        });

        // A3: Seed base currency (IDR) — all existing data is IDR
        $idrId = DB::table('currencies')->insertGetId([
            'code'           => 'IDR',
            'name'           => 'Indonesian Rupiah',
            'symbol'         => 'Rp',
            'decimal_places' => 0,
            'is_base'        => true,
            'is_active'      => true,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        // A4: Add currency columns to invoices
        Schema::table('invoices', function (Blueprint $table) use ($idrId) {
            $table->foreignId('currency_id')->default($idrId)->after('client_id')
                  ->constrained('currencies')->restrictOnDelete();
            $table->decimal('exchange_rate', 20, 6)->default(1.000000)->after('currency_id');
            $table->decimal('total_amount_base', 14, 2)->nullable()->after('total_amount');
        });

        // Backfill: total_amount_base = total_amount for all existing invoices
        DB::statement('UPDATE invoices SET total_amount_base = total_amount WHERE total_amount_base IS NULL');

        // A5: Add currency columns to payments
        Schema::table('payments', function (Blueprint $table) use ($idrId) {
            $table->foreignId('currency_id')->default($idrId)->after('invoice_id')
                  ->constrained('currencies')->restrictOnDelete();
            $table->decimal('exchange_rate', 20, 6)->default(1.000000)->after('currency_id');
            $table->decimal('amount_base', 14, 2)->nullable()->after('amount');
        });

        DB::statement('UPDATE payments SET amount_base = amount WHERE amount_base IS NULL');

        // A6: Add currency columns to journal_entries (header-level rate)
        Schema::table('journal_entries', function (Blueprint $table) use ($idrId) {
            $table->foreignId('currency_id')->default($idrId)->after('date')
                  ->constrained('currencies')->restrictOnDelete();
            $table->decimal('exchange_rate', 20, 6)->default(1.000000)->after('currency_id');
        });

        // A7: Add base-currency columns to journal_items
        Schema::table('journal_items', function (Blueprint $table) {
            $table->decimal('debit_base', 14, 2)->default(0)->after('debit');
            $table->decimal('credit_base', 14, 2)->default(0)->after('credit');
        });

        DB::statement('UPDATE journal_items SET debit_base = debit, credit_base = credit');

        // A8: Add currency columns to supplier_bills
        Schema::table('supplier_bills', function (Blueprint $table) use ($idrId) {
            $table->foreignId('currency_id')->default($idrId)->after('purchase_order_id')
                  ->constrained('currencies')->restrictOnDelete();
            $table->decimal('exchange_rate', 20, 6)->default(1.000000)->after('currency_id');
            $table->decimal('total_base', 15, 2)->nullable()->after('total');
        });

        DB::statement('UPDATE supplier_bills SET total_base = total WHERE total_base IS NULL');

        // A9: Add currency columns to supplier_payments
        Schema::table('supplier_payments', function (Blueprint $table) use ($idrId) {
            $table->foreignId('currency_id')->default($idrId)->after('supplier_bill_id')
                  ->constrained('currencies')->restrictOnDelete();
            $table->decimal('exchange_rate', 20, 6)->default(1.000000)->after('currency_id');
            $table->decimal('amount_base', 15, 2)->nullable()->after('amount');
        });

        DB::statement('UPDATE supplier_payments SET amount_base = amount WHERE amount_base IS NULL');

        // ══════════════════════════════════════════════════════════════
        // B: AUDIT TRAIL — FIELD-LEVEL TRACKING + IMMUTABILITY
        // ══════════════════════════════════════════════════════════════

        Schema::table('activity_logs', function (Blueprint $table) {
            // Polymorphic reference to the audited record
            $table->string('auditable_type', 100)->nullable()->after('action');
            $table->unsignedBigInteger('auditable_id')->nullable()->after('auditable_type');

            // Field-level changes: [{"field":"status","old":"draft","new":"posted"}, ...]
            $table->json('changes')->nullable()->after('new_data');

            $table->index(['auditable_type', 'auditable_id'], 'activity_logs_auditable_index');
        });
    }

    public function down(): void
    {
        // ── Reverse Audit Trail ──
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropIndex('activity_logs_auditable_index');
            $table->dropColumn(['auditable_type', 'auditable_id', 'changes']);
        });

        // ── Reverse Multi-Currency (reverse order) ──
        Schema::table('supplier_payments', function (Blueprint $table) {
            $table->dropForeign(['currency_id']);
            $table->dropColumn(['currency_id', 'exchange_rate', 'amount_base']);
        });

        Schema::table('supplier_bills', function (Blueprint $table) {
            $table->dropForeign(['currency_id']);
            $table->dropColumn(['currency_id', 'exchange_rate', 'total_base']);
        });

        Schema::table('journal_items', function (Blueprint $table) {
            $table->dropColumn(['debit_base', 'credit_base']);
        });

        Schema::table('journal_entries', function (Blueprint $table) {
            $table->dropForeign(['currency_id']);
            $table->dropColumn(['currency_id', 'exchange_rate']);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['currency_id']);
            $table->dropColumn(['currency_id', 'exchange_rate', 'amount_base']);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['currency_id']);
            $table->dropColumn(['currency_id', 'exchange_rate', 'total_amount_base']);
        });

        Schema::dropIfExists('exchange_rates');
        Schema::dropIfExists('currencies');
    }
};
