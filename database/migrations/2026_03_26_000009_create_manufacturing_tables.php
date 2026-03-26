<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Bill of Materials (recipe for manufacturing a product)
        Schema::create('bill_of_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->restrictOnDelete(); // finished product
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('output_quantity', 12, 2)->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('product_id');
        });

        Schema::create('bom_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bom_id')->constrained('bill_of_materials')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete(); // raw material / component
            $table->decimal('quantity', 12, 4);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Manufacturing Orders
        Schema::create('manufacturing_orders', function (Blueprint $table) {
            $table->id();
            $table->string('number', 30)->unique();
            $table->foreignId('bom_id')->constrained('bill_of_materials')->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete(); // output product
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('planned_quantity', 12, 2);
            $table->decimal('produced_quantity', 12, 2)->default(0);
            $table->enum('status', ['draft', 'confirmed', 'in_progress', 'done', 'cancelled'])->default('draft');
            $table->date('planned_start')->nullable();
            $table->date('planned_end')->nullable();
            $table->date('actual_start')->nullable();
            $table->date('actual_end')->nullable();
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('priority');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manufacturing_orders');
        Schema::dropIfExists('bom_items');
        Schema::dropIfExists('bill_of_materials');
    }
};
