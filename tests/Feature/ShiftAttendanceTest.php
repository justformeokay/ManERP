<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\ChartOfAccount;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Models\Pph21TerRate;
use App\Models\SalaryStructure;
use App\Models\Setting;
use App\Models\Shift;
use App\Models\ShiftSchedule;
use App\Models\User;
use App\Services\AttendanceService;
use App\Services\PayrollService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Industrial Shift Management & Attendance Logic Tests
 *
 *  T1: Late check-in calculation (clock_in > start_time + grace_period)
 *  T2: On-time within grace period is NOT late
 *  T3: Night shift bonus calculation in payroll
 *  T4: Shift rotation integrity (ShiftSchedule > default shift)
 *  T5: Cross-day shift overtime calculation (22:00–06:00)
 *  T6: Late deduction in payroll (bcmath)
 *  T7: AttendanceSync API uses shift-aware processing
 *  T8: Shift CRUD in settings (admin)
 *  T9: Employee shift assignment validation
 */
class ShiftAttendanceTest extends TestCase
{
    use RefreshDatabase;

    private AttendanceService $attendanceService;
    private PayrollService $payrollService;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->attendanceService = app(AttendanceService::class);
        $this->payrollService = app(PayrollService::class);

        $this->admin = User::factory()->create([
            'role'   => User::ROLE_ADMIN,
            'status' => User::STATUS_ACTIVE,
        ]);
    }

    // ═══════════════════════════════════════════════════════════════
    // T1: Late check-in calculation
    // ═══════════════════════════════════════════════════════════════

    public function test_late_check_in_calculation(): void
    {
        $shift = Shift::factory()->create([
            'name'         => 'Shift Pagi',
            'start_time'   => '08:00',
            'end_time'     => '17:00',
            'grace_period' => 15,
        ]);

        $employee = Employee::factory()->create(['shift_id' => $shift->id]);

        // Clock in at 08:20 → 20 minutes late (past 08:15 grace)
        $attendance = $this->attendanceService->processCheckIn(
            $employee,
            '2026-04-07',
            '2026-04-07 08:20:00'
        );

        $this->assertEquals('late', $attendance->status);
        $this->assertEquals(20, $attendance->late_minutes);
        $this->assertEquals($shift->id, $attendance->shift_id);
    }

    // ═══════════════════════════════════════════════════════════════
    // T2: On-time within grace period is NOT late
    // ═══════════════════════════════════════════════════════════════

    public function test_on_time_within_grace_period(): void
    {
        $shift = Shift::factory()->create([
            'start_time'   => '08:00',
            'end_time'     => '17:00',
            'grace_period' => 15,
        ]);

        $employee = Employee::factory()->create(['shift_id' => $shift->id]);

        // Clock in at 08:10 → within grace (08:15), should be 'present'
        $attendance = $this->attendanceService->processCheckIn(
            $employee,
            '2026-04-07',
            '2026-04-07 08:10:00'
        );

        $this->assertEquals('present', $attendance->status);
        $this->assertEquals(0, $attendance->late_minutes);
    }

    // ═══════════════════════════════════════════════════════════════
    // T3: Night shift bonus calculation in payroll
    // ═══════════════════════════════════════════════════════════════

    public function test_night_shift_bonus_calculation(): void
    {
        $this->seedMinimalPayrollInfra();

        $nightShift = Shift::factory()->night()->create([
            'night_shift_bonus' => 50000,
        ]);

        $employee = Employee::factory()->create(['shift_id' => $nightShift->id]);

        SalaryStructure::create([
            'employee_id'        => $employee->id,
            'basic_salary'       => 5000000,
            'fixed_allowance'    => 500000,
            'meal_allowance'     => 300000,
            'transport_allowance' => 200000,
            'overtime_rate'      => 30000,
            'effective_date'     => '2026-01-01',
        ]);

        $period = PayrollPeriod::create([
            'period_label' => 'April 2026',
            'month'        => 4,
            'year'         => 2026,
            'status'       => 'draft',
        ]);

        // Create 5 night shift attendance records
        for ($i = 1; $i <= 5; $i++) {
            Attendance::create([
                'employee_id'    => $employee->id,
                'date'           => "2026-04-" . str_pad($i, 2, '0', STR_PAD_LEFT),
                'clock_in'       => "2026-04-" . str_pad($i, 2, '0', STR_PAD_LEFT) . " 22:00:00",
                'clock_out'      => "2026-04-" . str_pad($i + 1, 2, '0', STR_PAD_LEFT) . " 06:00:00",
                'status'         => 'present',
                'shift_id'       => $nightShift->id,
                'late_minutes'   => 0,
                'overtime_hours' => 0,
                'source'         => 'test',
            ]);
        }

        $salary = $employee->salaryStructures()->latest('effective_date')->first();
        $payslip = $this->payrollService->generatePayslip($period, $employee, $salary);

        // 5 days × 50,000 = 250,000
        $this->assertEquals(250000, (float) $payslip->night_shift_bonus);
        $this->assertGreaterThan(0, (float) $payslip->gross_salary);

        // Verify payslip item exists
        $items = $payslip->items()->where('label', 'Premi Shift Malam')->get();
        $this->assertCount(1, $items);
        $this->assertEquals(250000, (float) $items->first()->amount);
    }

    // ═══════════════════════════════════════════════════════════════
    // T4: Shift rotation integrity (ShiftSchedule overrides default)
    // ═══════════════════════════════════════════════════════════════

    public function test_shift_rotation_integrity(): void
    {
        $shiftPagi  = Shift::factory()->create(['name' => 'Shift Pagi', 'start_time' => '08:00', 'end_time' => '17:00']);
        $shiftMalam = Shift::factory()->night()->create(['name' => 'Shift Malam']);

        // Default shift = Pagi
        $employee = Employee::factory()->create(['shift_id' => $shiftPagi->id]);

        // Schedule: week 2 = Malam
        ShiftSchedule::create([
            'employee_id' => $employee->id,
            'shift_id'    => $shiftMalam->id,
            'start_date'  => '2026-04-07',
            'end_date'    => '2026-04-13',
        ]);

        // Week 1 (before schedule) → falls back to default (Pagi)
        $shiftForWeek1 = $employee->getShiftForDate('2026-04-01');
        $this->assertEquals($shiftPagi->id, $shiftForWeek1->id);

        // Week 2 → rotation to Malam
        $shiftForWeek2 = $employee->getShiftForDate('2026-04-10');
        $this->assertEquals($shiftMalam->id, $shiftForWeek2->id);

        // Week 3 (after schedule) → back to default (Pagi)
        $shiftForWeek3 = $employee->getShiftForDate('2026-04-15');
        $this->assertEquals($shiftPagi->id, $shiftForWeek3->id);
    }

    // ═══════════════════════════════════════════════════════════════
    // T5: Cross-day shift overtime calculation
    // ═══════════════════════════════════════════════════════════════

    public function test_cross_day_shift_overtime(): void
    {
        $nightShift = Shift::factory()->night()->create([
            'start_time' => '22:00',
            'end_time'   => '06:00',
        ]);

        $employee = Employee::factory()->create(['shift_id' => $nightShift->id]);

        // Clock in at 22:00, clock out at 07:30 → 1.5h overtime
        $attendance = $this->attendanceService->processCheckIn(
            $employee,
            '2026-04-07',
            '2026-04-07 22:00:00',
            ['clock_out' => '2026-04-08 07:30:00']
        );

        $this->assertEquals('present', $attendance->status);
        $this->assertEquals(1.5, (float) $attendance->overtime_hours);
    }

    // ═══════════════════════════════════════════════════════════════
    // T6: Late deduction in payroll (bcmath)
    // ═══════════════════════════════════════════════════════════════

    public function test_late_deduction_in_payroll(): void
    {
        $this->seedMinimalPayrollInfra();

        Setting::set('late_deduction_per_minute', '5000');

        $shift = Shift::factory()->create([
            'start_time'   => '08:00',
            'end_time'     => '17:00',
            'grace_period' => 15,
        ]);

        $employee = Employee::factory()->create(['shift_id' => $shift->id]);

        SalaryStructure::create([
            'employee_id'        => $employee->id,
            'basic_salary'       => 5000000,
            'fixed_allowance'    => 500000,
            'meal_allowance'     => 300000,
            'transport_allowance' => 200000,
            'overtime_rate'      => 30000,
            'effective_date'     => '2026-01-01',
        ]);

        $period = PayrollPeriod::create([
            'period_label' => 'April 2026',
            'month'        => 4,
            'year'         => 2026,
            'status'       => 'draft',
        ]);

        // 3 late days: 20 min + 30 min + 10 min = 60 minutes total
        foreach ([20, 30, 10] as $i => $minutes) {
            $day = str_pad($i + 7, 2, '0', STR_PAD_LEFT); // 7th, 8th, 9th
            $clockIn = sprintf('08:%02d:00', $minutes);
            Attendance::create([
                'employee_id'    => $employee->id,
                'date'           => "2026-04-{$day}",
                'clock_in'       => "2026-04-{$day} {$clockIn}",
                'clock_out'      => "2026-04-{$day} 17:00:00",
                'status'         => 'late',
                'late_minutes'   => $minutes,
                'shift_id'       => $shift->id,
                'overtime_hours' => 0,
                'source'         => 'test',
            ]);
        }

        $salary = $employee->salaryStructures()->latest('effective_date')->first();
        $payslip = $this->payrollService->generatePayslip($period, $employee, $salary);

        // 60 minutes × 5,000 = 300,000
        $this->assertEquals(300000, (float) $payslip->late_deduction);

        // Verify deduction item
        $items = $payslip->items()->where('label', 'Potongan Keterlambatan')->get();
        $this->assertCount(1, $items);
        $this->assertEquals(300000, (float) $items->first()->amount);
    }

    // ═══════════════════════════════════════════════════════════════
    // T7: Attendance sync API uses shift-aware processing
    // ═══════════════════════════════════════════════════════════════

    public function test_attendance_sync_detects_lateness(): void
    {
        $shift = Shift::factory()->create([
            'start_time'   => '08:00',
            'end_time'     => '17:00',
            'grace_period' => 15,
        ]);

        $employee = Employee::factory()->create(['shift_id' => $shift->id]);

        $token = $this->admin->createToken('test')->plainTextToken;

        $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->postJson('/api/v1/attendance/sync', [
                'records' => [
                    [
                        'employee_id' => $employee->id,
                        'date'        => '2026-04-07',
                        'clock_in'    => '2026-04-07 08:25:00',
                        'clock_out'   => '2026-04-07 17:00:00',
                    ],
                ],
                'source' => 'fingerprint',
            ]);

        $response->assertOk();
        $response->assertJson(['synced' => 1, 'failed' => 0]);

        $attendance = Attendance::where('employee_id', $employee->id)
            ->whereDate('date', '2026-04-07')
            ->first();

        $this->assertNotNull($attendance);
        $this->assertEquals('late', $attendance->status);
        $this->assertEquals(25, $attendance->late_minutes);
        $this->assertEquals($shift->id, $attendance->shift_id);
    }

    // ═══════════════════════════════════════════════════════════════
    // T8: Shift CRUD in settings
    // ═══════════════════════════════════════════════════════════════

    public function test_admin_can_create_shift(): void
    {
        $response = $this->actingAs($this->admin)->post(route('settings.shifts.store'), [
            'name'              => 'Shift Pagi',
            'start_time'        => '08:00',
            'end_time'          => '17:00',
            'grace_period'      => 15,
            'is_night_shift'    => 0,
            'night_shift_bonus' => 0,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('shifts', ['name' => 'Shift Pagi', 'start_time' => '08:00']);
    }

    public function test_admin_can_delete_shift(): void
    {
        $shift = Shift::factory()->create();

        $response = $this->actingAs($this->admin)->delete(route('settings.shifts.destroy', $shift));

        $response->assertRedirect();
        $this->assertDatabaseMissing('shifts', ['id' => $shift->id]);
    }

    // ═══════════════════════════════════════════════════════════════
    // T9: Employee shift assignment validation
    // ═══════════════════════════════════════════════════════════════

    public function test_employee_can_be_assigned_shift(): void
    {
        $shift = Shift::factory()->create();
        $employee = Employee::factory()->create();

        $response = $this->actingAs($this->admin)->put(
            route('hr.employees.update', $employee),
            array_merge($employee->toArray(), [
                'shift_id'    => $shift->id,
                'ptkp_status' => $employee->ptkp_status,
                'status'      => $employee->status,
            ])
        );

        $response->assertRedirect();
        $this->assertEquals($shift->id, $employee->fresh()->shift_id);
    }

    public function test_employee_rejects_invalid_shift_id(): void
    {
        $employee = Employee::factory()->create();

        $response = $this->actingAs($this->admin)->put(
            route('hr.employees.update', $employee),
            array_merge($employee->toArray(), [
                'shift_id'    => 99999,
                'ptkp_status' => $employee->ptkp_status,
                'status'      => $employee->status,
            ])
        );

        $response->assertSessionHasErrors('shift_id');
    }

    // ═══════════════════════════════════════════════════════════════
    // T10: No shift assigned — default present behavior
    // ═══════════════════════════════════════════════════════════════

    public function test_no_shift_defaults_to_present(): void
    {
        $employee = Employee::factory()->create(['shift_id' => null]);

        $attendance = $this->attendanceService->processCheckIn(
            $employee,
            '2026-04-07',
            '2026-04-07 09:00:00'
        );

        $this->assertEquals('present', $attendance->status);
        $this->assertEquals(0, $attendance->late_minutes);
        $this->assertNull($attendance->shift_id);
    }

    // ═══════════════════════════════════════════════════════════════
    // T11: Cross-day night shift lateness detection
    // ═══════════════════════════════════════════════════════════════

    public function test_cross_day_night_shift_late_detection(): void
    {
        $nightShift = Shift::factory()->night()->create([
            'start_time'   => '22:00',
            'end_time'     => '06:00',
            'grace_period' => 15,
        ]);

        $employee = Employee::factory()->create(['shift_id' => $nightShift->id]);

        // Clock in at 22:25 → 25 minutes late
        $attendance = $this->attendanceService->processCheckIn(
            $employee,
            '2026-04-07',
            '2026-04-07 22:25:00'
        );

        $this->assertEquals('late', $attendance->status);
        $this->assertEquals(25, $attendance->late_minutes);
    }

    // ═══════════════════════════════════════════════════════════════
    // T12: Mark absent command considers shifts
    // ═══════════════════════════════════════════════════════════════

    public function test_mark_absent_includes_shift_workers_on_weekends(): void
    {
        $shift = Shift::factory()->create();

        // Employee with shift → should be marked absent even on Saturday
        $shiftEmployee = Employee::factory()->create(['shift_id' => $shift->id]);
        // Employee without shift → should NOT be marked on Saturday
        $noShiftEmployee = Employee::factory()->create(['shift_id' => null]);

        // Saturday: 2026-04-11
        $this->artisan('attendance:mark-absent', ['--date' => '2026-04-11'])
            ->assertExitCode(0);

        $this->assertDatabaseHas('attendances', [
            'employee_id' => $shiftEmployee->id,
            'date'        => '2026-04-11',
            'status'      => 'absent',
        ]);

        $this->assertDatabaseMissing('attendances', [
            'employee_id' => $noShiftEmployee->id,
            'date'        => '2026-04-11',
        ]);
    }

    // ═══════════════════════════════════════════════════════════════
    // Helpers
    // ═══════════════════════════════════════════════════════════════

    private function seedMinimalPayrollInfra(): void
    {
        // PPh21 TER rate
        Pph21TerRate::firstOrCreate(
            ['category' => 'A', 'min_salary' => 0],
            ['max_salary' => 999999999, 'rate' => 0]
        );

        // Minimal COA accounts needed by PayrollService
        $coaCodes = [
            '5100' => ['name' => 'Beban Gaji',           'type' => 'expense'],
            '5110' => ['name' => 'Beban Tunjangan',      'type' => 'expense'],
            '5120' => ['name' => 'Beban BPJS',           'type' => 'expense'],
            '2110' => ['name' => 'Utang Gaji',           'type' => 'liability'],
            '2120' => ['name' => 'Utang PPh21',          'type' => 'liability'],
            '2130' => ['name' => 'Utang BPJS',           'type' => 'liability'],
        ];

        foreach ($coaCodes as $code => $data) {
            ChartOfAccount::firstOrCreate(
                ['code' => $code],
                [
                    'name'       => $data['name'],
                    'type'       => $data['type'],
                    'parent_id'  => null,
                    'is_active'  => true,
                ]
            );
        }
    }
}
