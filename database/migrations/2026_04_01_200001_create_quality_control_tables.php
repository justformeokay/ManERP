<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // QC Parameters — reusable quality check criteria (e.g., Weight, Dimensions, Visual Check)
        Schema::create('qc_parameters', function (Blueprint $table) {
            $table->id();
            $table->string('name');                   // e.g. "Weight", "Visual Check", "Dimension Length"
            $table->text('description')->nullable();
            $table->enum('type', ['numeric', 'boolean', 'text'])->default('numeric');
            $table->string('unit')->nullable();        // e.g. "kg", "mm", "cm"
            $table->decimal('min_value', 12, 4)->nullable(); // for numeric type
            $table->decimal('max_value', 12, 4)->nullable(); // for numeric type
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // QC Inspections — header for each inspection event
        Schema::create('qc_inspections', function (Blueprint $table) {
            $table->id();
            $table->string('number', 30)->unique();
            $table->enum('inspection_type', ['incoming', 'in_process', 'final'])->default('incoming');
            $table->nullableMorphs('reference'); // polymorphic: manufacturing_order, purchase_order, sales_order
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('inspected_quantity', 12, 2);
            $table->decimal('passed_quantity', 12, 2)->default(0);
            $table->decimal('failed_quantity', 12, 2)->default(0);
            $table->enum('result', ['pending', 'passed', 'failed', 'partial'])->default('pending');
            $table->enum('status', ['draft', 'in_progress', 'completed'])->default('draft');
            $table->text('notes')->nullable();
            $table->foreignId('inspector_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('inspected_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('inspection_type');
            $table->index('result');
            $table->index('status');
        });

        // QC Inspection Items — individual parameter checks per inspection
        Schema::create('qc_inspection_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('qc_inspection_id')->constrained('qc_inspections')->cascadeOnDelete();
            $table->foreignId('qc_parameter_id')->constrained('qc_parameters')->restrictOnDelete();
            $table->decimal('min_value', 12, 4)->nullable();  // snapshot from parameter
            $table->decimal('max_value', 12, 4)->nullable();  // snapshot from parameter
            $table->string('measured_value')->nullable();       // actual measured value (string for all types)
            $table->enum('result', ['pending', 'pass', 'fail'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qc_inspection_items');
        Schema::dropIfExists('qc_inspections');
        Schema::dropIfExists('qc_parameters');
    }
};
