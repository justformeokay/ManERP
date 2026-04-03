<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockValuationLayer;
use App\Services\StockValuationService;
use Illuminate\Http\Request;

class StockValuationController extends Controller
{
    public function __construct(private StockValuationService $valuationService) {}

    /**
     * Stock Valuation Report: all products with WAC and total value.
     */
    public function index(Request $request)
    {
        $report = $this->valuationService->getStockValuationReport();

        return view('inventory.valuation.index', [
            'report'   => $report['products'],
            'grandTotal' => $report['grand_total_value'],
            'generatedAt' => $report['generated_at'],
        ]);
    }

    /**
     * Valuation history (layers) for a specific product.
     */
    public function show(Product $product, Request $request)
    {
        $from = $request->input('from');
        $to = $request->input('to');
        $layers = $this->valuationService->getProductValuationHistory($product->id, $from, $to);

        return view('inventory.valuation.show', [
            'product' => $product,
            'layers'  => $layers,
            'from'    => $from,
            'to'      => $to,
        ]);
    }
}
