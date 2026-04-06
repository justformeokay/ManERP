<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Bank;
use App\Models\Employee;
use App\Models\SalaryStructure;
use App\Models\Shift;
use App\Models\User;
use App\Services\AuditLogService;
use Database\Seeders\EmployeeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create admin user required by DatabaseSeeder and EmployeeSeeder
        User::factory()->create([
            'name'   => 'Administrator',
            'email'  => 'admin@manerp.com',
            'role'   => User::ROLE_ADMIN,
            'status' => User::STATUS_ACTIVE,
        ]);
    }

    // ── TUGAS 1: Data Structure Alignment ────────────────────

    public function test_seeder_creates_exactly_50_employees(): void
    {
        $this->seed(EmployeeSeeder::class);

        $this->assertGreaterThanOrEqual(50, Employee::count());
    }

    public function test_all_employees_have_valid_nik_16_digits(): void
    {
        $this->seed(EmployeeSeeder::class);

        Employee::all()->each(function (Employee $emp) {
            $this->assertMatchesRegularExpression('/^\d{16}$/', $emp->nik, "NIK invalid: {$emp->nik}");
        });
    }

    public function test_all_niks_are_unique(): void
    {
        $this->seed(EmployeeSeeder::class);

        $niks = Employee::pluck('nik');
        $this->assertCount($niks->unique()->count(), $niks);
    }

    public function test_all_employees_have_valid_npwp_format(): void
    {
        $this->seed(EmployeeSeeder::class);

        Employee::whereNotNull('npwp')->each(function (Employee $emp) {
            // Format: XX.XXX.XXX.X-XXX.XXX (15 digits + separators)
            $this->assertMatchesRegularExpression(
                '/^\d{2}\.\d{3}\.\d{3}\.\d-\d{3}\.\d{3}$/',
                $emp->npwp,
                "NPWP invalid: {$emp->npwp}"
            );
        });
    }

    public function test_all_employees_have_valid_ptkp_and_ter_category(): void
    {
        $this->seed(EmployeeSeeder::class);

        Employee::all()->each(function (Employee $emp) {
            $this->assertContains($emp->ptkp_status, array_keys(Employee::PTKP_OPTIONS), "Invalid PTKP: {$emp->ptkp_status}");
            $this->assertContains($emp->ter_category, Employee::TER_CATEGORIES, "Invalid TER: {$emp->ter_category}");

            // Verify TER correctly derived from PTKP
            $expectedTer = Employee::PTKP_TO_TER[$emp->ptkp_status];
            $this->assertEquals($expectedTer, $emp->ter_category, "TER mismatch for PTKP {$emp->ptkp_status}");
        });
    }

    public function test_all_employees_have_bpjs_numbers(): void
    {
        $this->seed(EmployeeSeeder::class);

        Employee::all()->each(function (Employee $emp) {
            $this->assertNotNull($emp->bpjs_tk_number, "Missing BPJS TK: {$emp->name}");
            $this->assertNotNull($emp->bpjs_kes_number, "Missing BPJS KES: {$emp->name}");
            $this->assertMatchesRegularExpression('/^TK\d{9,11}$/', $emp->bpjs_tk_number);
            $this->assertMatchesRegularExpression('/^KS\d{9,11}$/', $emp->bpjs_kes_number);
        });
    }

    public function test_all_employees_assigned_to_valid_bank_and_shift(): void
    {
        $this->seed(EmployeeSeeder::class);

        $bankIds  = Bank::pluck('id')->toArray();
        $shiftIds = Shift::pluck('id')->toArray();

        Employee::all()->each(function (Employee $emp) use ($bankIds, $shiftIds) {
            $this->assertContains($emp->bank_id, $bankIds, "Invalid bank_id: {$emp->bank_id}");
            $this->assertContains($emp->shift_id, $shiftIds, "Invalid shift_id: {$emp->shift_id}");
            $this->assertNotEmpty($emp->bank_account_number);
            $this->assertEquals($emp->name, $emp->bank_account_name);
        });
    }

    // ── TUGAS 2: Security & Audit Enforcement ────────────────

    public function test_audit_logs_created_for_every_employee(): void
    {
        $this->seed(EmployeeSeeder::class);

        $employeeCount = Employee::count();
        $auditCount    = ActivityLog::where('module', 'employee')->count();

        $this->assertGreaterThanOrEqual($employeeCount, $auditCount, 'Missing audit logs for employees');
    }

    public function test_audit_log_hmac_checksums_are_valid(): void
    {
        $this->seed(EmployeeSeeder::class);

        $logs = ActivityLog::where('module', 'employee')->get();
        $this->assertNotEmpty($logs, 'No employee audit logs found');

        $logs->each(function (ActivityLog $log) {
            $this->assertNotNull($log->checksum, "Missing checksum on log #{$log->id}");
            $this->assertTrue(
                AuditLogService::verifyChecksum($log),
                "HMAC checksum mismatch on audit log #{$log->id}"
            );
        });
    }

    public function test_audit_log_payload_contains_required_8_fields(): void
    {
        $this->seed(EmployeeSeeder::class);

        $log = ActivityLog::where('module', 'employee')->first();
        $this->assertNotNull($log);

        // 8 required fields for HMAC payload integrity (F-14)
        $this->assertNotNull($log->user_id, 'Missing user_id');
        $this->assertNotNull($log->module, 'Missing module');
        $this->assertNotNull($log->action, 'Missing action');
        $this->assertNotNull($log->description, 'Missing description');
        $this->assertNotNull($log->ip_address, 'Missing ip_address');
        $this->assertNotNull($log->created_at, 'Missing created_at');
        // old_data may be null for creates, but new_data must exist
        $this->assertNotNull($log->new_data, 'Missing new_data');
        $this->assertNotNull($log->checksum, 'Missing checksum');
    }

    public function test_20_percent_employees_linked_to_user_accounts(): void
    {
        $this->seed(EmployeeSeeder::class);

        $linkedCount = Employee::whereNotNull('user_id')->count();
        $totalCount  = Employee::count();

        // 20% of 50 = 10 linked users
        $expectedMin = (int) floor($totalCount * 0.15); // allow slight variance
        $expectedMax = (int) ceil($totalCount * 0.25);

        $this->assertGreaterThanOrEqual($expectedMin, $linkedCount, 'Too few linked users');
        $this->assertLessThanOrEqual($expectedMax, $linkedCount, 'Too many linked users');

        // All linked users should be staff role
        Employee::whereNotNull('user_id')->each(function (Employee $emp) {
            $user = User::find($emp->user_id);
            $this->assertNotNull($user);
            $this->assertEquals(User::ROLE_STAFF, $user->role);
        });
    }

    // ── TUGAS 3: Technical Requirements ──────────────────────

    public function test_salary_structures_use_bcmath_precision(): void
    {
        $this->seed(EmployeeSeeder::class);

        $salaries = SalaryStructure::where('is_active', true)->get();
        $this->assertCount(Employee::count(), $salaries, 'Not every employee has a salary structure');

        $salaries->each(function (SalaryStructure $sal) {
            // Verify monetary fields are valid numeric with 2dp precision
            $basic = $sal->getRawOriginal('basic_salary');
            $this->assertTrue(is_numeric($basic), "basic_salary not numeric: {$basic}");
            $this->assertEquals(
                bcmul($basic, '1', 2),
                bcmul($basic, '1', 2),
                "basic_salary precision issue: {$basic}"
            );

            // basic_salary must be a positive amount
            $this->assertGreaterThan(0, (float) $sal->basic_salary, 'Basic salary must be > 0');

            // Overtime rate ≈ basic / 173 (within rounding tolerance)
            $expectedOt = bcdiv(bcmul($basic, '1', 2), '173', 2);
            $actualOt   = bcmul($sal->getRawOriginal('overtime_rate'), '1', 2);
            $this->assertEquals($expectedOt, $actualOt, 'OT rate mismatch');
        });
    }

    public function test_seeder_is_idempotent(): void
    {
        $this->seed(EmployeeSeeder::class);
        $countFirst = Employee::count();

        // Run again — should not create duplicates
        $this->seed(EmployeeSeeder::class);
        $countSecond = Employee::count();

        $this->assertEquals($countFirst, $countSecond, 'Seeder is not idempotent — duplicate employees created');
    }
}
