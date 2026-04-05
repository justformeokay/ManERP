<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\ChartOfAccount;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\Payslip;
use App\Models\PayslipItem;
use App\Models\PayrollPeriod;
use App\Models\Pph21TerRate;
use App\Models\JournalEntry;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PayrollService
{
    /*
    |--------------------------------------------------------------------------
    | BPJS Rates & Caps — loaded from Settings (admin-configurable)
    |--------------------------------------------------------------------------
    */

    private function bpjsRate(string $key, float $default): float
    {
        return (float) Setting::get($key, $default) / 100;
    }

    private function bpjsCap(string $key, float $default): float
    {
        return (float) Setting::get($key, $default);
    }

    /*
    |--------------------------------------------------------------------------
    | COA Account Codes for Auto-Journal
    |--------------------------------------------------------------------------
    */
    private const COA_SALARY_EXPENSE     = '5100';
    private const COA_ALLOWANCE_EXPENSE  = '5110';
    private const COA_BPJS_EXPENSE       = '5120';
    private const COA_PAYROLL_PAYABLE    = '2110';
    private const COA_PPH21_PAYABLE      = '2120';
    private const COA_BPJS_PAYABLE       = '2130';

    public function __construct(private AccountingService $accountingService) {}

    /*
    |--------------------------------------------------------------------------
    | BPJS Calculation
    |--------------------------------------------------------------------------
    */

    /**
     * Calculate all BPJS contributions for an employee.
     *
     * @param float $monthlyFixed  Gaji pokok + tunjangan tetap (basis BPJS TK)
     * @param float $grossSalary   Total penghasilan bruto (basis BPJS Kesehatan)
     * @return array{
     *   jht_company: float, jht_employee: float,
     *   jkk_company: float, jkm_company: float,
     *   jp_company: float, jp_employee: float,
     *   kes_company: float, kes_employee: float,
     *   total_company: float, total_employee: float
     * }
     */
    public function calculateBpjs(float $monthlyFixed, float $grossSalary): array
    {
        // ── BPJS Ketenagakerjaan (rates from Settings) ──
        $jhtCompany  = round($monthlyFixed * $this->bpjsRate('bpjs_jht_company', 3.7), 2);
        $jhtEmployee = round($monthlyFixed * $this->bpjsRate('bpjs_jht_employee', 2), 2);
        $jkkCompany  = round($monthlyFixed * $this->bpjsRate('bpjs_jkk_rate', 0.24), 2);
        $jkmCompany  = round($monthlyFixed * $this->bpjsRate('bpjs_jkm_rate', 0.3), 2);

        // JP — capped at maximum salary (from Settings)
        $jpBase      = min($monthlyFixed, $this->bpjsCap('bpjs_jp_max_salary', 10042300));
        $jpCompany   = round($jpBase * $this->bpjsRate('bpjs_jp_company', 2), 2);
        $jpEmployee  = round($jpBase * $this->bpjsRate('bpjs_jp_employee', 1), 2);

        // ── BPJS Kesehatan (rates from Settings) ──
        $kesBase     = max(
            $this->bpjsCap('bpjs_kes_min_salary', 2942421),
            min($grossSalary, $this->bpjsCap('bpjs_kes_max_salary', 12000000))
        );
        $kesCompany  = round($kesBase * $this->bpjsRate('bpjs_kes_company', 4), 2);
        $kesEmployee = round($kesBase * $this->bpjsRate('bpjs_kes_employee', 1), 2);

        return [
            'jht_company'    => $jhtCompany,
            'jht_employee'   => $jhtEmployee,
            'jkk_company'    => $jkkCompany,
            'jkm_company'    => $jkmCompany,
            'jp_company'     => $jpCompany,
            'jp_employee'    => $jpEmployee,
            'kes_company'    => $kesCompany,
            'kes_employee'   => $kesEmployee,
            'total_company'  => round($jhtCompany + $jkkCompany + $jkmCompany + $jpCompany + $kesCompany, 2),
            'total_employee' => round($jhtEmployee + $jpEmployee + $kesEmployee, 2),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | PPh 21 — TER Method (PMK 168/2023, effective Jan 2024)
    |--------------------------------------------------------------------------
    |
    | Months 1-11: PPh 21 = Gross Bruto × TER rate
    | Month 12 (December): PPh 21 = Annual Tax (Pasal 17) − Σ PPh21(Jan..Nov)
    |
    */

    /**
     * Calculate PPh 21 using TER method for a single month.
     *
     * @param string $terCategory  A, B, or C
     * @param float  $grossBruto   Monthly gross income (bruto)
     * @param int    $month        1-12
     * @param float  $annualGrossSoFar  Cumulative gross Jan..current (for Dec calculation)
     * @param float  $pph21PaidSoFar    Cumulative PPh21 Jan..Nov (for Dec calculation)
     * @param string $ptkpStatus   PTKP status (for Dec Pasal 17 calculation)
     */
    public function calculatePph21TER(
        string $terCategory,
        float $grossBruto,
        int $month = 1,
        float $annualGrossSoFar = 0,
        float $pph21PaidSoFar = 0,
        string $ptkpStatus = 'TK/0',
        float $annualBpjsEmployee = 0
    ): float {
        if ($month < 12) {
            // ── TER Method (Jan–Nov) ──
            $rate = Pph21TerRate::getRate($terCategory, $grossBruto);
            return round($grossBruto * $rate, 2);
        }

        // ── December: Adjusted calculation using Pasal 17 brackets ──
        return $this->calculatePph21December(
            $annualGrossSoFar + $grossBruto,
            $pph21PaidSoFar,
            $ptkpStatus,
            $annualBpjsEmployee
        );
    }

    /**
     * December PPh 21 — calculate annual tax using Pasal 17 progressive rates,
     * then subtract what has already been withheld Jan–Nov.
     */
    private function calculatePph21December(
        float $annualGross,
        float $pph21PaidJanNov,
        string $ptkpStatus,
        float $annualBpjsEmployee = 0
    ): float {
        // Biaya Jabatan = 5% of annual gross, max 6.000.000/year
        $biayaJabatan = min($annualGross * 0.05, 6000000);

        // BPJS employee deductions (JHT + JP) — use actual YTD amounts from payslips
        // instead of approximating from gross (which over-estimates for variable earnings)
        $bpjsEmployeeDeduction = $annualBpjsEmployee;

        // Penghasilan Neto = Gross - Biaya Jabatan - BPJS (employee portion)
        $penghNeto = $annualGross - $biayaJabatan - $bpjsEmployeeDeduction;

        // PTKP
        $ptkpAmount = Employee::PTKP_AMOUNTS[$ptkpStatus] ?? 54000000;

        // PKP (Penghasilan Kena Pajak)
        $pkp = max($penghNeto - $ptkpAmount, 0);

        // Pasal 17 progressive tax brackets
        $annualTax = $this->calculatePasal17($pkp);

        // PPh 21 for December = Annual Tax - already withheld
        return round(max($annualTax - $pph21PaidJanNov, 0), 2);
    }

    /**
     * PPh 21 Pasal 17 progressive brackets (UU HPP 7/2021).
     */
    private function calculatePasal17(float $pkp): float
    {
        $brackets = [
            [60000000,   0.05],
            [250000000,  0.15],
            [500000000,  0.25],
            [5000000000, 0.30],
            [PHP_FLOAT_MAX, 0.35],
        ];

        $tax = 0;
        $remaining = $pkp;

        $prevLimit = 0;
        foreach ($brackets as [$limit, $rate]) {
            $bracketSize = $limit - $prevLimit;
            $taxable = min($remaining, $bracketSize);

            if ($taxable <= 0) break;

            $tax += $taxable * $rate;
            $remaining -= $taxable;
            $prevLimit = $limit;
        }

        return round($tax, 2);
    }

    /*
    |--------------------------------------------------------------------------
    | Payslip Generation
    |--------------------------------------------------------------------------
    */

    /**
     * Generate payslips for all active employees in a payroll period.
     *
     * @param PayrollPeriod $period
     * @param array         $overrides  Per-employee overrides keyed by employee_id
     *                                  e.g. [1 => ['overtime_hours' => 10, 'loan_deduction' => 500000]]
     */
    public function generatePayslips(PayrollPeriod $period, array $overrides = []): int
    {
        if ($period->status !== 'draft') {
            throw new InvalidArgumentException(
                __('messages.payroll_not_draft')
            );
        }

        $employees = Employee::active()
            ->with('salaryStructures')
            ->get();

        $count = 0;

        DB::transaction(function () use ($period, $employees, $overrides, &$count) {
            foreach ($employees as $employee) {
                $salary = $employee->getActiveSalaryStructure();
                if (!$salary) continue;

                $override = $overrides[$employee->id] ?? [];

                $this->generatePayslip($period, $employee, $salary, $override);
                $count++;
            }

            $period->recalculateTotals();
        });

        return $count;
    }

    /**
     * Generate a single payslip for one employee.
     * Integrates attendance data for overtime and absence deduction.
     */
    public function generatePayslip(
        PayrollPeriod $period,
        Employee $employee,
        \App\Models\SalaryStructure $salary,
        array $override = []
    ): Payslip {
        // ── Attendance Integration ──
        $attendanceData = $this->getAttendanceData($employee->id, $period->month, $period->year);

        // ── Earnings ──
        $basicSalary    = (float) $salary->basic_salary;
        $fixedAllowance = (float) $salary->fixed_allowance;
        $mealAllowance  = (float) $salary->meal_allowance;
        $transportAllow = (float) $salary->transport_allowance;

        // Overtime: attendance-based, fallback to manual override
        $overtimeHours  = $attendanceData['overtime_hours'] > 0
            ? $attendanceData['overtime_hours']
            : (float) ($override['overtime_hours'] ?? 0);
        $overtimeAmount = round($overtimeHours * (float) $salary->overtime_rate, 2);
        $otherEarnings  = (float) ($override['other_earnings'] ?? 0);

        $grossSalary = round(
            $basicSalary + $fixedAllowance + $mealAllowance + $transportAllow
            + $overtimeAmount + $otherEarnings,
            2
        );

        // ── BPJS ──
        $monthlyFixed = $basicSalary + $fixedAllowance;
        $bpjs = $this->calculateBpjs($monthlyFixed, $grossSalary);

        // ── PPh 21 TER ──
        // Bruto for PPh21 = gross + BPJS company (tunjangan pajak nature)
        // Per PMK 168/2023: bruto includes all income components
        $brutoForPph = $grossSalary;

        // For December, we need cumulative data
        $annualGrossSoFar = 0;
        $pph21PaidSoFar   = 0;
        $annualBpjsEmployee = 0;

        if ($period->month == 12) {
            $prior = Payslip::where('employee_id', $employee->id)
                ->whereHas('payrollPeriod', function ($q) use ($period) {
                    $q->where('year', $period->year)
                      ->where('month', '<', 12);
                })
                ->selectRaw('COALESCE(SUM(gross_salary), 0) as total_gross, COALESCE(SUM(pph21_amount), 0) as total_pph21, COALESCE(SUM(bpjs_jht_employee + bpjs_jp_employee), 0) as total_bpjs_emp')
                ->first();

            $annualGrossSoFar = (float) ($prior->total_gross ?? 0);
            $pph21PaidSoFar   = (float) ($prior->total_pph21 ?? 0);
            $annualBpjsEmployee = (float) ($prior->total_bpjs_emp ?? 0);
            // Add current month's BPJS employee
            $annualBpjsEmployee += $bpjs['jht_employee'] + $bpjs['jp_employee'];
        }

        $pph21 = $this->calculatePph21TER(
            $employee->ter_category,
            $brutoForPph,
            $period->month,
            $annualGrossSoFar,
            $pph21PaidSoFar,
            $employee->ptkp_status,
            $annualBpjsEmployee
        );

        // ── Other Deductions ──
        $loanDeduction    = (float) ($override['loan_deduction'] ?? 0);

        // Absence deduction: absent days lose proportional basic + meal + transport (bcmath)
        $workingDays = (string) $this->getWorkingDaysInMonth($period->month, $period->year);
        $absentDays  = $attendanceData['absent_days'];

        if ($absentDays > 0 && !isset($override['absence_deduction'])) {
            $dailyBasic     = bcdiv((string) $basicSalary,    $workingDays, 2);
            $dailyMeal      = bcdiv((string) $mealAllowance,  $workingDays, 2);
            $dailyTransport = bcdiv((string) $transportAllow, $workingDays, 2);
            $dailyTotal     = bcadd($dailyBasic, bcadd($dailyMeal, $dailyTransport, 2), 2);
            $absenceDeduction = (float) bcmul((string) $absentDays, $dailyTotal, 2);
        } else {
            $absenceDeduction = (float) ($override['absence_deduction'] ?? 0);
        }

        $otherDeductions  = (float) ($override['other_deductions'] ?? 0);

        // ── Total Deductions (employee portions only) ──
        $totalDeductions = round(
            $bpjs['jht_employee'] + $bpjs['jp_employee'] + $bpjs['kes_employee']
            + $pph21
            + $loanDeduction + $absenceDeduction + $otherDeductions,
            2
        );

        $netSalary = round($grossSalary - $totalDeductions, 2);

        // ── Create/Update Payslip ──
        $payslip = Payslip::updateOrCreate(
            [
                'payroll_period_id' => $period->id,
                'employee_id'       => $employee->id,
            ],
            [
                'basic_salary'        => $basicSalary,
                'fixed_allowance'     => $fixedAllowance,
                'meal_allowance'      => $mealAllowance,
                'transport_allowance' => $transportAllow,
                'overtime_hours'      => $overtimeHours,
                'overtime_amount'     => $overtimeAmount,
                'other_earnings'      => $otherEarnings,
                'gross_salary'        => $grossSalary,
                // BPJS Company
                'bpjs_jht_company'    => $bpjs['jht_company'],
                'bpjs_jkk_company'    => $bpjs['jkk_company'],
                'bpjs_jkm_company'    => $bpjs['jkm_company'],
                'bpjs_jp_company'     => $bpjs['jp_company'],
                'bpjs_kes_company'    => $bpjs['kes_company'],
                // BPJS Employee
                'bpjs_jht_employee'   => $bpjs['jht_employee'],
                'bpjs_jp_employee'    => $bpjs['jp_employee'],
                'bpjs_kes_employee'   => $bpjs['kes_employee'],
                // PPh 21
                'pph21_amount'        => $pph21,
                // Deductions
                'loan_deduction'      => $loanDeduction,
                'absence_deduction'   => $absenceDeduction,
                'other_deductions'    => $otherDeductions,
                'total_deductions'    => $totalDeductions,
                'net_salary'          => $netSalary,
            ]
        );

        // ── Build detail items ──
        $this->buildPayslipItems($payslip, $bpjs, $pph21, $override);

        return $payslip;
    }

    /**
     * Create line-item breakdown for payslip display.
     */
    private function buildPayslipItems(Payslip $payslip, array $bpjs, float $pph21, array $override): void
    {
        // Delete existing items and rebuild
        $payslip->items()->delete();

        $items = [];
        $order = 0;

        // ── Earnings ──
        $earnings = [
            'Gaji Pokok'            => $payslip->basic_salary,
            'Tunjangan Tetap'       => $payslip->fixed_allowance,
            'Tunjangan Makan'       => $payslip->meal_allowance,
            'Tunjangan Transport'   => $payslip->transport_allowance,
            'Lembur'                => $payslip->overtime_amount,
            'Pendapatan Lainnya'    => $payslip->other_earnings,
        ];

        foreach ($earnings as $label => $amount) {
            if ((float) $amount > 0) {
                $items[] = [
                    'payslip_id' => $payslip->id,
                    'type'       => 'earning',
                    'label'      => $label,
                    'amount'     => $amount,
                    'sort_order' => ++$order,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        // ── Deductions ──
        $order = 0;
        $deductions = [
            'BPJS JHT (2%)'         => $bpjs['jht_employee'],
            'BPJS JP (1%)'          => $bpjs['jp_employee'],
            'BPJS Kesehatan (1%)'   => $bpjs['kes_employee'],
            'PPh 21'                => $pph21,
            'Potongan Kasbon'       => (float) ($override['loan_deduction'] ?? 0),
            'Potongan Absensi'      => (float) $payslip->absence_deduction,
            'Potongan Lainnya'      => (float) ($override['other_deductions'] ?? 0),
        ];

        foreach ($deductions as $label => $amount) {
            if ((float) $amount > 0) {
                $items[] = [
                    'payslip_id' => $payslip->id,
                    'type'       => 'deduction',
                    'label'      => $label,
                    'amount'     => $amount,
                    'sort_order' => ++$order,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        if (!empty($items)) {
            PayslipItem::insert($items);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Accounting Integration — Auto-Journal on Post
    |--------------------------------------------------------------------------
    */

    /**
     * Post payroll to accounting: create journal entry for the entire period.
     * Uses bcmath for monetary precision and passes sourceable link for drill-down.
     *
     * Debit:
     *   5100 — Beban Gaji & Upah (total basic + overtime)
     *   5110 — Beban Tunjangan Karyawan (allowances)
     *   5120 — Beban BPJS Perusahaan (company BPJS contributions)
     *
     * Credit:
     *   2110 — Utang Gaji / Payroll Payable (net salaries)
     *   2120 — Utang PPh 21 (total PPh 21 withheld)
     *   2130 — Utang BPJS (employee + company BPJS)
     */
    public function postToAccounting(PayrollPeriod $period): JournalEntry
    {
        $payslips = $period->payslips;

        if ($payslips->isEmpty()) {
            throw new InvalidArgumentException(__('messages.payroll_no_payslips'));
        }

        // ── Aggregate amounts using bcmath ──
        $totalBasicOT   = '0';
        $totalAllowance = '0';
        $totalBpjsCo    = '0';
        $totalNet       = '0';
        $totalPph21     = '0';
        $totalBpjsAll   = '0';

        foreach ($payslips as $p) {
            $basicOT = bcadd((string) $p->basic_salary, (string) $p->overtime_amount, 2);
            $totalBasicOT = bcadd($totalBasicOT, $basicOT, 2);

            $allowance = bcadd(
                bcadd((string) $p->fixed_allowance, (string) $p->meal_allowance, 2),
                bcadd((string) $p->transport_allowance, (string) $p->other_earnings, 2),
                2
            );
            $totalAllowance = bcadd($totalAllowance, $allowance, 2);

            $totalBpjsCo = bcadd($totalBpjsCo, (string) $p->total_bpjs_company, 2);
            $totalNet    = bcadd($totalNet, (string) $p->net_salary, 2);
            $totalPph21  = bcadd($totalPph21, (string) $p->pph21_amount, 2);

            $bpjsAll = bcadd((string) $p->total_bpjs_company, (string) $p->total_bpjs_employee, 2);
            $totalBpjsAll = bcadd($totalBpjsAll, $bpjsAll, 2);
        }

        // ── Resolve COA accounts ──
        $accounts = $this->resolvePayrollAccounts();

        $totalDebit  = bcadd(bcadd($totalBasicOT, $totalAllowance, 2), $totalBpjsCo, 2);
        $totalCredit = bcadd(bcadd($totalNet, $totalPph21, 2), $totalBpjsAll, 2);

        // Balance check — adjust rounding differences to payroll payable (≤ 1.00)
        $diff = bcsub($totalDebit, $totalCredit, 2);
        if (bccomp((string) abs((float) $diff), '0', 2) > 0 && bccomp((string) abs((float) $diff), '1.00', 2) <= 0) {
            $totalNet = bcadd($totalNet, $diff, 2);
        }

        $date = Carbon::create($period->year, $period->month, 1)->endOfMonth()->toDateString();

        $entries = [];

        // Debits
        if (bccomp($totalBasicOT, '0', 2) > 0) {
            $entries[] = ['account_id' => $accounts['salary']->id,    'debit' => (float) $totalBasicOT,   'credit' => 0];
        }
        if (bccomp($totalAllowance, '0', 2) > 0) {
            $entries[] = ['account_id' => $accounts['allowance']->id, 'debit' => (float) $totalAllowance, 'credit' => 0];
        }
        if (bccomp($totalBpjsCo, '0', 2) > 0) {
            $entries[] = ['account_id' => $accounts['bpjs_exp']->id,  'debit' => (float) $totalBpjsCo,    'credit' => 0];
        }

        // Credits
        if (bccomp($totalNet, '0', 2) > 0) {
            $entries[] = ['account_id' => $accounts['payable']->id,     'debit' => 0, 'credit' => (float) $totalNet];
        }
        if (bccomp($totalPph21, '0', 2) > 0) {
            $entries[] = ['account_id' => $accounts['pph21']->id,       'debit' => 0, 'credit' => (float) $totalPph21];
        }
        if (bccomp($totalBpjsAll, '0', 2) > 0) {
            $entries[] = ['account_id' => $accounts['bpjs_payable']->id,'debit' => 0, 'credit' => (float) $totalBpjsAll];
        }

        $reference = sprintf('PAYROLL-%04d-%02d', $period->year, $period->month);

        return $this->accountingService->createJournalEntry(
            $reference,
            $date,
            "Payroll {$period->period_label}",
            $entries,
            PayrollPeriod::class,
            $period->id
        );
    }

    /**
     * Resolve COA accounts needed for payroll journal.
     */
    private function resolvePayrollAccounts(): array
    {
        $codes = [
            'salary'       => self::COA_SALARY_EXPENSE,
            'allowance'    => self::COA_ALLOWANCE_EXPENSE,
            'bpjs_exp'     => self::COA_BPJS_EXPENSE,
            'payable'      => self::COA_PAYROLL_PAYABLE,
            'pph21'        => self::COA_PPH21_PAYABLE,
            'bpjs_payable' => self::COA_BPJS_PAYABLE,
        ];

        $accounts = [];
        foreach ($codes as $key => $code) {
            $account = ChartOfAccount::where('code', $code)->first();
            if (!$account) {
                throw new InvalidArgumentException(
                    __('messages.payroll_coa_missing', ['code' => $code])
                );
            }
            $accounts[$key] = $account;
        }

        return $accounts;
    }

    /*
    |--------------------------------------------------------------------------
    | Attendance Integration
    |--------------------------------------------------------------------------
    */

    /**
     * Get attendance summary for an employee in a given month.
     * Returns overtime hours and absent days from the attendances table.
     */
    public function getAttendanceData(int $employeeId, int $month, int $year): array
    {
        $attendances = Attendance::where('employee_id', $employeeId)
            ->forPeriod($month, $year)
            ->get();

        $overtimeHours = $attendances->sum('overtime_hours');
        $absentDays = $attendances->where('status', 'absent')->count();

        return [
            'overtime_hours' => (float) $overtimeHours,
            'absent_days'    => $absentDays,
            'present_days'   => $attendances->whereIn('status', ['present', 'late'])->count(),
            'late_days'      => $attendances->where('status', 'late')->count(),
            'leave_days'     => $attendances->where('status', 'leave')->count(),
        ];
    }

    /**
     * Get number of working days in a month (excludes weekends).
     * Uses Carbon for portability (no calendar extension needed).
     */
    public function getWorkingDaysInMonth(int $month, int $year): int
    {
        $start = Carbon::create($year, $month, 1);
        $end   = $start->copy()->endOfMonth();
        $days  = 0;

        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            if (!$date->isWeekend()) {
                $days++;
            }
        }

        return max($days, 1); // Prevent division by zero
    }
}
