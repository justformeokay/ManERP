<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ══════════════════════════════════════════════════════════════
        // A: USER TABLE — Password Expiry + MFA Readiness
        // ══════════════════════════════════════════════════════════════

        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('password_changed_at')->nullable()->after('password');
            $table->text('two_factor_secret')->nullable()->after('remember_token');
            $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_secret');
            $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_recovery_codes');
        });

        // Back-fill: set password_changed_at = now for existing users
        DB::table('users')->whereNull('password_changed_at')->update([
            'password_changed_at' => now(),
        ]);

        // ══════════════════════════════════════════════════════════════
        // B: ACTIVITY_LOGS — Integrity Checksum + Session ID
        // ══════════════════════════════════════════════════════════════

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->string('session_id', 120)->nullable()->after('user_agent');
            $table->string('checksum', 64)->nullable()->after('session_id');
        });
    }

    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropColumn(['session_id', 'checksum']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'password_changed_at',
                'two_factor_secret',
                'two_factor_recovery_codes',
                'two_factor_confirmed_at',
            ]);
        });
    }
};
