<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\ChartOfAccount;
use App\Services\BankReconciliationService;
use Illuminate\Http\Request;

class BankAccountController extends Controller
{
    public function __construct(private BankReconciliationService $bankService) {}

    public function index()
    {
        $accounts = BankAccount::with('chartOfAccount')->latest()->get();
        return view('accounting.bank.index', compact('accounts'));
    }

    public function create()
    {
        $coaAccounts = ChartOfAccount::active()->where('code', 'like', '11%')->orderBy('code')->get();
        return view('accounting.bank.create', compact('coaAccounts'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'            => 'required|string|max:255',
            'account_number'  => 'required|string|max:50',
            'bank_name'       => 'required|string|max:255',
            'opening_balance' => 'required|numeric|min:0',
            'coa_id'          => 'required|exists:chart_of_accounts,id',
        ]);

        $validated['current_balance'] = $validated['opening_balance'];
        $validated['is_active'] = true;
        BankAccount::create($validated);

        return redirect()->route('accounting.bank.index')
            ->with('success', __('messages.bank_account_created'));
    }

    public function show(BankAccount $bankAccount)
    {
        $bankAccount->load('transactions');
        return view('accounting.bank.show', compact('bankAccount'));
    }

    public function transactions(BankAccount $bankAccount, Request $request)
    {
        $transactions = $bankAccount->transactions()->latest('transaction_date')->paginate(50);
        return view('accounting.bank.transactions', compact('bankAccount', 'transactions'));
    }

    public function storeTransaction(BankAccount $bankAccount, Request $request)
    {
        $validated = $request->validate([
            'transaction_date' => 'required|date',
            'description'      => 'required|string|max:255',
            'amount'           => 'required|numeric|min:0.01',
            'type'             => 'required|in:debit,credit',
            'reference'        => 'nullable|string|max:255',
        ]);

        $validated['bank_account_id'] = $bankAccount->id;
        $this->bankService->recordTransaction($validated);

        return redirect()->route('accounting.bank.transactions', $bankAccount)
            ->with('success', __('messages.transaction_recorded'));
    }
}
