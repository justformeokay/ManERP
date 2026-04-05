<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\ChartOfAccount;
use App\Models\Employee;
use App\Models\Payslip;
use App\Models\PayrollPeriod;
use App\Models\Pph21TerRate;
use App\Models\SalaryStructure;
use App\Models\User;
use App\Services\PayrollService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * User-Employee Sync & Attendance Engine Integration Tests
 *
 *  T1: Creating a staff User auto-creates an Employee record (UserObserver)
 *  T2: Creating an admin User does NOT create an Employee
 *  T3: MarkAbsentEmployees command marks missing employees as absent
 *  T4: MarkAbsentEmployees skips weekends
 *  T5: API /api/v1/attendance/sync bulk-imports attendance records
 *  T6: API validation rejects invalid payloads
 *  T7: Absence deduction includes meal + transport (bcmath)
 *  T8: Employee soft-delete does NOT cascade to User
 *  T9: Link to User via EmployeeController
 */
class AttendanceIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private PayrollService $payrollService;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->payrollService = app(PayrollService::class);

        // Create admin (observer skips admin role — no Employee created)
        $this->admin = User::factory()->create([
            'role'   => User::ROLE_ADMIN,
            'status' => User::STATUS_ACTIVE,
        ]);
    }

    // ═══════════════════════════════════════════════════════════════
    // T1: Staff user auto-creates Employee
    // ═══════════════════════════════════════════════════════════════

    public function test_creating_staff_user_creates_employee_record(): void
    {
        $user = User::factory()->create([
            'name'   => 'John Doe',
            'role'   => User::ROLE_STAFF,
            'status' => User::STATUS_ACTIVE,
        ]);

        $employee = Employee::where('user_id', $user->id)->first();

        $this->assertNotNull($employee, 'Employee should be auto-created for staff user');
        $this->assertEquals('John Doe', $employee->name);
        $this->assertEquals('active', $employee->status);
        $this->assertEquals('TK/0', $employee->ptkp_status);
        $this->assertStringStartsWith('EMP-', $employee->nik);
    }

    // ═══════════════════════════════════════════════════════════════
    // T2: Admin user does NOT create Employee
    // ═══════════════════════════════════════════════════════════════

    public function test_admin_user_does_not_create_employee(): void
    {
        $admin = User::factory()->create([
            'role'   => User::ROLE_ADMIN,
            'status' => User::STATUS_ACTIVE,
        ]);

        $this->assertNull(
            Employee::where('user_id', $admin->id)->first(),
            'Admin users should not create Employee records'
        );
    }

    // ═══════════════════════════════════════════════════════════════
    // T3: MarkAbsentEmployees command
    // ═══════════════════════════════════════════════════════════════

    public function test_mark_absent_command_creates_absent_records(): void
    {
        $employee = Employee::factory()->create(['status' => 'active']);

        // Use a known weekday: 2026-03-10 is Tuesday
        $this->artisan('attendance:mark-absent', ['--date' => '2026-03-10'])
            ->assertExitCode(0);

        $this->assertDatabaseHas('attendances', [
            'employee_id' => $employee->id,
            'date'        => '2026-03-10',
            'status'      => 'absent',
            'source'      => 'system',
        ]);
    }

    public function test_mark_absent_skips_employee_with_existing_attendance(): void
    {
        $employee = Employee::factory()->create(['status' => 'active']);

        Attendance::create([
            'employee_id' => $employee->id,
            'date'        => '2026-03-10',
            'clock_in'    => '2026-03-10 08:00:00',
            'status'      => 'present',
        ]);

        $this->artisan('attendance:mark-absent', ['--date' => '2026-03-10'])
            ->assertExitCode(0);

        // Should still be present, not overwritten
        $this->assertEquals(
            'present',
            Attendance::where('employee_id', $employee->id)->whereDate('date', '2026-03-10')->first()->status
        );
    }

    // ═══════════════════════════════════════════════════════════════
    // T4: MarkAbsentEmployees skips weekends
    // ═══════════════════════════════════════════════════════════════

    public function test_mark_absent_skips_weekends(): void
    {
        Employee::factory()->create(['status' => 'active']);

        // 2026-03-14 is Saturday
        $this->artisan('attendance:mark-absent', ['--date' => '2026-03-14'])
            ->assertExitCode(0);

        $this->assertDatabaseMissing('attendances', [
            'date' => '2026-03-14',
        ]);
    }

    // ═══════════════════════════════════════════════════════════════
    // T5: API attendance sync
    // ═══════════════════════════════════════════════════════════════

    public function test_api_attendance_sync_creates_records(): void
    {
        $employee = Employee::factory()->create(['status' => 'active']);

        $token = $this->admin->createToken('test-token')->plainTextToken;

        $response = $this->postJson('/api/v1/attendance/sync', [
            'source'  => 'fingerprint',
            'records' => [
                [
                    'employee_id' => $employee->id,
                    'date'        => '2026-03-10',
                    'clock_in'    => '2026-03-10 08:00:00',
                    'clock_out'   => '2026-03-10 17:00:00',
                    'latitude'    => -6.2088,
                    'longitude'   => 106.8456,
                ],
            ],
        ], ['Authorization' => "Bearer $token"]);

        $response->assertOk()
            ->assertJson(['synced' => 1, 'failed' => 0]);

        $att = Attendance::where('employee_id', $employee->id)->whereDate('date', '2026-03-10')->first();
        $this->assertNotNull($att);
        $this->assertEquals('present', $att->status);
        $this->assertEquals('fingerprint', $att->source);
    }

    public function test_api_attendance_sync_updates_existing_record(): void
    {
        $employee = Employee::factory()->create(['status' => 'active']);

        Attendance::create([
            'employee_id' => $employee->id,
            'date'        => '2026-03-10',
            'clock_in'    => '2026-03-10 08:00:00',
            'status'      => 'present',
            'source'      => 'manual',
        ]);

        $token = $this->admin->createToken('test-token')->plainTextToken;

        $response = $this->postJson('/api/v1/attendance/sync', [
            'source'  => 'fingerprint',
            'records' => [
                [
                    'employee_id' => $employee->id,
                    'date'        => '2026-03-10',
                    'clock_in'    => '2026-03-10 07:55:00',
                    'clock_out'   => '2026-03-10 17:30:00',
                ],
            ],
        ], ['Authorization' => "Bearer $token"]);

        $response->assertOk();

        // Record updated, not duplicated
        $this->assertEquals(1, Attendance::where('employee_id', $employee->id)->whereDate('date', '2026-03-10')->count());
        $this->assertEquals('fingerprint', Attendance::where('employee_id', $employee->id)->whereDate('date', '2026-03-10')->first()->source);
    }

    // ═══════════════════════════════════════════════════════════════
    // T6: API validation
    // ═══════════════════════════════════════════════════════════════

    public function test_api_attendance_sync_rejects_invalid_payload(): void
    {
        $token = $this->admin->createToken('test-token')->plainTextToken;

        $response = $this->postJson('/api/v1/attendance/sync', [
            'records' => [
                [
                    'employee_id' => 999999,
                    'date'        => 'not-a-date',
                ],
            ],
        ], ['Authorization' => "Bearer $token"]);

        $response->assertStatus(422);
    }

    public function test_api_attendance_sync_requires_auth(): void
    {
        $response = $this->postJson('/api/v1/attendance/sync', [
            'records' => [],
        ]);

        $response->assertStatus(401);
    }

    // ═══════════════════════════════════════════════════════════════
    // T7: Absence deduction includes meal + transport (bcmath)
    // ═══════════════════════════════════════════════════════════════

    public function test_attendance_absence_deducts_meal_and_transport(): void
    {
        $this->seedTerRates();
        $this->seedPayrollAccounts();

        $employee = Employee::factory()->withPtkp('TK/0')->create();

        $salary = SalaryStructure::create([
            'employee_id'         => $employee->id,
            'basic_salary'        => 10000000,
            'fixed_allowance'     => 1000000,
            'meal_allowance'      => 500000,
            'transport_allowance' => 500000,
            'overtime_rate'       => 50000,
            'effective_date'      => now()->subYear()->toDateString(),
            'is_active'           => true,
        ]);

        $period = PayrollPeriod::create([
            'month' => 3, 'year' => 2026, 'status' => 'draft',
        ]);

        // 3 absent days
        foreach (['2026-03-05', '2026-03-12', '2026-03-19'] as $date) {
            Attendance::create([
                'employee_id' => $employee->id,
                'date'        => $date,
                'status'      => 'absent',
            ]);
        }

        $payslip = $this->payrollService->generatePayslip($period, $employee, $salary);

        // March 2026 has 22 working days
        $wd = (string) $this->payrollService->getWorkingDaysInMonth(3, 2026);

        // bcmath: dailyBasic + dailyMeal + dailyTransport
        $dailyBasic     = bcdiv('10000000', $wd, 2);
        $dailyMeal      = bcdiv('500000', $wd, 2);
        $dailyTransport = bcdiv('500000', $wd, 2);
        $dailyTotal     = bcadd($dailyBasic, bcadd($dailyMeal, $dailyTransport, 2), 2);
        $expected        = (float) bcmul('3', $dailyTotal, 2);

        $this->assertEquals($expected, (float) $payslip->absence_deduction);

        // Verify it's bigger than just basic salary deduction
        $basicOnlyDeduction = (float) bcmul('3', bcdiv('10000000', $wd, 2), 2);
        $this->assertGreaterThan($basicOnlyDeduction, (float) $payslip->absence_deduction);
    }

    // ═══════════════════════════════════════════════════════════════
    // T8: Employee soft-delete does NOT cascade to User
    // ═══════════════════════════════════════════════════════════════

    public function test_employee_soft_delete_does_not_delete_user(): void
    {
        $user = User::factory()->create([
            'role'   => User::ROLE_STAFF,
            'status' => User::STATUS_ACTIVE,
        ]);

        $employee = Employee::where('user_id', $user->id)->first();
        $this->assertNotNull($employee);

        $employee->delete(); // soft delete

        // Employee soft-deleted
        $this->assertSoftDeleted('employees', ['id' => $employee->id]);

        // User still exists and is untouched
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    // ═══════════════════════════════════════════════════════════════
    // T9: Link employee to user via controller
    // ═══════════════════════════════════════════════════════════════

    public function test_link_employee_to_user_via_update(): void
    {
        $this->actingAs($this->admin);

        $user = User::factory()->create([
            'role'   => User::ROLE_STAFF,
            'status' => User::STATUS_ACTIVE,
        ]);

        // The observer already created an employee for this user.
        // Create a separate unlinked employee to test linking.
        $employee = Employee::factory()->create(['user_id' => null]);

        $response = $this->put(route('hr.employees.update', $employee), [
            'nik'          => $employee->nik,
            'name'         => $employee->name,
            'join_date'    => $employee->join_date->format('Y-m-d'),
            'ptkp_status'  => $employee->ptkp_status,
            'status'       => $employee->status,
            'user_id'      => $user->id,
        ]);

        // The observer-created employee already uses this user_id.
        // So the unique validation should prevent re-linking.
        // Instead, test with a fresh user that has no employee.
        $freshUser = User::factory()->create([
            'role'   => User::ROLE_ADMIN, // Admin — no auto-employee
            'status' => User::STATUS_ACTIVE,
        ]);

        $response = $this->put(route('hr.employees.update', $employee), [
            'nik'          => $employee->nik,
            'name'         => $employee->name,
            'join_date'    => $employee->join_date->format('Y-m-d'),
            'ptkp_status'  => $employee->ptkp_status,
            'status'       => $employee->status,
            'user_id'      => $freshUser->id,
        ]);

        $response->assertRedirect();

        $employee->refresh();
        $this->assertEquals($freshUser->id, $employee->user_id);
    }

    // ═══════════════════════════════════════════════════════════════
    // T10: GPS data stored via API sync
    // ═══════════════════════════════════════════════════════════════

    public function test_gps_coordinates_stored_via_api(): void
    {
        $employee = Employee::factory()->create(['status' => 'active']);
        $token = $this->admin->createToken('test-token')->plainTextToken;

        $this->postJson('/api/v1/attendance/sync', [
            'source'  => 'mobile',
            'records' => [[
                'employee_id' => $employee->id,
                'date'        => '2026-03-10',
                'clock_in'    => '2026-03-10 08:00:00',
                'latitude'    => -6.2088000,
                'longitude'   => 106.8456000,
            ]],
        ], ['Authorization' => "Bearer $token"])->assertOk();

        $att = Attendance::where('employee_id', $employee->id)->whereDate('date', '2026-03-10')->first();

        $this->assertNotNull($att);
        $this->assertEquals(-6.2088000, (float) $att->latitude);
        $this->assertEquals(106.8456000, (float) $att->longitude);
        $this->assertEquals('mobile', $att->source);
    }

    // ═══════════════════════════════════════════════════════════════
    // Helpers
    // ═══════════════════════════════════════════════════════════════

    private function seedTerRates(): void
    {
        Pph21TerRate::query()->delete();

        $rates = [
            ['category' => 'A', 'min_salary' => 0, 'max_salary' => 5400000, 'rate' => 0.0000],
            ['category' => 'A', 'min_salary' => 5400000, 'max_salary' => 5650000, 'rate' => 0.0025],
            ['category' => 'A', 'min_salary' => 5650000, 'max_salary' => 5950000, 'rate' => 0.0050],
            ['category' => 'A', 'min_salary' => 5950000, 'max_salary' => 6300000, 'rate' => 0.0075],
            ['category' => 'A', 'min_salary' => 6300000, 'max_salary' => 6750000, 'rate' => 0.0100],
            ['category' => 'A', 'min_salary' => 6750000, 'max_salary' => 7500000, 'rate' => 0.0125],
            ['category' => 'A', 'min_salary' => 7500000, 'max_salary' => 8550000, 'rate' => 0.0150],
            ['category' => 'A', 'min_salary' => 8550000, 'max_salary' => 9650000, 'rate' => 0.0175],
            ['category' => 'A', 'min_salary' => 9650000, 'max_salary' => 10050000, 'rate' => 0.0200],
            ['category' => 'A', 'min_salary' => 10050000, 'max_salary' => 10350000, 'rate' => 0.0225],
            ['category' => 'A', 'min_salary' => 10350000, 'max_salary' => 10700000, 'rate' => 0.0250],
            ['category' => 'A', 'min_salary' => 10700000, 'max_salary' => 11050000, 'rate' => 0.0300],
            ['category' => 'A', 'min_salary' => 11050000, 'max_salary' => 11600000, 'rate' => 0.0350],
            ['category' => 'A', 'min_salary' => 11600000, 'max_salary' => 12500000, 'rate' => 0.0400],
            ['category' => 'A', 'min_salary' => 12500000, 'max_salary' => 13750000, 'rate' => 0.0500],
            ['category' => 'A', 'min_salary' => 13750000, 'max_salary' => 15100000, 'rate' => 0.0550],
            ['category' => 'A', 'min_salary' => 15100000, 'max_salary' => 16950000, 'rate' => 0.0600],
        ];

        foreach ($rates as $rate) {
            Pph21TerRate::create($rate);
        }
    }

    private function seedPayrollAccounts(): void
    {
        $accounts = [
            ['code' => '1100', 'name' => 'Cash & Bank', 'type' => 'asset'],
            ['code' => '3200', 'name' => 'Retained Earnings', 'type' => 'equity'],
            ['code' => '5100', 'name' => 'Beban Gaji & Upah', 'type' => 'expense'],
            ['code' => '5110', 'name' => 'Beban Tunjangan', 'type' => 'expense'],
            ['code' => '5120', 'name' => 'Beban BPJS Perusahaan', 'type' => 'expense'],
            ['code' => '2110', 'name' => 'Utang Gaji', 'type' => 'liability'],
            ['code' => '2120', 'name' => 'Utang PPh 21', 'type' => 'liability'],
            ['code' => '2130', 'name' => 'Utang BPJS', 'type' => 'liability'],
        ];

        foreach ($accounts as $acc) {
            ChartOfAccount::firstOrCreate(
                ['code' => $acc['code']],
                ['name' => $acc['name'], 'type' => $acc['type'], 'is_active' => true]
            );
        }
    }
}
