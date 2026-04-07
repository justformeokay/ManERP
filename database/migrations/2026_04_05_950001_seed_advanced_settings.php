<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $settings = [
            // ── Akuntansi & Finansial ──
            'fiscal_year_start_month'   => '1',
            'fiscal_closing_month'      => '12',
            'system_account_lock'       => '0',  // 0=off, 1=on (block manual journal on control accounts)
            'opening_balance_date'      => '',

            // ── SDM & Penggajian (BPJS Rates) ──
            'bpjs_jht_company'          => '3.7',
            'bpjs_jht_employee'         => '2',
            'bpjs_jkk_rate'             => '0.24',
            'bpjs_jkm_rate'             => '0.3',
            'bpjs_jp_company'           => '2',
            'bpjs_jp_employee'          => '1',
            'bpjs_jp_max_salary'        => '10042300',
            'bpjs_kes_company'          => '4',
            'bpjs_kes_employee'         => '1',
            'bpjs_kes_min_salary'       => '2942421',
            'bpjs_kes_max_salary'       => '12000000',
            'standard_work_hours'       => '8',
            'late_tolerance_minutes'    => '15',

            // ── Sistem & Keamanan ──
            'session_lifetime_minutes'  => '120',
            'mandatory_2fa_admin'       => '0',  // 0=off, 1=on
            'api_rate_limit_per_minute' => '60',

            // ── Lokalisasi ──
            'currency_symbol'           => 'Rp',
            'thousand_separator'        => '.',
            'decimal_separator'         => ',',
            'decimal_places'            => '0',
            'default_locale'            => 'id',
            'default_currency'          => 'IDR',
        ];

        $now = now();
        foreach ($settings as $key => $value) {
            DB::table('settings')->insertOrIgnore([
                'key'        => $key,
                'value'      => $value,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('settings')->whereIn('key', [
            'fiscal_year_start_month', 'fiscal_closing_month', 'system_account_lock', 'opening_balance_date',
            'bpjs_jht_company', 'bpjs_jht_employee', 'bpjs_jkk_rate', 'bpjs_jkm_rate',
            'bpjs_jp_company', 'bpjs_jp_employee', 'bpjs_jp_max_salary',
            'bpjs_kes_company', 'bpjs_kes_employee', 'bpjs_kes_min_salary', 'bpjs_kes_max_salary',
            'standard_work_hours', 'late_tolerance_minutes',
            'session_lifetime_minutes', 'mandatory_2fa_admin', 'api_rate_limit_per_minute',
            'currency_symbol', 'thousand_separator', 'decimal_separator', 'decimal_places', 'default_locale',
        ])->delete();
    }
};
