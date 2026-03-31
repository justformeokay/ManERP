<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\SalesOrder;
use Illuminate\Support\Facades\DB;

class FinanceService
{
    /**
     * Create an invoice from a confirmed/shipped/completed sales order.
     */
    public function createInvoiceFromSalesOrder(SalesOrder $salesOrder, array $extra = []): Invoice
    {
        return DB::transaction(function () use ($salesOrder, $extra) {
            $salesOrder->load('items.product', 'client');

            $invoice = Invoice::create([
                'sales_order_id' => $salesOrder->id,
                'client_id' => $salesOrder->client_id,
                'invoice_date' => now()->toDateString(),
                'due_date' => $extra['due_date'] ?? now()->addDays(30)->toDateString(),
                'subtotal' => $salesOrder->subtotal,
                'tax_amount' => $salesOrder->tax_amount,
                'discount' => $salesOrder->discount,
                'total_amount' => $salesOrder->total,
                'status' => 'unpaid',
                'notes' => $extra['notes'] ?? null,
            ]);

            foreach ($salesOrder->items as $item) {
                $invoice->items()->create([
                    'product_id' => $item->product_id,
                    'description' => $item->product->name ?? null,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'discount' => $item->discount,
                    'total' => $item->total,
                ]);
            }

            // Mark sales order as completed
            $salesOrder->update(['status' => 'completed']);

            return $invoice;
        });
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

            return $payment;
        });
    }

    /**
     * Cancel an invoice and void all payments.
     */
    public function cancelInvoice(Invoice $invoice): void
    {
        DB::transaction(function () use ($invoice) {
            $invoice->payments()->delete();
            $invoice->update([
                'status' => 'cancelled',
                'paid_amount' => 0,
            ]);
        });
    }
}
