<?php

namespace App\Http\Controllers;

use App\Models\CompanySetting;
use App\Models\Department;
use App\Models\Position;
use App\Models\Setting;
use App\Models\Shift;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class SettingsController extends Controller
{
    private const TABS = ['company', 'financial', 'payroll', 'security', 'localization', 'crm'];

    /**
     * Maps currency codes to localization defaults (auto-synced when default_currency changes).
     */
    private const CURRENCY_LOCALE_MAP = [
        'IDR' => ['currency_symbol' => 'Rp',  'thousand_separator' => '.', 'decimal_separator' => ',', 'decimal_places' => '0'],
        'USD' => ['currency_symbol' => '$',   'thousand_separator' => ',', 'decimal_separator' => '.', 'decimal_places' => '2'],
        'CNY' => ['currency_symbol' => '¥',   'thousand_separator' => ',', 'decimal_separator' => '.', 'decimal_places' => '2'],
        'KRW' => ['currency_symbol' => '₩',   'thousand_separator' => ',', 'decimal_separator' => '.', 'decimal_places' => '0'],
        'EUR' => ['currency_symbol' => '€',   'thousand_separator' => '.', 'decimal_separator' => ',', 'decimal_places' => '2'],
        'GBP' => ['currency_symbol' => '£',   'thousand_separator' => ',', 'decimal_separator' => '.', 'decimal_places' => '2'],
        'JPY' => ['currency_symbol' => '¥',   'thousand_separator' => ',', 'decimal_separator' => '.', 'decimal_places' => '0'],
        'SGD' => ['currency_symbol' => 'S$',  'thousand_separator' => ',', 'decimal_separator' => '.', 'decimal_places' => '2'],
        'MYR' => ['currency_symbol' => 'RM',  'thousand_separator' => ',', 'decimal_separator' => '.', 'decimal_places' => '2'],
        'AUD' => ['currency_symbol' => 'A$',  'thousand_separator' => ',', 'decimal_separator' => '.', 'decimal_places' => '2'],
    ];

    // ════════════════════════════════════════════════════════════════
    // INDEX — Tabbed Interface
    // ════════════════════════════════════════════════════════════════

    public function index(Request $request): View
    {
        $tab = in_array($request->query('tab'), self::TABS, true)
            ? $request->query('tab')
            : 'company';

        $data = ['currentTab' => $tab, 'tabs' => self::TABS];

        $data += match ($tab) {
            'company'      => $this->companyData(),
            'financial'    => $this->financialData(),
            'payroll'      => $this->payrollData(),
            'security'     => $this->securityData(),
            'localization' => $this->localizationData(),
            'crm'          => $this->crmData(),
        };

        // Configuration version history (last 20 settings changes)
        $data['configHistory'] = \App\Models\ActivityLog::where('module', 'settings')
            ->latest()
            ->limit(20)
            ->get();

        return view('settings.index', $data);
    }

    // ════════════════════════════════════════════════════════════════
    // TAB: Company Profile
    // ════════════════════════════════════════════════════════════════

    private function companyData(): array
    {
        $company = CompanySetting::getSettings();
        return ['company' => $company];
    }

    public function updateCompany(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company_name'    => 'nullable|string|max:255',
            'company_email'   => 'nullable|email|max:255',
            'company_phone'   => 'nullable|string|max:30',
            'company_address' => 'nullable|string|max:1000',
            'company_logo'    => 'nullable|image|mimes:png,jpg,jpeg,svg|max:1024',
        ]);

        $company = CompanySetting::first() ?? new CompanySetting();
        $oldData = $company->only(['name', 'email', 'phone', 'address', 'logo']);

        $company->name    = $validated['company_name'] ?? $company->name;
        $company->email   = $validated['company_email'] ?? $company->email;
        $company->phone   = $validated['company_phone'] ?? $company->phone;
        $company->address = $validated['company_address'] ?? $company->address;

        if ($request->hasFile('company_logo')) {
            if ($company->logo) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($company->logo);
            }
            $company->logo = $request->file('company_logo')->store('logos', 'public');
        }

        $company->save();

        // Also sync to key-value settings for backward compat
        Setting::setMany([
            'company_name'    => $company->name,
            'company_email'   => $company->email,
            'company_phone'   => $company->phone,
            'company_address' => $company->address,
        ]);

        $newData = $company->only(['name', 'email', 'phone', 'address', 'logo']);

        AuditLogService::log('settings', 'update', 'Updated company profile', $oldData, $newData);

        return back()->with('success', __('messages.settings_saved'));
    }

    // ════════════════════════════════════════════════════════════════
    // TAB: Accounting & Financial
    // ════════════════════════════════════════════════════════════════

    private function financialData(): array
    {
        return [
            'settings' => $this->getSettingsArray([
                'fiscal_year_start_month', 'fiscal_closing_month',
                'system_account_lock', 'opening_balance_date',
                'default_tax_rate', 'default_payment_terms',
                'default_currency', 'timezone',
            ]),
            'currencies' => $this->getCurrencies(),
            'timezones'  => timezone_identifiers_list(),
        ];
    }

    public function updateFinancial(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'fiscal_year_start_month' => 'required|integer|min:1|max:12',
            'fiscal_closing_month'    => 'required|integer|min:1|max:12',
            'system_account_lock'     => 'required|boolean',
            'opening_balance_date'    => 'nullable|date',
            'default_tax_rate'        => 'required|numeric|min:0|max:100',
            'default_payment_terms'   => 'required|integer|min:0|max:365',
            'default_currency'        => 'required|string|max:10',
            'timezone'                => 'required|string|timezone',
        ]);

        // Auto-sync localization settings when default_currency changes
        $oldCurrency = Setting::get('default_currency', '');
        if ($validated['default_currency'] !== $oldCurrency) {
            $localeDefaults = self::CURRENCY_LOCALE_MAP[$validated['default_currency']] ?? null;
            if ($localeDefaults) {
                Setting::setMany($localeDefaults);
            }
        }

        return $this->saveSettings($validated, 'Updated accounting & financial settings', 'financial');
    }

    // ════════════════════════════════════════════════════════════════
    // TAB: HR & Payroll
    // ════════════════════════════════════════════════════════════════

    private function payrollData(): array
    {
        return [
            'settings' => $this->getSettingsArray([
                'bpjs_jht_company', 'bpjs_jht_employee',
                'bpjs_jkk_rate', 'bpjs_jkm_rate',
                'bpjs_jp_company', 'bpjs_jp_employee', 'bpjs_jp_max_salary',
                'bpjs_kes_company', 'bpjs_kes_employee',
                'bpjs_kes_min_salary', 'bpjs_kes_max_salary',
                'standard_work_hours', 'late_tolerance_minutes',
                'late_deduction_per_minute',
                'nik_min_length', 'nik_max_length',
                'bank_account_min_length', 'bank_account_max_length',
            ]),
            'shifts'      => Shift::orderBy('name')->get(),
            'departments' => Department::orderBy('name')->get(),
            'positions'   => Position::orderBy('name')->get(),
        ];
    }

    public function updatePayroll(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'bpjs_jht_company'       => 'required|numeric|min:0|max:100',
            'bpjs_jht_employee'      => 'required|numeric|min:0|max:100',
            'bpjs_jkk_rate'          => 'required|numeric|min:0|max:100',
            'bpjs_jkm_rate'          => 'required|numeric|min:0|max:100',
            'bpjs_jp_company'        => 'required|numeric|min:0|max:100',
            'bpjs_jp_employee'       => 'required|numeric|min:0|max:100',
            'bpjs_jp_max_salary'     => 'required|numeric|min:0',
            'bpjs_kes_company'       => 'required|numeric|min:0|max:100',
            'bpjs_kes_employee'      => 'required|numeric|min:0|max:100',
            'bpjs_kes_min_salary'    => 'required|numeric|min:0',
            'bpjs_kes_max_salary'    => 'required|numeric|min:0',
            'standard_work_hours'    => 'required|integer|min:1|max:24',
            'late_tolerance_minutes' => 'required|integer|min:0|max:120',
            'late_deduction_per_minute' => 'required|numeric|min:0',
            'nik_min_length'         => 'required|integer|min:1|max:30',
            'nik_max_length'         => 'required|integer|min:1|max:30|gte:nik_min_length',
            'bank_account_min_length'=> 'required|integer|min:1|max:30',
            'bank_account_max_length'=> 'required|integer|min:1|max:30|gte:bank_account_min_length',
        ]);

        return $this->saveSettings($validated, 'Updated HR & payroll settings', 'payroll');
    }

    // ════════════════════════════════════════════════════════════════
    // TAB: System & Security
    // ════════════════════════════════════════════════════════════════

    private function securityData(): array
    {
        return [
            'settings' => $this->getSettingsArray([
                'session_lifetime_minutes',
                'mandatory_2fa_admin',
                'api_rate_limit_per_minute',
                'low_stock_threshold',
                'items_per_page',
            ]),
        ];
    }

    public function updateSecurity(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'session_lifetime_minutes'  => 'required|integer|min:5|max:1440',
            'mandatory_2fa_admin'       => 'required|boolean',
            'api_rate_limit_per_minute' => 'required|integer|min:10|max:1000',
            'low_stock_threshold'       => 'required|integer|min:0',
            'items_per_page'            => 'required|integer|in:10,15,25,50,100',
        ]);

        return $this->saveSettings($validated, 'Updated system & security settings', 'security');
    }

    // ════════════════════════════════════════════════════════════════
    // TAB: Localization
    // ════════════════════════════════════════════════════════════════

    private function localizationData(): array
    {
        return [
            'settings' => $this->getSettingsArray([
                'currency_symbol', 'thousand_separator',
                'decimal_separator', 'decimal_places',
                'default_locale',
            ]),
        ];
    }

    public function updateLocalization(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'currency_symbol'     => 'required|string|max:10',
            'thousand_separator'  => 'required|string|max:1',
            'decimal_separator'   => 'required|string|max:1',
            'decimal_places'      => 'required|integer|min:0|max:4',
            'default_locale'      => 'required|string|in:en,id,ko,zh',
        ]);

        return $this->saveSettings($validated, 'Updated localization settings', 'localization');
    }

    // ════════════════════════════════════════════════════════════════
    // TAB: CRM
    // ════════════════════════════════════════════════════════════════

    private function crmData(): array
    {
        return [
            'settings' => $this->getSettingsArray([
                'lead_followup_days',
                'lead_escalation_days',
                'lead_followup_email_enabled',
            ]),
        ];
    }

    public function updateCrm(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'lead_followup_days'          => 'required|integer|min:1|max:90',
            'lead_escalation_days'        => 'required|integer|min:2|max:180',
            'lead_followup_email_enabled' => 'required|boolean',
        ]);

        if ((int) $validated['lead_escalation_days'] <= (int) $validated['lead_followup_days']) {
            return back()->withErrors(['lead_escalation_days' => __('messages.escalation_must_exceed_followup')])->withInput();
        }

        return $this->saveSettings($validated, 'Updated CRM settings', 'crm');
    }

    // ════════════════════════════════════════════════════════════════
    // HELPERS
    // ════════════════════════════════════════════════════════════════

    private function saveSettings(array $validated, string $description, string $tab): RedirectResponse
    {
        $oldData = [];
        foreach (array_keys($validated) as $key) {
            $oldData[$key] = Setting::get($key);
        }

        $newData = array_map(fn($v) => (string) $v, $validated);
        Setting::setMany($newData);

        // Force cache bust
        Cache::forget('app_settings');

        AuditLogService::log('settings', 'update', $description, $oldData, $newData);

        return redirect()
            ->route('settings.index', ['tab' => $tab])
            ->with('success', __('messages.settings_saved'));
    }

    private function getSettingsArray(array $keys): array
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = Setting::get($key, '');
        }
        return $result;
    }

    private function getCurrencies(): array
    {
        return [
            'IDR' => 'Rp - Indonesian Rupiah',
            'USD' => '$ - US Dollar',
            'CNY' => '¥ - Chinese Yuan',
            'KRW' => '₩ - Korean Won',
            'EUR' => '€ - Euro',
            'GBP' => '£ - British Pound',
            'JPY' => '¥ - Japanese Yen',
            'SGD' => 'S$ - Singapore Dollar',
            'MYR' => 'RM - Malaysian Ringgit',
            'AUD' => 'A$ - Australian Dollar',
        ];
    }

    // ════════════════════════════════════════════════════════════════
    // LEGACY — Keep for backward compat (redirects to new system)
    // ════════════════════════════════════════════════════════════════

    public function update(Request $request): RedirectResponse
    {
        // Delegate to financial tab for legacy POST
        return $this->updateFinancial($request);
    }

    // ════════════════════════════════════════════════════════════════
    // SHIFT CRUD (under Payroll tab)
    // ════════════════════════════════════════════════════════════════

    public function storeShift(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'              => 'required|string|max:50',
            'start_time'        => 'required|date_format:H:i',
            'end_time'          => 'required|date_format:H:i',
            'grace_period'      => 'required|integer|min:0|max:120',
            'is_night_shift'    => 'nullable|boolean',
            'night_shift_bonus' => 'required|numeric|min:0',
        ]);

        $validated['is_night_shift'] = $request->boolean('is_night_shift');

        $shift = Shift::create($validated);

        AuditLogService::log('settings', 'create', "Created shift: {$shift->name}", [], $shift->toArray());

        return redirect()
            ->route('settings.index', ['tab' => 'payroll'])
            ->with('success', __('messages.shift_created'));
    }

    public function updateShift(Request $request, Shift $shift): RedirectResponse
    {
        $validated = $request->validate([
            'name'              => 'required|string|max:50',
            'start_time'        => 'required|date_format:H:i',
            'end_time'          => 'required|date_format:H:i',
            'grace_period'      => 'required|integer|min:0|max:120',
            'is_night_shift'    => 'nullable|boolean',
            'night_shift_bonus' => 'required|numeric|min:0',
            'is_active'         => 'nullable|boolean',
        ]);

        $validated['is_night_shift'] = $request->boolean('is_night_shift');
        $validated['is_active']      = $request->boolean('is_active');

        $oldData = $shift->toArray();
        $shift->update($validated);

        AuditLogService::log('settings', 'update', "Updated shift: {$shift->name}", $oldData, $shift->fresh()->toArray());

        return redirect()
            ->route('settings.index', ['tab' => 'payroll'])
            ->with('success', __('messages.shift_updated'));
    }

    public function destroyShift(Shift $shift): RedirectResponse
    {
        $oldData = $shift->toArray();
        $shift->delete();

        AuditLogService::log('settings', 'delete', "Deleted shift: {$oldData['name']}", $oldData, []);

        return redirect()
            ->route('settings.index', ['tab' => 'payroll'])
            ->with('success', __('messages.shift_deleted'));
    }

    // ════════════════════════════════════════════════════════════════
    // DEPARTMENT CRUD (under Payroll tab)
    // ════════════════════════════════════════════════════════════════

    public function storeDepartment(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:20|alpha_num:ascii|uppercase|unique:departments,code',
        ]);

        $dept = Department::create($validated + ['is_active' => true]);

        AuditLogService::log('settings', 'create', "Created department: [{$dept->code}] {$dept->name}", null, $dept->toArray(), $dept);

        return redirect()
            ->route('settings.index', ['tab' => 'payroll'])
            ->with('success', __('messages.department_created'));
    }

    public function updateDepartment(Request $request, Department $department): RedirectResponse
    {
        $validated = $request->validate([
            'name'      => 'required|string|max:100',
            'is_active' => 'nullable|boolean',
        ]);

        // Code is read-only after creation — not accepted from the request
        $validated['is_active'] = $request->boolean('is_active');
        $oldData = $department->toArray();
        $department->update($validated);

        AuditLogService::log('settings', 'update', "Updated department: [{$department->code}] {$department->name}", $oldData, $department->fresh()->toArray(), $department);

        return redirect()
            ->route('settings.index', ['tab' => 'payroll'])
            ->with('success', __('messages.department_updated'));
    }

    public function destroyDepartment(Department $department): RedirectResponse
    {
        $employeeCount = $department->employeeCount();

        if ($employeeCount > 0) {
            // Soft delete: deactivate instead of hard-deleting
            $oldData = $department->toArray();
            $department->update(['is_active' => false]);

            AuditLogService::log('settings', 'update', "Deactivated department: [{$department->code}] {$department->name} ({$employeeCount} employees)", $oldData, $department->fresh()->toArray(), $department);

            return redirect()
                ->route('settings.index', ['tab' => 'payroll'])
                ->with('success', __('messages.department_deactivated'));
        }

        $oldData = $department->toArray();
        $department->delete();

        AuditLogService::log('settings', 'delete', "Deleted department: [{$oldData['code']}] {$oldData['name']}", $oldData, null);

        return redirect()
            ->route('settings.index', ['tab' => 'payroll'])
            ->with('success', __('messages.department_deleted'));
    }

    // ════════════════════════════════════════════════════════════════
    // POSITION CRUD (under Payroll tab)
    // ════════════════════════════════════════════════════════════════

    public function storePosition(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:20|alpha_num:ascii|uppercase|unique:positions,code',
        ]);

        $pos = Position::create($validated + ['is_active' => true]);

        AuditLogService::log('settings', 'create', "Created position: [{$pos->code}] {$pos->name}", null, $pos->toArray(), $pos);

        return redirect()
            ->route('settings.index', ['tab' => 'payroll'])
            ->with('success', __('messages.position_created'));
    }

    public function updatePosition(Request $request, Position $position): RedirectResponse
    {
        $validated = $request->validate([
            'name'      => 'required|string|max:100',
            'is_active' => 'nullable|boolean',
        ]);

        // Code is read-only after creation — not accepted from the request
        $validated['is_active'] = $request->boolean('is_active');
        $oldData = $position->toArray();
        $position->update($validated);

        AuditLogService::log('settings', 'update', "Updated position: [{$position->code}] {$position->name}", $oldData, $position->fresh()->toArray(), $position);

        return redirect()
            ->route('settings.index', ['tab' => 'payroll'])
            ->with('success', __('messages.position_updated'));
    }

    public function destroyPosition(Position $position): RedirectResponse
    {
        $employeeCount = $position->employeeCount();

        if ($employeeCount > 0) {
            // Soft delete: deactivate instead of hard-deleting
            $oldData = $position->toArray();
            $position->update(['is_active' => false]);

            AuditLogService::log('settings', 'update', "Deactivated position: [{$position->code}] {$position->name} ({$employeeCount} employees)", $oldData, $position->fresh()->toArray(), $position);

            return redirect()
                ->route('settings.index', ['tab' => 'payroll'])
                ->with('success', __('messages.position_deactivated'));
        }

        $oldData = $position->toArray();
        $position->delete();

        AuditLogService::log('settings', 'delete', "Deleted position: [{$oldData['code']}] {$oldData['name']}", $oldData, null);

        return redirect()
            ->route('settings.index', ['tab' => 'payroll'])
            ->with('success', __('messages.position_deleted'));
    }
}
