<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Setting;
use App\Models\User;
use App\Services\PayrollService;
use App\Services\TaxService;
use App\Services\AccountingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Tests\TestCase;

class SettingsOverhaulTest extends TestCase
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

    // ═══════════════════════════════════════════════════════════════
    // TAB NAVIGATION
    // ═══════════════════════════════════════════════════════════════

    public function test_settings_index_defaults_to_company_tab(): void
    {
        $response = $this->actingAs($this->admin)->get(route('settings.index'));

        $response->assertOk();
        $response->assertViewHas('currentTab', 'company');
    }

    public function test_settings_tabs_are_navigable(): void
    {
        foreach (['company', 'financial', 'payroll', 'security', 'localization'] as $tab) {
            $response = $this->actingAs($this->admin)
                ->get(route('settings.index', ['tab' => $tab]));

            $response->assertOk();
            $response->assertViewHas('currentTab', $tab);
        }
    }

    public function test_invalid_tab_defaults_to_company(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('settings.index', ['tab' => 'nonexistent']));

        $response->assertOk();
        $response->assertViewHas('currentTab', 'company');
    }

    public function test_non_admin_cannot_access_settings(): void
    {
        $response = $this->actingAs($this->staff)->get(route('settings.index'));
        $response->assertForbidden();
    }

    // ═══════════════════════════════════════════════════════════════
    // COMPANY TAB
    // ═══════════════════════════════════════════════════════════════

    public function test_update_company_saves_data(): void
    {
        $response = $this->actingAs($this->admin)->post(route('settings.update.company'), [
            'company_name'    => 'PT Test Corp',
            'company_email'   => 'test@corp.id',
            'company_phone'   => '+62 812 1234',
            'company_address' => 'Jl. Test No. 1',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('company_settings', ['name' => 'PT Test Corp']);
        $this->assertEquals('PT Test Corp', Setting::get('company_name'));
    }

    public function test_company_logo_upload(): void
    {
        Storage::fake('public');

        $response = $this->actingAs($this->admin)->post(route('settings.update.company'), [
            'company_name' => 'Logo Test Corp',
            'company_logo' => UploadedFile::fake()->image('logo.png', 200, 200)->size(512),
        ]);

        $response->assertRedirect();
        Storage::disk('public')->assertExists('logos');
    }

    public function test_company_logo_rejects_oversized(): void
    {
        Storage::fake('public');

        $response = $this->actingAs($this->admin)->post(route('settings.update.company'), [
            'company_name' => 'Big Logo Corp',
            'company_logo' => UploadedFile::fake()->image('logo.png')->size(2048), // 2MB > 1MB max
        ]);

        $response->assertSessionHasErrors('company_logo');
    }

    // ═══════════════════════════════════════════════════════════════
    // FINANCIAL TAB
    // ═══════════════════════════════════════════════════════════════

    public function test_update_financial_saves_fiscal_year(): void
    {
        $response = $this->actingAs($this->admin)->post(route('settings.update.financial'), [
            'fiscal_year_start_month' => 4,
            'fiscal_closing_month'    => 3,
            'system_account_lock'     => 1,
            'opening_balance_date'    => '2024-01-01',
            'default_tax_rate'        => 12,
            'default_payment_terms'   => 45,
            'default_currency'        => 'USD',
            'timezone'                => 'Asia/Jakarta',
        ]);

        $response->assertRedirect(route('settings.index', ['tab' => 'financial']));
        $this->assertEquals('4', Setting::get('fiscal_year_start_month'));
        $this->assertEquals('1', Setting::get('system_account_lock'));
        $this->assertEquals('12', Setting::get('default_tax_rate'));
    }

    public function test_financial_validates_fiscal_month_range(): void
    {
        $response = $this->actingAs($this->admin)->post(route('settings.update.financial'), [
            'fiscal_year_start_month' => 13, // invalid
            'fiscal_closing_month'    => 0,  // invalid
            'system_account_lock'     => 0,
            'default_tax_rate'        => 11,
            'default_payment_terms'   => 30,
            'default_currency'        => 'IDR',
            'timezone'                => 'Asia/Jakarta',
        ]);

        $response->assertSessionHasErrors(['fiscal_year_start_month', 'fiscal_closing_month']);
    }

    // ═══════════════════════════════════════════════════════════════
    // PAYROLL TAB
    // ═══════════════════════════════════════════════════════════════

    public function test_update_payroll_saves_bpjs_rates(): void
    {
        $response = $this->actingAs($this->admin)->post(route('settings.update.payroll'), [
            'bpjs_jht_company'       => 3.7,
            'bpjs_jht_employee'      => 2,
            'bpjs_jkk_rate'          => 0.54,
            'bpjs_jkm_rate'          => 0.3,
            'bpjs_jp_company'        => 2,
            'bpjs_jp_employee'       => 1,
            'bpjs_jp_max_salary'     => 10042300,
            'bpjs_kes_company'       => 4,
            'bpjs_kes_employee'      => 1,
            'bpjs_kes_min_salary'    => 2942421,
            'bpjs_kes_max_salary'    => 12000000,
            'standard_work_hours'    => 8,
            'late_tolerance_minutes' => 15,
        ]);

        $response->assertRedirect(route('settings.index', ['tab' => 'payroll']));
        $this->assertEquals('0.54', Setting::get('bpjs_jkk_rate'));
    }

    public function test_payroll_validates_bpjs_rate_range(): void
    {
        $response = $this->actingAs($this->admin)->post(route('settings.update.payroll'), [
            'bpjs_jht_company'       => 150, // >100 invalid
            'bpjs_jht_employee'      => -5,  // <0 invalid
            'bpjs_jkk_rate'          => 0.24,
            'bpjs_jkm_rate'          => 0.3,
            'bpjs_jp_company'        => 2,
            'bpjs_jp_employee'       => 1,
            'bpjs_jp_max_salary'     => 10042300,
            'bpjs_kes_company'       => 4,
            'bpjs_kes_employee'      => 1,
            'bpjs_kes_min_salary'    => 2942421,
            'bpjs_kes_max_salary'    => 12000000,
            'standard_work_hours'    => 8,
            'late_tolerance_minutes' => 15,
        ]);

        $response->assertSessionHasErrors(['bpjs_jht_company', 'bpjs_jht_employee']);
    }

    // ═══════════════════════════════════════════════════════════════
    // SECURITY TAB
    // ═══════════════════════════════════════════════════════════════

    public function test_update_security_settings(): void
    {
        $response = $this->actingAs($this->admin)->post(route('settings.update.security'), [
            'session_lifetime_minutes'  => 30,
            'mandatory_2fa_admin'       => 1,
            'api_rate_limit_per_minute' => 120,
            'low_stock_threshold'       => 5,
            'items_per_page'            => 25,
        ]);

        $response->assertRedirect(route('settings.index', ['tab' => 'security']));
        $this->assertEquals('30', Setting::get('session_lifetime_minutes'));
        $this->assertEquals('1', Setting::get('mandatory_2fa_admin'));
    }

    public function test_security_validates_session_lifetime(): void
    {
        $response = $this->actingAs($this->admin)->post(route('settings.update.security'), [
            'session_lifetime_minutes'  => 3,  // <5 invalid
            'mandatory_2fa_admin'       => 0,
            'api_rate_limit_per_minute' => 60,
            'low_stock_threshold'       => 10,
            'items_per_page'            => 25,
        ]);

        $response->assertSessionHasErrors('session_lifetime_minutes');
    }

    // ═══════════════════════════════════════════════════════════════
    // LOCALIZATION TAB
    // ═══════════════════════════════════════════════════════════════

    public function test_update_localization_settings(): void
    {
        $response = $this->actingAs($this->admin)->post(route('settings.update.localization'), [
            'currency_symbol'    => '$',
            'thousand_separator' => ',',
            'decimal_separator'  => '.',
            'decimal_places'     => 2,
            'default_locale'     => 'en',
        ]);

        $response->assertRedirect(route('settings.index', ['tab' => 'localization']));
        $this->assertEquals('$', Setting::get('currency_symbol'));
        $this->assertEquals('en', Setting::get('default_locale'));
    }

    public function test_localization_rejects_invalid_locale(): void
    {
        $response = $this->actingAs($this->admin)->post(route('settings.update.localization'), [
            'currency_symbol'    => 'Rp',
            'thousand_separator' => '.',
            'decimal_separator'  => ',',
            'decimal_places'     => 0,
            'default_locale'     => 'fr', // not in allowed list
        ]);

        $response->assertSessionHasErrors('default_locale');
    }

    // ═══════════════════════════════════════════════════════════════
    // AUDIT TRAIL
    // ═══════════════════════════════════════════════════════════════

    public function test_settings_update_creates_audit_log(): void
    {
        $this->actingAs($this->admin)->post(route('settings.update.financial'), [
            'fiscal_year_start_month' => 7,
            'fiscal_closing_month'    => 6,
            'system_account_lock'     => 0,
            'opening_balance_date'    => '',
            'default_tax_rate'        => 11,
            'default_payment_terms'   => 30,
            'default_currency'        => 'IDR',
            'timezone'                => 'Asia/Jakarta',
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'module'      => 'settings',
            'action'      => 'update',
            'user_id'     => $this->admin->id,
        ]);

        $log = ActivityLog::where('module', 'settings')->latest()->first();
        $this->assertNotNull($log->old_data);
        $this->assertNotNull($log->new_data);
    }

    public function test_each_tab_update_creates_audit_log(): void
    {
        // Payroll tab
        $this->actingAs($this->admin)->post(route('settings.update.payroll'), [
            'bpjs_jht_company'       => 3.7,
            'bpjs_jht_employee'      => 2,
            'bpjs_jkk_rate'          => 0.24,
            'bpjs_jkm_rate'          => 0.3,
            'bpjs_jp_company'        => 2,
            'bpjs_jp_employee'       => 1,
            'bpjs_jp_max_salary'     => 10042300,
            'bpjs_kes_company'       => 4,
            'bpjs_kes_employee'      => 1,
            'bpjs_kes_min_salary'    => 2942421,
            'bpjs_kes_max_salary'    => 12000000,
            'standard_work_hours'    => 8,
            'late_tolerance_minutes' => 15,
        ]);

        $this->assertTrue(
            ActivityLog::where('module', 'settings')
                ->where('description', 'like', '%payroll%')
                ->exists()
        );
    }

    // ═══════════════════════════════════════════════════════════════
    // SETTINGS AFFECT SYSTEM BEHAVIOR — PayrollService
    // ═══════════════════════════════════════════════════════════════

    public function test_bpjs_rates_from_settings_affect_payroll_calculation(): void
    {
        // Default rates
        $payrollService = app(PayrollService::class);
        $defaultBpjs = $payrollService->calculateBpjs(5000000, 6000000);

        // Change JHT company rate from 3.7% to 5%
        Setting::set('bpjs_jht_company', '5');
        Cache::forget('app_settings');

        $newBpjs = $payrollService->calculateBpjs(5000000, 6000000);

        // JHT company should be higher with 5% vs 3.7%
        $this->assertGreaterThan($defaultBpjs['jht_company'], $newBpjs['jht_company']);
        $this->assertEquals(round(5000000 * 0.05, 2), $newBpjs['jht_company']);
    }

    public function test_jp_max_salary_cap_from_settings(): void
    {
        Setting::set('bpjs_jp_max_salary', '8000000');
        Cache::forget('app_settings');

        $payrollService = app(PayrollService::class);
        $bpjs = $payrollService->calculateBpjs(10000000, 12000000);

        // JP should be capped at 8M, not 10M — JP company = 8M * 2% = 160000
        $this->assertEquals(round(8000000 * 0.02, 2), $bpjs['jp_company']);
    }

    public function test_bpjs_kesehatan_caps_from_settings(): void
    {
        Setting::set('bpjs_kes_max_salary', '10000000');
        Cache::forget('app_settings');

        $payrollService = app(PayrollService::class);
        $bpjs = $payrollService->calculateBpjs(5000000, 15000000);

        // Gross 15M > max 10M, so kes base should be 10M
        $this->assertEquals(round(10000000 * 0.04, 2), $bpjs['kes_company']);
    }

    // ═══════════════════════════════════════════════════════════════
    // SETTINGS AFFECT SYSTEM BEHAVIOR — TaxService
    // ═══════════════════════════════════════════════════════════════

    public function test_tax_rate_from_settings_affects_ppn_calculation(): void
    {
        // Default 11%
        $taxService = app(TaxService::class);
        $default = $taxService->calculatePPN(1000000);
        $this->assertEquals(11, $default['rate']);
        $this->assertEquals(110000, $default['ppn']);

        // Change to 12%
        Setting::set('default_tax_rate', '12');
        Cache::forget('app_settings');

        $updated = $taxService->calculatePPN(1000000);
        $this->assertEquals(12, $updated['rate']);
        $this->assertEquals(120000, $updated['ppn']);
    }

    public function test_ppn_rate_method_returns_configurable_rate(): void
    {
        Setting::set('default_tax_rate', '15');
        Cache::forget('app_settings');

        $this->assertEquals(15, TaxService::ppnRate());
    }

    // ═══════════════════════════════════════════════════════════════
    // CACHE BUSTING
    // ═══════════════════════════════════════════════════════════════

    public function test_settings_update_busts_cache(): void
    {
        // Warm the cache
        Setting::get('default_tax_rate');

        $this->actingAs($this->admin)->post(route('settings.update.financial'), [
            'fiscal_year_start_month' => 1,
            'fiscal_closing_month'    => 12,
            'system_account_lock'     => 0,
            'opening_balance_date'    => '',
            'default_tax_rate'        => 15,
            'default_payment_terms'   => 60,
            'default_currency'        => 'IDR',
            'timezone'                => 'Asia/Jakarta',
        ]);

        // After update, cache should be cleared and new value available
        $this->assertEquals('15', Setting::get('default_tax_rate'));
    }

    // ═══════════════════════════════════════════════════════════════
    // NON-ADMIN CANNOT POST TO ANY TAB
    // ═══════════════════════════════════════════════════════════════

    public function test_non_admin_cannot_update_any_tab(): void
    {
        $tabs = ['company', 'financial', 'payroll', 'security', 'localization'];

        foreach ($tabs as $tab) {
            $response = $this->actingAs($this->staff)
                ->post(route("settings.update.{$tab}"), []);

            $response->assertForbidden();
        }
    }

    // ═══════════════════════════════════════════════════════════════
    // CONFIG HISTORY SHOWN IN VIEW
    // ═══════════════════════════════════════════════════════════════

    public function test_config_history_is_passed_to_view(): void
    {
        // Create a settings audit log
        $this->actingAs($this->admin)->post(route('settings.update.localization'), [
            'currency_symbol'    => 'Rp',
            'thousand_separator' => '.',
            'decimal_separator'  => ',',
            'decimal_places'     => 0,
            'default_locale'     => 'id',
        ]);

        $response = $this->actingAs($this->admin)->get(route('settings.index'));

        $response->assertOk();
        $response->assertViewHas('configHistory');
    }
}
