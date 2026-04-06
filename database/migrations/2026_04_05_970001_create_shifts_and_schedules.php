<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Shifts (Work Shift Definitions) ──────────────────────
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);           // Pagi, Siang, Malam, Full Day
            $table->time('start_time');            // 08:00
            $table->time('end_time');              // 16:00
            $table->unsignedSmallInteger('grace_period')->default(15); // minutes
            $table->boolean('is_night_shift')->default(false);
            $table->decimal('night_shift_bonus', 15, 2)->default(0);  // premi shift malam
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // ── Shift Schedules (Rotation Assignments) ───────────────
        Schema::create('shift_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shift_id')->constrained()->cascadeOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->timestamps();

            $table->index(['employee_id', 'start_date', 'end_date']);
        });

        // ── Add shift_id default to employees ────────────────────
        Schema::table('employees', function (Blueprint $table) {
            $table->foreignId('shift_id')->nullable()->after('user_id')
                ->constrained()->nullOnDelete();
        });

        // ── Add late_minutes + shift_id to attendances ───────────
        Schema::table('attendances', function (Blueprint $table) {
            $table->unsignedSmallInteger('late_minutes')->default(0)->after('status');
            $table->foreignId('shift_id')->nullable()->after('source')
                ->constrained()->nullOnDelete();
        });

        // ── Add late_deduction + night_shift_bonus to payslips ───
        Schema::table('payslips', function (Blueprint $table) {
            $table->decimal('late_deduction', 15, 2)->default(0)->after('absence_deduction');
            $table->decimal('night_shift_bonus', 15, 2)->default(0)->after('other_earnings');
        });
    }

    public function down(): void
    {
        Schema::table('payslips', function (Blueprint $table) {
            $table->dropColumn(['late_deduction', 'night_shift_bonus']);
        });
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropConstrainedForeignId('shift_id');
            $table->dropColumn('late_minutes');
        });
        Schema::table('employees', function (Blueprint $table) {
            $table->dropConstrainedForeignId('shift_id');
        });
        Schema::dropIfExists('shift_schedules');
        Schema::dropIfExists('shifts');
    }
};
