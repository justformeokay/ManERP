<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs_archives', function (Blueprint $table) {
            // Mirror the exact structure of activity_logs for checksum preservation
            $table->id();
            $table->unsignedBigInteger('original_id')->index();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('module', 50)->index();
            $table->string('action', 30)->index();
            $table->string('auditable_type', 100)->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->string('description')->nullable();
            $table->json('old_data')->nullable();
            $table->json('new_data')->nullable();
            $table->json('changes')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('session_id', 120)->nullable();
            $table->string('checksum', 64)->nullable();
            $table->timestamp('created_at')->nullable()->index();
            $table->timestamp('archived_at')->useCurrent();

            $table->index(['auditable_type', 'auditable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs_archives');
    }
};
