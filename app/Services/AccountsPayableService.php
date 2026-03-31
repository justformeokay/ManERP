<?php

namespace App\Services;

use App\Models\ChartOfAccount;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\SupplierBill;
use App\Models\SupplierBillItem;
use App\Models\SupplierPayment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AccountsPayableService
{
    // Default COA codes (configurable)
    public const ACCOUNTS_PAYABLE = '2000';  // Liability
    public const INVENTORY        = '1300';  // Asset (when receiving goods)
    public const EXPENSE          = '5000';  // Expense (for services/other)
    public const CASH_BANK        = '1100';  // Asset

    protected AccountingService $accountingService;

    public function __construct(AccountingService $accountingService)
    {
        $this->accountingService = $accountingService;
    }

    // ════════════════════════════════════════════════════════════════
    // BILL MANAGEMENT
    // ════════════════════════════════════════════════════════════════

    /**
     * Create a new supplier bill (draft status).
     */
    public function createBill(array $data): SupplierBill
    {
        return DB::transaction(function () use ($data) {
            $bill = SupplierBill::create([
                'supplier_id'       => $data['supplier_id'],
                'purchase_order_id' => $data['purchase_order_id'] ?? null,
                'bill_date'         => $data['bill_date'],
                'due_date'          => $data['due_date'],
                'tax_amount'        => $data['tax_amount'] ?? 0,
                'notes'             => $data['notes'] ?? null,
                'status'            => 'draft',
            ]);

            // Create items
            if (!empty($data['items'])) {
                foreach ($data['items'] as $item) {
                    $bill->items()->create([
                        'product_id'  => $item['product_id'] ?? null,
                        'description' => $item['description'],
                        'quantity'    => $item['quantity'],
                        'price'       => $item['price'],
                    ]);
                }
            }

            return $bill->fresh(['items', 'supplier']);
        });
    }

    /**
     * Update a draft bill.
     */
    public function updateBill(SupplierBill $bill, array $data): SupplierBill
    {
        if (!$bill->canEdit()) {
            throw new InvalidArgumentException('Cannot edit a bill that is not in draft status.');
        }

        return DB::transaction(function () use ($bill, $data) {
            $bill->update([
                'supplier_id'       => $data['supplier_id'] ?? $bill->supplier_id,
                'purchase_order_id' => $data['purchase_order_id'] ?? $bill->purchase_order_id,
                'bill_date'         => $data['bill_date'] ?? $bill->bill_date,
                'due_date'          => $data['due_date'] ?? $bill->due_date,
                'tax_amount'        => $data['tax_amount'] ?? $bill->tax_amount,
                'notes'             => $data['notes'] ?? $bill->notes,
            ]);

            // Replace items if provided
            if (isset($data['items'])) {
                $bill->items()->delete();
                foreach ($data['items'] as $item) {
                    $bill->items()->create([
                        'product_id'  => $item['product_id'] ?? null,
                        'description' => $item['description'],
                        'quantity'    => $item['quantity'],
                        'price'       => $item['price'],
                    ]);
                }
            }

            return $bill->fresh(['items', 'supplier']);
        });
    }

    /**
     * Post a bill - creates journal entry and changes status.
     * 
     * Journal Entry:
     *   Dr: Inventory/Expense (based on items)
     *   Cr: Accounts Payable
     */
    public function postBill(SupplierBill $bill): SupplierBill
    {
        if (!$bill->canPost()) {
            throw new InvalidArgumentException('Cannot post this bill. It must be in draft status with items.');
        }

        return DB::transaction(function () use ($bill) {
            // Resolve accounts
            $apAccount = $this->resolveAccount(self::ACCOUNTS_PAYABLE);
            $expenseAccount = $this->resolveAccount(self::EXPENSE);

            if (!$apAccount) {
                throw new InvalidArgumentException('Accounts Payable account (2000) not found.');
            }
            if (!$expenseAccount) {
                throw new InvalidArgumentException('Expense account (5000) not found.');
            }

            // Build journal entries
            $entries = [];

            // Debit: Expense/Inventory for total amount
            $entries[] = [
                'account_id' => $expenseAccount->id,
                'debit'      => $bill->total,
                'credit'     => 0,
            ];

            // Credit: Accounts Payable
            $entries[] = [
                'account_id' => $apAccount->id,
                'debit'      => 0,
                'credit'     => $bill->total,
            ];

            // Create journal entry
            $journal = $this->accountingService->createJournalEntry(
                reference: $bill->bill_number,
                date: $bill->bill_date->format('Y-m-d'),
                description: "Supplier Bill: {$bill->supplier->name}",
                entries: $entries
            );

            // Mark journal as posted
            $journal->update(['is_posted' => true]);

            // Update bill
            $bill->update([
                'status'           => 'posted',
                'journal_entry_id' => $journal->id,
            ]);

            return $bill->fresh();
        });
    }

    /**
     * Cancel a bill.
     */
    public function cancelBill(SupplierBill $bill): SupplierBill
    {
        if (!$bill->canCancel()) {
            throw new InvalidArgumentException('Cannot cancel this bill. It may have payments or invalid status.');
        }

        return DB::transaction(function () use ($bill) {
            // If posted, create reversing journal entry
            if ($bill->status === 'posted' && $bill->journal_entry_id) {
                $originalJournal = $bill->journalEntry;

                // Create reversing entries
                $reversingEntries = $originalJournal->items->map(function ($item) {
                    return [
                        'account_id' => $item->account_id,
                        'debit'      => $item->credit, // Swap
                        'credit'     => $item->debit,
                    ];
                })->toArray();

                $this->accountingService->createJournalEntry(
                    reference: "{$bill->bill_number}-VOID",
                    date: now()->format('Y-m-d'),
                    description: "Void: {$bill->bill_number}",
                    entries: $reversingEntries
                );
            }

            $bill->update(['status' => 'cancelled']);

            return $bill->fresh();
        });
    }

    // ════════════════════════════════════════════════════════════════
    // PAYMENT MANAGEMENT
    // ════════════════════════════════════════════════════════════════

    /**
     * Record a payment against a supplier bill.
     * 
     * Journal Entry:
     *   Dr: Accounts Payable
     *   Cr: Cash/Bank
     */
    public function recordPayment(array $data): SupplierPayment
    {
        $bill = SupplierBill::findOrFail($data['supplier_bill_id']);

        if (!$bill->canPay()) {
            throw new InvalidArgumentException('Cannot pay this bill. Check status or outstanding amount.');
        }

        $amount = (float) $data['amount'];
        if ($amount <= 0) {
            throw new InvalidArgumentException('Payment amount must be greater than zero.');
        }

        if ($amount > $bill->outstanding) {
            throw new InvalidArgumentException(
                "Payment amount ({$amount}) exceeds outstanding balance ({$bill->outstanding})."
            );
        }

        return DB::transaction(function () use ($bill, $data, $amount) {
            // Resolve accounts
            $apAccount = $this->resolveAccount(self::ACCOUNTS_PAYABLE);
            $cashAccount = $this->resolveAccount(self::CASH_BANK);

            if (!$apAccount || !$cashAccount) {
                throw new InvalidArgumentException('Required accounts not found (AP or Cash/Bank).');
            }

            // Create payment record
            $payment = SupplierPayment::create([
                'supplier_id'      => $bill->supplier_id,
                'supplier_bill_id' => $bill->id,
                'payment_date'     => $data['payment_date'],
                'amount'           => $amount,
                'payment_method'   => $data['payment_method'] ?? 'bank_transfer',
                'reference_number' => $data['reference_number'] ?? null,
                'notes'            => $data['notes'] ?? null,
            ]);

            // Create journal entry
            $journal = $this->accountingService->createJournalEntry(
                reference: $payment->payment_number,
                date: $payment->payment_date->format('Y-m-d'),
                description: "Payment to {$bill->supplier->name} for {$bill->bill_number}",
                entries: [
                    ['account_id' => $apAccount->id, 'debit' => $amount, 'credit' => 0],
                    ['account_id' => $cashAccount->id, 'debit' => 0, 'credit' => $amount],
                ]
            );

            $journal->update(['is_posted' => true]);
            $payment->update(['journal_entry_id' => $journal->id]);

            // Update bill paid amount and status
            $newPaidAmount = $bill->paid_amount + $amount;
            $newStatus = $newPaidAmount >= $bill->total ? 'paid' : 'partial';

            $bill->update([
                'paid_amount' => $newPaidAmount,
                'status'      => $newStatus,
            ]);

            return $payment->fresh(['supplier', 'supplierBill']);
        });
    }

    // ════════════════════════════════════════════════════════════════
    // PURCHASE ORDER INTEGRATION
    // ════════════════════════════════════════════════════════════════

    /**
     * Create a bill from a purchase order.
     */
    public function createBillFromPO(PurchaseOrder $po, array $overrides = []): SupplierBill
    {
        $items = $po->items->map(function ($item) {
            return [
                'product_id'  => $item->product_id,
                'description' => $item->product->name ?? $item->description ?? 'Item',
                'quantity'    => $item->quantity,
                'price'       => $item->price,
            ];
        })->toArray();

        return $this->createBill(array_merge([
            'supplier_id'       => $po->supplier_id,
            'purchase_order_id' => $po->id,
            'bill_date'         => now()->format('Y-m-d'),
            'due_date'          => now()->addDays(30)->format('Y-m-d'),
            'tax_amount'        => $po->tax_amount ?? 0,
            'items'             => $items,
        ], $overrides));
    }

    // ════════════════════════════════════════════════════════════════
    // AGING REPORT
    // ════════════════════════════════════════════════════════════════

    /**
     * Get AP Aging Report grouped by supplier.
     */
    public function getAgingReport(?int $supplierId = null): array
    {
        $query = SupplierBill::with('supplier')
            ->whereIn('status', ['posted', 'partial'])
            ->select([
                'id',
                'bill_number',
                'supplier_id',
                'total',
                'paid_amount',
                'due_date',
            ]);

        if ($supplierId) {
            $query->where('supplier_id', $supplierId);
        }

        $bills = $query->get();

        // Group by supplier
        $grouped = $bills->groupBy('supplier_id');

        $report = [];
        $totals = [
            'current' => 0,
            '1-30'    => 0,
            '31-60'   => 0,
            '61-90'   => 0,
            '90+'     => 0,
            'total'   => 0,
        ];

        foreach ($grouped as $supplierId => $supplierBills) {
            $supplier = $supplierBills->first()->supplier;

            $row = [
                'supplier_id'   => $supplierId,
                'supplier_name' => $supplier->name ?? 'Unknown',
                'current'       => 0,
                '1-30'          => 0,
                '31-60'         => 0,
                '61-90'         => 0,
                '90+'           => 0,
                'total'         => 0,
            ];

            foreach ($supplierBills as $bill) {
                $outstanding = $bill->total - $bill->paid_amount;
                $bucket = $bill->aging_bucket;

                $row[$bucket] += $outstanding;
                $row['total'] += $outstanding;

                $totals[$bucket] += $outstanding;
                $totals['total'] += $outstanding;
            }

            $report[] = $row;
        }

        // Sort by total outstanding descending
        usort($report, fn($a, $b) => $b['total'] <=> $a['total']);

        return [
            'data'       => $report,
            'totals'     => $totals,
            'as_of_date' => now()->format('Y-m-d'),
        ];
    }

    /**
     * Get summary statistics.
     */
    public function getSummary(): array
    {
        $unpaidBills = SupplierBill::whereIn('status', ['posted', 'partial']);

        return [
            'total_outstanding' => (float) $unpaidBills->sum(DB::raw('total - paid_amount')),
            'total_bills'       => $unpaidBills->count(),
            'overdue_amount'    => (float) SupplierBill::overdue()->sum(DB::raw('total - paid_amount')),
            'overdue_count'     => SupplierBill::overdue()->count(),
            'draft_count'       => SupplierBill::where('status', 'draft')->count(),
        ];
    }

    // ════════════════════════════════════════════════════════════════
    // HELPERS
    // ════════════════════════════════════════════════════════════════

    /**
     * Resolve COA by code.
     */
    protected function resolveAccount(string $code): ?ChartOfAccount
    {
        return ChartOfAccount::where('code', $code)->first();
    }
}
