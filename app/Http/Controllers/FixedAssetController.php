<?php

namespace App\Http\Controllers;

use App\Models\ChartOfAccount;
use App\Models\FixedAsset;
use App\Services\FixedAssetService;
use Illuminate\Http\Request;

class FixedAssetController extends Controller
{
    public function __construct(private FixedAssetService $assetService) {}

    public function index()
    {
        $assets = FixedAsset::latest()->get();
        $summary = [
            'total'              => $assets->count(),
            'active'             => $assets->where('status', 'active')->count(),
            'total_cost'         => $assets->sum('purchase_cost'),
            'total_depreciation' => $assets->sum('accumulated_depreciation'),
            'total_book_value'   => $assets->sum('book_value'),
        ];
        return view('accounting.assets.index', compact('assets', 'summary'));
    }

    public function create()
    {
        $assetAccounts       = ChartOfAccount::active()->where('code', 'like', '15%')->orderBy('code')->get();
        $depreciationAccounts = ChartOfAccount::active()->where('code', 'like', '15%')->orderBy('code')->get();
        $expenseAccounts     = ChartOfAccount::active()->where('code', 'like', '5%')->orderBy('code')->get();
        $categories = FixedAsset::categoryOptions();
        $methods    = FixedAsset::methodOptions();
        return view('accounting.assets.create', compact('assetAccounts', 'depreciationAccounts', 'expenseAccounts', 'categories', 'methods'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code'                 => 'required|string|max:50|unique:fixed_assets,code',
            'name'                 => 'required|string|max:255',
            'category'             => 'required|in:' . implode(',', FixedAsset::categoryOptions()),
            'description'          => 'nullable|string|max:1000',
            'purchase_date'        => 'required|date',
            'purchase_cost'        => 'required|numeric|min:0',
            'useful_life_months'   => 'required|integer|min:1',
            'salvage_value'        => 'required|numeric|min:0',
            'depreciation_method'  => 'required|in:' . implode(',', FixedAsset::methodOptions()),
            'location'             => 'nullable|string|max:255',
            'coa_asset_id'         => 'nullable|exists:chart_of_accounts,id',
            'coa_depreciation_id'  => 'nullable|exists:chart_of_accounts,id',
            'coa_expense_id'       => 'nullable|exists:chart_of_accounts,id',
        ]);

        $validated['book_value'] = $validated['purchase_cost'];
        $validated['accumulated_depreciation'] = 0;
        $validated['status'] = 'active';
        FixedAsset::create($validated);

        return redirect()->route('accounting.assets.index')
            ->with('success', __('messages.asset_created'));
    }

    public function show(FixedAsset $asset)
    {
        $asset->load('depreciationEntries');
        $schedule = $this->assetService->getDepreciationSchedule($asset);
        return view('accounting.assets.show', compact('asset', 'schedule'));
    }

    public function runDepreciation(Request $request)
    {
        $request->validate(['period_date' => 'required|date']);
        $results = $this->assetService->runMonthlyDepreciation($request->period_date);

        return back()->with('success', __('messages.depreciation_processed', [
            'count' => $results['processed'],
        ]));
    }

    public function dispose(FixedAsset $asset, Request $request)
    {
        $request->validate([
            'disposed_date'   => 'required|date',
            'disposal_amount' => 'required|numeric|min:0',
        ]);

        $this->assetService->disposeAsset($asset, $request->disposed_date, $request->disposal_amount);

        return redirect()->route('accounting.assets.index')
            ->with('success', __('messages.asset_disposed'));
    }
}
