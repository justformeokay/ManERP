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
    public const PPN_MASUKAN      = '1140';  // PPN Masukan (Input VAT — asset)
    public const PPV              = '5101';  // Purchase Price Variance

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
     * Accrual-to-Bill logic:
     *  - If linked to a PO (goods already accrued at receive), the bill
     *    only journals any price variance (PPV) and PPN Masukan.
     *  - If NOT linked to a PO (service bill), full expense journal.
     *
     * Journal for PO-linked bill (no variance):
     *   Dr: PPN Masukan (1140)     = tax_amount
     *   Cr: Accounts Payable (2000) = tax_amount   (only the PPN portion)
     *
     * Journal for PO-linked bill (with variance):
     *   Dr/Cr: PPV (5101)          = |bill_dpp - accrued_value|
     *   Dr:    PPN Masukan (1140)   = tax_amount
     *   Cr:    Accounts Payable (2000) = variance + tax_amount
     *
     * Journal for non-PO bill:
     *   Dr: Expense (5000)          = DPP
     *   Dr: PPN Masukan (1140)      = tax_amount
     *   Cr: Accounts Payable (2000) = total
     */
    public function postBill(SupplierBill $bill): SupplierBill
    {
        if (!$bill->canPost()) {
            throw new InvalidArgumentException(__('messages.purchase_bill_cannot_post'));
        }

        return DB::transaction(function () use ($bill) {
            $bill->load(['items', 'supplier']);

            // Resolve required accounts
            $apAccount  = $this->resolveAccount(self::ACCOUNTS_PAYABLE);
            $ppnAccount = $this->resolveAccount(self::PPN_MASUKAN);

            if (!$apAccount) {
                throw new \RuntimeException(__('messages.purchase_coa_ap_missing'));
            }

            $taxAmount = (float) ($bill->tax_amount ?? 0);
            $billDpp   = (float) ($bill->dpp > 0 ? $bill->dpp : bcsub((string) $bill->total, (string) $taxAmount, 2));

            $entries = [];

            // ── Determine if this bill is linked to a received PO ───
            $po = $bill->purchase_order_id
                ? PurchaseOrder::with('items')->find($bill->purchase_order_id)
                : null;

            if ($po) {
                // Accrual-to-bill: goods value was already journaled at PO receive
                // (Dr Inventory 1300 / Cr AP 2000). We only journal the DIFFERENCE.
                $accruedValue = '0';
                foreach ($po->items as $poItem) {
                    $accruedValue = bcadd(
                        $accruedValue,
                        bcmul((string) $poItem->received_quantity, (string) $poItem->unit_price, 4),
                        4
                    );
                }

                $variance = bcsub((string) $billDpp, $accruedValue, 2);

                // Journal price variance if any
                if (bccomp($variance, '0', 2) !== 0) {
                    $ppvAccount = $this->resolveAccount(self::PPV);
                    if (!$ppvAccount) {
                        throw new \RuntimeException(__('messages.purchase_coa_ppv_missing'));
                    }

                    if (bccomp($variance, '0', 2) > 0) {
                        // Bill > accrued: additional expense (debit PPV)
                        $entries[] = ['account_id' => $ppvAccount->id, 'debit' => (float) $variance, 'credit' => 0];
                    } else {
                        // Bill < accrued: favorable variance (credit PPV)
                        $entries[] = ['account_id' => $ppvAccount->id, 'debit' => 0, 'credit' => (float) abs((float) $variance)];
                    }
                }

                // AP credit = variance + tax (the DPP portion was already in AP from receive)
                $apCreditAmount = bcadd((string) abs((float) $variance), (string) $taxAmount, 2);
                if (bccomp($variance, '0', 2) < 0) {
                    // Favorable variance: AP credit is tax minus the favorable amount
                    $apCreditAmount = bcsub((string) $taxAmount, (string) abs((float) $variance), 2);
                }
            } else {
                // Non-PO bill: full expense debit
                $expenseAccount = $this->resolveAccount(self::EXPENSE);
                if (!$expenseAccount) {
                    throw new \RuntimeException(__('messages.purchase_coa_expense_missing'));
                }
                $entries[] = ['account_id' => $expenseAccount->id, 'debit' => $billDpp, 'credit' => 0];
                $apCreditAmount = (string) $bill->total;
            }

            // PPN Masukan debit (if any tax)
            if (bccomp((string) $taxAmount, '0', 2) > 0) {
                if (!$ppnAccount) {
                    throw new \RuntimeException(__('messages.purchase_coa_ppn_masukan_missing'));
                }
                $entries[] = ['account_id' => $ppnAccount->id, 'debit' => $taxAmount, 'credit' => 0];
            }

            // AP credit
            if (bccomp((string) $apCreditAmount, '0', 2) > 0) {
                $entries[] = ['account_id' => $apAccount->id, 'debit' => 0, 'credit' => (float) $apCreditAmount];
            } elseif (bccomp((string) $apCreditAmount, '0', 2) < 0) {
                // Negative means AP should be debited (favorable PPV > tax)
                $entries[] = ['account_id' => $apAccount->id, 'debit' => (float) abs((float) $apCreditAmount), 'credit' => 0];
            }

            // Only create journal if there are entries
            if (!empty($entries)) {
                $journal = $this->accountingService->createJournalEntry(
                    reference: $bill->bill_number,
                    date: $bill->bill_date->format('Y-m-d'),
                    description: "Supplier Bill: {$bill->supplier->name}",
                    entries: $entries,
                    sourceableType: SupplierBill::class,
                    sourceableId: $bill->id
                );
                $journal->update(['is_posted' => true]);
                $bill->update([
                    'status'           => 'posted',
                    'journal_entry_id' => $journal->id,
                ]);
            } else {
                // No journal needed (PO-linked, no variance, no tax)
                $bill->update(['status' => 'posted']);
            }

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
                    entries: $reversingEntries,
                    sourceableType: SupplierBill::class,
                    sourceableId: $bill->id
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
        return DB::transaction(function () use ($data) {
            // Lock the bill row to prevent double payment from concurrent requests
            $bill = SupplierBill::lockForUpdate()->findOrFail($data['supplier_bill_id']);

            if (!$bill->canPay()) {
                throw new InvalidArgumentException(__('messages.purchase_bill_cannot_pay'));
            }

            $amount = (float) $data['amount'];
            if ($amount <= 0) {
                throw new InvalidArgumentException(__('messages.purchase_payment_amount_positive'));
            }

            if (bccomp((string) $amount, (string) $bill->outstanding, 2) > 0) {
                throw new InvalidArgumentException(__('messages.purchase_payment_exceeds_outstanding', [
                    'amount'      => $amount,
                    'outstanding' => $bill->outstanding,
                ]));
            }

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
                ],
                sourceableType: SupplierPayment::class,
                sourceableId: $payment->id
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
     * Create a bill from a purchase order (uses received_quantity, not ordered).
     */
    public function createBillFromPO(PurchaseOrder $po, array $overrides = []): SupplierBill
    {
        $po->load('items.product');

        $items = $po->items
            ->filter(fn($item) => $item->received_quantity > 0)
            ->map(function ($item) {
                return [
                    'product_id'  => $item->product_id,
                    'description' => $item->product->name ?? $item->description ?? 'Item',
                    'quantity'    => $item->received_quantity,
                    'price'       => $item->unit_price,
                ];
            })->values()->toArray();

        if (empty($items)) {
            throw new InvalidArgumentException(__('messages.purchase_bill_no_received_items'));
        }

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
