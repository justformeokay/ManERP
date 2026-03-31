<?php

namespace App\Http\Controllers;

use App\Http\Requests\PaymentRequest;
use App\Models\Invoice;
use App\Services\FinanceService;
use App\Traits\Auditable;

class PaymentController extends Controller
{
    use Auditable;

    protected string $model = 'finance';

    public function __construct(private FinanceService $financeService) {}

    public function store(PaymentRequest $request)
    {
        $invoice = Invoice::findOrFail($request->invoice_id);

        if (in_array($invoice->status, ['paid', 'cancelled'])) {
            return back()->with('error', 'Cannot add payment to this invoice.');
        }

        $payment = $this->financeService->recordPayment($invoice, $request->validated());
        $this->logAction($invoice->fresh(), 'payment', "Payment of {$payment->amount} recorded for invoice {$invoice->invoice_number}");

        return back()->with('success', 'Payment recorded successfully.');
    }
}
