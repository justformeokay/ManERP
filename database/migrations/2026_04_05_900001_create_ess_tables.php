<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Employee Documents (KTP, NPWP, Ijazah) ──────────────────
        Schema::create('employee_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('type');       // ktp, npwp, ijazah
            $table->string('file_path');  // relative path under private disk
            $table->string('file_hash');  // SHA-256 of file content for integrity
            $table->string('original_name');
            $table->string('mime_type');
            $table->unsignedInteger('file_size'); // bytes
            $table->string('status')->default('pending'); // pending, verified, rejected
            $table->text('rejection_reason')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'type']);
        });

        // ── Employee Data Change Requests (Approval Workflow) ────────
        Schema::create('employee_data_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->json('requested_changes'); // {"phone": "081xxx", "bank_account_number": "123"}
            $table->json('original_data');     // snapshot before changes
            $table->string('status')->default('pending'); // pending, approved, rejected
            $table->text('rejection_reason')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_data_changes');
        Schema::dropIfExists('employee_documents');
    }
};
