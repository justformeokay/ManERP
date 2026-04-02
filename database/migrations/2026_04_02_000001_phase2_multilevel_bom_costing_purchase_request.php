<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Enhance BOM for multi-level support + versioning
        Schema::table('bill_of_materials', function (Blueprint $table) {
            $table->unsignedInteger('version')->default(1)->after('output_quantity');
            $table->foreignId('parent_bom_id')->nullable()->after('id')
                  ->constrained('bill_of_materials')->nullOnDelete();
            $table->integer('level')->default(0)->after('version'); // depth in tree
        });

        // 2. Enhance BOM items — allow sub-BOM reference
        Schema::table('bom_items', function (Blueprint $table) {
            $table->foreignId('sub_bom_id')->nullable()->after('product_id')
                  ->constrained('bill_of_materials')->nullOnDelete();
            $table->decimal('unit_cost', 14, 2)->default(0)->after('quantity');
            $table->decimal('line_cost', 14, 2)->default(0)->after('unit_cost');
        });

        // 3. Product cost enhancements
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('overhead_cost', 14, 2)->default(0)->after('cost_price');
            $table->decimal('labor_cost', 14, 2)->default(0)->after('overhead_cost');
            $table->decimal('standard_cost', 14, 2)->default(0)->after('labor_cost');
        });

        // 4. Production Costs table (HPP tracking per manufacturing order)
        Schema::create('production_costs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manufacturing_order_id')->constrained('manufacturing_orders')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->decimal('material_cost', 14, 2)->default(0);
            $table->decimal('labor_cost', 14, 2)->default(0);
            $table->decimal('overhead_cost', 14, 2)->default(0);
            $table->decimal('total_cost', 14, 2)->default(0);
            $table->decimal('cost_per_unit', 14, 4)->default(0);
            $table->integer('produced_quantity')->default(0);
            $table->json('cost_breakdown')->nullable(); // detailed per-material breakdown
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 5. Purchase Requests table
        Schema::create('purchase_requests', function (Blueprint $table) {
            $table->id();
            $table->string('number', 20)->unique();
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('status', ['draft', 'pending', 'approved', 'rejected', 'converted'])->default('draft');
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            $table->date('required_date')->nullable();
            $table->text('reason')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // 6. Purchase Request Items table
        Schema::create('purchase_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 12, 2);
            $table->decimal('estimated_price', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);
            $table->text('specification')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 7. Link PO back to PR
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->foreignId('purchase_request_id')->nullable()->after('id')
                  ->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('purchase_request_id');
        });

        Schema::dropIfExists('purchase_request_items');
        Schema::dropIfExists('purchase_requests');
        Schema::dropIfExists('production_costs');

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['overhead_cost', 'labor_cost', 'standard_cost']);
        });

        Schema::table('bom_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sub_bom_id');
            $table->dropColumn(['unit_cost', 'line_cost']);
        });

        Schema::table('bill_of_materials', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_bom_id');
            $table->dropColumn(['version', 'level']);
        });
    }
};
