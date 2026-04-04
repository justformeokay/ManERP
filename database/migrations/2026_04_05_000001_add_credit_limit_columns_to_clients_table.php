<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->decimal('credit_limit', 18, 2)->default(0)->after('notes');
            $table->unsignedInteger('payment_terms')->default(30)->after('credit_limit');
            $table->boolean('is_credit_blocked')->default(false)->after('payment_terms');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['credit_limit', 'payment_terms', 'is_credit_blocked']);
        });
    }
};
