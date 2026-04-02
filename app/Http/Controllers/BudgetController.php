<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\BudgetLine;
use App\Models\ChartOfAccount;
use App\Services\BudgetService;
use Illuminate\Http\Request;

class BudgetController extends Controller
{
    public function __construct(private BudgetService $budgetService) {}

    public function index()
    {
        $budgets = Budget::with('createdBy')->latest()->get();
        return view('accounting.budgets.index', compact('budgets'));
    }

    public function create()
    {
        $accounts = ChartOfAccount::active()
            ->whereIn('type', ['expense', 'revenue'])
            ->orderBy('code')
            ->get();
        return view('accounting.budgets.create', compact('accounts'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'fiscal_year' => 'required|integer|min:2020|max:2099',
            'description' => 'nullable|string|max:1000',
            'lines'       => 'required|array|min:1',
            'lines.*.account_id' => 'required|exists:chart_of_accounts,id',
            'lines.*.jan' => 'nullable|numeric|min:0',
            'lines.*.feb' => 'nullable|numeric|min:0',
            'lines.*.mar' => 'nullable|numeric|min:0',
            'lines.*.apr' => 'nullable|numeric|min:0',
            'lines.*.may' => 'nullable|numeric|min:0',
            'lines.*.jun' => 'nullable|numeric|min:0',
            'lines.*.jul' => 'nullable|numeric|min:0',
            'lines.*.aug' => 'nullable|numeric|min:0',
            'lines.*.sep' => 'nullable|numeric|min:0',
            'lines.*.oct' => 'nullable|numeric|min:0',
            'lines.*.nov' => 'nullable|numeric|min:0',
            'lines.*.dec' => 'nullable|numeric|min:0',
        ]);

        $budget = Budget::create([
            'name'        => $validated['name'],
            'fiscal_year' => $validated['fiscal_year'],
            'description' => $validated['description'] ?? null,
            'status'      => 'draft',
            'created_by'  => auth()->id(),
        ]);

        foreach ($validated['lines'] as $line) {
            $budget->lines()->create($line);
        }

        return redirect()->route('accounting.budgets.index')
            ->with('success', __('messages.budget_created'));
    }

    public function show(Budget $budget)
    {
        $budget->load('lines.account');
        $comparison = $this->budgetService->getBudgetVsActual($budget);
        return view('accounting.budgets.show', compact('budget', 'comparison'));
    }

    public function approve(Budget $budget)
    {
        $budget->update([
            'status'      => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return back()->with('success', __('messages.budget_approved'));
    }

    public function destroy(Budget $budget)
    {
        if (!$budget->isDraft()) {
            return back()->with('error', __('messages.cannot_delete_approved_budget'));
        }

        $budget->lines()->delete();
        $budget->delete();

        return redirect()->route('accounting.budgets.index')
            ->with('success', __('messages.budget_deleted'));
    }
}
