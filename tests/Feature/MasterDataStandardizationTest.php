<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\Rules\Password;
use Tests\TestCase;

class MasterDataStandardizationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();
        Password::defaults(fn () => Password::min(8));

        $this->admin = User::factory()->create([
            'role'   => User::ROLE_ADMIN,
            'status' => User::STATUS_ACTIVE,
        ]);

        $this->staff = User::factory()->create([
            'role'        => User::ROLE_STAFF,
            'status'      => User::STATUS_ACTIVE,
            'permissions' => ['hr.view'],
        ]);
    }

    // ═══════════════════════════════════════════════════════════════
    // TUGAS 1: SEEDERS
    // ═══════════════════════════════════════════════════════════════

    public function test_department_seeder_creates_10_standard_departments(): void
    {
        $this->seed(\Database\Seeders\DepartmentSeeder::class);

        $this->assertDatabaseCount('departments', 10);
        $this->assertDatabaseHas('departments', ['code' => 'PROD', 'name' => 'Produksi', 'is_active' => true]);
        $this->assertDatabaseHas('departments', ['code' => 'PPIC', 'name' => 'PPIC', 'is_active' => true]);
        $this->assertDatabaseHas('departments', ['code' => 'QUAL', 'name' => 'Quality Control', 'is_active' => true]);
        $this->assertDatabaseHas('departments', ['code' => 'MAIN', 'name' => 'Maintenance', 'is_active' => true]);
        $this->assertDatabaseHas('departments', ['code' => 'WHSE', 'name' => 'Gudang', 'is_active' => true]);
        $this->assertDatabaseHas('departments', ['code' => 'HRGA', 'name' => 'HR & GA', 'is_active' => true]);
        $this->assertDatabaseHas('departments', ['code' => 'FINA', 'name' => 'Finance', 'is_active' => true]);
        $this->assertDatabaseHas('departments', ['code' => 'PURC', 'name' => 'Purchasing', 'is_active' => true]);
        $this->assertDatabaseHas('departments', ['code' => 'SALE', 'name' => 'Sales', 'is_active' => true]);
        $this->assertDatabaseHas('departments', ['code' => 'ITSY', 'name' => 'IT System', 'is_active' => true]);
    }

    public function test_position_seeder_creates_6_standard_positions(): void
    {
        $this->seed(\Database\Seeders\PositionSeeder::class);

        $this->assertDatabaseCount('positions', 6);
        $this->assertDatabaseHas('positions', ['code' => 'DIR', 'name' => 'Direktur', 'is_active' => true]);
        $this->assertDatabaseHas('positions', ['code' => 'MGR', 'name' => 'Manager', 'is_active' => true]);
        $this->assertDatabaseHas('positions', ['code' => 'SPV', 'name' => 'Supervisor', 'is_active' => true]);
        $this->assertDatabaseHas('positions', ['code' => 'STF', 'name' => 'Staff', 'is_active' => true]);
        $this->assertDatabaseHas('positions', ['code' => 'OPR', 'name' => 'Operator', 'is_active' => true]);
        $this->assertDatabaseHas('positions', ['code' => 'TECH', 'name' => 'Technician', 'is_active' => true]);
    }

    public function test_department_seeder_is_idempotent(): void
    {
        $this->seed(\Database\Seeders\DepartmentSeeder::class);
        $this->seed(\Database\Seeders\DepartmentSeeder::class);

        $this->assertDatabaseCount('departments', 10);
    }

    public function test_position_seeder_is_idempotent(): void
    {
        $this->seed(\Database\Seeders\PositionSeeder::class);
        $this->seed(\Database\Seeders\PositionSeeder::class);

        $this->assertDatabaseCount('positions', 6);
    }

    public function test_department_codes_are_3_or_4_uppercase_chars(): void
    {
        $this->seed(\Database\Seeders\DepartmentSeeder::class);

        $departments = Department::all();
        foreach ($departments as $dept) {
            $this->assertMatchesRegularExpression('/^[A-Z]{3,4}$/', $dept->code, "Department code '{$dept->code}' is not 3-4 uppercase letters");
        }
    }

    public function test_position_codes_are_3_or_4_uppercase_chars(): void
    {
        $this->seed(\Database\Seeders\PositionSeeder::class);

        $positions = Position::all();
        foreach ($positions as $pos) {
            $this->assertMatchesRegularExpression('/^[A-Z]{3,4}$/', $pos->code, "Position code '{$pos->code}' is not 3-4 uppercase letters");
        }
    }

    // ═══════════════════════════════════════════════════════════════
    // TUGAS 2: CODE READ-ONLY ON EDIT
    // ═══════════════════════════════════════════════════════════════

    public function test_department_code_not_changed_on_update(): void
    {
        $dept = Department::create(['name' => 'Test Dept', 'code' => 'TST', 'is_active' => true]);

        $this->actingAs($this->admin)->put(route('settings.departments.update', $dept), [
            'name'      => 'Updated Name',
            'code'      => 'XXX', // should be ignored
            'is_active' => true,
        ]);

        $dept->refresh();
        $this->assertEquals('TST', $dept->code); // code remains unchanged
        $this->assertEquals('Updated Name', $dept->name);
    }

    public function test_position_code_not_changed_on_update(): void
    {
        $pos = Position::create(['name' => 'Test Pos', 'code' => 'TSP', 'is_active' => true]);

        $this->actingAs($this->admin)->put(route('settings.positions.update', $pos), [
            'name'      => 'Updated Pos',
            'code'      => 'YYY', // should be ignored
            'is_active' => true,
        ]);

        $pos->refresh();
        $this->assertEquals('TSP', $pos->code); // code remains unchanged
        $this->assertEquals('Updated Pos', $pos->name);
    }

    public function test_department_code_must_be_uppercase_alphanumeric(): void
    {
        $response = $this->actingAs($this->admin)->post(route('settings.departments.store'), [
            'name' => 'Test',
            'code' => 'ab-c', // lowercase + special char
        ]);

        $response->assertSessionHasErrors('code');
    }

    public function test_position_code_must_be_uppercase_alphanumeric(): void
    {
        $response = $this->actingAs($this->admin)->post(route('settings.positions.store'), [
            'name' => 'Test',
            'code' => 'x_1', // lowercase + underscore
        ]);

        $response->assertSessionHasErrors('code');
    }

    // ═══════════════════════════════════════════════════════════════
    // TUGAS 2: SOFT DELETE — DEACTIVATE IF HAS EMPLOYEES
    // ═══════════════════════════════════════════════════════════════

    public function test_department_with_employees_is_deactivated_not_deleted(): void
    {
        $dept = Department::create(['name' => 'IT System', 'code' => 'ITSY', 'is_active' => true]);
        Employee::factory()->create(['department' => 'IT System']);

        $response = $this->actingAs($this->admin)->delete(route('settings.departments.destroy', $dept));

        $response->assertSessionHas('success');
        // Department should still exist but be deactivated
        $this->assertDatabaseHas('departments', ['id' => $dept->id, 'is_active' => false]);
    }

    public function test_department_without_employees_is_hard_deleted(): void
    {
        $dept = Department::create(['name' => 'Empty Dept', 'code' => 'EMP', 'is_active' => true]);

        $response = $this->actingAs($this->admin)->delete(route('settings.departments.destroy', $dept));

        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('departments', ['id' => $dept->id]);
    }

    public function test_position_with_employees_is_deactivated_not_deleted(): void
    {
        $pos = Position::create(['name' => 'Manager', 'code' => 'MGR', 'is_active' => true]);
        Employee::factory()->create(['position' => 'Manager']);

        $response = $this->actingAs($this->admin)->delete(route('settings.positions.destroy', $pos));

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('positions', ['id' => $pos->id, 'is_active' => false]);
    }

    public function test_position_without_employees_is_hard_deleted(): void
    {
        $pos = Position::create(['name' => 'Empty Pos', 'code' => 'EPS', 'is_active' => true]);

        $response = $this->actingAs($this->admin)->delete(route('settings.positions.destroy', $pos));

        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('positions', ['id' => $pos->id]);
    }

    public function test_model_employee_count_method(): void
    {
        $dept = Department::create(['name' => 'Produksi', 'code' => 'PROD', 'is_active' => true]);
        $this->assertEquals(0, $dept->employeeCount());

        Employee::factory()->create(['department' => 'Produksi']);
        Employee::factory()->create(['department' => 'Produksi']);

        $this->assertEquals(2, $dept->employeeCount());
    }

    // ═══════════════════════════════════════════════════════════════
    // TUGAS 3: EMPLOYEE FORM [CODE] - Name FORMAT
    // ═══════════════════════════════════════════════════════════════

    public function test_employee_form_shows_code_name_format_for_departments(): void
    {
        Department::create(['name' => 'Produksi', 'code' => 'PROD', 'is_active' => true]);

        $response = $this->actingAs($this->admin)->get(route('hr.employees.create'));

        $response->assertOk();
        $response->assertSee('[PROD] - Produksi');
    }

    public function test_employee_form_shows_code_name_format_for_positions(): void
    {
        Position::create(['name' => 'Manager', 'code' => 'MGR', 'is_active' => true]);

        $response = $this->actingAs($this->admin)->get(route('hr.employees.create'));

        $response->assertOk();
        $response->assertSee('[MGR] - Manager');
    }

    public function test_employee_edit_form_shows_code_name_format(): void
    {
        Department::create(['name' => 'Finance', 'code' => 'FINA', 'is_active' => true]);
        Position::create(['name' => 'Staff', 'code' => 'STF', 'is_active' => true]);
        $employee = Employee::factory()->create(['department' => 'Finance', 'position' => 'Staff']);

        $response = $this->actingAs($this->admin)->get(route('hr.employees.edit', $employee));

        $response->assertOk();
        $response->assertSee('[FINA] - Finance');
        $response->assertSee('[STF] - Staff');
    }

    // ═══════════════════════════════════════════════════════════════
    // TUGAS 4: AUDIT TRAIL & HMAC
    // ═══════════════════════════════════════════════════════════════

    public function test_department_create_audit_has_valid_hmac(): void
    {
        $this->actingAs($this->admin)->post(route('settings.departments.store'), [
            'name' => 'QC Dept',
            'code' => 'QUAL',
        ]);

        $log = ActivityLog::where('module', 'settings')
            ->where('action', 'create')
            ->where('description', 'like', '%QUAL%')
            ->first();

        $this->assertNotNull($log);
        $this->assertNotNull($log->checksum);
        $this->assertTrue(AuditLogService::verifyChecksum($log));
    }

    public function test_department_update_audit_has_old_and_new_data(): void
    {
        $dept = Department::create(['name' => 'Old', 'code' => 'OLD', 'is_active' => true]);

        $this->actingAs($this->admin)->put(route('settings.departments.update', $dept), [
            'name'      => 'New Name',
            'is_active' => true,
        ]);

        $log = ActivityLog::where('module', 'settings')
            ->where('action', 'update')
            ->where('description', 'like', '%OLD%')
            ->first();

        $this->assertNotNull($log);
        $this->assertNotNull($log->old_data);
        $this->assertNotNull($log->new_data);
        $this->assertTrue(AuditLogService::verifyChecksum($log));
    }

    public function test_department_deactivation_creates_update_audit(): void
    {
        $dept = Department::create(['name' => 'Deact Dept', 'code' => 'DCA', 'is_active' => true]);
        Employee::factory()->create(['department' => 'Deact Dept']);

        $this->actingAs($this->admin)->delete(route('settings.departments.destroy', $dept));

        $log = ActivityLog::where('module', 'settings')
            ->where('action', 'update')
            ->where('description', 'like', '%Deactivated department%')
            ->first();

        $this->assertNotNull($log);
        $this->assertTrue(AuditLogService::verifyChecksum($log));
    }

    public function test_department_hard_delete_creates_delete_audit(): void
    {
        $dept = Department::create(['name' => 'Del Dept', 'code' => 'DLD', 'is_active' => true]);

        $this->actingAs($this->admin)->delete(route('settings.departments.destroy', $dept));

        $log = ActivityLog::where('module', 'settings')
            ->where('action', 'delete')
            ->where('description', 'like', '%DLD%')
            ->first();

        $this->assertNotNull($log);
        $this->assertNotNull($log->old_data);
        $this->assertNull($log->new_data);
    }

    public function test_position_create_audit_has_valid_hmac(): void
    {
        $this->actingAs($this->admin)->post(route('settings.positions.store'), [
            'name' => 'Director',
            'code' => 'DIR',
        ]);

        $log = ActivityLog::where('module', 'settings')
            ->where('action', 'create')
            ->where('description', 'like', '%DIR%')
            ->first();

        $this->assertNotNull($log);
        $this->assertTrue(AuditLogService::verifyChecksum($log));
    }

    public function test_position_deactivation_creates_update_audit(): void
    {
        $pos = Position::create(['name' => 'Deact Pos', 'code' => 'DCP', 'is_active' => true]);
        Employee::factory()->create(['position' => 'Deact Pos']);

        $this->actingAs($this->admin)->delete(route('settings.positions.destroy', $pos));

        $log = ActivityLog::where('module', 'settings')
            ->where('action', 'update')
            ->where('description', 'like', '%Deactivated position%')
            ->first();

        $this->assertNotNull($log);
        $this->assertTrue(AuditLogService::verifyChecksum($log));
    }

    // ═══════════════════════════════════════════════════════════════
    // TUGAS 4: AUTHORIZATION
    // ═══════════════════════════════════════════════════════════════

    public function test_non_admin_cannot_create_department(): void
    {
        $response = $this->actingAs($this->staff)->post(route('settings.departments.store'), [
            'name' => 'Hack',
            'code' => 'HCK',
        ]);

        $response->assertForbidden();
    }

    public function test_non_admin_cannot_update_department(): void
    {
        $dept = Department::create(['name' => 'Dept', 'code' => 'DPT', 'is_active' => true]);

        $response = $this->actingAs($this->staff)->put(route('settings.departments.update', $dept), [
            'name'      => 'Hacked',
            'is_active' => true,
        ]);

        $response->assertForbidden();
    }

    public function test_non_admin_cannot_delete_department(): void
    {
        $dept = Department::create(['name' => 'Dept', 'code' => 'DPT', 'is_active' => true]);

        $response = $this->actingAs($this->staff)->delete(route('settings.departments.destroy', $dept));

        $response->assertForbidden();
    }

    public function test_non_admin_cannot_create_position(): void
    {
        $response = $this->actingAs($this->staff)->post(route('settings.positions.store'), [
            'name' => 'Hack',
            'code' => 'HCK',
        ]);

        $response->assertForbidden();
    }

    // ═══════════════════════════════════════════════════════════════
    // SETTINGS UI
    // ═══════════════════════════════════════════════════════════════

    public function test_settings_payroll_tab_shows_departments_with_employee_count(): void
    {
        Department::create(['name' => 'Produksi', 'code' => 'PROD', 'is_active' => true]);
        Employee::factory()->create(['department' => 'Produksi']);

        $response = $this->actingAs($this->admin)->get(route('settings.index', ['tab' => 'payroll']));

        $response->assertOk();
        $response->assertSee('PROD');
        $response->assertSee('Produksi');
    }

    // ═══════════════════════════════════════════════════════════════
    // I18N KEYS
    // ═══════════════════════════════════════════════════════════════

    public function test_i18n_master_data_keys_exist_for_all_languages(): void
    {
        $requiredKeys = [
            'code_readonly_hint',
            'employee_count',
            'deactivate',
            'department_deactivated',
            'position_deactivated',
            'confirm_deactivate_has_employees',
        ];

        foreach (['en', 'id', 'ko', 'zh'] as $locale) {
            $messages = require base_path("lang/{$locale}/messages.php");
            foreach ($requiredKeys as $key) {
                $this->assertArrayHasKey($key, $messages, "Missing i18n key '{$key}' in {$locale}/messages.php");
            }
        }
    }

    // ═══════════════════════════════════════════════════════════════
    // DATABASE SEEDER REGISTRATION
    // ═══════════════════════════════════════════════════════════════

    public function test_database_seeder_calls_department_and_position_seeders(): void
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        $this->assertTrue(Department::where('code', 'PROD')->exists());
        $this->assertTrue(Position::where('code', 'MGR')->exists());
    }
}
