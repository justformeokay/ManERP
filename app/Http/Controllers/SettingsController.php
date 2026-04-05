<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\AuditLogService;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index()
    {
        $settings = [
            'company_name' => Setting::get('company_name', ''),
            'company_email' => Setting::get('company_email', ''),
            'company_phone' => Setting::get('company_phone', ''),
            'company_address' => Setting::get('company_address', ''),
            'default_currency' => Setting::get('default_currency', 'USD'),
            'timezone' => Setting::get('timezone', 'UTC'),
            'default_payment_terms' => Setting::get('default_payment_terms', '30'),
            'default_tax_rate' => Setting::get('default_tax_rate', '11'),
            'low_stock_threshold' => Setting::get('low_stock_threshold', '10'),
            'items_per_page' => Setting::get('items_per_page', '15'),
        ];

        $currencies = [
            'USD' => '$ - US Dollar',
            'IDR' => 'Rp - Indonesian Rupiah',
            'CNY' => '¥ - Chinese Yuan',
            'KRW' => '₩ - Korean Won',
            'EUR' => '€ - Euro',
            'GBP' => '£ - British Pound',
            'JPY' => '¥ - Japanese Yen',
            'SGD' => 'S$ - Singapore Dollar',
            'MYR' => 'RM - Malaysian Ringgit',
            'AUD' => 'A$ - Australian Dollar',
            'CAD' => 'C$ - Canadian Dollar',
            'CHF' => 'Fr - Swiss Franc',
        ];
        $timezones = timezone_identifiers_list();

        return view('settings.index', compact('settings', 'currencies', 'timezones'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'company_name' => 'nullable|string|max:255',
            'company_email' => 'nullable|email|max:255',
            'company_phone' => 'nullable|string|max:30',
            'company_address' => 'nullable|string|max:1000',
            'default_currency' => 'required|string|max:10',
            'timezone' => 'required|string|timezone',
            'default_payment_terms' => 'required|integer|min:0|max:365',
            'default_tax_rate' => 'required|numeric|min:0|max:100',
            'low_stock_threshold' => 'required|integer|min:0',
            'items_per_page' => 'required|integer|min:5|max:100',
        ]);

        // Capture old values before update
        $oldData = [];
        foreach (array_keys($validated) as $key) {
            $oldData[$key] = Setting::get($key);
        }

        Setting::setMany($validated);

        // Cast validated to string values for comparison (settings store strings)
        $newData = array_map(fn($v) => (string) $v, $validated);

        AuditLogService::log(
            'settings',
            'update',
            'Updated system settings',
            $oldData,
            $newData,
        );

        return back()->with('success', __('messages.settings_saved'));
    }
}
