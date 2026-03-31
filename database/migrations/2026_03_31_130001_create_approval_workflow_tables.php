<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Approval Roles (Manager, Director, Finance, etc.)
        Schema::create('approval_roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');           // Manager, Director, Finance Manager
            $table->string('slug')->unique(); // manager, director, finance_manager
            $table->integer('level')->default(1); // Hierarchy level (higher = more authority)
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Link users to approval roles (many-to-many)
        Schema::create('approval_role_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('approval_role_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->unique(['user_id', 'approval_role_id']);
        });

        // Approval Flows (one per module type)
        Schema::create('approval_flows', function (Blueprint $table) {
            $table->id();
            $table->string('module');         // purchase_order, invoice, payment, supplier_bill
            $table->string('name');           // "PO Approval Flow", "Invoice Approval"
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('module');         // One active flow per module
        });

        // Approval Steps (multiple steps per flow)
        Schema::create('approval_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('approval_flow_id')->constrained()->onDelete('cascade');
            $table->integer('step_order');    // 1, 2, 3...
            $table->foreignId('approval_role_id')->constrained()->onDelete('cascade');
            $table->decimal('min_amount', 15, 2)->nullable(); // Condition: amount >= min
            $table->decimal('max_amount', 15, 2)->nullable(); // Condition: amount <= max
            $table->boolean('is_required')->default(true);    // Skip if not required
            $table->integer('timeout_hours')->nullable();     // Escalation timeout
            $table->timestamps();

            $table->unique(['approval_flow_id', 'step_order']);
            $table->index(['min_amount', 'max_amount']);
        });

        // Approvals (actual approval requests)
        Schema::create('approvals', function (Blueprint $table) {
            $table->id();
            $table->string('module');                         // purchase_order, invoice, etc.
            $table->unsignedBigInteger('reference_id');       // PO id, Invoice id, etc.
            $table->decimal('amount', 15, 2)->default(0);     // Amount for routing
            $table->integer('current_step')->default(1);
            $table->integer('total_steps')->default(1);
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');
            $table->foreignId('requested_by')->constrained('users')->onDelete('cascade');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamps();

            $table->index(['module', 'reference_id']);
            $table->index('status');
        });

        // Approval Logs (audit trail)
        Schema::create('approval_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('approval_id')->constrained()->onDelete('cascade');
            $table->integer('step_order');
            $table->foreignId('approval_role_id')->constrained();
            $table->foreignId('acted_by')->nullable()->constrained('users')->onDelete('set null');
            $table->enum('action', ['pending', 'approved', 'rejected', 'skipped', 'escalated']);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['approval_id', 'step_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_logs');
        Schema::dropIfExists('approvals');
        Schema::dropIfExists('approval_steps');
        Schema::dropIfExists('approval_flows');
        Schema::dropIfExists('approval_role_user');
        Schema::dropIfExists('approval_roles');
    }
};
