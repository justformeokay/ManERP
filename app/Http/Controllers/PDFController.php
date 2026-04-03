<?php

namespace App\Http\Controllers;

use App\Services\AuditLogService;
use App\Services\PDFService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PDFController extends Controller
{
    protected PDFService $pdfService;

    public function __construct(PDFService $pdfService)
    {
        $this->pdfService = $pdfService;
    }

    public function invoice(Request $request, int $id): Response
    {
        AuditLogService::log('finance', 'export', "Invoice PDF generated: #{$id}");
        $download = $request->boolean('download', false);
        return $this->pdfService->generateInvoicePDF($id, $download);
    }

    public function purchaseOrder(Request $request, int $id): Response
    {
        AuditLogService::log('inventory', 'export', "Purchase Order PDF generated: #{$id}");
        $download = $request->boolean('download', false);
        return $this->pdfService->generatePurchaseOrderPDF($id, $download);
    }

    public function supplierBill(Request $request, int $id): Response
    {
        AuditLogService::log('finance', 'export', "Supplier Bill PDF generated: #{$id}");
        $download = $request->boolean('download', false);
        return $this->pdfService->generateSupplierBillPDF($id, $download);
    }
}
