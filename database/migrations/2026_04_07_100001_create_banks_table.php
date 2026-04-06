<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 10)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Add bank_id FK to employees (alongside existing bank_name for backward compat)
        Schema::table('employees', function (Blueprint $table) {
            $table->foreignId('bank_id')->nullable()->after('bank_name')
                ->constrained('banks')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropConstrainedForeignId('bank_id');
        });

        Schema::dropIfExists('banks');
    }
};
