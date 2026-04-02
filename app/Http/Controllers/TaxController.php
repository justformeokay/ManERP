<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Services\TaxService;
use Illuminate\Http\Request;

class TaxController extends Controller
{
    public function __construct(private TaxService $taxService) {}

    /**
     * SPT Masa PPN dashboard.
     */
    public function sptMasaPPN(Request $request)
    {
        $year  = (int) $request->input('year', now()->year);
        $month = (int) $request->input('month', now()->month);

        $spt = $this->taxService->getSptMasaPPN($year, $month);
        $ppnKeluaranDetail = $this->taxService->getPPNKeluaranDetail($year, $month);
        $ppnMasukanDetail  = $this->taxService->getPPNMasukanDetail($year, $month);

        return view('accounting.tax.spt-masa-ppn', compact(
            'spt', 'ppnKeluaranDetail', 'ppnMasukanDetail', 'year', 'month'
        ));
    }

    /**
     * Annual tax summary.
     */
    public function annualSummary(Request $request)
    {
        $year = (int) $request->input('year', now()->year);
        $data = $this->taxService->getAnnualTaxSummary($year);

        return view('accounting.tax.annual', compact('data', 'year'));
    }

    /**
     * PPN Calculator (utility).
     */
    public function calculator()
    {
        return view('accounting.tax.calculator');
    }
}
