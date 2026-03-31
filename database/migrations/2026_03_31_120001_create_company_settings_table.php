<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_settings', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('logo')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->string('tax_id')->nullable();
            $table->string('currency', 3)->default('IDR');
            $table->text('invoice_terms')->nullable();
            $table->text('po_terms')->nullable();
            $table->timestamps();
        });

        // Insert default company settings
        \DB::table('company_settings')->insert([
            'name' => 'ManERP Company',
            'address' => '123 Business Street',
            'city' => 'Jakarta',
            'country' => 'Indonesia',
            'phone' => '+62 21 1234567',
            'email' => 'info@manerp.com',
            'currency' => 'IDR',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('company_settings');
    }
};
