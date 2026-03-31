<?php

namespace App\Http\Controllers;

use App\Models\ChartOfAccount;
use App\Services\AccountingService;
use Illuminate\Http\Request;

class AccountingReportController extends Controller
{
    public function __construct(private AccountingService $accountingService) {}

    public function ledger(Request $request)
    {
        $accounts = ChartOfAccount::active()->orderBy('code')->get();
        $data = null;

        if ($request->filled('account_id')) {
            $data = $this->accountingService->getLedger(
                $request->input('account_id'),
                $request->input('from'),
                $request->input('to')
            );
        }

        return view('accounting.ledger', compact('accounts', 'data'));
    }

    public function trialBalance(Request $request)
    {
        $data = $this->accountingService->getTrialBalance(
            $request->input('from'),
            $request->input('to')
        );

        return view('accounting.trial-balance', compact('data'));
    }
}
