<?php

namespace App\Http\Controllers;

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

    /**
     * Generate and stream/download Invoice PDF.
     *
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function invoice(Request $request, int $id): Response
    {
        $download = $request->boolean('download', false);

        return $this->pdfService->generateInvoicePDF($id, $download);
    }

    /**
     * Generate and stream/download Purchase Order PDF.
     *
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function purchaseOrder(Request $request, int $id): Response
    {
        $download = $request->boolean('download', false);

        return $this->pdfService->generatePurchaseOrderPDF($id, $download);
    }

    /**
     * Generate and stream/download Supplier Bill PDF.
     *
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function supplierBill(Request $request, int $id): Response
    {
        $download = $request->boolean('download', false);

        return $this->pdfService->generateSupplierBillPDF($id, $download);
    }
}
