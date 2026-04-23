<?php

namespace App\Services;

use App\Models\ChartOfAccount;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\SalesOrder;
use Illuminate\Support\Facades\DB;

class FinanceService
{
    /** PPN Keluaran (Output VAT — liability) */
    public const PPN_KELUARAN = '2110';

    public function __construct(private AccountingService $accountingService) {}

    /**
     * Create an invoice from a confirmed/shipped/completed sales order.
     * Supports partial invoicing via $extra['items'] array.
     */
    public function createInvoiceFromSalesOrder(SalesOrder $salesOrder, array $extra = []): Invoice
    {
        return DB::transaction(function () use ($salesOrder, $extra) {
            $salesOrder->load('items.product', 'client');

            $items = $extra['items'] ?? [];
            $taxRate = (float) ($extra['tax_rate'] ?? 11); // PPN 11% default

            // Calculate totals from line items (partial invoicing support)
            $subtotal = 0;
            $invoiceItems = [];

            if (!empty($items)) {
                // Partial invoicing: use submitted quantities
                foreach ($salesOrder->items as $soItem) {
                    $key = (string) $soItem->id;
                    if (!isset($items[$key]) || (float) $items[$key]['quantity'] <= 0) {
                        continue;
                    }

                    $qty = min((float) $items[$key]['quantity'], $soItem->remaining_invoiceable);
                    if ($qty <= 0) continue;

                    $lineDiscount = round(($soItem->discount / max($soItem->quantity, 1)) * $qty, 2);
                    $lineTotal = round(($qty * (float) $soItem->unit_price) - $lineDiscount, 2);
                    $subtotal += $lineTotal;

                    $invoiceItems[] = [
                        'so_item' => $soItem,
                        'quantity' => $qty,
                        'unit_price' => $soItem->unit_price,
                        'discount' => $lineDiscount,
                        'total' => $lineTotal,
                    ];
                }
            } else {
                // Full invoicing: take all remaining quantities
                foreach ($salesOrder->items as $soItem) {
                    $qty = $soItem->remaining_invoiceable;
                    if ($qty <= 0) continue;

                    $lineDiscount = round(($soItem->discount / max($soItem->quantity, 1)) * $qty, 2);
                    $lineTotal = round(($qty * (float) $soItem->unit_price) - $lineDiscount, 2);
                    $subtotal += $lineTotal;

                    $invoiceItems[] = [
                        'so_item' => $soItem,
                        'quantity' => $qty,
                        'unit_price' => $soItem->unit_price,
                        'discount' => $lineDiscount,
                        'total' => $lineTotal,
                    ];
                }
            }

            // Calculate PPN
            $includeTax = (bool) ($extra['include_tax'] ?? true);
            $taxAmount = $includeTax ? round($subtotal * $taxRate / 100, 2) : 0;
            $dpp = $subtotal;
            $totalAmount = round($subtotal + $taxAmount - 0, 2); // discount already in line items

            $invoice = Invoice::create([
                'sales_order_id' => $salesOrder->id,
                'client_id' => $salesOrder->client_id,
                'invoice_date' => $extra['invoice_date'] ?? now()->toDateString(),
                'due_date' => $extra['due_date'] ?? now()->addDays(30)->toDateString(),
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'tax_rate' => $includeTax ? $taxRate : 0,
                'dpp' => $dpp,
                'discount' => 0,
                'total_amount' => $totalAmount,
                'status' => 'draft',
                'notes' => $extra['notes'] ?? null,
            ]);

            foreach ($invoiceItems as $line) {
                $invoice->items()->create([
                    'product_id' => $line['so_item']->product_id,
                    'description' => $line['so_item']->product->name ?? null,
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'discount' => $line['discount'],
                    'total' => $line['total'],
                ]);

                // Track invoiced quantity on SO items
                $line['so_item']->increment('invoiced_quantity', $line['quantity']);
            }

            return $invoice;
        });
    }

    /**
     * Approve (confirm) an invoice: transition to unpaid, create journal entry.
     */
    public function approveInvoice(Invoice $invoice): void
    {
        DB::transaction(function () use ($invoice) {
            $invoice->update(['status' => 'unpaid']);
            $this->createInvoiceJournal($invoice);
        });
    }

    /**
     * Mark invoice as sent to client.
     */
    public function sendInvoice(Invoice $invoice): void
    {
        $invoice->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    /**
     * Record a payment against an invoice.
     */
    public function recordPayment(Invoice $invoice, array $data): Payment
    {
        return DB::transaction(function () use ($invoice, $data) {
            $invoice = Invoice::lockForUpdate()->findOrFail($invoice->id);

            $payment = $invoice->payments()->create([
                'amount' => $data['amount'],
                'payment_date' => $data['payment_date'],
                'payment_method' => $data['payment_method'],
                'reference_number' => $data['reference_number'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            $invoice->paid_amount = $invoice->payments()->sum('amount');
            $invoice->save();
            $invoice->recalculateStatus();

            // Auto-create journal entry: Debit Cash/Bank, Credit AR
            $this->createPaymentJournal($invoice, $payment);

            return $payment;
        });
    }

    /**
     * Cancel an invoice: void payments AND create reversing journal.
     */
    public function cancelInvoice(Invoice $invoice): void
    {
        DB::transaction(function () use ($invoice) {
            // Create reversing journal BEFORE deleting data
            $this->createInvoiceCancelJournal($invoice);

            $invoice->payments()->delete();
            $invoice->update([
                'status' => 'cancelled',
                'paid_amount' => 0,
            ]);
        });
    }

    /**
     * Auto-create journal for invoice issuance.
     *
     * Dr Piutang (1200) = total_amount
     * Cr Pendapatan (4000) = total_amount − tax_amount
     * Cr PPN Keluaran (2110) = tax_amount
     *
     * @throws \RuntimeException if required CoA accounts are missing
     */
    private function createInvoiceJournal(Invoice $invoice): void
    {
        $ar = $this->resolveAccountOrFail(AccountingService::ACCOUNTS_RECEIVABLE);
        $revenue = $this->resolveAccountOrFail(AccountingService::REVENUE);

        $totalAmount = (float) $invoice->total_amount;
        $taxAmount = (float) $invoice->tax_amount;
        $revenueAmount = round((float) bcsub((string) $totalAmount, (string) $taxAmount, 4), 2);

        $entries = [
            ['account_id' => $ar->id, 'debit' => round($totalAmount, 2), 'credit' => 0],
            ['account_id' => $revenue->id, 'debit' => 0, 'credit' => $revenueAmount],
        ];

        // Split PPN to Hutang PPN Keluaran (2110) when tax exists
        if ($taxAmount > 0) {
            $ppnAccount = $this->resolveAccountOrFail(self::PPN_KELUARAN);
            $entries[] = ['account_id' => $ppnAccount->id, 'debit' => 0, 'credit' => round($taxAmount, 2)];
        }

        $this->accountingService->createJournalEntry(
            $invoice->invoice_number,
            $invoice->invoice_date,
            "Invoice {$invoice->invoice_number} issued",
            $entries,
            Invoice::class,
            $invoice->id,
            'system'
        );
    }

    /**
     * Create reversing journal when an invoice is cancelled.
     *
     * Mirrors the original invoice journal in reverse:
     * Cr Piutang (1200) = total_amount
     * Dr Pendapatan (4000) = total_amount − tax_amount
     * Dr PPN Keluaran (2110) = tax_amount
     *
     * @throws \RuntimeException if required CoA accounts are missing
     */
    private function createInvoiceCancelJournal(Invoice $invoice): void
    {
        $ar = $this->resolveAccountOrFail(AccountingService::ACCOUNTS_RECEIVABLE);
        $revenue = $this->resolveAccountOrFail(AccountingService::REVENUE);

        $totalAmount = (float) $invoice->total_amount;
        $taxAmount = (float) $invoice->tax_amount;
        $revenueAmount = round((float) bcsub((string) $totalAmount, (string) $taxAmount, 4), 2);

        $entries = [
            ['account_id' => $revenue->id, 'debit' => $revenueAmount, 'credit' => 0],
            ['account_id' => $ar->id, 'debit' => 0, 'credit' => round($totalAmount, 2)],
        ];

        if ($taxAmount > 0) {
            $ppnAccount = $this->resolveAccountOrFail(self::PPN_KELUARAN);
            $entries[] = ['account_id' => $ppnAccount->id, 'debit' => round($taxAmount, 2), 'credit' => 0];
        }

        $this->accountingService->createJournalEntry(
            'REV-' . $invoice->invoice_number,
            now()->toDateString(),
            "Reversing journal — invoice {$invoice->invoice_number} cancelled",
            $entries,
            Invoice::class,
            $invoice->id,
            'system'
        );
    }

    /**
     * Auto-create journal entry for a payment: Debit Cash/Bank, Credit AR.
     *
     * @throws \RuntimeException if required CoA accounts are missing
     */
    private function createPaymentJournal(Invoice $invoice, Payment $payment): void
    {
        $cash = $this->resolveAccountOrFail(AccountingService::CASH_BANK);
        $ar = $this->resolveAccountOrFail(AccountingService::ACCOUNTS_RECEIVABLE);

        $this->accountingService->createJournalEntry(
            'PMT-' . $invoice->invoice_number,
            $payment->payment_date,
            "Payment received for {$invoice->invoice_number}",
            [
                ['account_id' => $cash->id, 'debit' => $payment->amount, 'credit' => 0],
                ['account_id' => $ar->id, 'debit' => 0, 'credit' => $payment->amount],
            ],
            Payment::class,
            $payment->id,
            'system'
        );
    }

    /**
     * Resolve a CoA account by code, or throw RuntimeException.
     *
     * @throws \RuntimeException
     */
    private function resolveAccountOrFail(string $code): ChartOfAccount
    {
        $account = $this->accountingService->resolveAccount($code);

        if (!$account) {
            throw new \RuntimeException(
                "Required Chart of Account '{$code}' not found. Please seed the CoA before processing financial transactions."
            );
        }

        return $account;
    }
}
