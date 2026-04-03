<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add avg_cost to products
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('avg_cost', 15, 4)->default(0)->after('standard_cost')
                  ->comment('Weighted Average Cost per unit (PSAK 14)');
        });

        // 2. Add unit_cost & total_value to stock_movements for audit trail
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->decimal('unit_cost', 15, 4)->default(0)->after('balance_after')
                  ->comment('Cost per unit at time of movement');
            $table->decimal('total_value', 15, 4)->default(0)->after('unit_cost')
                  ->comment('Total value = quantity × unit_cost');
        });

        // 3. Create stock_valuation_layers table
        Schema::create('stock_valuation_layers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('stock_movement_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('direction', ['in', 'out']);
            $table->decimal('quantity', 12, 2)->comment('Movement quantity (always positive)');
            $table->decimal('unit_cost', 15, 4)->comment('Cost per unit for this layer');
            $table->decimal('total_value', 15, 4)->comment('quantity × unit_cost');
            $table->decimal('remaining_qty', 12, 2)->comment('Remaining qty (for future FIFO if needed)');
            $table->decimal('remaining_value', 15, 4)->comment('Remaining value');
            $table->decimal('avg_cost_after', 15, 4)->comment('Product avg_cost after this layer');
            $table->string('reference_type', 50)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'created_at']);
            $table->index(['reference_type', 'reference_id']);
            $table->index('direction');
        });

        // 4. Seed avg_cost from existing cost_price as opening balance
        DB::statement('UPDATE products SET avg_cost = cost_price WHERE cost_price > 0');
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_valuation_layers');

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropColumn(['unit_cost', 'total_value']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('avg_cost');
        });
    }
};
