<?php

namespace Tests\Feature;

use App\Models\ChartOfAccount;
use App\Models\CompanySetting;
use App\Models\FiscalPeriod;
use App\Models\JournalEntry;
use App\Models\User;
use App\Services\AccountingService;
use App\Services\CashFlowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashFlowExportTest extends TestCase
{
    use RefreshDatabase;

    private CashFlowService $cashFlowService;
    private AccountingService $accountingService;
    private User $admin;
    private User $viewer;
    private User $unauthorised;

    // Accounts
    private ChartOfAccount $cashAccount;
    private ChartOfAccount $arAccount;
    private ChartOfAccount $inventoryAccount;
    private ChartOfAccount $apAccount;
    private ChartOfAccount $equityAccount;
    private ChartOfAccount $revenueAccount;
    private ChartOfAccount $expenseAccount;
    private ChartOfAccount $retainedEarnings;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cashFlowService  = app(CashFlowService::class);
        $this->accountingService = app(AccountingService::class);

        // Users
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->viewer = User::factory()->create(['role' => 'manager']);
        $this->unauthorised = User::factory()->create(['role' => 'employee']);

        // Seed standard COA with cash_flow_category
        $this->cashAccount = ChartOfAccount::firstOrCreate(
            ['code' => '1100'],
            ['name' => 'Cash & Bank', 'type' => 'asset', 'is_active' => true,
             'is_system_account' => false, 'cash_flow_category' => 'cash',
             'liquidity_classification' => 'current']
        );
        $this->arAccount = ChartOfAccount::firstOrCreate(
            ['code' => '1200'],
            ['name' => 'Accounts Receivable', 'type' => 'asset', 'is_active' => true,
             'is_system_account' => true, 'cash_flow_category' => 'operating',
             'liquidity_classification' => 'current']
        );
        $this->inventoryAccount = ChartOfAccount::firstOrCreate(
            ['code' => '1300'],
            ['name' => 'Inventory', 'type' => 'asset', 'is_active' => true,
             'is_system_account' => true, 'cash_flow_category' => 'operating',
             'liquidity_classification' => 'current']
        );
        $this->apAccount = ChartOfAccount::firstOrCreate(
            ['code' => '2000'],
            ['name' => 'Accounts Payable', 'type' => 'liability', 'is_active' => true,
             'is_system_account' => true, 'cash_flow_category' => 'operating',
             'liquidity_classification' => 'current']
        );
        $this->equityAccount = ChartOfAccount::firstOrCreate(
            ['code' => '3000'],
            ['name' => "Owner's Equity", 'type' => 'equity', 'is_active' => true,
             'is_system_account' => false, 'cash_flow_category' => 'financing']
        );
        $this->retainedEarnings = ChartOfAccount::firstOrCreate(
            ['code' => '3200'],
            ['name' => 'Retained Earnings', 'type' => 'equity', 'is_active' => true,
             'is_system_account' => true]
        );
        $this->revenueAccount = ChartOfAccount::firstOrCreate(
            ['code' => '4000'],
            ['name' => 'Revenue', 'type' => 'revenue', 'is_active' => true,
             'is_system_account' => false]
        );
        $this->expenseAccount = ChartOfAccount::firstOrCreate(
            ['code' => '5000'],
            ['name' => 'Cost of Goods Sold', 'type' => 'expense', 'is_active' => true,
             'is_system_account' => false]
        );

        CompanySetting::firstOrCreate([], [
            'name' => 'Test Company',
            'currency' => 'IDR',
        ]);
    }

    /**
     * Create a journal entry and immediately post it.
     */
    private function createPostedJournal(string $ref, string $date, string $desc, array $entries): JournalEntry
    {
        $journal = $this->accountingService->createJournalEntry($ref, $date, $desc, $entries, null, null, 'auto');
        $journal->is_posted = true;
        $journal->save();

        return $journal;
    }

    // ═══════════════════════════════════════════════════════════════
    // TUGAS 4A: Cash flow figures sync with Balance Sheet
    // ═══════════════════════════════════════════════════════════════

    public function test_cash_flow_reconciles_with_balance_sheet(): void
    {
        $this->actingAs($this->admin);
        $today = now()->toDateString();
        $startOfYear = now()->startOfYear()->toDateString();

        // Create revenue journal: Cash debit 100k, Revenue credit 100k
        $this->createPostedJournal('REV-001', $today, 'Sales revenue', [
            ['account_id' => $this->cashAccount->id, 'debit' => 100000, 'credit' => 0],
            ['account_id' => $this->revenueAccount->id, 'debit' => 0, 'credit' => 100000],
        ]);

        // Create expense journal: Expense debit 40k, Cash credit 40k
        $this->createPostedJournal('EXP-001', $today, 'Expense payment', [
            ['account_id' => $this->expenseAccount->id, 'debit' => 40000, 'credit' => 0],
            ['account_id' => $this->cashAccount->id, 'debit' => 0, 'credit' => 40000],
        ]);

        // Create working capital: AR debit 20k, Revenue credit 20k (on account sale)
        $this->createPostedJournal('REV-002', $today, 'On-account sale', [
            ['account_id' => $this->arAccount->id, 'debit' => 20000, 'credit' => 0],
            ['account_id' => $this->revenueAccount->id, 'debit' => 0, 'credit' => 20000],
        ]);

        $data = $this->cashFlowService->getCashFlowStatement($startOfYear, $today);

        // Net income = 120k revenue - 40k expense = 80k
        $this->assertEquals(80000.0, $data['net_income']);

        // Reconciliation: beginning + net_change = ending
        $this->assertEquals(
            (float) bcadd((string) $data['beginning_cash'], (string) $data['net_cash_change'], 2),
            $data['ending_cash']
        );

        // Total operating = net_income + adjustments
        $opAdjustments = array_sum(array_column($data['operating'], 'amount'));
        $this->assertEquals(
            (float) bcadd((string) $data['net_income'], (string) $opAdjustments, 2),
            $data['total_operating']
        );

        // Actual ending cash should be verifiable
        // Cash journal movements: +100k - 40k = 60k
        $this->assertEquals(60000.0, $data['actual_ending_cash']);
    }

    public function test_net_income_matches_profit_loss(): void
    {
        $this->actingAs($this->admin);
        $today = now()->toDateString();
        $startOfYear = now()->startOfYear()->toDateString();

        // Revenue 50k
        $j1 = $this->createPostedJournal('REV-010', $today, 'Service revenue', [
            ['account_id' => $this->cashAccount->id, 'debit' => 50000, 'credit' => 0],
            ['account_id' => $this->revenueAccount->id, 'debit' => 0, 'credit' => 50000],
        ]);

        // Expense 15k
        $j2 = $this->createPostedJournal('EXP-010', $today, 'Office supplies', [
            ['account_id' => $this->expenseAccount->id, 'debit' => 15000, 'credit' => 0],
            ['account_id' => $this->cashAccount->id, 'debit' => 0, 'credit' => 15000],
        ]);

        $cashFlowData = $this->cashFlowService->getCashFlowStatement($startOfYear, $today);
        $plData = $this->accountingService->getProfitLoss($startOfYear, $today);

        // Both must be non-zero to validate meaningful comparison
        $this->assertNotEquals(0.0, $plData['net_profit'], 'P&L net_profit should not be zero');

        // Net income from cash flow must match P&L net profit
        $this->assertEquals($plData['net_profit'], $cashFlowData['net_income']);
    }

    // ═══════════════════════════════════════════════════════════════
    // TUGAS 4B: Permission / Data leakage
    // ═══════════════════════════════════════════════════════════════

    public function test_pdf_export_requires_accounting_view_permission(): void
    {
        // Admin should be able to access
        $this->actingAs($this->admin);
        $response = $this->get(route('accounting.cash-flow.pdf'));
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_unauthorised_user_cannot_export_pdf(): void
    {
        $this->actingAs($this->unauthorised);
        $response = $this->get(route('accounting.cash-flow.pdf'));
        $response->assertStatus(403);
    }

    public function test_html_report_requires_accounting_view_permission(): void
    {
        $this->actingAs($this->admin);
        $response = $this->get(route('accounting.cash-flow'));
        $response->assertStatus(200);
    }

    public function test_unauthorised_user_cannot_view_html_report(): void
    {
        $this->actingAs($this->unauthorised);
        $response = $this->get(route('accounting.cash-flow'));
        $response->assertStatus(403);
    }

    // ═══════════════════════════════════════════════════════════════
    // TUGAS 4C: Draft watermark when fiscal period is open
    // ═══════════════════════════════════════════════════════════════

    public function test_draft_flag_when_fiscal_period_open(): void
    {
        FiscalPeriod::create([
            'name' => 'Q1 2026',
            'start_date' => now()->startOfYear()->toDateString(),
            'end_date' => now()->endOfMonth()->toDateString(),
            'status' => 'open',
        ]);

        $data = $this->cashFlowService->getCashFlowStatement(
            now()->startOfYear()->toDateString(),
            now()->toDateString()
        );

        $this->assertTrue($data['is_draft']);
    }

    public function test_no_draft_flag_when_fiscal_period_closed(): void
    {
        FiscalPeriod::create([
            'name' => 'Q1 2026',
            'start_date' => now()->startOfYear()->toDateString(),
            'end_date' => now()->endOfMonth()->toDateString(),
            'status' => 'closed',
            'closed_at' => now(),
        ]);

        $data = $this->cashFlowService->getCashFlowStatement(
            now()->startOfYear()->toDateString(),
            now()->toDateString()
        );

        $this->assertFalse($data['is_draft']);
    }

    // ═══════════════════════════════════════════════════════════════
    // TUGAS 4D: bcmath precision
    // ═══════════════════════════════════════════════════════════════

    public function test_bcmath_arithmetic_consistency(): void
    {
        $this->actingAs($this->admin);
        $today = now()->toDateString();
        $start = now()->startOfYear()->toDateString();

        $this->createPostedJournal('REV-020', $today, 'Precise revenue', [
            ['account_id' => $this->cashAccount->id, 'debit' => 33333.33, 'credit' => 0],
            ['account_id' => $this->revenueAccount->id, 'debit' => 0, 'credit' => 33333.33],
        ]);

        $data = $this->cashFlowService->getCashFlowStatement($start, $today);

        // Verify reconciliation holds
        $computed = (float) bcadd((string) $data['beginning_cash'], (string) $data['net_cash_change'], 2);
        $this->assertEquals($data['ending_cash'], $computed);

        // Total operating + investing + financing = net_cash_change
        $totalAll = (float) bcadd(
            bcadd((string) $data['total_operating'], (string) $data['total_investing'], 2),
            (string) $data['total_financing'],
            2
        );
        $this->assertEquals($data['net_cash_change'], $totalAll);
    }

    // ═══════════════════════════════════════════════════════════════
    // TUGAS 4E: Audit trail
    // ═══════════════════════════════════════════════════════════════

    public function test_pdf_export_creates_audit_log(): void
    {
        $this->actingAs($this->admin);

        $this->get(route('accounting.cash-flow.pdf'));

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->admin->id,
            'module'  => 'accounting',
            'action'  => 'export_pdf',
        ]);
    }
}
