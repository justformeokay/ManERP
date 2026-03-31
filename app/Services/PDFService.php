<?php

namespace App\Services;

use App\Models\CompanySetting;
use App\Models\Invoice;
use App\Models\PurchaseOrder;
use App\Models\SupplierBill;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class PDFService
{
    protected CompanySetting $company;
    protected string $paper;
    protected string $orientation;

    public function __construct()
    {
        $this->company = CompanySetting::getSettings();
        $this->paper = config('pdf.paper', 'A4');
        $this->orientation = config('pdf.orientation', 'portrait');
    }

    /**
     * Generate Invoice PDF.
     *
     * @param int $invoiceId
     * @param bool $download  If true, force download; otherwise stream (preview).
     * @return Response
     * @throws ModelNotFoundException
     */
    public function generateInvoicePDF(int $invoiceId, bool $download = false): Response
    {
        $invoice = Invoice::with([
            'client',
            'items.product',
            'salesOrder',
            'payments',
        ])->findOrFail($invoiceId);

        $data = [
            'company' => $this->company,
            'invoice' => $invoice,
            'items'   => $invoice->items,
            'client'  => $invoice->client,
            'watermark' => $this->getWatermark($invoice->status),
        ];

        $pdf = Pdf::loadView('pdf.invoice', $data)
            ->setPaper($this->paper, $this->orientation)
            ->setOptions([
                'dpi' => config('pdf.dpi', 150),
                'isRemoteEnabled' => config('pdf.enable_remote', true),
                'defaultFont' => config('pdf.default_font', 'sans-serif'),
            ]);

        $filename = "Invoice-{$invoice->invoice_number}.pdf";

        return $download
            ? $pdf->download($filename)
            : $pdf->stream($filename);
    }

    /**
     * Generate Purchase Order PDF.
     *
     * @param int $poId
     * @param bool $download
     * @return Response
     * @throws ModelNotFoundException
     */
    public function generatePurchaseOrderPDF(int $poId, bool $download = false): Response
    {
        $po = PurchaseOrder::with([
            'supplier',
            'items.product',
            'warehouse',
        ])->findOrFail($poId);

        $data = [
            'company' => $this->company,
            'po'      => $po,
            'items'   => $po->items,
            'supplier' => $po->supplier,
            'watermark' => $this->getWatermark($po->status),
        ];

        $pdf = Pdf::loadView('pdf.purchase_order', $data)
            ->setPaper($this->paper, $this->orientation)
            ->setOptions([
                'dpi' => config('pdf.dpi', 150),
                'isRemoteEnabled' => config('pdf.enable_remote', true),
                'defaultFont' => config('pdf.default_font', 'sans-serif'),
            ]);

        $filename = "PO-{$po->number}.pdf";

        return $download
            ? $pdf->download($filename)
            : $pdf->stream($filename);
    }

    /**
     * Generate Supplier Bill PDF.
     *
     * @param int $billId
     * @param bool $download
     * @return Response
     * @throws ModelNotFoundException
     */
    public function generateSupplierBillPDF(int $billId, bool $download = false): Response
    {
        $bill = SupplierBill::with([
            'supplier',
            'items.product',
            'purchaseOrder',
            'payments',
        ])->findOrFail($billId);

        $data = [
            'company' => $this->company,
            'bill'    => $bill,
            'items'   => $bill->items,
            'supplier' => $bill->supplier,
            'watermark' => $this->getWatermark($bill->status),
        ];

        $pdf = Pdf::loadView('pdf.supplier_bill', $data)
            ->setPaper($this->paper, $this->orientation)
            ->setOptions([
                'dpi' => config('pdf.dpi', 150),
                'isRemoteEnabled' => config('pdf.enable_remote', true),
                'defaultFont' => config('pdf.default_font', 'sans-serif'),
            ]);

        $filename = "Bill-{$bill->bill_number}.pdf";

        return $download
            ? $pdf->download($filename)
            : $pdf->stream($filename);
    }

    /**
     * Get watermark text based on document status.
     */
    protected function getWatermark(string $status): ?string
    {
        if (!config('pdf.watermark.enabled', false)) {
            return null;
        }

        return match ($status) {
            'draft'     => 'DRAFT',
            'cancelled' => 'CANCELLED',
            'paid'      => 'PAID',
            default     => null,
        };
    }

    /**
     * Format currency amount using the global format_currency() helper.
     */
    public static function formatCurrency(float|string|null $amount, ?string $currency = null): string
    {
        return format_currency($amount, $currency);
    }
}
