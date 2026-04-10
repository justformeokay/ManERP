<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('type', 20)->default('sales')->after('code');
            $table->decimal('estimated_budget', 15, 2)->nullable()->after('budget');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropIndex(['type']);
            $table->dropColumn(['type', 'estimated_budget']);
        });
    }
};
