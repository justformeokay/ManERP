<?php

namespace Tests\Feature;

use App\Models\ChartOfAccount;
use App\Models\FiscalPeriod;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\InventoryStock;
use App\Models\StockMovement;
use App\Models\ActivityLog;
use App\Services\AccountingService;
use App\Services\AuditLogService;
use App\Services\StockService;
use App\Services\StockValuationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class AccountingIntegrityPatchTest extends TestCase
{
    use RefreshDatabase;

    private AccountingService $accountingService;
    private StockValuationService $valuationService;
    private StockService $stockService;
    private User $admin;

    // Accounts
    private ChartOfAccount $cashAccount;
    private ChartOfAccount $arAccount;
    private ChartOfAccount $inventoryAccount;
    private ChartOfAccount $apAccount;
    private ChartOfAccount $equityAccount;
    private ChartOfAccount $revenueAccount;
    private ChartOfAccount $expenseAccount;
    private ChartOfAccount $varianceAccount;
    private ChartOfAccount $retainedEarnings;

    protected function setUp(): void
    {
        parent::setUp();

        $this->accountingService = app(AccountingService::class);
        $this->valuationService  = app(StockValuationService::class);
        $this->stockService      = app(StockService::class);

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($this->admin);

        // Seed accounts WITH system flag and classification
        $this->cashAccount = ChartOfAccount::firstOrCreate(
            ['code' => '1100'],
            ['name' => 'Cash & Bank', 'type' => 'asset', 'is_active' => true, 'is_system_account' => false, 'liquidity_classification' => 'current']
        );
        $this->arAccount = ChartOfAccount::firstOrCreate(
            ['code' => '1200'],
            ['name' => 'Accounts Receivable', 'type' => 'asset', 'is_active' => true, 'is_system_account' => true, 'liquidity_classification' => 'current']
        );
        $this->inventoryAccount = ChartOfAccount::firstOrCreate(
            ['code' => '1300'],
            ['name' => 'Inventory', 'type' => 'asset', 'is_active' => true, 'is_system_account' => true, 'liquidity_classification' => 'current']
        );
        $this->apAccount = ChartOfAccount::firstOrCreate(
            ['code' => '2000'],
            ['name' => 'Accounts Payable', 'type' => 'liability', 'is_active' => true, 'is_system_account' => true, 'liquidity_classification' => 'current']
        );
        $this->equityAccount = ChartOfAccount::firstOrCreate(
            ['code' => '3000'],
            ['name' => "Owner's Equity", 'type' => 'equity', 'is_active' => true, 'is_system_account' => false]
        );
        $this->retainedEarnings = ChartOfAccount::firstOrCreate(
            ['code' => '3200'],
            ['name' => 'Retained Earnings', 'type' => 'equity', 'is_active' => true, 'is_system_account' => true]
        );
        $this->revenueAccount = ChartOfAccount::firstOrCreate(
            ['code' => '4000'],
            ['name' => 'Revenue', 'type' => 'revenue', 'is_active' => true, 'is_system_account' => false]
        );
        $this->expenseAccount = ChartOfAccount::firstOrCreate(
            ['code' => '5000'],
            ['name' => 'Cost of Goods Sold', 'type' => 'expense', 'is_active' => true, 'is_system_account' => false]
        );
        $this->varianceAccount = ChartOfAccount::firstOrCreate(
            ['code' => '5102'],
            ['name' => 'Inventory Adjustment Variance', 'type' => 'expense', 'is_active' => true, 'is_system_account' => true]
        );
    }

    // ═══════════════════════════════════════════════════════════════
    // TUGAS 1: System Account Protection
    // ═══════════════════════════════════════════════════════════════

    public function test_manual_journal_to_system_account_is_blocked(): void
    {
        $this->expectException(InvalidArgumentException::class);

        // Try to create a manual journal entry writing to AR (system account)
        $this->accountingService->createJournalEntry(
            'MANUAL-001', now()->toDateString(), 'Direct AR manipulation',
            [
                ['account_id' => $this->cashAccount->id, 'debit' => 5000, 'credit' => 0],
                ['account_id' => $this->arAccount->id, 'debit' => 0, 'credit' => 5000],
            ]
        );
    }

    public function test_manual_journal_to_inventory_account_is_blocked(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->accountingService->createJournalEntry(
            'MANUAL-002', now()->toDateString(), 'Direct inventory manipulation',
            [
                ['account_id' => $this->inventoryAccount->id, 'debit' => 10000, 'credit' => 0],
                ['account_id' => $this->cashAccount->id, 'debit' => 0, 'credit' => 10000],
            ]
        );
    }

    public function test_manual_journal_to_ap_account_is_blocked(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->accountingService->createJournalEntry(
            'MANUAL-003', now()->toDateString(), 'Direct AP manipulation',
            [
                ['account_id' => $this->apAccount->id, 'debit' => 5000, 'credit' => 0],
                ['account_id' => $this->cashAccount->id, 'debit' => 0, 'credit' => 5000],
            ]
        );
    }

    public function test_auto_journal_to_system_account_succeeds(): void
    {
        $journal = $this->accountingService->createJournalEntry(
            'AUTO-001', now()->toDateString(), 'Auto journal to inventory',
            [
                ['account_id' => $this->inventoryAccount->id, 'debit' => 10000, 'credit' => 0],
                ['account_id' => $this->apAccount->id, 'debit' => 0, 'credit' => 10000],
            ],
            null,
            null,
            'auto'
        );

        $this->assertNotNull($journal->id);
        $this->assertEquals('auto', $journal->entry_type);
    }

    public function test_closing_journal_to_system_account_succeeds(): void
    {
        // Closing entries should be able to write to Retained Earnings (3200, system)
        $journal = $this->accountingService->createJournalEntry(
            'CLOSE-TEST', now()->toDateString(), 'Closing test',
            [
                ['account_id' => $this->revenueAccount->id, 'debit' => 5000, 'credit' => 0],
                ['account_id' => $this->retainedEarnings->id, 'debit' => 0, 'credit' => 5000],
            ],
            null,
            null,
            'closing'
        );

        $this->assertNotNull($journal->id);
        $this->assertEquals('closing', $journal->entry_type);
    }

    public function test_manual_journal_to_non_system_account_succeeds(): void
    {
        $journal = $this->accountingService->createJournalEntry(
            'OK-001', now()->toDateString(), 'Normal manual journal',
            [
                ['account_id' => $this->cashAccount->id, 'debit' => 5000, 'credit' => 0],
                ['account_id' => $this->revenueAccount->id, 'debit' => 0, 'credit' => 5000],
            ]
        );

        $this->assertNotNull($journal->id);
        $this->assertEquals('manual', $journal->entry_type);
    }

    public function test_system_account_error_contains_account_codes(): void
    {
        try {
            $this->accountingService->createJournalEntry(
                'MANUAL-ERR', now()->toDateString(), 'Should fail',
                [
                    ['account_id' => $this->inventoryAccount->id, 'debit' => 1000, 'credit' => 0],
                    ['account_id' => $this->apAccount->id, 'debit' => 0, 'credit' => 1000],
                ]
            );
            $this->fail('Expected InvalidArgumentException');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('1300', $e->getMessage());
        }
    }

    public function test_scope_system_returns_only_system_accounts(): void
    {
        $systemAccounts = ChartOfAccount::system()->pluck('code')->toArray();

        $this->assertContains('1200', $systemAccounts);
        $this->assertContains('1300', $systemAccounts);
        $this->assertContains('2000', $systemAccounts);
        $this->assertContains('5102', $systemAccounts);
        $this->assertNotContains('1100', $systemAccounts);
        $this->assertNotContains('4000', $systemAccounts);
    }

    // ═══════════════════════════════════════════════════════════════
    // TUGAS 2: Inventory Traceability (sourceable on journals)
    // ═══════════════════════════════════════════════════════════════

    public function test_journal_purchase_receive_with_sourceable(): void
    {
        $this->valuationService->journalPurchaseReceive(
            'PO-SRC-001',
            now()->toDateString(),
            50000,
            'PO receive with source',
            'App\\Models\\PurchaseOrder',
            99
        );

        $journal = JournalEntry::where('reference', 'PO-SRC-001')->first();
        $this->assertNotNull($journal);
        $this->assertEquals('App\\Models\\PurchaseOrder', $journal->sourceable_type);
        $this->assertEquals(99, $journal->sourceable_id);
        $this->assertEquals('auto', $journal->entry_type);
    }

    public function test_journal_purchase_cancel_with_sourceable(): void
    {
        $this->valuationService->journalPurchaseCancel(
            'PO-SRC-002',
            now()->toDateString(),
            30000,
            'PO cancel with source',
            'App\\Models\\PurchaseOrder',
            88
        );

        $journal = JournalEntry::where('reference', 'PO-SRC-002')->first();
        $this->assertNotNull($journal);
        $this->assertEquals('App\\Models\\PurchaseOrder', $journal->sourceable_type);
        $this->assertEquals(88, $journal->sourceable_id);
        $this->assertEquals('auto', $journal->entry_type);
    }

    public function test_journal_sales_cogs_with_sourceable(): void
    {
        $this->valuationService->journalSalesCogs(
            'SO-SRC-001',
            now()->toDateString(),
            25000,
            'COGS with source',
            'App\\Models\\SalesOrder',
            77
        );

        $journal = JournalEntry::where('reference', 'SO-SRC-001')->first();
        $this->assertNotNull($journal);
        $this->assertEquals('App\\Models\\SalesOrder', $journal->sourceable_type);
        $this->assertEquals(77, $journal->sourceable_id);
        $this->assertEquals('auto', $journal->entry_type);
    }

    public function test_journal_sales_cancel_with_sourceable(): void
    {
        $this->valuationService->journalSalesCancel(
            'SO-SRC-002',
            now()->toDateString(),
            15000,
            'Sales cancel with source',
            'App\\Models\\SalesOrder',
            66
        );

        $journal = JournalEntry::where('reference', 'SO-SRC-002')->first();
        $this->assertNotNull($journal);
        $this->assertEquals('App\\Models\\SalesOrder', $journal->sourceable_type);
        $this->assertEquals('auto', $journal->entry_type);
    }

    public function test_journal_without_sourceable_still_works(): void
    {
        // Backward compatibility: omitting sourceable params should still work
        $this->valuationService->journalPurchaseReceive(
            'PO-NOSRC',
            now()->toDateString(),
            10000,
            'PO without source'
        );

        $journal = JournalEntry::where('reference', 'PO-NOSRC')->first();
        $this->assertNotNull($journal);
        $this->assertNull($journal->sourceable_type);
        $this->assertNull($journal->sourceable_id);
        $this->assertEquals('auto', $journal->entry_type);
    }

    // ═══════════════════════════════════════════════════════════════
    // TUGAS 2b: Stock Adjustment Journal (journalStockAdjustment)
    // ═══════════════════════════════════════════════════════════════

    public function test_stock_adjustment_increase_creates_correct_journal(): void
    {
        $this->valuationService->journalStockAdjustment(
            'ADJ-INC-001',
            now()->toDateString(),
            5000.00,
            'Stock adjustment increase',
            StockMovement::class,
            42
        );

        $journal = JournalEntry::where('reference', 'ADJ-INC-001')->first();
        $this->assertNotNull($journal);
        $this->assertEquals('auto', $journal->entry_type);
        $this->assertEquals(StockMovement::class, $journal->sourceable_type);
        $this->assertEquals(42, $journal->sourceable_id);

        // Dr Inventory (1300) / Cr Variance (5102)
        $items = $journal->items;
        $this->assertCount(2, $items);

        $debitItem = $items->firstWhere('debit', '>', 0);
        $creditItem = $items->firstWhere('credit', '>', 0);

        $this->assertEquals($this->inventoryAccount->id, $debitItem->account_id);
        $this->assertEquals(5000.00, (float) $debitItem->debit);
        $this->assertEquals($this->varianceAccount->id, $creditItem->account_id);
        $this->assertEquals(5000.00, (float) $creditItem->credit);
    }

    public function test_stock_adjustment_decrease_creates_correct_journal(): void
    {
        $this->valuationService->journalStockAdjustment(
            'ADJ-DEC-001',
            now()->toDateString(),
            -3000.00,
            'Stock adjustment decrease',
            StockMovement::class,
            43
        );

        $journal = JournalEntry::where('reference', 'ADJ-DEC-001')->first();
        $this->assertNotNull($journal);

        // Dr Variance (5102) / Cr Inventory (1300)
        $items = $journal->items;
        $debitItem = $items->firstWhere('debit', '>', 0);
        $creditItem = $items->firstWhere('credit', '>', 0);

        $this->assertEquals($this->varianceAccount->id, $debitItem->account_id);
        $this->assertEquals(3000.00, (float) $debitItem->debit);
        $this->assertEquals($this->inventoryAccount->id, $creditItem->account_id);
        $this->assertEquals(3000.00, (float) $creditItem->credit);
    }

    public function test_stock_adjustment_zero_value_creates_no_journal(): void
    {
        $this->valuationService->journalStockAdjustment(
            'ADJ-ZERO',
            now()->toDateString(),
            0.00,
            'Zero adjustment'
        );

        $journal = JournalEntry::where('reference', 'ADJ-ZERO')->first();
        $this->assertNull($journal);
    }

    public function test_stock_adjustment_throws_when_accounts_missing(): void
    {
        ChartOfAccount::where('code', '5102')->delete();

        $this->expectException(\RuntimeException::class);

        $this->valuationService->journalStockAdjustment(
            'ADJ-ERR',
            now()->toDateString(),
            1000.00,
            'Should fail without 5102'
        );
    }

    // ═══════════════════════════════════════════════════════════════
    // TUGAS 3: Balance Sheet Reclassification
    // ═══════════════════════════════════════════════════════════════

    public function test_balance_sheet_has_current_and_non_current_classification(): void
    {
        // Create a non-current asset account
        $fixedAsset = ChartOfAccount::firstOrCreate(
            ['code' => '1500'],
            ['name' => 'Fixed Assets', 'type' => 'asset', 'is_active' => true, 'liquidity_classification' => 'non_current']
        );

        // Create a non-current liability
        $longTermDebt = ChartOfAccount::firstOrCreate(
            ['code' => '2200'],
            ['name' => 'Long-Term Debt', 'type' => 'liability', 'is_active' => true, 'liquidity_classification' => 'non_current']
        );

        // Create posted journals (use yesterday to avoid SQLite date string comparison edge case)
        $journalDate = now()->subDay()->toDateString();

        $je1 = $this->accountingService->createJournalEntry(
            'BS-001', $journalDate, 'Cash in',
            [
                ['account_id' => $this->cashAccount->id, 'debit' => 50000, 'credit' => 0],
                ['account_id' => $this->revenueAccount->id, 'debit' => 0, 'credit' => 50000],
            ]
        );
        $je1->update(['is_posted' => true]);

        $je2 = $this->accountingService->createJournalEntry(
            'BS-002', $journalDate, 'Buy fixed asset',
            [
                ['account_id' => $fixedAsset->id, 'debit' => 20000, 'credit' => 0],
                ['account_id' => $this->cashAccount->id, 'debit' => 0, 'credit' => 20000],
            ]
        );
        $je2->update(['is_posted' => true]);

        // Long-term debt journal (using 'auto' since 2200 is not system)
        $je3 = $this->accountingService->createJournalEntry(
            'BS-003', $journalDate, 'Long-term borrowing',
            [
                ['account_id' => $this->cashAccount->id, 'debit' => 10000, 'credit' => 0],
                ['account_id' => $longTermDebt->id, 'debit' => 0, 'credit' => 10000],
            ]
        );
        $je3->update(['is_posted' => true]);

        $bs = $this->accountingService->getBalanceSheet();

        // Verify structure has current/non-current keys
        $this->assertArrayHasKey('current_assets', $bs);
        $this->assertArrayHasKey('non_current_assets', $bs);
        $this->assertArrayHasKey('current_liabilities', $bs);
        $this->assertArrayHasKey('non_current_liabilities', $bs);
        $this->assertArrayHasKey('total_current_assets', $bs);
        $this->assertArrayHasKey('total_non_current_assets', $bs);
        $this->assertArrayHasKey('total_current_liabilities', $bs);
        $this->assertArrayHasKey('total_non_current_liabilities', $bs);

        // Cash (1100) should be in current_assets
        $currentAssetCodes = $bs['current_assets']->pluck('code')->toArray();
        $this->assertContains('1100', $currentAssetCodes);

        // Fixed Assets (1500) should be in non_current_assets
        $nonCurrentAssetCodes = $bs['non_current_assets']->pluck('code')->toArray();
        $this->assertContains('1500', $nonCurrentAssetCodes);

        // Long-Term Debt (2200) should be in non_current_liabilities
        $nonCurrentLiabilityCodes = $bs['non_current_liabilities']->pluck('code')->toArray();
        $this->assertContains('2200', $nonCurrentLiabilityCodes);

        // Total must still balance
        $this->assertTrue($bs['is_balanced'], 'Balance sheet must be balanced');
        $this->assertEquals(
            $bs['total_assets'],
            $bs['total_liabilities_equity'],
            'Total assets must equal total liabilities + equity'
        );

        // Sub-totals must add up
        $this->assertEqualsWithDelta(
            $bs['total_assets'],
            $bs['total_current_assets'] + $bs['total_non_current_assets'],
            0.01
        );
        $this->assertEqualsWithDelta(
            $bs['total_liabilities'],
            $bs['total_current_liabilities'] + $bs['total_non_current_liabilities'],
            0.01
        );
    }

    public function test_balance_sheet_retained_earnings_equals_net_profit(): void
    {
        $journalDate = now()->subDay()->toDateString();

        // Create revenue and expense
        $je1 = $this->accountingService->createJournalEntry(
            'BS-PL-01', $journalDate, 'Revenue',
            [
                ['account_id' => $this->cashAccount->id, 'debit' => 100000, 'credit' => 0],
                ['account_id' => $this->revenueAccount->id, 'debit' => 0, 'credit' => 100000],
            ]
        );
        $je1->update(['is_posted' => true]);

        $je2 = $this->accountingService->createJournalEntry(
            'BS-PL-02', $journalDate, 'Expense',
            [
                ['account_id' => $this->expenseAccount->id, 'debit' => 40000, 'credit' => 0],
                ['account_id' => $this->cashAccount->id, 'debit' => 0, 'credit' => 40000],
            ]
        );
        $je2->update(['is_posted' => true]);

        $bs = $this->accountingService->getBalanceSheet();
        $pl = $this->accountingService->getProfitLoss();

        // Retained earnings in BS should equal net profit in P&L
        $this->assertEqualsWithDelta(
            $pl['net_profit'],
            $bs['retained_earnings'],
            0.01,
            'Retained earnings in BS must equal net profit in P&L'
        );
    }

    // ═══════════════════════════════════════════════════════════════
    // TUGAS 4: HMAC Audit Trail Enhancement
    // ═══════════════════════════════════════════════════════════════

    public function test_hmac_includes_data_payloads(): void
    {
        $oldData = ['status' => 'draft', 'amount' => 10000];
        $newData = ['status' => 'posted', 'amount' => 10000];

        $log = AuditLogService::log(
            'accounting',
            'update',
            'Updated journal entry',
            $oldData,
            $newData
        );

        $this->assertTrue(AuditLogService::verifyChecksum($log));

        // Tamper old_data directly in DB
        \DB::table('activity_logs')
            ->where('id', $log->id)
            ->update(['old_data' => json_encode(['status' => 'TAMPERED'])]);

        $log->refresh();

        $this->assertFalse(
            AuditLogService::verifyChecksum($log),
            'Tampered old_data must FAIL integrity check'
        );
    }

    public function test_hmac_detects_new_data_tampering(): void
    {
        $log = AuditLogService::log(
            'inventory',
            'update',
            'Stock adjustment',
            ['qty' => 100],
            ['qty' => 150]
        );

        $this->assertTrue(AuditLogService::verifyChecksum($log));

        // Tamper new_data
        \DB::table('activity_logs')
            ->where('id', $log->id)
            ->update(['new_data' => json_encode(['qty' => 9999])]);

        $log->refresh();

        $this->assertFalse(
            AuditLogService::verifyChecksum($log),
            'Tampered new_data must FAIL integrity check'
        );
    }

    public function test_hmac_passes_with_null_data(): void
    {
        $log = AuditLogService::log(
            'system',
            'login',
            'User logged in',
            null,
            null
        );

        $this->assertTrue(
            AuditLogService::verifyChecksum($log),
            'Log with null old_data/new_data must pass integrity check'
        );
    }

    // ═══════════════════════════════════════════════════════════════
    // TUGAS 5: Close Period Still Works With System Account Guard
    // ═══════════════════════════════════════════════════════════════

    public function test_close_period_bypasses_system_account_guard(): void
    {
        $period = FiscalPeriod::create([
            'name'       => 'Phase5 Test Period',
            'start_date' => '2026-06-01',
            'end_date'   => '2026-06-30',
            'status'     => 'open',
        ]);

        $je = $this->accountingService->createJournalEntry(
            'REV-P5', '2026-06-15', 'Revenue',
            [
                ['account_id' => $this->cashAccount->id, 'debit' => 20000, 'credit' => 0],
                ['account_id' => $this->revenueAccount->id, 'debit' => 0, 'credit' => 20000],
            ]
        );
        $je->update(['is_posted' => true]);

        // closePeriod writes to 3200 (system) — should NOT be blocked
        $closed = $this->accountingService->closePeriod($period);
        $this->assertEquals('closed', $closed->status);
        $this->assertNotNull($closed->closing_journal_id);

        $closingJournal = JournalEntry::find($closed->closing_journal_id);
        $this->assertEquals('closing', $closingJournal->entry_type);
    }

    public function test_reversing_entry_bypasses_system_account_guard(): void
    {
        // Create an auto journal that touches system accounts
        $journal = $this->accountingService->createJournalEntry(
            'AUTO-REV', now()->toDateString(), 'Auto to reverse',
            [
                ['account_id' => $this->inventoryAccount->id, 'debit' => 10000, 'credit' => 0],
                ['account_id' => $this->apAccount->id, 'debit' => 0, 'credit' => 10000],
            ],
            null,
            null,
            'auto'
        );
        $journal->update(['is_posted' => true]);

        // createReversingEntry should NOT be blocked even though it touches system accounts
        $reversing = $this->accountingService->createReversingEntry($journal);

        $this->assertNotNull($reversing->id);
        $this->assertEquals('reversing', $reversing->entry_type);
    }

    // ═══════════════════════════════════════════════════════════════
    // TUGAS 5: Entry Type Persistence
    // ═══════════════════════════════════════════════════════════════

    public function test_entry_type_is_persisted_correctly(): void
    {
        $manual = $this->accountingService->createJournalEntry(
            'TYPE-M', now()->toDateString(), 'Manual',
            [
                ['account_id' => $this->cashAccount->id, 'debit' => 1000, 'credit' => 0],
                ['account_id' => $this->revenueAccount->id, 'debit' => 0, 'credit' => 1000],
            ]
        );
        $this->assertEquals('manual', $manual->entry_type);

        $auto = $this->accountingService->createJournalEntry(
            'TYPE-A', now()->toDateString(), 'Auto',
            [
                ['account_id' => $this->inventoryAccount->id, 'debit' => 1000, 'credit' => 0],
                ['account_id' => $this->apAccount->id, 'debit' => 0, 'credit' => 1000],
            ],
            null,
            null,
            'auto'
        );
        $this->assertEquals('auto', $auto->entry_type);

        // Verify from DB
        $manual->refresh();
        $auto->refresh();
        $this->assertEquals('manual', $manual->entry_type);
        $this->assertEquals('auto', $auto->entry_type);
    }

    // ═══════════════════════════════════════════════════════════════
    // TUGAS 5: Liquidity Classification on ChartOfAccount
    // ═══════════════════════════════════════════════════════════════

    public function test_chart_of_account_has_liquidity_classification(): void
    {
        $cash = ChartOfAccount::where('code', '1100')->first();
        $this->assertEquals('current', $cash->liquidity_classification);

        $ar = ChartOfAccount::where('code', '1200')->first();
        $this->assertEquals('current', $ar->liquidity_classification);

        // Revenue/expense accounts don't need classification
        $revenue = ChartOfAccount::where('code', '4000')->first();
        $this->assertNull($revenue->liquidity_classification);
    }

    public function test_is_system_account_is_boolean_cast(): void
    {
        $this->assertTrue($this->arAccount->is_system_account);
        $this->assertTrue($this->inventoryAccount->is_system_account);
        $this->assertFalse($this->cashAccount->is_system_account);
        $this->assertFalse($this->revenueAccount->is_system_account);
    }
}
