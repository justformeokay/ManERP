<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChartOfAccountRequest;
use App\Models\ChartOfAccount;
use App\Traits\Auditable;
use Illuminate\Http\Request;

class ChartOfAccountController extends Controller
{
    use Auditable;

    protected string $model = 'accounting';

    public function index(Request $request)
    {
        $accounts = ChartOfAccount::query()
            ->with('parent')
            ->search($request->input('search'))
            ->when($request->input('type'), fn($q, $t) => $q->where('type', $t))
            ->orderBy('code')
            ->paginate(25)
            ->withQueryString();

        return view('accounting.coa.index', compact('accounts'));
    }

    public function create()
    {
        $parents = ChartOfAccount::active()->orderBy('code')->get();

        return view('accounting.coa.create', compact('parents'));
    }

    public function store(ChartOfAccountRequest $request)
    {
        $account = ChartOfAccount::create($request->validated());
        $this->logCreate($account);

        return redirect()->route('accounting.coa.index')
            ->with('success', "Account {$account->code} — {$account->name} created.");
    }

    public function edit(ChartOfAccount $account)
    {
        $parents = ChartOfAccount::active()
            ->where('id', '!=', $account->id)
            ->orderBy('code')
            ->get();

        return view('accounting.coa.edit', compact('account', 'parents'));
    }

    public function update(ChartOfAccountRequest $request, ChartOfAccount $account)
    {
        $old = $account->toArray();
        $account->update($request->validated());
        $this->logUpdate($account, $old);

        return redirect()->route('accounting.coa.index')
            ->with('success', "Account {$account->code} updated.");
    }

    public function destroy(ChartOfAccount $account)
    {
        if ($account->journalItems()->exists()) {
            return back()->with('error', 'Cannot delete account with journal entries.');
        }

        if ($account->children()->exists()) {
            return back()->with('error', 'Cannot delete account with child accounts.');
        }

        $this->logDelete($account);
        $account->delete();

        return redirect()->route('accounting.coa.index')
            ->with('success', "Account {$account->code} deleted.");
    }
}
