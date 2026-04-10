<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->string('purchase_type', 20)->default('operational')->after('number');
            $table->foreignId('department_id')->nullable()->after('warehouse_id')
                ->constrained('departments')->nullOnDelete();
            $table->string('priority', 10)->default('normal')->after('expected_date');
            $table->text('justification')->nullable()->after('notes');

            $table->index('purchase_type');
            $table->index('priority');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropIndex(['purchase_type']);
            $table->dropIndex(['priority']);
            $table->dropColumn(['purchase_type', 'department_id', 'priority', 'justification']);
        });
    }
};
