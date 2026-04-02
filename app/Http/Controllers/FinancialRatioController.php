<?php

namespace App\Http\Controllers;

use App\Services\FinancialRatioService;
use Illuminate\Http\Request;

class FinancialRatioController extends Controller
{
    public function __construct(private FinancialRatioService $ratioService) {}

    public function index(Request $request)
    {
        $date = $request->input('date', now()->toDateString());
        $ratios = $this->ratioService->getRatios($date);
        return view('accounting.reports.financial-ratios', compact('ratios', 'date'));
    }
}
