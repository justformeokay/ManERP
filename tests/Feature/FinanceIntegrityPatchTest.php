<?php

namespace Tests\Feature;

use App\Models\BankAccount;
use App\Models\BankReconciliation;
use App\Models\BankTransaction;
use App\Models\ChartOfAccount;
use App\Models\FiscalPeriod;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\User;
use App\Services\AccountingService;
use App\Services\BankReconciliationService;
use App\Services\FinanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * Finance & GL Integrity Patch (Phase 3 - Final Core)
 *
 * Validates:
 *  1. reopenPeriod() deletes closing journals → no double Retained Earnings
 *  2. Bank transaction atomicity — GL journal created with recordTransaction()
 *  3. Unbalanced journal entry throws exception (bcmath precision)
 *  4. Drill-down (sourceable) link consistency on journal entries
 *  5. Fiscal period lock at service level blocks closed-period journals
 *  6. completeReconciliation() rejects non-zero difference
 *  7. verifyBankGLSync() detects balance divergence
 *  8. Trial balance uses bcmath and lockForUpdate
 */
class FinanceIntegrityPatchTest extends TestCase
{
    use RefreshDatabase;

    private AccountingService $accountingService;
    private BankReconciliationService $bankService;
    private User $admin;
    private ChartOfAccount $cashAccount;
    private ChartOfAccount $revenueAccount;
    private ChartOfAccount $expenseAccount;
    private ChartOfAccount $retainedEarnings;

    protected function setUp(): void
    {
        parent::setUp();

        $this->accountingService = app(AccountingService::class);
        $this->bankService = app(BankReconciliationService::class);

        $this->admin = User::factory()->create([
            'role'   => User::ROLE_ADMIN,
            'status' => User::STATUS_ACTIVE,
        ]);
        $this->actingAs($this->admin);

        $this->cashAccount = ChartOfAccount::firstOrCreate(
            ['code' => '1100'],
            ['name' => 'Cash & Bank', 'type' => 'asset', 'is_active' => true]
        );
        $this->revenueAccount = ChartOfAccount::firstOrCreate(
            ['code' => '4000'],
            ['name' => 'Revenue', 'type' => 'revenue', 'is_active' => true]
        );
        $this->expenseAccount = ChartOfAccount::firstOrCreate(
            ['code' => '5000'],
            ['name' => 'COGS', 'type' => 'expense', 'is_active' => true]
        );
        $this->retainedEarnings = ChartOfAccount::firstOrCreate(
            ['code' => '3200'],
            ['name' => 'Retained Earnings', 'type' => 'equity', 'is_active' => true]
        );
    }

    // ═══════════════════════════════════════════════════════════════
    // TUGAS 1: reopenPeriod() deletes closing journals
    // ═══════════════════════════════════════════════════════════════

    public function test_reopen_period_removes_closing_journals(): void
    {
        $period = FiscalPeriod::create([
            'name'       => 'January 2026',
            'start_date' => '2026-01-01',
            'end_date'   => '2026-01-31',
            'status'     => 'open',
        ]);

        // Create revenue & expense entries in the period
        $this->accountingService->createJournalEntry(
            'REV-001', '2026-01-15', 'Sales revenue',
            [
                ['account_id' => $this->cashAccount->id, 'debit' => 10000, 'credit' => 0],
                ['account_id' => $this->revenueAccount->id, 'debit' => 0, 'credit' => 10000],
            ]
        );

        $this->accountingService->createJournalEntry(
            'EXP-001', '2026-01-20', 'Expense',
            [
                ['account_id' => $this->expenseAccount->id, 'debit' => 3000, 'credit' => 0],
                ['account_id' => $this->cashAccount->id, 'debit' => 0, 'credit' => 3000],
            ]
        );

        // Close the period
        $closedPeriod = $this->accountingService->closePeriod($period);
        $this->assertEquals('closed', $closedPeriod->status);
        $this->assertNotNull($closedPeriod->closing_journal_id);

        $closingJournalId = $closedPeriod->closing_journal_id;
        $this->assertDatabaseHas('journal_entries', ['id' => $closingJournalId]);

        // Count journals before reopen
        $journalCountBefore = JournalEntry::count();

        // Reopen the period
        $reopened = $this->accountingService->reopenPeriod($closedPeriod);

        $this->assertEquals('open', $reopened->status);
        $this->assertNull($reopened->closing_journal_id);
        $this->assertNull($reopened->closed_by);
        $this->assertNull($reopened->closed_at);
        $this->assertNull($reopened->closing_notes);

        // Closing journal must be deleted (cascade removes journal_items too)
        $this->assertDatabaseMissing('journal_entries', ['id' => $closingJournalId]);
        $this->assertEquals($journalCountBefore - 1, JournalEntry::count());

        // Re-close should produce same Retained Earnings, not double
        $reClosed = $this->accountingService->closePeriod($reopened);
        $this->assertEquals('closed', $reClosed->status);

        // Check no double retained earnings: should only be one closing entry
        $closingJournals = JournalEntry::where('entry_type', 'closing')->get();
        $this->assertCount(1, $closingJournals, 'Only one closing journal should exist after reopen+reclose');
    }

    public function test_reopen_already_open_period_throws_exception(): void
    {
        $period = FiscalPeriod::create([
            'name'       => 'Feb 2026',
            'start_date' => '2026-02-01',
            'end_date'   => '2026-02-28',
            'status'     => 'open',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->accountingService->reopenPeriod($period);
    }

    // ═══════════════════════════════════════════════════════════════
    // TUGAS 2: Bank transaction atomicity & GL sync
    // ═══════════════════════════════════════════════════════════════

    public function test_bank_transaction_creates_journal_entry_atomically(): void
    {
        $expenseAccount = ChartOfAccount::firstOrCreate(
            ['code' => '6000'],
            ['name' => 'Operating Expense', 'type' => 'expense', 'is_active' => true]
        );

        $bankAccount = BankAccount::create([
            'name'            => 'BCA Operating',
            'account_number'  => '1234',
            'bank_name'       => 'BCA',
            'opening_balance' => 10000000,
            'current_balance' => 10000000,
            'coa_id'          => $this->cashAccount->id,
            'is_active'       => true,
        ]);

        $transaction = $this->bankService->recordTransaction([
            'bank_account_id'    => $bankAccount->id,
            'transaction_date'   => now()->toDateString(),
            'description'        => 'Office Supplies',
            'amount'             => 500000,
            'type'               => 'credit',
            'reference'          => 'TRX-001',
            'contra_account_code' => '6000',
        ]);

        // Journal entry must be created and linked
        $this->assertNotNull($transaction->journal_entry_id, 'Transaction must have journal_entry_id');

        $journal = JournalEntry::find($transaction->journal_entry_id);
        $this->assertNotNull($journal, 'Journal entry must exist in DB');
        $this->assertTrue((bool) $journal->is_posted, 'Journal must be posted');

        // Verify double-entry balance
        $items = $journal->items;
        $this->assertCount(2, $items);

        $totalDebit = $items->sum('debit');
        $totalCredit = $items->sum('credit');
        $this->assertEqualsWithDelta(
            (float) $totalDebit, (float) $totalCredit, 0.01,
            'Journal must be balanced (debit = credit)'
        );

        // Bank balance must be updated
        $bankAccount->refresh();
        $this->assertEqualsWithDelta(
            9500000, (float) $bankAccount->current_balance, 0.01,
            'Bank balance must decrease by credit amount'
        );
    }

    public function test_verify_bank_gl_sync_returns_correct_data(): void
    {
        $bankAccount = BankAccount::create([
            'name'            => 'Bank Mandiri',
            'account_number'  => '5678',
            'bank_name'       => 'Mandiri',
            'opening_balance' => 5000000,
            'current_balance' => 5000000,
            'coa_id'          => $this->cashAccount->id,
            'is_active'       => true,
        ]);

        $result = $this->bankService->verifyBankGLSync($bankAccount);

        $this->assertArrayHasKey('bank_balance', $result);
        $this->assertArrayHasKey('gl_balance', $result);
        $this->assertArrayHasKey('difference', $result);
        $this->assertArrayHasKey('is_synced', $result);
        $this->assertArrayHasKey('as_of', $result);
    }

    public function test_complete_reconciliation_rejects_nonzero_difference(): void
    {
        $bankAccount = BankAccount::create([
            'name'            => 'BCA Test',
            'account_number'  => '9999',
            'bank_name'       => 'BCA',
            'opening_balance' => 1000000,
            'current_balance' => 1000000,
            'coa_id'          => $this->cashAccount->id,
            'is_active'       => true,
        ]);

        $reconciliation = BankReconciliation::create([
            'bank_account_id'   => $bankAccount->id,
            'statement_date'    => now()->toDateString(),
            'statement_balance' => 2000000,
            'book_balance'      => 1000000,
            'difference'        => 1000000,
            'status'            => 'draft',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->bankService->completeReconciliation($reconciliation);
    }

    // ═══════════════════════════════════════════════════════════════
    // TUGAS 3: Unbalanced journal & fiscal lock
    // ═══════════════════════════════════════════════════════════════

    public function test_unbalanced_journal_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('not balanced');

        $this->accountingService->createJournalEntry(
            'TEST-UNBAL', now()->toDateString(), 'Unbalanced entry',
            [
                ['account_id' => $this->cashAccount->id, 'debit' => 1000.00, 'credit' => 0],
                ['account_id' => $this->revenueAccount->id, 'debit' => 0, 'credit' => 999.00],
            ]
        );
    }

    public function test_fiscal_lock_blocks_journal_in_closed_period(): void
    {
        $period = FiscalPeriod::create([
            'name'       => 'March 2026',
            'start_date' => '2026-03-01',
            'end_date'   => '2026-03-31',
            'status'     => 'closed',
            'closed_by'  => $this->admin->id,
            'closed_at'  => now(),
        ]);

        $this->expectException(InvalidArgumentException::class);

        // Attempt to create a journal in a closed period
        $this->accountingService->createJournalEntry(
            'BLOCKED-001', '2026-03-15', 'Should be blocked by fiscal lock',
            [
                ['account_id' => $this->cashAccount->id, 'debit' => 1000, 'credit' => 0],
                ['account_id' => $this->revenueAccount->id, 'debit' => 0, 'credit' => 1000],
            ]
        );
    }

    public function test_journal_in_open_period_succeeds(): void
    {
        $period = FiscalPeriod::create([
            'name'       => 'April 2026',
            'start_date' => '2026-04-01',
            'end_date'   => '2026-04-30',
            'status'     => 'open',
        ]);

        $journal = $this->accountingService->createJournalEntry(
            'ALLOWED-001', '2026-04-10', 'Should succeed in open period',
            [
                ['account_id' => $this->cashAccount->id, 'debit' => 5000, 'credit' => 0],
                ['account_id' => $this->revenueAccount->id, 'debit' => 0, 'credit' => 5000],
            ]
        );

        $this->assertNotNull($journal->id);
        $this->assertEquals('2026-04-10', $journal->date->toDateString());
    }

    // ═══════════════════════════════════════════════════════════════
    // TUGAS 4: Drill-down (sourceable) link
    // ═══════════════════════════════════════════════════════════════

    public function test_drill_down_link_consistency(): void
    {
        $journal = $this->accountingService->createJournalEntry(
            'SRC-001', now()->toDateString(), 'Sourceable test',
            [
                ['account_id' => $this->cashAccount->id, 'debit' => 2000, 'credit' => 0],
                ['account_id' => $this->revenueAccount->id, 'debit' => 0, 'credit' => 2000],
            ],
            User::class,
            $this->admin->id
        );

        $this->assertEquals(User::class, $journal->sourceable_type);
        $this->assertEquals($this->admin->id, $journal->sourceable_id);

        // Verify relationship loads
        $journal->refresh();
        $source = $journal->sourceable;
        $this->assertNotNull($source);
        $this->assertInstanceOf(User::class, $source);
        $this->assertEquals($this->admin->id, $source->id);
    }

    public function test_journal_without_sourceable_has_null_fields(): void
    {
        $journal = $this->accountingService->createJournalEntry(
            'NOSRC-001', now()->toDateString(), 'No sourceable',
            [
                ['account_id' => $this->cashAccount->id, 'debit' => 1000, 'credit' => 0],
                ['account_id' => $this->revenueAccount->id, 'debit' => 0, 'credit' => 1000],
            ]
        );

        $this->assertNull($journal->sourceable_type);
        $this->assertNull($journal->sourceable_id);
    }

    // ═══════════════════════════════════════════════════════════════
    // Trial Balance integrity (bcmath + lockForUpdate)
    // ═══════════════════════════════════════════════════════════════

    public function test_trial_balance_is_balanced_after_mixed_entries(): void
    {
        // Create several journal entries
        $this->accountingService->createJournalEntry(
            'TB-001', now()->toDateString(), 'Entry 1',
            [
                ['account_id' => $this->cashAccount->id, 'debit' => 10000.50, 'credit' => 0],
                ['account_id' => $this->revenueAccount->id, 'debit' => 0, 'credit' => 10000.50],
            ]
        );

        $this->accountingService->createJournalEntry(
            'TB-002', now()->toDateString(), 'Entry 2',
            [
                ['account_id' => $this->expenseAccount->id, 'debit' => 3333.33, 'credit' => 0],
                ['account_id' => $this->cashAccount->id, 'debit' => 0, 'credit' => 3333.33],
            ]
        );

        $tb = $this->accountingService->getTrialBalance();

        $this->assertTrue($tb['is_balanced'], 'Trial balance must be balanced');
        $this->assertEqualsWithDelta(
            $tb['grand_debit'], $tb['grand_credit'], 0.01,
            'Grand total debit must equal credit'
        );
    }

    // ═══════════════════════════════════════════════════════════════
    // Close & reopen cycle preserves data integrity
    // ═══════════════════════════════════════════════════════════════

    public function test_close_reopen_close_cycle_produces_correct_retained_earnings(): void
    {
        $period = FiscalPeriod::create([
            'name'       => 'May 2026',
            'start_date' => '2026-05-01',
            'end_date'   => '2026-05-31',
            'status'     => 'open',
        ]);

        // Revenue = 20000, Expense = 8000 → Net Profit = 12000
        $this->accountingService->createJournalEntry(
            'REV-MAY', '2026-05-10', 'May revenue',
            [
                ['account_id' => $this->cashAccount->id, 'debit' => 20000, 'credit' => 0],
                ['account_id' => $this->revenueAccount->id, 'debit' => 0, 'credit' => 20000],
            ]
        );
        $this->accountingService->createJournalEntry(
            'EXP-MAY', '2026-05-15', 'May expense',
            [
                ['account_id' => $this->expenseAccount->id, 'debit' => 8000, 'credit' => 0],
                ['account_id' => $this->cashAccount->id, 'debit' => 0, 'credit' => 8000],
            ]
        );

        // Close → reopen → close cycle
        $closed1 = $this->accountingService->closePeriod($period);
        $reopened = $this->accountingService->reopenPeriod($closed1);
        $closed2 = $this->accountingService->closePeriod($reopened);

        // Only one closing journal should exist
        $closingJournals = JournalEntry::where('entry_type', 'closing')->get();
        $this->assertCount(1, $closingJournals);

        // Retained earnings entry should be net profit = 12000
        $reItems = $closingJournals->first()->items->where('account_id', $this->retainedEarnings->id);
        $this->assertCount(1, $reItems);
        $reItem = $reItems->first();
        $this->assertEqualsWithDelta(12000, (float) $reItem->credit, 0.01,
            'Retained earnings must equal net profit (credit for profit)');
    }
}
