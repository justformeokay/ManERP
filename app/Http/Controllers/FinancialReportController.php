<?php

namespace App\Http\Controllers;

use App\Services\AccountingService;
use Illuminate\Http\Request;

class FinancialReportController extends Controller
{
    public function __construct(private AccountingService $accountingService) {}

    public function balanceSheet(Request $request)
    {
        $date = $request->input('date', now()->toDateString());
        $data = $this->accountingService->getBalanceSheet($date);

        return view('accounting.reports.balance-sheet', compact('data'));
    }

    public function profitLoss(Request $request)
    {
        $data = $this->accountingService->getProfitLoss(
            $request->input('from', now()->startOfYear()->toDateString()),
            $request->input('to', now()->toDateString())
        );

        return view('accounting.reports.profit-loss', compact('data'));
    }
}
