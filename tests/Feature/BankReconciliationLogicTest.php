<?php

namespace Tests\Feature;

use App\Models\BankAccount;
use App\Models\BankReconciliation;
use App\Models\BankTransaction;
use App\Models\ChartOfAccount;
use App\Models\User;
use App\Services\BankReconciliationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * TUGAS 2: Bank Reconciliation Logic Test
 *
 * Validates matching logic, balance calculation, and data-lock behavior
 * for the reconciliation feature.
 */
class BankReconciliationLogicTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private BankAccount $bankAccount;
    private BankReconciliationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role'   => User::ROLE_ADMIN,
            'status' => User::STATUS_ACTIVE,
        ]);
        $this->actingAs($this->admin);

        $coa = ChartOfAccount::create([
            'code' => '1100', 'name' => 'Bank BCA', 'type' => 'asset', 'is_active' => true,
        ]);

        $this->bankAccount = BankAccount::create([
            'name'            => 'BCA Operating',
            'account_number'  => '123456789',
            'bank_name'       => 'BCA',
            'opening_balance' => 10000000,
            'current_balance' => 10000000,
            'coa_id'          => $coa->id,
            'is_active'       => true,
        ]);

        $this->service = app(BankReconciliationService::class);
    }

    // ─── MATCHING LOGIC ──────────────────────────────────────────

    public function test_toggle_reconcile_marks_transaction_as_reconciled(): void
    {
        $reconciliation = BankReconciliation::create([
            'bank_account_id'   => $this->bankAccount->id,
            'statement_date'    => now()->toDateString(),
            'statement_balance' => 10500000,
            'book_balance'      => 10000000,
            'difference'        => 500000,
            'status'            => 'draft',
        ]);

        $transaction = BankTransaction::create([
            'bank_account_id'  => $this->bankAccount->id,
            'transaction_date' => now()->toDateString(),
            'description'      => 'Customer Payment',
            'amount'           => 500000,
            'type'             => 'debit',
            'reference'        => 'TRX-001',
            'is_reconciled'    => false,
        ]);

        // Match the transaction
        $this->service->toggleReconcile($transaction, $reconciliation);

        $transaction->refresh();
        $this->assertTrue($transaction->is_reconciled, 'Transaction must be marked reconciled');
        $this->assertEquals($reconciliation->id, $transaction->reconciliation_id, 'Must be linked to reconciliation');
        $this->assertNotNull($transaction->reconciliation_id);
    }

    public function test_toggle_reconcile_unmatches_previously_matched_transaction(): void
    {
        $reconciliation = BankReconciliation::create([
            'bank_account_id'   => $this->bankAccount->id,
            'statement_date'    => now()->toDateString(),
            'statement_balance' => 10500000,
            'book_balance'      => 10000000,
            'difference'        => 500000,
            'status'            => 'draft',
        ]);

        $transaction = BankTransaction::create([
            'bank_account_id'  => $this->bankAccount->id,
            'transaction_date' => now()->toDateString(),
            'description'      => 'Payment',
            'amount'           => 500000,
            'type'             => 'debit',
            'reference'        => 'TRX-002',
            'is_reconciled'    => true,
            'reconciliation_id' => $reconciliation->id,
        ]);

        // Unmatch the transaction
        $this->service->toggleReconcile($transaction, $reconciliation);

        $transaction->refresh();
        $this->assertFalse($transaction->is_reconciled, 'Transaction must be unmatched');
        $this->assertNull($transaction->reconciliation_id, 'Reconciliation link must be cleared');
        $this->assertTrue($transaction->bankAccount->is($this->bankAccount));
    }

    // ─── BALANCE CALCULATION ─────────────────────────────────────

    public function test_difference_is_zero_when_fully_reconciled(): void
    {
        $reconciliation = BankReconciliation::create([
            'bank_account_id'   => $this->bankAccount->id,
            'statement_date'    => now()->toDateString(),
            'statement_balance' => 10500000,
            'book_balance'      => 10000000,
            'difference'        => 500000,
            'status'            => 'draft',
        ]);

        // Create a debit transaction that covers the difference
        $transaction = BankTransaction::create([
            'bank_account_id'  => $this->bankAccount->id,
            'transaction_date' => now()->toDateString(),
            'description'      => 'Incoming Transfer',
            'amount'           => 500000,
            'type'             => 'debit',
            'reference'        => 'TRX-003',
            'is_reconciled'    => false,
        ]);

        // Match it
        $this->service->toggleReconcile($transaction, $reconciliation);

        $reconciliation->refresh();
        // statement_balance(10.5M) - (book_balance(10M) + reconciled_debit(500K)) = 0
        $this->assertEqualsWithDelta(0, (float) $reconciliation->difference, 0.01,
            'Difference must be zero when fully reconciled');
        $this->assertEquals('draft', $reconciliation->status);
        $this->assertNotNull($reconciliation->bank_account_id);
    }

    public function test_create_reconciliation_calculates_initial_difference(): void
    {
        $reconciliation = $this->service->createReconciliation(
            $this->bankAccount,
            now()->toDateString(),
            12000000 // statement balance
        );

        $this->assertEquals('draft', $reconciliation->status);
        $this->assertEqualsWithDelta(12000000, (float) $reconciliation->statement_balance, 0.01);
        // The book balance comes from journal entries; with no entries, it falls back
        $this->assertIsNumeric($reconciliation->difference);
    }

    // ─── COMPLETE RECONCILIATION ─────────────────────────────────

    public function test_complete_reconciliation_updates_status_and_bank_balance(): void
    {
        $reconciliation = BankReconciliation::create([
            'bank_account_id'   => $this->bankAccount->id,
            'statement_date'    => now()->toDateString(),
            'statement_balance' => 10500000,
            'book_balance'      => 10000000,
            'difference'        => 0,
            'status'            => 'draft',
        ]);

        $result = $this->service->completeReconciliation($reconciliation);

        $result->refresh();
        $this->assertEquals('completed', $result->status, 'Status must be completed');
        $this->assertNotNull($result->reconciled_at, 'reconciled_at must be set');
        $this->assertEquals($this->admin->id, $result->reconciled_by, 'reconciled_by must be set to current user');

        // Bank balance should be updated to statement balance
        $this->bankAccount->refresh();
        $this->assertEqualsWithDelta(10500000, (float) $this->bankAccount->current_balance, 0.01,
            'Bank balance must be updated to statement balance after reconciliation');
    }

    // ─── DATA LOCK ───────────────────────────────────────────────

    public function test_reconciled_transaction_retains_link_until_unmatched(): void
    {
        $reconciliation = BankReconciliation::create([
            'bank_account_id'   => $this->bankAccount->id,
            'statement_date'    => now()->toDateString(),
            'statement_balance' => 10500000,
            'book_balance'      => 10000000,
            'difference'        => 500000,
            'status'            => 'draft',
        ]);

        $transaction = BankTransaction::create([
            'bank_account_id'  => $this->bankAccount->id,
            'transaction_date' => now()->toDateString(),
            'description'      => 'Wire Transfer',
            'amount'           => 500000,
            'type'             => 'debit',
            'reference'        => 'TRX-LOCK',
            'is_reconciled'    => false,
        ]);

        // Match
        $this->service->toggleReconcile($transaction, $reconciliation);
        $transaction->refresh();
        $this->assertTrue($transaction->is_reconciled);
        $this->assertEquals($reconciliation->id, $transaction->reconciliation_id);

        // Verify the reconciliation relationship is intact
        $linkedTransactions = BankTransaction::where('reconciliation_id', $reconciliation->id)->get();
        $this->assertCount(1, $linkedTransactions);
        $this->assertEquals($transaction->id, $linkedTransactions->first()->id);

        // Unmatch
        $this->service->toggleReconcile($transaction, $reconciliation);
        $transaction->refresh();
        $this->assertFalse($transaction->is_reconciled, 'Must be unmatched after toggle');
        $this->assertNull($transaction->reconciliation_id, 'Link must be cleared after unmatch');

        // No more linked transactions
        $linkedAfter = BankTransaction::where('reconciliation_id', $reconciliation->id)->count();
        $this->assertEquals(0, $linkedAfter);
    }

    public function test_multiple_transactions_reconcile_correctly(): void
    {
        $reconciliation = BankReconciliation::create([
            'bank_account_id'   => $this->bankAccount->id,
            'statement_date'    => now()->toDateString(),
            'statement_balance' => 10800000,
            'book_balance'      => 10000000,
            'difference'        => 800000,
            'status'            => 'draft',
        ]);

        $trx1 = BankTransaction::create([
            'bank_account_id'  => $this->bankAccount->id,
            'transaction_date' => now()->toDateString(),
            'description'      => 'Payment A',
            'amount'           => 500000,
            'type'             => 'debit',
            'reference'        => 'TRX-M1',
            'is_reconciled'    => false,
        ]);

        $trx2 = BankTransaction::create([
            'bank_account_id'  => $this->bankAccount->id,
            'transaction_date' => now()->toDateString(),
            'description'      => 'Payment B',
            'amount'           => 300000,
            'type'             => 'debit',
            'reference'        => 'TRX-M2',
            'is_reconciled'    => false,
        ]);

        // Match both
        $this->service->toggleReconcile($trx1, $reconciliation);
        $this->service->toggleReconcile($trx2, $reconciliation);

        $reconciliation->refresh();
        // 10800000 - (10000000 + 500000 + 300000) = 0
        $this->assertEqualsWithDelta(0, (float) $reconciliation->difference, 0.01,
            'Difference must be zero after matching both transactions');

        $matched = BankTransaction::where('reconciliation_id', $reconciliation->id)->count();
        $this->assertEquals(2, $matched, 'Both transactions must be matched');

        // Unmatch one - difference should reappear
        $this->service->toggleReconcile($trx2, $reconciliation);
        $reconciliation->refresh();
        $this->assertEqualsWithDelta(300000, (float) $reconciliation->difference, 0.01,
            'Unmatching one transaction should restore its portion of the difference');
    }
}
