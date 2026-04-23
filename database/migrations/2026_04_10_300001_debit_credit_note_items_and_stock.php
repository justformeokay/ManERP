<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Add warehouse_id to credit_notes ─────────────────────
        Schema::table('credit_notes', function (Blueprint $table) {
            $table->foreignId('warehouse_id')->nullable()->after('client_id')
                  ->constrained()->nullOnDelete();
        });

        // ── Add warehouse_id to debit_notes ──────────────────────
        Schema::table('debit_notes', function (Blueprint $table) {
            $table->foreignId('warehouse_id')->nullable()->after('supplier_id')
                  ->constrained()->nullOnDelete();
        });

        // ── Credit note line items ───────────────────────────────
        Schema::create('credit_note_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('credit_note_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 14, 2);
            $table->decimal('unit_price', 20, 2);
            $table->decimal('subtotal', 20, 2);
            $table->timestamps();

            $table->index('credit_note_id');
            $table->index('product_id');
        });

        // ── Debit note line items ────────────────────────────────
        Schema::create('debit_note_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('debit_note_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 14, 2);
            $table->decimal('unit_price', 20, 2);
            $table->decimal('subtotal', 20, 2);
            $table->timestamps();

            $table->index('debit_note_id');
            $table->index('product_id');
        });

        // ── Seed Sales Return CoA account if absent ──────────────
        \Illuminate\Support\Facades\DB::table('chart_of_accounts')->insertOrIgnore([
            'code'       => '4100',
            'name'       => 'Sales Returns & Allowances',
            'type'       => 'revenue',
            'is_active'  => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('debit_note_items');
        Schema::dropIfExists('credit_note_items');

        Schema::table('debit_notes', function (Blueprint $table) {
            $table->dropForeign(['warehouse_id']);
            $table->dropColumn('warehouse_id');
        });

        Schema::table('credit_notes', function (Blueprint $table) {
            $table->dropForeign(['warehouse_id']);
            $table->dropColumn('warehouse_id');
        });

        \Illuminate\Support\Facades\DB::table('chart_of_accounts')->where('code', '4100')->delete();
    }
};
