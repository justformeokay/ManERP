<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Department;
use App\Models\Position;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rules\Password;
use Tests\TestCase;

class SettingsHrMasterDataTest extends TestCase
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
            'permissions' => ['sales.view'],
        ]);
    }

    private function payrollPayload(array $merge = []): array
    {
        return array_merge([
            'bpjs_jht_company'       => '3.70',
            'bpjs_jht_employee'      => '2.00',
            'bpjs_jkk_rate'          => '0.24',
            'bpjs_jkm_rate'          => '0.30',
            'bpjs_jp_company'        => '2.00',
            'bpjs_jp_employee'       => '1.00',
            'bpjs_jp_max_salary'     => '9559600',
            'bpjs_kes_company'       => '4.00',
            'bpjs_kes_employee'      => '1.00',
            'bpjs_kes_min_salary'    => '0',
            'bpjs_kes_max_salary'    => '12000000',
            'standard_work_hours'    => 8,
            'late_tolerance_minutes' => 15,
            'late_deduction_per_minute' => '500',
            'nik_min_length'         => 16,
            'nik_max_length'         => 16,
            'bank_account_min_length'=> 10,
            'bank_account_max_length'=> 20,
        ], $merge);
    }

    // ═══════════════════════════════════════════════════════════════
    // HR VALIDATION SETTINGS
    // ═══════════════════════════════════════════════════════════════

    public function test_payroll_tab_shows_hr_validation_settings(): void
    {
        $response = $this->actingAs($this->admin)->get(route('settings.index', ['tab' => 'payroll']));

        $response->assertOk();
        $response->assertSee('nik_min_length');
        $response->assertSee('nik_max_length');
        $response->assertSee('bank_account_min_length');
        $response->assertSee('bank_account_max_length');
    }

    public function test_update_payroll_saves_hr_validation_settings(): void
    {
        $response = $this->actingAs($this->admin)->post(route('settings.update.payroll'), $this->payrollPayload([
            'nik_min_length'          => 10,
            'nik_max_length'          => 20,
            'bank_account_min_length' => 8,
            'bank_account_max_length' => 25,
        ]));

        $response->assertRedirect();

        $this->assertEquals('10', Setting::get('nik_min_length'));
        $this->assertEquals('20', Setting::get('nik_max_length'));
        $this->assertEquals('8', Setting::get('bank_account_min_length'));
        $this->assertEquals('25', Setting::get('bank_account_max_length'));
    }

    public function test_hr_validation_rejects_max_less_than_min_nik(): void
    {
        $response = $this->actingAs($this->admin)->post(route('settings.update.payroll'), $this->payrollPayload([
            'nik_min_length'          => 20,
            'nik_max_length'          => 10,
        ]));

        $response->assertSessionHasErrors('nik_max_length');
    }

    public function test_hr_validation_rejects_max_less_than_min_bank(): void
    {
        $response = $this->actingAs($this->admin)->post(route('settings.update.payroll'), $this->payrollPayload([
            'bank_account_min_length' => 25,
            'bank_account_max_length' => 8,
        ]));

        $response->assertSessionHasErrors('bank_account_max_length');
    }

    public function test_hr_validation_busts_settings_cache(): void
    {
        Cache::put('app_settings', ['old' => 'value']);

        $this->actingAs($this->admin)->post(route('settings.update.payroll'), $this->payrollPayload([
            'nik_min_length'          => 10,
            'nik_max_length'          => 20,
            'bank_account_min_length' => 8,
            'bank_account_max_length' => 25,
        ]));

        $this->assertNull(Cache::get('app_settings'));
    }

    // ═══════════════════════════════════════════════════════════════
    // DEPARTMENT CRUD
    // ═══════════════════════════════════════════════════════════════

    public function test_admin_can_create_department(): void
    {
        $response = $this->actingAs($this->admin)->post(route('settings.departments.store'), [
            'name' => 'Engineering',
            'code' => 'ENG',
        ]);

        $response->assertRedirect(route('settings.index', ['tab' => 'payroll']));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('departments', [
            'name'      => 'Engineering',
            'code'      => 'ENG',
            'is_active' => true,
        ]);
    }

    public function test_department_create_logs_audit_trail(): void
    {
        $this->actingAs($this->admin)->post(route('settings.departments.store'), [
            'name' => 'Finance',
            'code' => 'FIN',
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'module' => 'settings',
            'action' => 'create',
            'user_id' => $this->admin->id,
        ]);

        $log = ActivityLog::where('module', 'settings')
            ->where('action', 'create')
            ->latest('id')
            ->first();

        $this->assertNotNull($log->checksum);
        $this->assertStringContainsString('Finance', $log->description);
    }

    public function test_department_code_must_be_unique(): void
    {
        Department::create(['name' => 'Dept A', 'code' => 'DPT', 'is_active' => true]);

        $response = $this->actingAs($this->admin)->post(route('settings.departments.store'), [
            'name' => 'Dept B',
            'code' => 'DPT',
        ]);

        $response->assertSessionHasErrors('code');
    }

    public function test_department_name_is_required(): void
    {
        $response = $this->actingAs($this->admin)->post(route('settings.departments.store'), [
            'name' => '',
            'code' => 'ENG',
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_admin_can_update_department(): void
    {
        $dept = Department::create(['name' => 'Old Name', 'code' => 'OLD', 'is_active' => true]);

        $response = $this->actingAs($this->admin)->put(route('settings.departments.update', $dept), [
            'name'      => 'New Name',
            'is_active' => true,
        ]);

        $response->assertRedirect(route('settings.index', ['tab' => 'payroll']));

        $dept->refresh();
        $this->assertEquals('New Name', $dept->name);
        $this->assertEquals('OLD', $dept->code); // code is read-only
    }

    public function test_department_update_logs_old_and_new_data(): void
    {
        $dept = Department::create(['name' => 'Original', 'code' => 'ORG', 'is_active' => true]);

        $this->actingAs($this->admin)->put(route('settings.departments.update', $dept), [
            'name'      => 'Updated',
            'is_active' => true,
        ]);

        $log = ActivityLog::where('module', 'settings')
            ->where('action', 'update')
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertNotNull($log->old_data);
        $this->assertNotNull($log->new_data);
        $this->assertNotNull($log->checksum);
    }

    public function test_admin_can_delete_department(): void
    {
        $dept = Department::create(['name' => 'ToDelete', 'code' => 'DEL', 'is_active' => true]);

        $response = $this->actingAs($this->admin)->delete(route('settings.departments.destroy', $dept));

        $response->assertRedirect(route('settings.index', ['tab' => 'payroll']));
        $this->assertDatabaseMissing('departments', ['id' => $dept->id]);
    }

    public function test_department_delete_logs_audit_trail(): void
    {
        $dept = Department::create(['name' => 'Audit Test', 'code' => 'AUD', 'is_active' => true]);

        $this->actingAs($this->admin)->delete(route('settings.departments.destroy', $dept));

        $log = ActivityLog::where('module', 'settings')
            ->where('action', 'delete')
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertNotNull($log->old_data);
        $this->assertNull($log->new_data);
        $this->assertStringContainsString('Audit Test', $log->description);
    }

    public function test_staff_cannot_create_department(): void
    {
        $response = $this->actingAs($this->staff)->post(route('settings.departments.store'), [
            'name' => 'Unauthorized',
            'code' => 'UNA',
        ]);

        $response->assertForbidden();
    }

    // ═══════════════════════════════════════════════════════════════
    // POSITION CRUD
    // ═══════════════════════════════════════════════════════════════

    public function test_admin_can_create_position(): void
    {
        $response = $this->actingAs($this->admin)->post(route('settings.positions.store'), [
            'name' => 'Software Engineer',
            'code' => 'SE',
        ]);

        $response->assertRedirect(route('settings.index', ['tab' => 'payroll']));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('positions', [
            'name'      => 'Software Engineer',
            'code'      => 'SE',
            'is_active' => true,
        ]);
    }

    public function test_position_create_logs_audit_trail(): void
    {
        $this->actingAs($this->admin)->post(route('settings.positions.store'), [
            'name' => 'Manager',
            'code' => 'MGR',
        ]);

        $log = ActivityLog::where('module', 'settings')
            ->where('action', 'create')
            ->latest('id')
            ->first();

        $this->assertNotNull($log->checksum);
        $this->assertStringContainsString('Manager', $log->description);
    }

    public function test_position_code_must_be_unique(): void
    {
        Position::create(['name' => 'Pos A', 'code' => 'POS', 'is_active' => true]);

        $response = $this->actingAs($this->admin)->post(route('settings.positions.store'), [
            'name' => 'Pos B',
            'code' => 'POS',
        ]);

        $response->assertSessionHasErrors('code');
    }

    public function test_admin_can_update_position(): void
    {
        $pos = Position::create(['name' => 'Old Pos', 'code' => 'OLP', 'is_active' => true]);

        $response = $this->actingAs($this->admin)->put(route('settings.positions.update', $pos), [
            'name'      => 'New Pos',
            'is_active' => true,
        ]);

        $response->assertRedirect(route('settings.index', ['tab' => 'payroll']));

        $pos->refresh();
        $this->assertEquals('New Pos', $pos->name);
        $this->assertEquals('OLP', $pos->code); // code is read-only
    }

    public function test_position_update_logs_old_and_new_data(): void
    {
        $pos = Position::create(['name' => 'Original', 'code' => 'ORP', 'is_active' => true]);

        $this->actingAs($this->admin)->put(route('settings.positions.update', $pos), [
            'name'      => 'Updated',
            'is_active' => true,
        ]);

        $log = ActivityLog::where('module', 'settings')
            ->where('action', 'update')
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertNotNull($log->old_data);
        $this->assertNotNull($log->new_data);
    }

    public function test_admin_can_delete_position(): void
    {
        $pos = Position::create(['name' => 'ToDelete', 'code' => 'DLP', 'is_active' => true]);

        $response = $this->actingAs($this->admin)->delete(route('settings.positions.destroy', $pos));

        $response->assertRedirect(route('settings.index', ['tab' => 'payroll']));
        $this->assertDatabaseMissing('positions', ['id' => $pos->id]);
    }

    public function test_position_delete_logs_audit_trail(): void
    {
        $pos = Position::create(['name' => 'Audit Pos', 'code' => 'AUP', 'is_active' => true]);

        $this->actingAs($this->admin)->delete(route('settings.positions.destroy', $pos));

        $log = ActivityLog::where('module', 'settings')
            ->where('action', 'delete')
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertStringContainsString('Audit Pos', $log->description);
    }

    public function test_staff_cannot_create_position(): void
    {
        $response = $this->actingAs($this->staff)->post(route('settings.positions.store'), [
            'name' => 'Unauthorized',
            'code' => 'UNA',
        ]);

        $response->assertForbidden();
    }

    // ═══════════════════════════════════════════════════════════════
    // EMPLOYEE FORM INTEGRATION
    // ═══════════════════════════════════════════════════════════════

    public function test_employee_create_page_receives_departments_and_positions(): void
    {
        Department::create(['name' => 'IT', 'code' => 'IT', 'is_active' => true]);
        Position::create(['name' => 'Dev', 'code' => 'DEV', 'is_active' => true]);

        $response = $this->actingAs($this->admin)->get(route('hr.employees.create'));

        $response->assertOk();
        $response->assertViewHas('departments');
        $response->assertViewHas('positions');
    }

    public function test_employee_edit_page_receives_departments_and_positions(): void
    {
        $dept = Department::create(['name' => 'IT', 'code' => 'IT', 'is_active' => true]);
        $pos = Position::create(['name' => 'Dev', 'code' => 'DEV', 'is_active' => true]);

        $employee = \App\Models\Employee::factory()->create();

        $response = $this->actingAs($this->admin)->get(route('hr.employees.edit', $employee));

        $response->assertOk();
        $response->assertViewHas('departments');
        $response->assertViewHas('positions');
    }

    public function test_inactive_departments_excluded_from_employee_form(): void
    {
        Department::create(['name' => 'Active Dept', 'code' => 'ACT', 'is_active' => true]);
        Department::create(['name' => 'Inactive Dept', 'code' => 'INA', 'is_active' => false]);

        $response = $this->actingAs($this->admin)->get(route('hr.employees.create'));

        $departments = $response->viewData('departments');
        $this->assertCount(1, $departments);
        $this->assertEquals('Active Dept', $departments->first()->name);
    }

    // ═══════════════════════════════════════════════════════════════
    // DYNAMIC EMPLOYEE VALIDATION
    // ═══════════════════════════════════════════════════════════════

    public function test_employee_nik_uses_dynamic_min_length(): void
    {
        Setting::set('nik_min_length', 10);
        Setting::set('nik_max_length', 20);

        $response = $this->actingAs($this->admin)->post(route('hr.employees.store'), [
            'nik'         => '123',
            'name'        => 'Test Employee',
            'join_date'   => '2024-01-01',
            'ptkp_status' => 'TK/0',
            'status'      => 'active',
        ]);

        $response->assertSessionHasErrors('nik');
    }

    public function test_employee_nik_uses_dynamic_max_length(): void
    {
        Setting::set('nik_min_length', 5);
        Setting::set('nik_max_length', 10);

        $response = $this->actingAs($this->admin)->post(route('hr.employees.store'), [
            'nik'         => '123456789012345',
            'name'        => 'Test Employee',
            'join_date'   => '2024-01-01',
            'ptkp_status' => 'TK/0',
            'status'      => 'active',
        ]);

        $response->assertSessionHasErrors('nik');
    }

    public function test_employee_bank_account_uses_dynamic_validation(): void
    {
        Setting::set('bank_account_min_length', 10);
        Setting::set('bank_account_max_length', 20);

        $response = $this->actingAs($this->admin)->post(route('hr.employees.store'), [
            'nik'                 => '1234567890123456',
            'name'                => 'Test Employee',
            'join_date'           => '2024-01-01',
            'ptkp_status'         => 'TK/0',
            'status'              => 'active',
            'bank_account_number' => '123',
        ]);

        $response->assertSessionHasErrors('bank_account_number');
    }

    // ═══════════════════════════════════════════════════════════════
    // AUDIT CHECKSUM INTEGRITY
    // ═══════════════════════════════════════════════════════════════

    public function test_department_crud_produces_valid_hmac_checksum(): void
    {
        $this->actingAs($this->admin)->post(route('settings.departments.store'), [
            'name' => 'Checksum Dept',
            'code' => 'CHK',
        ]);

        $log = ActivityLog::where('module', 'settings')->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertNotEmpty($log->checksum);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $log->checksum);
    }

    public function test_position_crud_produces_valid_hmac_checksum(): void
    {
        $this->actingAs($this->admin)->post(route('settings.positions.store'), [
            'name' => 'Checksum Pos',
            'code' => 'CHP',
        ]);

        $log = ActivityLog::where('module', 'settings')->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertNotEmpty($log->checksum);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $log->checksum);
    }

    // ═══════════════════════════════════════════════════════════════
    // i18n KEYS EXIST
    // ═══════════════════════════════════════════════════════════════

    public function test_i18n_keys_exist_for_all_languages(): void
    {
        $requiredKeys = [
            'hr_validation_settings', 'hr_validation_settings_desc',
            'nik_min_length', 'nik_max_length',
            'bank_account_min_length', 'bank_account_max_length',
            'department_management', 'department_management_desc',
            'add_department', 'dept_name', 'dept_code',
            'department_created', 'department_updated', 'department_deleted',
            'no_departments_configured', 'select_department',
            'position_management', 'position_management_desc',
            'add_position', 'position_name', 'position_code',
            'position_created', 'position_updated', 'position_deleted',
            'no_positions_configured', 'select_position',
        ];

        foreach (['en', 'id', 'ko', 'zh'] as $locale) {
            $messages = require base_path("lang/{$locale}/messages.php");

            foreach ($requiredKeys as $key) {
                $this->assertArrayHasKey($key, $messages, "Missing i18n key '{$key}' in {$locale}/messages.php");
            }
        }
    }

    // ═══════════════════════════════════════════════════════════════
    // MODEL SCOPES
    // ═══════════════════════════════════════════════════════════════

    public function test_department_active_scope(): void
    {
        Department::create(['name' => 'Active', 'code' => 'ACT', 'is_active' => true]);
        Department::create(['name' => 'Inactive', 'code' => 'INA', 'is_active' => false]);

        $active = Department::active()->get();
        $this->assertCount(1, $active);
        $this->assertEquals('Active', $active->first()->name);
    }

    public function test_position_active_scope(): void
    {
        Position::create(['name' => 'Active', 'code' => 'ACT', 'is_active' => true]);
        Position::create(['name' => 'Inactive', 'code' => 'INA', 'is_active' => false]);

        $active = Position::active()->get();
        $this->assertCount(1, $active);
        $this->assertEquals('Active', $active->first()->name);
    }
}
