<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_licenses', function (Blueprint $table) {
            $table->id();
            $table->text('license_key');
            $table->string('license_type', 20); // subscription, lifetime
            $table->string('plan_name', 100);
            $table->unsignedInteger('user_limit')->default(5);
            $table->json('features_enabled')->nullable();
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->boolean('is_active')->default(false);
            $table->dateTime('activated_at')->nullable();
            $table->string('company_name', 255)->nullable();
            $table->string('domain', 255)->nullable();
            $table->string('serial_number', 64)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_licenses');
    }
};
