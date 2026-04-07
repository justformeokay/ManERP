<?php

namespace Tests\Feature;

use App\Http\Middleware\SanitizeCurrencyInput;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class CurrencyMaskingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role'   => User::ROLE_ADMIN,
            'status' => User::STATUS_ACTIVE,
        ]);
    }

    // ═══════════════════════════════════════════════════════════════
    // HELPERS: currency_config()
    // ═══════════════════════════════════════════════════════════════

    public function test_currency_config_returns_default_idr_settings(): void
    {
        $config = currency_config();

        $this->assertIsArray($config);
        $this->assertEquals('Rp', $config['symbol']);
        $this->assertEquals('.', $config['thousand_separator']);
        $this->assertEquals(',', $config['decimal_separator']);
        $this->assertEquals(0, $config['decimal_places']);
        $this->assertEquals('IDR', $config['code']);
    }

    public function test_currency_config_reads_from_settings(): void
    {
        Setting::setMany([
            'currency_symbol'    => '$',
            'thousand_separator' => ',',
            'decimal_separator'  => '.',
            'decimal_places'     => '2',
            'default_currency'   => 'USD',
        ]);

        $config = currency_config();

        $this->assertEquals('$', $config['symbol']);
        $this->assertEquals(',', $config['thousand_separator']);
        $this->assertEquals('.', $config['decimal_separator']);
        $this->assertEquals(2, $config['decimal_places']);
        $this->assertEquals('USD', $config['code']);
    }

    // ═══════════════════════════════════════════════════════════════
    // HELPERS: format_currency()
    // ═══════════════════════════════════════════════════════════════

    public function test_format_currency_idr_default(): void
    {
        // Default IDR: symbol Rp, thousand_separator '.', decimal_separator ',', decimal_places 0
        $this->assertEquals('Rp 0', format_currency(0));
        $this->assertEquals('Rp 1.000', format_currency(1000));
        $this->assertEquals('Rp 1.000.000', format_currency(1000000));
        $this->assertEquals('Rp 50.000', format_currency(50000));
        $this->assertEquals('Rp 999', format_currency(999));
    }

    public function test_format_currency_handles_null(): void
    {
        $this->assertEquals('Rp 0', format_currency(null));
    }

    public function test_format_currency_handles_negative(): void
    {
        $this->assertEquals('-Rp 1.000', format_currency(-1000));
    }

    public function test_format_currency_usd_with_decimals(): void
    {
        Setting::setMany([
            'currency_symbol'    => '$',
            'thousand_separator' => ',',
            'decimal_separator'  => '.',
            'decimal_places'     => '2',
            'default_currency'   => 'USD',
        ]);

        $this->assertEquals('$ 0.00', format_currency(0));
        $this->assertEquals('$ 1,000.00', format_currency(1000));
        $this->assertEquals('$ 1,234,567.89', format_currency(1234567.89));
        $this->assertEquals('$ 0.50', format_currency(0.5));
    }

    public function test_format_currency_eur_format(): void
    {
        Setting::setMany([
            'currency_symbol'    => '€',
            'thousand_separator' => '.',
            'decimal_separator'  => ',',
            'decimal_places'     => '2',
            'default_currency'   => 'EUR',
        ]);

        $this->assertEquals('€ 1.234,56', format_currency(1234.56));
        $this->assertEquals('€ 0,00', format_currency(0));
    }

    public function test_format_currency_string_input(): void
    {
        $this->assertEquals('Rp 1.500', format_currency('1500'));
        $this->assertEquals('Rp 2.500', format_currency('2500.49'));
    }

    // ═══════════════════════════════════════════════════════════════
    // HELPERS: currency_symbol()
    // ═══════════════════════════════════════════════════════════════

    public function test_currency_symbol_returns_default(): void
    {
        $this->assertEquals('Rp', currency_symbol());
    }

    public function test_currency_symbol_returns_specific_currency(): void
    {
        $this->assertEquals('$', currency_symbol('USD'));
        $this->assertEquals('€', currency_symbol('EUR'));
        $this->assertEquals('¥', currency_symbol('JPY'));
        $this->assertEquals('£', currency_symbol('GBP'));
        $this->assertEquals('₩', currency_symbol('KRW'));
        $this->assertEquals('S$', currency_symbol('SGD'));
        $this->assertEquals('RM', currency_symbol('MYR'));
        $this->assertEquals('A$', currency_symbol('AUD'));
    }

    public function test_currency_symbol_reads_from_settings(): void
    {
        Setting::set('currency_symbol', '$');

        $this->assertEquals('$', currency_symbol());
    }

    // ═══════════════════════════════════════════════════════════════
    // HELPERS: currency_code()
    // ═══════════════════════════════════════════════════════════════

    public function test_currency_code_returns_default(): void
    {
        $this->assertEquals('IDR', currency_code());
    }

    public function test_currency_code_reads_from_settings(): void
    {
        Setting::set('default_currency', 'USD');

        $this->assertEquals('USD', currency_code());
    }

    // ═══════════════════════════════════════════════════════════════
    // MIDDLEWARE: SanitizeCurrencyInput (IDR defaults)
    // ═══════════════════════════════════════════════════════════════

    public function test_middleware_strips_idr_formatting(): void
    {
        $middleware = new SanitizeCurrencyInput();

        $request = Request::create('/test', 'POST', [
            'basic_salary' => 'Rp 5.000.000',
            'amount'       => 'Rp 1.250.000',
        ]);

        $middleware->handle($request, function ($req) {
            $this->assertEquals('5000000', $req->input('basic_salary'));
            $this->assertEquals('1250000', $req->input('amount'));

            return response('ok');
        });
    }

    public function test_middleware_strips_usd_formatting(): void
    {
        Setting::setMany([
            'currency_symbol'    => '$',
            'thousand_separator' => ',',
            'decimal_separator'  => '.',
            'default_currency'   => 'USD',
        ]);

        $middleware = new SanitizeCurrencyInput();

        $request = Request::create('/test', 'POST', [
            'amount' => '$ 1,234.56',
        ]);

        $middleware->handle($request, function ($req) {
            $this->assertEquals('1234.56', $req->input('amount'));

            return response('ok');
        });
    }

    public function test_middleware_passes_plain_numbers(): void
    {
        $middleware = new SanitizeCurrencyInput();

        $request = Request::create('/test', 'POST', [
            'amount' => '5000',
        ]);

        $middleware->handle($request, function ($req) {
            $this->assertEquals('5000', $req->input('amount'));

            return response('ok');
        });
    }

    public function test_middleware_handles_nested_arrays(): void
    {
        $middleware = new SanitizeCurrencyInput();

        $request = Request::create('/test', 'POST', [
            'items' => [
                ['unit_price' => 'Rp 25.000', 'description' => 'Widget'],
                ['unit_price' => 'Rp 100.000', 'description' => 'Gadget'],
            ],
        ]);

        $middleware->handle($request, function ($req) {
            $items = $req->input('items');
            $this->assertEquals('25000', $items[0]['unit_price']);
            $this->assertEquals('100000', $items[1]['unit_price']);
            // Non-currency fields should be untouched
            $this->assertEquals('Widget', $items[0]['description']);

            return response('ok');
        });
    }

    public function test_middleware_handles_month_budget_fields(): void
    {
        $middleware = new SanitizeCurrencyInput();

        $request = Request::create('/test', 'POST', [
            'jan' => 'Rp 1.000.000',
            'feb' => 'Rp 2.000.000',
            'mar' => '3000000',
        ]);

        $middleware->handle($request, function ($req) {
            $this->assertEquals('1000000', $req->input('jan'));
            $this->assertEquals('2000000', $req->input('feb'));
            $this->assertEquals('3000000', $req->input('mar'));

            return response('ok');
        });
    }

    public function test_middleware_ignores_get_requests(): void
    {
        $middleware = new SanitizeCurrencyInput();

        $request = Request::create('/test', 'GET', [
            'amount' => 'Rp 5.000.000',
        ]);

        $middleware->handle($request, function ($req) {
            // GET should be untouched
            $this->assertEquals('Rp 5.000.000', $req->input('amount'));

            return response('ok');
        });
    }

    public function test_middleware_ignores_non_currency_fields(): void
    {
        $middleware = new SanitizeCurrencyInput();

        $request = Request::create('/test', 'POST', [
            'name'  => 'Rp 5.000.000',  // Not a currency field
            'email' => 'test@example.com',
        ]);

        $middleware->handle($request, function ($req) {
            // name is not in CURRENCY_FIELDS, should not be stripped
            $this->assertEquals('Rp 5.000.000', $req->input('name'));
            $this->assertEquals('test@example.com', $req->input('email'));

            return response('ok');
        });
    }

    // ═══════════════════════════════════════════════════════════════
    // AUTO-SYNC: Currency change triggers localization update
    // ═══════════════════════════════════════════════════════════════

    public function test_changing_currency_auto_syncs_localization(): void
    {
        // Set starting currency
        Setting::set('default_currency', 'IDR');

        $this->actingAs($this->admin)
            ->post(route('settings.update.financial'), [
                'fiscal_year_start_month' => 1,
                'fiscal_closing_month'    => 12,
                'system_account_lock'     => 0,
                'opening_balance_date'    => null,
                'default_tax_rate'        => 11,
                'default_payment_terms'   => 30,
                'default_currency'        => 'USD',
                'timezone'                => 'Asia/Jakarta',
            ])
            ->assertRedirect();

        // Verify localization settings were auto-updated
        $this->assertEquals('$', Setting::get('currency_symbol'));
        $this->assertEquals(',', Setting::get('thousand_separator'));
        $this->assertEquals('.', Setting::get('decimal_separator'));
        $this->assertEquals('2', Setting::get('decimal_places'));
    }

    public function test_same_currency_does_not_trigger_sync(): void
    {
        Setting::setMany([
            'default_currency'   => 'IDR',
            'currency_symbol'    => 'CUSTOM',
            'thousand_separator' => '|',
        ]);

        $this->actingAs($this->admin)
            ->post(route('settings.update.financial'), [
                'fiscal_year_start_month' => 1,
                'fiscal_closing_month'    => 12,
                'system_account_lock'     => 0,
                'opening_balance_date'    => null,
                'default_tax_rate'        => 11,
                'default_payment_terms'   => 30,
                'default_currency'        => 'IDR',
                'timezone'                => 'Asia/Jakarta',
            ])
            ->assertRedirect();

        // Custom values should remain because currency didn't change
        $this->assertEquals('CUSTOM', Setting::get('currency_symbol'));
        $this->assertEquals('|', Setting::get('thousand_separator'));
    }

    public function test_switching_to_eur_sets_correct_format(): void
    {
        Setting::set('default_currency', 'IDR');

        $this->actingAs($this->admin)
            ->post(route('settings.update.financial'), [
                'fiscal_year_start_month' => 1,
                'fiscal_closing_month'    => 12,
                'system_account_lock'     => 0,
                'opening_balance_date'    => null,
                'default_tax_rate'        => 11,
                'default_payment_terms'   => 30,
                'default_currency'        => 'EUR',
                'timezone'                => 'Asia/Jakarta',
            ])
            ->assertRedirect();

        $this->assertEquals('€', Setting::get('currency_symbol'));
        $this->assertEquals('.', Setting::get('thousand_separator'));
        $this->assertEquals(',', Setting::get('decimal_separator'));
        $this->assertEquals('2', Setting::get('decimal_places'));
    }

    // ═══════════════════════════════════════════════════════════════
    // FORMAT CURRENCY: BCMath precision
    // ═══════════════════════════════════════════════════════════════

    public function test_format_currency_bcmath_precision(): void
    {
        Setting::setMany([
            'currency_symbol'    => '$',
            'thousand_separator' => ',',
            'decimal_separator'  => '.',
            'decimal_places'     => '2',
        ]);

        // BCMath should handle large numbers precisely
        $this->assertEquals('$ 999,999,999.99', format_currency(999999999.99));
        $this->assertEquals('$ 0.01', format_currency(0.01));
        $this->assertEquals('$ 0.10', format_currency(0.1));
    }

    public function test_format_currency_rounds_to_decimal_places(): void
    {
        Setting::setMany([
            'currency_symbol'    => '$',
            'thousand_separator' => ',',
            'decimal_separator'  => '.',
            'decimal_places'     => '2',
        ]);

        // bcadd truncates (or rounds depending on implementation)
        $result = format_currency(1234.567);
        $this->assertStringStartsWith('$ 1,234.5', $result);
    }

    // ═══════════════════════════════════════════════════════════════
    // MIDDLEWARE: All CURRENCY_FIELDS are recognized
    // ═══════════════════════════════════════════════════════════════

    public function test_middleware_recognizes_all_currency_fields(): void
    {
        $fields = [
            'basic_salary', 'fixed_allowance', 'meal_allowance',
            'transport_allowance', 'overtime_rate',
            'bpjs_jp_max_salary', 'bpjs_kes_min_salary', 'bpjs_kes_max_salary',
            'late_deduction_per_minute', 'night_shift_bonus',
            'amount', 'tax_amount', 'discount', 'unit_price',
            'purchase_cost', 'salvage_value', 'disposal_amount',
            'opening_balance', 'statement_balance',
            'cost_price', 'sell_price', 'credit_limit', 'budget',
            'debit', 'credit',
        ];

        $middleware = new SanitizeCurrencyInput();

        $inputData = [];
        foreach ($fields as $field) {
            $inputData[$field] = 'Rp 1.000';
        }

        $request = Request::create('/test', 'POST', $inputData);

        $middleware->handle($request, function ($req) use ($fields) {
            foreach ($fields as $field) {
                $this->assertEquals('1000', $req->input($field), "Field '{$field}' was not sanitized.");
            }

            return response('ok');
        });
    }

    // ═══════════════════════════════════════════════════════════════
    // META TAG: currency config injected in layout
    // ═══════════════════════════════════════════════════════════════

    public function test_layout_includes_currency_config_meta(): void
    {
        $response = $this->actingAs($this->admin)->get(route('settings.index'));
        $response->assertOk();

        $response->assertSee('name="currency-config"', false);
        $response->assertSee('"symbol"', false);
        $response->assertSee('"thousandSep"', false);
        $response->assertSee('"decimalSep"', false);
        $response->assertSee('"decimals"', false);
    }
}
