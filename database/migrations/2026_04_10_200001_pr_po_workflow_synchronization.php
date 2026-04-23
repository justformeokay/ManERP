<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // TUGAS 2: Add department_id and purchase_type to purchase_requests
        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->after('project_id')
                ->constrained('departments')->nullOnDelete();
            $table->string('purchase_type', 20)->default('operational')->after('department_id');

            $table->index('purchase_type');
        });

        // TUGAS 3: Add payment_terms and shipping_address to purchase_orders
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->string('payment_terms', 30)->default('net_30')->after('notes');
            $table->text('shipping_address')->nullable()->after('payment_terms');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropIndex(['purchase_type']);
            $table->dropColumn(['department_id', 'purchase_type']);
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn(['payment_terms', 'shipping_address']);
        });
    }
};
