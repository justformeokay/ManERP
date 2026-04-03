<?php

namespace App\Services;

use App\Models\ChartOfAccount;
use App\Models\Employee;
use App\Models\Payslip;
use App\Models\PayslipItem;
use App\Models\PayrollPeriod;
use App\Models\Pph21TerRate;
use App\Models\JournalEntry;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PayrollService
{
    /*
    |--------------------------------------------------------------------------
    | BPJS Rates & Caps (per January 2024 — latest regulations)
    |--------------------------------------------------------------------------
    */

    // BPJS Ketenagakerjaan
    private const JHT_COMPANY  = 0.037;   // 3.7%
    private const JHT_EMPLOYEE = 0.02;    // 2%
    private const JKK_RATE     = 0.0024;  // 0.24% (Tingkat risiko rendah — configurable)
    private const JKM_RATE     = 0.003;   // 0.3%
    private const JP_COMPANY   = 0.02;    // 2%
    private const JP_EMPLOYEE  = 0.01;    // 1%
    private const JP_MAX_SALARY = 10042300; // Batas upah JP 2024 (updated annually by BPJS)

    // BPJS Kesehatan
    private const BPJS_KES_COMPANY  = 0.04;      // 4%
    private const BPJS_KES_EMPLOYEE = 0.01;       // 1%
    private const BPJS_KES_MIN_SALARY = 2942421;  // UMP DKI 2024 as floor
    private const BPJS_KES_MAX_SALARY = 12000000; // Batas atas BPJS Kesehatan

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
        // ── BPJS Ketenagakerjaan ──
        $jhtCompany  = round($monthlyFixed * self::JHT_COMPANY, 2);
        $jhtEmployee = round($monthlyFixed * self::JHT_EMPLOYEE, 2);
        $jkkCompany  = round($monthlyFixed * self::JKK_RATE, 2);
        $jkmCompany  = round($monthlyFixed * self::JKM_RATE, 2);

        // JP — capped at maximum salary
        $jpBase      = min($monthlyFixed, self::JP_MAX_SALARY);
        $jpCompany   = round($jpBase * self::JP_COMPANY, 2);
        $jpEmployee  = round($jpBase * self::JP_EMPLOYEE, 2);

        // ── BPJS Kesehatan ──
        $kesBase     = max(
            self::BPJS_KES_MIN_SALARY,
            min($grossSalary, self::BPJS_KES_MAX_SALARY)
        );
        $kesCompany  = round($kesBase * self::BPJS_KES_COMPANY, 2);
        $kesEmployee = round($kesBase * self::BPJS_KES_EMPLOYEE, 2);

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
        string $ptkpStatus = 'TK/0'
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
            $ptkpStatus
        );
    }

    /**
     * December PPh 21 — calculate annual tax using Pasal 17 progressive rates,
     * then subtract what has already been withheld Jan–Nov.
     */
    private function calculatePph21December(
        float $annualGross,
        float $pph21PaidJanNov,
        string $ptkpStatus
    ): float {
        // Biaya Jabatan = 5% of annual gross, max 6.000.000/year
        $biayaJabatan = min($annualGross * 0.05, 6000000);

        // BPJS JHT employee = 2% (already deducted monthly, annualize it)
        // For simplicity, we approximate annual JHT employee deduction
        $annualJhtEmployee = $annualGross * self::JHT_EMPLOYEE;
        $annualJpEmployee  = min($annualGross, self::JP_MAX_SALARY * 12) * self::JP_EMPLOYEE;

        // Penghasilan Neto = Gross - Biaya Jabatan - BPJS (employee portion)
        $penghNeto = $annualGross - $biayaJabatan - $annualJhtEmployee - $annualJpEmployee;

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
     */
    public function generatePayslip(
        PayrollPeriod $period,
        Employee $employee,
        \App\Models\SalaryStructure $salary,
        array $override = []
    ): Payslip {
        // ── Earnings ──
        $basicSalary    = (float) $salary->basic_salary;
        $fixedAllowance = (float) $salary->fixed_allowance;
        $mealAllowance  = (float) $salary->meal_allowance;
        $transportAllow = (float) $salary->transport_allowance;
        $overtimeHours  = (float) ($override['overtime_hours'] ?? 0);
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

        if ($period->month == 12) {
            $prior = Payslip::where('employee_id', $employee->id)
                ->whereHas('payrollPeriod', function ($q) use ($period) {
                    $q->where('year', $period->year)
                      ->where('month', '<', 12);
                })
                ->selectRaw('COALESCE(SUM(gross_salary), 0) as total_gross, COALESCE(SUM(pph21_amount), 0) as total_pph21')
                ->first();

            $annualGrossSoFar = (float) ($prior->total_gross ?? 0);
            $pph21PaidSoFar   = (float) ($prior->total_pph21 ?? 0);
        }

        $pph21 = $this->calculatePph21TER(
            $employee->ter_category,
            $brutoForPph,
            $period->month,
            $annualGrossSoFar,
            $pph21PaidSoFar,
            $employee->ptkp_status
        );

        // ── Other Deductions ──
        $loanDeduction    = (float) ($override['loan_deduction'] ?? 0);
        $absenceDeduction = (float) ($override['absence_deduction'] ?? 0);
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
            'BPJS JHT (2%)'     => $bpjs['jht_employee'],
            'BPJS JP (1%)'      => $bpjs['jp_employee'],
            'BPJS Kesehatan (1%)' => $bpjs['kes_employee'],
            'PPh 21'             => $pph21,
            'Potongan Kasbon'    => (float) ($override['loan_deduction'] ?? 0),
            'Potongan Absensi'   => (float) ($override['absence_deduction'] ?? 0),
            'Potongan Lainnya'   => (float) ($override['other_deductions'] ?? 0),
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
            throw new InvalidArgumentException('No payslips found for this period.');
        }

        // ── Aggregate amounts ──
        $totalBasicOT   = round($payslips->sum(fn($p) => (float) $p->basic_salary + (float) $p->overtime_amount), 2);
        $totalAllowance = round($payslips->sum(fn($p) => (float) $p->fixed_allowance + (float) $p->meal_allowance + (float) $p->transport_allowance + (float) $p->other_earnings), 2);
        $totalBpjsCo    = round($payslips->sum(fn($p) => $p->total_bpjs_company), 2);
        $totalNet       = round($payslips->sum('net_salary'), 2);
        $totalPph21     = round($payslips->sum('pph21_amount'), 2);
        $totalBpjsAll   = round($payslips->sum(fn($p) => $p->total_bpjs_company + $p->total_bpjs_employee), 2);

        // ── Resolve COA accounts ──
        $accounts = $this->resolvePayrollAccounts();

        $totalDebit  = round($totalBasicOT + $totalAllowance + $totalBpjsCo, 2);
        $totalCredit = round($totalNet + $totalPph21 + $totalBpjsAll, 2);

        // Balance check — adjust rounding differences to payroll payable
        $diff = round($totalDebit - $totalCredit, 2);
        if (abs($diff) > 0 && abs($diff) <= 1) {
            $totalNet = round($totalNet + $diff, 2);
        }

        $date = sprintf('%04d-%02d-%02d', $period->year, $period->month,
            cal_days_in_month(CAL_GREGORIAN, $period->month, $period->year));

        $entries = [];

        // Debits
        if ($totalBasicOT > 0) {
            $entries[] = ['account_id' => $accounts['salary']->id,    'debit' => $totalBasicOT,   'credit' => 0];
        }
        if ($totalAllowance > 0) {
            $entries[] = ['account_id' => $accounts['allowance']->id, 'debit' => $totalAllowance, 'credit' => 0];
        }
        if ($totalBpjsCo > 0) {
            $entries[] = ['account_id' => $accounts['bpjs_exp']->id,  'debit' => $totalBpjsCo,    'credit' => 0];
        }

        // Credits
        if ($totalNet > 0) {
            $entries[] = ['account_id' => $accounts['payable']->id,     'debit' => 0, 'credit' => $totalNet];
        }
        if ($totalPph21 > 0) {
            $entries[] = ['account_id' => $accounts['pph21']->id,       'debit' => 0, 'credit' => $totalPph21];
        }
        if ($totalBpjsAll > 0) {
            $entries[] = ['account_id' => $accounts['bpjs_payable']->id,'debit' => 0, 'credit' => $totalBpjsAll];
        }

        $reference = sprintf('PAYROLL-%04d-%02d', $period->year, $period->month);

        return $this->accountingService->createJournalEntry(
            $reference,
            $date,
            "Payroll {$period->period_label}",
            $entries
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
                throw new InvalidArgumentException("COA account {$code} not found. Please run the HR migration.");
            }
            $accounts[$key] = $account;
        }

        return $accounts;
    }
}
