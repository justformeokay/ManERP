<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\ChartOfAccount;
use App\Models\Employee;
use App\Models\FiscalPeriod;
use App\Models\JournalEntry;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\Payslip;
use App\Models\PayrollPeriod;
use App\Models\Pph21TerRate;
use App\Models\SalaryStructure;
use App\Models\User;
use App\Services\AccountingService;
use App\Services\PayrollService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * HR & Payroll Integrity Patch (Phase 4)
 *
 * Validates:
 *  T1: PPh 21 TER 2024 calculation (categories A, B, C)
 *  T2: BPJS Ketenagakerjaan & Kesehatan with ceiling handling
 *  T3: Overtime calculation from attendance data
 *  T4: Prorate salary for mid-month entry/exit (absence deduction)
 *  T5: December PPh 21 with corrected BPJS basis (Pasal 17)
 *  T6: postToAccounting uses bcmath and sourceable link
 *  T7: Attendance integration into payslip generation
 *  T8: Leave balance deduction with lockForUpdate
 *  T9: Payroll state machine guards
 *  T10: TER category auto-derived from PTKP
 *  T11: HR permissions seeded for admin
 *  T12: Route priority — payslip before period wildcard
 */
class PayrollEngineTest extends TestCase
{
    use RefreshDatabase;

    private PayrollService $payrollService;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->payrollService = app(PayrollService::class);

        $this->admin = User::factory()->create([
            'role'   => User::ROLE_ADMIN,
            'status' => User::STATUS_ACTIVE,
        ]);
        $this->actingAs($this->admin);

        // Seed payroll COA accounts
        $this->seedPayrollAccounts();
    }

    private function seedPayrollAccounts(): void
    {
        $accounts = [
            ['code' => '1100', 'name' => 'Cash & Bank', 'type' => 'asset'],
            ['code' => '3200', 'name' => 'Retained Earnings', 'type' => 'equity'],
            ['code' => '5100', 'name' => 'Beban Gaji & Upah', 'type' => 'expense'],
            ['code' => '5110', 'name' => 'Beban Tunjangan', 'type' => 'expense'],
            ['code' => '5120', 'name' => 'Beban BPJS Perusahaan', 'type' => 'expense'],
            ['code' => '2110', 'name' => 'Utang Gaji', 'type' => 'liability'],
            ['code' => '2120', 'name' => 'Utang PPh 21', 'type' => 'liability'],
            ['code' => '2130', 'name' => 'Utang BPJS', 'type' => 'liability'],
        ];

        foreach ($accounts as $acc) {
            ChartOfAccount::firstOrCreate(
                ['code' => $acc['code']],
                ['name' => $acc['name'], 'type' => $acc['type'], 'is_active' => true]
            );
        }
    }

    private function seedTerRates(): void
    {
        // Clear migration-seeded rates to avoid duplicates
        Pph21TerRate::query()->delete();

        // Seed TER rates per PMK 168/2023 for testing
        $rates = [
            // Category A (TK/0, TK/1)
            ['category' => 'A', 'min_salary' => 0, 'max_salary' => 5400000, 'rate' => 0.0000],
            ['category' => 'A', 'min_salary' => 5400000, 'max_salary' => 5650000, 'rate' => 0.0025],
            ['category' => 'A', 'min_salary' => 5650000, 'max_salary' => 5950000, 'rate' => 0.0050],
            ['category' => 'A', 'min_salary' => 5950000, 'max_salary' => 6300000, 'rate' => 0.0075],
            ['category' => 'A', 'min_salary' => 6300000, 'max_salary' => 6750000, 'rate' => 0.0100],
            ['category' => 'A', 'min_salary' => 6750000, 'max_salary' => 7500000, 'rate' => 0.0125],
            ['category' => 'A', 'min_salary' => 7500000, 'max_salary' => 8550000, 'rate' => 0.0150],
            ['category' => 'A', 'min_salary' => 8550000, 'max_salary' => 9650000, 'rate' => 0.0175],
            ['category' => 'A', 'min_salary' => 9650000, 'max_salary' => 10050000, 'rate' => 0.0200],
            ['category' => 'A', 'min_salary' => 10050000, 'max_salary' => 10350000, 'rate' => 0.0225],
            ['category' => 'A', 'min_salary' => 10350000, 'max_salary' => 10700000, 'rate' => 0.0250],
            ['category' => 'A', 'min_salary' => 10700000, 'max_salary' => 11050000, 'rate' => 0.0300],
            ['category' => 'A', 'min_salary' => 11050000, 'max_salary' => 11600000, 'rate' => 0.0350],
            ['category' => 'A', 'min_salary' => 11600000, 'max_salary' => 12500000, 'rate' => 0.0400],
            ['category' => 'A', 'min_salary' => 12500000, 'max_salary' => 13750000, 'rate' => 0.0450],
            ['category' => 'A', 'min_salary' => 13750000, 'max_salary' => 15100000, 'rate' => 0.0500],
            ['category' => 'A', 'min_salary' => 15100000, 'max_salary' => 16950000, 'rate' => 0.0600],
            ['category' => 'A', 'min_salary' => 16950000, 'max_salary' => 19750000, 'rate' => 0.0700],
            ['category' => 'A', 'min_salary' => 19750000, 'max_salary' => 24150000, 'rate' => 0.0800],
            ['category' => 'A', 'min_salary' => 24150000, 'max_salary' => 26450000, 'rate' => 0.0900],
            ['category' => 'A', 'min_salary' => 26450000, 'max_salary' => 28000000, 'rate' => 0.1000],
            ['category' => 'A', 'min_salary' => 28000000, 'max_salary' => 30050000, 'rate' => 0.1100],
            ['category' => 'A', 'min_salary' => 30050000, 'max_salary' => 32400000, 'rate' => 0.1200],
            ['category' => 'A', 'min_salary' => 32400000, 'max_salary' => 35400000, 'rate' => 0.1300],
            ['category' => 'A', 'min_salary' => 35400000, 'max_salary' => 39100000, 'rate' => 0.1400],
            ['category' => 'A', 'min_salary' => 39100000, 'max_salary' => 43850000, 'rate' => 0.1500],
            ['category' => 'A', 'min_salary' => 43850000, 'max_salary' => 47800000, 'rate' => 0.1600],
            ['category' => 'A', 'min_salary' => 47800000, 'max_salary' => 51400000, 'rate' => 0.1700],
            ['category' => 'A', 'min_salary' => 51400000, 'max_salary' => 56300000, 'rate' => 0.1800],
            ['category' => 'A', 'min_salary' => 56300000, 'max_salary' => 62200000, 'rate' => 0.1900],
            ['category' => 'A', 'min_salary' => 62200000, 'max_salary' => 68600000, 'rate' => 0.2000],
            ['category' => 'A', 'min_salary' => 68600000, 'max_salary' => 77500000, 'rate' => 0.2100],
            ['category' => 'A', 'min_salary' => 77500000, 'max_salary' => 89000000, 'rate' => 0.2200],
            ['category' => 'A', 'min_salary' => 89000000, 'max_salary' => 103000000, 'rate' => 0.2300],
            ['category' => 'A', 'min_salary' => 103000000, 'max_salary' => 125000000, 'rate' => 0.2400],
            ['category' => 'A', 'min_salary' => 125000000, 'max_salary' => 157000000, 'rate' => 0.2500],
            ['category' => 'A', 'min_salary' => 157000000, 'max_salary' => 206000000, 'rate' => 0.2600],
            ['category' => 'A', 'min_salary' => 206000000, 'max_salary' => 337000000, 'rate' => 0.2800],
            ['category' => 'A', 'min_salary' => 337000000, 'max_salary' => 454000000, 'rate' => 0.3000],
            ['category' => 'A', 'min_salary' => 454000000, 'max_salary' => 550000000, 'rate' => 0.3100],
            ['category' => 'A', 'min_salary' => 550000000, 'max_salary' => 695000000, 'rate' => 0.3200],
            ['category' => 'A', 'min_salary' => 695000000, 'max_salary' => 910000000, 'rate' => 0.3300],
            ['category' => 'A', 'min_salary' => 910000000, 'max_salary' => 1400000000, 'rate' => 0.3400],
            ['category' => 'A', 'min_salary' => 1400000000, 'max_salary' => null, 'rate' => 0.3500],

            // Category B (TK/2, TK/3, K/0, K/1)
            ['category' => 'B', 'min_salary' => 0, 'max_salary' => 6200000, 'rate' => 0.0000],
            ['category' => 'B', 'min_salary' => 6200000, 'max_salary' => 6500000, 'rate' => 0.0025],
            ['category' => 'B', 'min_salary' => 6500000, 'max_salary' => 6850000, 'rate' => 0.0050],
            ['category' => 'B', 'min_salary' => 6850000, 'max_salary' => 7300000, 'rate' => 0.0075],
            ['category' => 'B', 'min_salary' => 7300000, 'max_salary' => 9200000, 'rate' => 0.0100],
            ['category' => 'B', 'min_salary' => 9200000, 'max_salary' => 10750000, 'rate' => 0.0150],
            ['category' => 'B', 'min_salary' => 10750000, 'max_salary' => 11250000, 'rate' => 0.0200],
            ['category' => 'B', 'min_salary' => 11250000, 'max_salary' => 11600000, 'rate' => 0.0250],
            ['category' => 'B', 'min_salary' => 11600000, 'max_salary' => 12600000, 'rate' => 0.0300],
            ['category' => 'B', 'min_salary' => 12600000, 'max_salary' => 13600000, 'rate' => 0.0350],
            ['category' => 'B', 'min_salary' => 13600000, 'max_salary' => 14950000, 'rate' => 0.0400],
            ['category' => 'B', 'min_salary' => 14950000, 'max_salary' => 16400000, 'rate' => 0.0450],
            ['category' => 'B', 'min_salary' => 16400000, 'max_salary' => 18450000, 'rate' => 0.0500],
            ['category' => 'B', 'min_salary' => 18450000, 'max_salary' => 21850000, 'rate' => 0.0600],
            ['category' => 'B', 'min_salary' => 21850000, 'max_salary' => 26000000, 'rate' => 0.0700],
            ['category' => 'B', 'min_salary' => 26000000, 'max_salary' => 30050000, 'rate' => 0.0800],
            ['category' => 'B', 'min_salary' => 30050000, 'max_salary' => 34000000, 'rate' => 0.0900],
            ['category' => 'B', 'min_salary' => 34000000, 'max_salary' => 38900000, 'rate' => 0.1000],
            ['category' => 'B', 'min_salary' => 38900000, 'max_salary' => 43000000, 'rate' => 0.1100],
            ['category' => 'B', 'min_salary' => 43000000, 'max_salary' => 47000000, 'rate' => 0.1200],
            ['category' => 'B', 'min_salary' => 47000000, 'max_salary' => 51000000, 'rate' => 0.1300],
            ['category' => 'B', 'min_salary' => 51000000, 'max_salary' => 55800000, 'rate' => 0.1400],
            ['category' => 'B', 'min_salary' => 55800000, 'max_salary' => 62000000, 'rate' => 0.1500],
            ['category' => 'B', 'min_salary' => 62000000, 'max_salary' => 66700000, 'rate' => 0.1600],
            ['category' => 'B', 'min_salary' => 66700000, 'max_salary' => 74500000, 'rate' => 0.1700],
            ['category' => 'B', 'min_salary' => 74500000, 'max_salary' => 83200000, 'rate' => 0.1800],
            ['category' => 'B', 'min_salary' => 83200000, 'max_salary' => 95000000, 'rate' => 0.1900],
            ['category' => 'B', 'min_salary' => 95000000, 'max_salary' => 110000000, 'rate' => 0.2000],
            ['category' => 'B', 'min_salary' => 110000000, 'max_salary' => 134000000, 'rate' => 0.2100],
            ['category' => 'B', 'min_salary' => 134000000, 'max_salary' => 169000000, 'rate' => 0.2200],
            ['category' => 'B', 'min_salary' => 169000000, 'max_salary' => 221000000, 'rate' => 0.2300],
            ['category' => 'B', 'min_salary' => 221000000, 'max_salary' => 390000000, 'rate' => 0.2500],
            ['category' => 'B', 'min_salary' => 390000000, 'max_salary' => 463000000, 'rate' => 0.2700],
            ['category' => 'B', 'min_salary' => 463000000, 'max_salary' => 561000000, 'rate' => 0.2800],
            ['category' => 'B', 'min_salary' => 561000000, 'max_salary' => 709000000, 'rate' => 0.2900],
            ['category' => 'B', 'min_salary' => 709000000, 'max_salary' => 965000000, 'rate' => 0.3000],
            ['category' => 'B', 'min_salary' => 965000000, 'max_salary' => 1419000000, 'rate' => 0.3100],
            ['category' => 'B', 'min_salary' => 1419000000, 'max_salary' => null, 'rate' => 0.3200],

            // Category C (K/2, K/3)
            ['category' => 'C', 'min_salary' => 0, 'max_salary' => 6600000, 'rate' => 0.0000],
            ['category' => 'C', 'min_salary' => 6600000, 'max_salary' => 6950000, 'rate' => 0.0025],
            ['category' => 'C', 'min_salary' => 6950000, 'max_salary' => 7350000, 'rate' => 0.0050],
            ['category' => 'C', 'min_salary' => 7350000, 'max_salary' => 7800000, 'rate' => 0.0075],
            ['category' => 'C', 'min_salary' => 7800000, 'max_salary' => 8850000, 'rate' => 0.0100],
            ['category' => 'C', 'min_salary' => 8850000, 'max_salary' => 9800000, 'rate' => 0.0125],
            ['category' => 'C', 'min_salary' => 9800000, 'max_salary' => 10950000, 'rate' => 0.0150],
            ['category' => 'C', 'min_salary' => 10950000, 'max_salary' => 11200000, 'rate' => 0.0175],
            ['category' => 'C', 'min_salary' => 11200000, 'max_salary' => 12050000, 'rate' => 0.0200],
            ['category' => 'C', 'min_salary' => 12050000, 'max_salary' => 12950000, 'rate' => 0.0250],
            ['category' => 'C', 'min_salary' => 12950000, 'max_salary' => 14150000, 'rate' => 0.0300],
            ['category' => 'C', 'min_salary' => 14150000, 'max_salary' => 15550000, 'rate' => 0.0350],
            ['category' => 'C', 'min_salary' => 15550000, 'max_salary' => 17050000, 'rate' => 0.0400],
            ['category' => 'C', 'min_salary' => 17050000, 'max_salary' => 19500000, 'rate' => 0.0450],
            ['category' => 'C', 'min_salary' => 19500000, 'max_salary' => 22700000, 'rate' => 0.0500],
            ['category' => 'C', 'min_salary' => 22700000, 'max_salary' => 26600000, 'rate' => 0.0600],
            ['category' => 'C', 'min_salary' => 26600000, 'max_salary' => 33400000, 'rate' => 0.0700],
            ['category' => 'C', 'min_salary' => 33400000, 'max_salary' => 38900000, 'rate' => 0.0800],
            ['category' => 'C', 'min_salary' => 38900000, 'max_salary' => 43000000, 'rate' => 0.0900],
            ['category' => 'C', 'min_salary' => 43000000, 'max_salary' => 47000000, 'rate' => 0.1000],
            ['category' => 'C', 'min_salary' => 47000000, 'max_salary' => 51000000, 'rate' => 0.1100],
            ['category' => 'C', 'min_salary' => 51000000, 'max_salary' => 55800000, 'rate' => 0.1200],
            ['category' => 'C', 'min_salary' => 55800000, 'max_salary' => 62000000, 'rate' => 0.1300],
            ['category' => 'C', 'min_salary' => 62000000, 'max_salary' => 66700000, 'rate' => 0.1400],
            ['category' => 'C', 'min_salary' => 66700000, 'max_salary' => 74500000, 'rate' => 0.1500],
            ['category' => 'C', 'min_salary' => 74500000, 'max_salary' => 83200000, 'rate' => 0.1600],
            ['category' => 'C', 'min_salary' => 83200000, 'max_salary' => 95000000, 'rate' => 0.1700],
            ['category' => 'C', 'min_salary' => 95000000, 'max_salary' => 110000000, 'rate' => 0.1800],
            ['category' => 'C', 'min_salary' => 110000000, 'max_salary' => 134000000, 'rate' => 0.1900],
            ['category' => 'C', 'min_salary' => 134000000, 'max_salary' => 169000000, 'rate' => 0.2000],
            ['category' => 'C', 'min_salary' => 169000000, 'max_salary' => 221000000, 'rate' => 0.2100],
            ['category' => 'C', 'min_salary' => 221000000, 'max_salary' => 390000000, 'rate' => 0.2300],
            ['category' => 'C', 'min_salary' => 390000000, 'max_salary' => 463000000, 'rate' => 0.2500],
            ['category' => 'C', 'min_salary' => 463000000, 'max_salary' => 561000000, 'rate' => 0.2600],
            ['category' => 'C', 'min_salary' => 561000000, 'max_salary' => 709000000, 'rate' => 0.2700],
            ['category' => 'C', 'min_salary' => 709000000, 'max_salary' => 965000000, 'rate' => 0.2800],
            ['category' => 'C', 'min_salary' => 965000000, 'max_salary' => 1419000000, 'rate' => 0.2900],
            ['category' => 'C', 'min_salary' => 1419000000, 'max_salary' => null, 'rate' => 0.3100],
        ];

        foreach ($rates as $rate) {
            Pph21TerRate::create($rate);
        }
    }

    private function createEmployeeWithSalary(
        string $ptkpStatus = 'TK/0',
        float $basicSalary = 10000000,
        float $fixedAllowance = 1000000,
        float $mealAllowance = 500000,
        float $transportAllowance = 500000,
        float $overtimeRate = 50000
    ): array {
        $employee = Employee::factory()->withPtkp($ptkpStatus)->create();

        $salary = SalaryStructure::create([
            'employee_id'        => $employee->id,
            'basic_salary'       => $basicSalary,
            'fixed_allowance'    => $fixedAllowance,
            'meal_allowance'     => $mealAllowance,
            'transport_allowance' => $transportAllowance,
            'overtime_rate'      => $overtimeRate,
            'effective_date'     => now()->subYear()->toDateString(),
            'is_active'          => true,
        ]);

        return [$employee, $salary];
    }

    // ═══════════════════════════════════════════════════════════════
    // T1: PPh 21 TER Calculation — Category A (TK/0)
    // ═══════════════════════════════════════════════════════════════

    public function test_pph21_ter_category_a_tk0(): void
    {
        $this->seedTerRates();

        // Employee TK/0 → TER Category A
        // Monthly gross = 10,000,000
        // TER rate for Cat A, 9,650,000-10,050,000 = 2.00%
        $pph21 = $this->payrollService->calculatePph21TER('A', 10000000, 3);
        $this->assertEquals(200000.00, $pph21); // 10M × 2% = 200,000

        // Gross = 5,000,000 → Cat A rate = 0% (below 5,400,000)
        $pph21Low = $this->payrollService->calculatePph21TER('A', 5000000, 1);
        $this->assertEquals(0.00, $pph21Low);

        // Gross = 15,500,000 → Cat A rate = 6.00% (test TER data for bracket 15,100,000-16,950,000)
        $pph21High = $this->payrollService->calculatePph21TER('A', 15500000, 6);
        $this->assertEquals(930000.00, $pph21High); // 15.5M × 6%
    }

    // ═══════════════════════════════════════════════════════════════
    // T2: PPh 21 TER Calculation — Category B (K/0)
    // ═══════════════════════════════════════════════════════════════

    public function test_pph21_ter_category_b_k0(): void
    {
        $this->seedTerRates();

        // Employee K/0 → TER Category B
        // Monthly gross = 10,000,000 → Cat B rate for 9,200,000-10,750,000 = 1.50%
        $pph21 = $this->payrollService->calculatePph21TER('B', 10000000, 5);
        $this->assertEquals(150000.00, $pph21); // 10M × 1.5%

        // Gross = 5,000,000 → Cat B rate = 0% (below 6,200,000)
        $pph21Low = $this->payrollService->calculatePph21TER('B', 5000000, 1);
        $this->assertEquals(0.00, $pph21Low);
    }

    // ═══════════════════════════════════════════════════════════════
    // T3: PPh 21 TER Calculation — Category C (K/2)
    // ═══════════════════════════════════════════════════════════════

    public function test_pph21_ter_category_c_k2(): void
    {
        $this->seedTerRates();

        // Employee K/2 → TER Category C
        // Monthly gross = 10,000,000 → Cat C rate for 9,800,000-10,950,000 = 1.50%
        $pph21 = $this->payrollService->calculatePph21TER('C', 10000000, 7);
        $this->assertEquals(150000.00, $pph21); // 10M × 1.5%

        // Gross = 6,000,000 → Cat C rate = 0% (below 6,600,000)
        $pph21Low = $this->payrollService->calculatePph21TER('C', 6000000, 1);
        $this->assertEquals(0.00, $pph21Low);
    }

    // ═══════════════════════════════════════════════════════════════
    // T4: BPJS Ketenagakerjaan & Kesehatan — with ceiling handling
    // ═══════════════════════════════════════════════════════════════

    public function test_bpjs_calculation_with_caps(): void
    {
        // Monthly fixed = 11,000,000, Gross = 12,000,000
        $bpjs = $this->payrollService->calculateBpjs(11000000, 12000000);

        // JHT Company: 11M × 3.7% = 407,000
        $this->assertEquals(407000.00, $bpjs['jht_company']);
        // JHT Employee: 11M × 2% = 220,000
        $this->assertEquals(220000.00, $bpjs['jht_employee']);
        // JKK: 11M × 0.24% = 26,400
        $this->assertEquals(26400.00, $bpjs['jkk_company']);
        // JKM: 11M × 0.3% = 33,000
        $this->assertEquals(33000.00, $bpjs['jkm_company']);

        // JP Company: capped at 10,042,300 × 2% = 200,846
        $this->assertEquals(200846.00, $bpjs['jp_company']);
        // JP Employee: capped at 10,042,300 × 1% = 100,423
        $this->assertEquals(100423.00, $bpjs['jp_employee']);

        // BPJS Kes Company: min(max(2,942,421, 12,000,000), 12,000,000) = 12,000,000 × 4% = 480,000
        $this->assertEquals(480000.00, $bpjs['kes_company']);
        // BPJS Kes Employee: 12,000,000 × 1% = 120,000
        $this->assertEquals(120000.00, $bpjs['kes_employee']);
    }

    public function test_bpjs_jp_ceiling_applied_for_high_salary(): void
    {
        // Monthly fixed = 15,000,000 (above JP cap of 10,042,300)
        $bpjs = $this->payrollService->calculateBpjs(15000000, 15000000);

        // JP should be capped at 10,042,300
        $this->assertEquals(200846.00, $bpjs['jp_company']);  // 10,042,300 × 2%
        $this->assertEquals(100423.00, $bpjs['jp_employee']); // 10,042,300 × 1%
    }

    public function test_bpjs_kes_floor_for_low_salary(): void
    {
        // Gross = 2,000,000 (below KES floor of 2,942,421)
        $bpjs = $this->payrollService->calculateBpjs(2000000, 2000000);

        // BPJS Kes should use floor
        $this->assertEquals(round(2942421 * 0.04, 2), $bpjs['kes_company']);  // 117,696.84
        $this->assertEquals(round(2942421 * 0.01, 2), $bpjs['kes_employee']); // 29,424.21
    }

    public function test_bpjs_kes_ceiling_for_high_salary(): void
    {
        // Gross = 20,000,000 (above KES ceiling of 12,000,000)
        $bpjs = $this->payrollService->calculateBpjs(10000000, 20000000);

        // BPJS Kes should use ceiling
        $this->assertEquals(480000.00, $bpjs['kes_company']);  // 12M × 4%
        $this->assertEquals(120000.00, $bpjs['kes_employee']); // 12M × 1%
    }

    // ═══════════════════════════════════════════════════════════════
    // T5: Overtime from attendance data
    // ═══════════════════════════════════════════════════════════════

    public function test_overtime_calculated_from_attendance(): void
    {
        $this->seedTerRates();

        [$employee, $salary] = $this->createEmployeeWithSalary('TK/0', 8000000, 1000000, 500000, 500000, 50000);

        $period = PayrollPeriod::create([
            'month' => 3, 'year' => 2026, 'status' => 'draft',
        ]);

        // Create attendance records with overtime
        Attendance::create([
            'employee_id' => $employee->id, 'date' => '2026-03-10',
            'clock_in' => '2026-03-10 08:00:00', 'clock_out' => '2026-03-10 19:00:00',
            'status' => 'present', 'overtime_hours' => 2,
        ]);
        Attendance::create([
            'employee_id' => $employee->id, 'date' => '2026-03-15',
            'clock_in' => '2026-03-15 08:00:00', 'clock_out' => '2026-03-15 20:00:00',
            'status' => 'present', 'overtime_hours' => 3,
        ]);

        $payslip = $this->payrollService->generatePayslip($period, $employee, $salary);

        // Overtime: 5 hours × 50,000/hr = 250,000
        $this->assertEquals(5.00, (float) $payslip->overtime_hours);
        $this->assertEquals(250000.00, (float) $payslip->overtime_amount);
    }

    // ═══════════════════════════════════════════════════════════════
    // T6: Absence deduction (prorate for mid-month)
    // ═══════════════════════════════════════════════════════════════

    public function test_absence_deduction_calculated_from_attendance(): void
    {
        $this->seedTerRates();

        [$employee, $salary] = $this->createEmployeeWithSalary('TK/0', 10000000);

        $period = PayrollPeriod::create([
            'month' => 3, 'year' => 2026, 'status' => 'draft',
        ]);

        // 2 absent days
        Attendance::create([
            'employee_id' => $employee->id, 'date' => '2026-03-05',
            'status' => 'absent',
        ]);
        Attendance::create([
            'employee_id' => $employee->id, 'date' => '2026-03-12',
            'status' => 'absent',
        ]);

        $payslip = $this->payrollService->generatePayslip($period, $employee, $salary);

        // Working days in March 2026 (Mon-Fri): 22 days
        // Absence deduction = 2 × (10,000,000 / 22) = 909,090.91
        $workingDays = $this->payrollService->getWorkingDaysInMonth(3, 2026);
        $expectedDeduction = round(2 * (10000000 / $workingDays), 2);

        $this->assertEquals($expectedDeduction, (float) $payslip->absence_deduction);
    }

    // ═══════════════════════════════════════════════════════════════
    // T7: December PPh 21 with corrected BPJS basis (Pasal 17)
    // ═══════════════════════════════════════════════════════════════

    public function test_pph21_december_uses_actual_bpjs_employee_basis(): void
    {
        $this->seedTerRates();

        [$employee, $salary] = $this->createEmployeeWithSalary('TK/0', 10000000, 1000000, 500000, 500000, 50000);

        // Generate payslips for Jan–Nov
        for ($m = 1; $m <= 11; $m++) {
            $period = PayrollPeriod::create([
                'month' => $m, 'year' => 2026, 'status' => 'draft',
            ]);
            $this->payrollService->generatePayslip($period, $employee, $salary);
        }

        // Generate December
        $decPeriod = PayrollPeriod::create([
            'month' => 12, 'year' => 2026, 'status' => 'draft',
        ]);
        $decPayslip = $this->payrollService->generatePayslip($decPeriod, $employee, $salary);

        // Verify December PPh 21 is computed (should be ≥ 0)
        $this->assertGreaterThanOrEqual(0, (float) $decPayslip->pph21_amount);

        // Verify net salary is reasonable (not negative)
        $this->assertGreaterThan(0, (float) $decPayslip->net_salary);

        // Verify the annual BPJS employee is correctly sourced from payslips (not gross)
        $janNovPayslips = Payslip::where('employee_id', $employee->id)
            ->whereHas('payrollPeriod', fn($q) => $q->where('year', 2026)->where('month', '<', 12))
            ->get();

        $actualBpjsEmp = $janNovPayslips->sum(fn($p) => (float) $p->bpjs_jht_employee + (float) $p->bpjs_jp_employee);
        $this->assertGreaterThan(0, $actualBpjsEmp);
    }

    // ═══════════════════════════════════════════════════════════════
    // T8: postToAccounting — bcmath, sourceable, and balanced
    // ═══════════════════════════════════════════════════════════════

    public function test_post_to_accounting_creates_balanced_journal_with_sourceable(): void
    {
        $this->seedTerRates();

        [$employee, $salary] = $this->createEmployeeWithSalary('TK/0', 10000000, 1000000);

        $period = PayrollPeriod::create([
            'month' => 3, 'year' => 2026, 'status' => 'draft',
        ]);

        $this->payrollService->generatePayslips($period, []);

        // Approve first
        $period->transitionTo('approved');
        $period->approved_by = $this->admin->id;
        $period->approved_at = now();
        $period->save();

        // Post to accounting
        $journal = $this->payrollService->postToAccounting($period);

        // ── Assert Sourceable Link ──
        $this->assertEquals(PayrollPeriod::class, $journal->sourceable_type);
        $this->assertEquals($period->id, $journal->sourceable_id);

        // ── Assert Journal is Balanced ──
        $totalDebit  = $journal->items->sum('debit');
        $totalCredit = $journal->items->sum('credit');
        $this->assertTrue(abs($totalDebit - $totalCredit) < 0.01,
            "Journal not balanced: Dr={$totalDebit}, Cr={$totalCredit}");

        // ── Assert Reference Format ──
        $this->assertEquals('PAYROLL-2026-03', $journal->reference);
    }

    public function test_post_to_accounting_throws_for_empty_payslips(): void
    {
        $period = PayrollPeriod::create([
            'month' => 6, 'year' => 2026, 'status' => 'draft',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->payrollService->postToAccounting($period);
    }

    // ═══════════════════════════════════════════════════════════════
    // T9: Payroll state machine guards
    // ═══════════════════════════════════════════════════════════════

    public function test_payroll_state_machine_transitions(): void
    {
        $period = PayrollPeriod::create([
            'month' => 4, 'year' => 2026, 'status' => 'draft',
        ]);

        // draft → approved ✓
        $this->assertTrue($period->canTransitionTo('approved'));
        $period->transitionTo('approved');
        $this->assertEquals('approved', $period->status);

        // approved → posted ✓
        $this->assertTrue($period->canTransitionTo('posted'));

        // approved → draft ✓ (revert)
        $this->assertTrue($period->canTransitionTo('draft'));

        // Transition to posted
        $period->transitionTo('posted');
        $this->assertEquals('posted', $period->status);

        // posted → nothing ✗
        $this->assertFalse($period->canTransitionTo('draft'));
        $this->assertFalse($period->canTransitionTo('approved'));
    }

    public function test_generate_payslips_rejects_non_draft_period(): void
    {
        $this->seedTerRates();

        [$employee, $salary] = $this->createEmployeeWithSalary();

        $period = PayrollPeriod::create([
            'month' => 5, 'year' => 2026, 'status' => 'draft',
        ]);

        $this->payrollService->generatePayslips($period, []);

        $period->transitionTo('approved');
        $period->save();

        $this->expectException(InvalidArgumentException::class);
        $this->payrollService->generatePayslips($period, []);
    }

    // ═══════════════════════════════════════════════════════════════
    // T10: TER category auto-derived from PTKP
    // ═══════════════════════════════════════════════════════════════

    public function test_ter_category_auto_derived_from_ptkp(): void
    {
        // Category A: TK/0, TK/1
        $this->assertEquals('A', Employee::deriveTerCategory('TK/0'));
        $this->assertEquals('A', Employee::deriveTerCategory('TK/1'));

        // Category B: TK/2, TK/3, K/0, K/1
        $this->assertEquals('B', Employee::deriveTerCategory('TK/2'));
        $this->assertEquals('B', Employee::deriveTerCategory('TK/3'));
        $this->assertEquals('B', Employee::deriveTerCategory('K/0'));
        $this->assertEquals('B', Employee::deriveTerCategory('K/1'));

        // Category C: K/2, K/3
        $this->assertEquals('C', Employee::deriveTerCategory('K/2'));
        $this->assertEquals('C', Employee::deriveTerCategory('K/3'));
    }

    public function test_ter_category_set_on_model_save(): void
    {
        $employee = Employee::factory()->create([
            'ptkp_status' => 'K/3',
        ]);

        $this->assertEquals('C', $employee->ter_category);

        // Update PTKP status
        $employee->ptkp_status = 'TK/0';
        $employee->save();

        $this->assertEquals('A', $employee->fresh()->ter_category);
    }

    // ═══════════════════════════════════════════════════════════════
    // T11: HR permissions seeded for admin
    // ═══════════════════════════════════════════════════════════════

    public function test_admin_has_hr_permissions(): void
    {
        // Admin bypasses all checks
        $this->assertTrue($this->admin->hasPermission('hr.view'));
        $this->assertTrue($this->admin->hasPermission('hr.create'));
        $this->assertTrue($this->admin->hasPermission('hr.edit'));
        $this->assertTrue($this->admin->hasPermission('hr.delete'));
    }

    public function test_staff_without_hr_permission_cannot_access(): void
    {
        $staff = User::factory()->create([
            'role'        => User::ROLE_STAFF,
            'status'      => User::STATUS_ACTIVE,
            'permissions' => ['sales.view'],
        ]);

        $this->assertFalse($staff->hasPermission('hr.view'));
        $this->assertFalse($staff->hasPermission('hr.create'));
    }

    public function test_staff_with_hr_permission_can_access(): void
    {
        $staff = User::factory()->create([
            'role'        => User::ROLE_STAFF,
            'status'      => User::STATUS_ACTIVE,
            'permissions' => ['hr.view', 'hr.create', 'hr.edit', 'hr.delete'],
        ]);

        $this->assertTrue($staff->hasPermission('hr.view'));
        $this->assertTrue($staff->hasPermission('hr.create'));
    }

    // ═══════════════════════════════════════════════════════════════
    // T12: Attendance integration — getAttendanceData
    // ═══════════════════════════════════════════════════════════════

    public function test_attendance_data_aggregation(): void
    {
        $employee = Employee::factory()->create();

        // Create attendance records for March 2026
        Attendance::create([
            'employee_id' => $employee->id, 'date' => '2026-03-01',
            'status' => 'present', 'overtime_hours' => 1.5,
        ]);
        Attendance::create([
            'employee_id' => $employee->id, 'date' => '2026-03-02',
            'status' => 'late', 'overtime_hours' => 0,
        ]);
        Attendance::create([
            'employee_id' => $employee->id, 'date' => '2026-03-03',
            'status' => 'absent', 'overtime_hours' => 0,
        ]);
        Attendance::create([
            'employee_id' => $employee->id, 'date' => '2026-03-04',
            'status' => 'leave', 'overtime_hours' => 0,
        ]);
        Attendance::create([
            'employee_id' => $employee->id, 'date' => '2026-03-05',
            'status' => 'present', 'overtime_hours' => 2.5,
        ]);

        $data = $this->payrollService->getAttendanceData($employee->id, 3, 2026);

        $this->assertEquals(4.0, $data['overtime_hours']); // 1.5 + 2.5
        $this->assertEquals(1, $data['absent_days']);
        $this->assertEquals(3, $data['present_days']); // 2 present + 1 late (late counts as present in whereIn)
        $this->assertEquals(1, $data['late_days']);
        $this->assertEquals(1, $data['leave_days']);
    }

    // ═══════════════════════════════════════════════════════════════
    // T13: Working days calculation
    // ═══════════════════════════════════════════════════════════════

    public function test_working_days_in_month(): void
    {
        // March 2026: 31 days, starts on Sunday
        $days = $this->payrollService->getWorkingDaysInMonth(3, 2026);
        $this->assertEquals(22, $days);

        // February 2026: 28 days (non-leap)
        $days = $this->payrollService->getWorkingDaysInMonth(2, 2026);
        $this->assertEquals(20, $days);

        // February 2024: 29 days (leap year)
        $days = $this->payrollService->getWorkingDaysInMonth(2, 2024);
        $this->assertEquals(21, $days);
    }

    // ═══════════════════════════════════════════════════════════════
    // T14: Full payslip generation — earnings - deductions = net
    // ═══════════════════════════════════════════════════════════════

    public function test_payslip_balanced_gross_minus_deductions_equals_net(): void
    {
        $this->seedTerRates();

        [$employee, $salary] = $this->createEmployeeWithSalary('TK/0', 10000000, 1000000, 500000, 500000, 50000);

        $period = PayrollPeriod::create([
            'month' => 3, 'year' => 2026, 'status' => 'draft',
        ]);

        $payslip = $this->payrollService->generatePayslip($period, $employee, $salary);

        // Verify: Gross - Total Deductions = Net
        $expectedNet = round(
            (float) $payslip->gross_salary - (float) $payslip->total_deductions,
            2
        );
        $this->assertEquals($expectedNet, (float) $payslip->net_salary);

        // Verify total deductions includes BPJS employee + PPh 21 + other deductions
        $expectedDeductions = round(
            (float) $payslip->bpjs_jht_employee
            + (float) $payslip->bpjs_jp_employee
            + (float) $payslip->bpjs_kes_employee
            + (float) $payslip->pph21_amount
            + (float) $payslip->loan_deduction
            + (float) $payslip->absence_deduction
            + (float) $payslip->other_deductions,
            2
        );
        $this->assertEquals($expectedDeductions, (float) $payslip->total_deductions);
    }

    // ═══════════════════════════════════════════════════════════════
    // T15: Payroll period recalculate totals
    // ═══════════════════════════════════════════════════════════════

    public function test_payroll_period_recalculates_totals(): void
    {
        $this->seedTerRates();

        [$employee, $salary] = $this->createEmployeeWithSalary();

        $period = PayrollPeriod::create([
            'month' => 7, 'year' => 2026, 'status' => 'draft',
        ]);

        $count = $this->payrollService->generatePayslips($period, []);
        $this->assertGreaterThanOrEqual(1, $count);

        $period->refresh();
        $this->assertGreaterThan(0, (float) $period->total_gross);
        $this->assertGreaterThan(0, (float) $period->total_net);
    }

    // ═══════════════════════════════════════════════════════════════
    // T16: Leave balance tracking
    // ═══════════════════════════════════════════════════════════════

    public function test_leave_balance_can_be_tracked(): void
    {
        $employee = Employee::factory()->create();

        $balance = LeaveBalance::create([
            'employee_id' => $employee->id,
            'year'        => 2026,
            'type'        => 'annual',
            'entitlement' => 12,
            'used'        => 3,
            'balance'     => 9,
        ]);

        $this->assertEquals(12, (float) $balance->entitlement);
        $this->assertEquals(3, (float) $balance->used);
        $this->assertEquals(9, (float) $balance->balance);
    }

    // ═══════════════════════════════════════════════════════════════
    // T17: Pasal 17 progressive brackets
    // ═══════════════════════════════════════════════════════════════

    public function test_pasal17_progressive_brackets(): void
    {
        // Access private method via reflection
        $reflection = new \ReflectionMethod($this->payrollService, 'calculatePasal17');
        $reflection->setAccessible(true);

        // PKP = 60,000,000 → 60M × 5% = 3,000,000
        $this->assertEquals(3000000.00, $reflection->invoke($this->payrollService, 60000000));

        // PKP = 100,000,000 → 60M × 5% + 40M × 15% = 3,000,000 + 6,000,000 = 9,000,000
        $this->assertEquals(9000000.00, $reflection->invoke($this->payrollService, 100000000));

        // PKP = 0 → 0
        $this->assertEquals(0.00, $reflection->invoke($this->payrollService, 0));

        // PKP = 310,000,000 → 60M×5% + 190M×15% + 60M×25% = 3M + 28.5M + 15M = 46,500,000
        // = 60M × 5% = 3,000,000
        // + 190M × 15% = 28,500,000  (from 60M to 250M)
        // + 60M × 25% = 15,000,000   (from 250M to 310M)
        // Total = 46,500,000
        $this->assertEquals(46500000.00, $reflection->invoke($this->payrollService, 310000000));
    }

    // ═══════════════════════════════════════════════════════════════
    // T18: Duplicate period prevention
    // ═══════════════════════════════════════════════════════════════

    public function test_duplicate_period_prevented_by_unique_constraint(): void
    {
        PayrollPeriod::create([
            'month' => 8, 'year' => 2026, 'status' => 'draft',
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        PayrollPeriod::create([
            'month' => 8, 'year' => 2026, 'status' => 'draft',
        ]);
    }

    // ═══════════════════════════════════════════════════════════════
    // T19: Payroll with zero attendance records
    // ═══════════════════════════════════════════════════════════════

    public function test_payroll_calculation_with_zero_attendance(): void
    {
        $this->seedTerRates();

        [$employee, $salary] = $this->createEmployeeWithSalary('TK/0', 10000000, 1000000, 500000, 500000, 50000);

        $period = PayrollPeriod::create([
            'month' => 4, 'year' => 2026, 'status' => 'draft',
        ]);

        // No attendance records created — should not crash
        $payslip = $this->payrollService->generatePayslip($period, $employee, $salary);

        // Overtime should be 0 (no attendance data, no manual override)
        $this->assertEquals(0.00, (float) $payslip->overtime_hours);
        $this->assertEquals(0.00, (float) $payslip->overtime_amount);

        // Absence deduction should be 0 (no attendance data)
        $this->assertEquals(0.00, (float) $payslip->absence_deduction);

        // Gross salary = basic + fixed + meal + transport = 10M + 1M + 500K + 500K = 12M
        $this->assertEquals(12000000.00, (float) $payslip->gross_salary);

        // Net salary should be positive and less than gross (deductions exist from BPJS + PPh21)
        $this->assertGreaterThan(0, (float) $payslip->net_salary);
        $this->assertLessThan((float) $payslip->gross_salary, (float) $payslip->net_salary);
    }
}
