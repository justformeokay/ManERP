<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Add status column replacing is_active
            $table->string('status', 20)->default('active')->after('phone');
        });
        
        // Migrate existing is_active data to status
        DB::statement("UPDATE users SET status = CASE WHEN is_active = 1 THEN 'active' ELSE 'inactive' END");
        
        // Update role defaults - change 'user' to 'staff'
        DB::statement("UPDATE users SET role = 'staff' WHERE role = 'user' OR role IS NULL OR role = ''");
        
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('phone');
        });
        
        DB::statement("UPDATE users SET is_active = CASE WHEN status = 'active' THEN 1 ELSE 0 END");
        
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
