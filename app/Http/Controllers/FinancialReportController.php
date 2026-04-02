<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Services\AccountingService;
use App\Services\CashFlowService;
use Illuminate\Http\Request;

class FinancialReportController extends Controller
{
    public function __construct(
        private AccountingService $accountingService,
        private CashFlowService $cashFlowService,
    ) {}

    public function index()
    {
        return view('accounting.reports.index');
    }

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

    public function cashFlow(Request $request)
    {
        $data = $this->cashFlowService->getCashFlowStatement(
            $request->input('from', now()->startOfYear()->toDateString()),
            $request->input('to', now()->toDateString())
        );

        return view('accounting.reports.cash-flow', compact('data'));
    }

    public function arAging(Request $request)
    {
        $clients = Client::active()->orderBy('name')->get();
        $result  = $this->accountingService->getARAgingReport(
            $request->input('client_id') ? (int) $request->input('client_id') : null
        );

        return view('accounting.reports.ar-aging', compact('result', 'clients'));
    }
}
