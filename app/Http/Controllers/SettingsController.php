<?php

namespace App\Http\Controllers;

use App\Models\Setting;
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
        ];

        $currencies = ['USD', 'EUR', 'GBP', 'JPY', 'IDR', 'SGD', 'MYR', 'AUD', 'CAD', 'CHF'];
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
        ]);

        Setting::setMany($validated);

        return back()->with('success', 'Settings saved successfully.');
    }
}
