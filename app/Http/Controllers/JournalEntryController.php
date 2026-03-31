<?php

namespace App\Http\Controllers;

use App\Http\Requests\JournalEntryRequest;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Services\AccountingService;
use App\Traits\Auditable;
use Illuminate\Http\Request;

class JournalEntryController extends Controller
{
    use Auditable;

    protected string $model = 'accounting';

    public function __construct(private AccountingService $accountingService) {}

    public function index(Request $request)
    {
        $journals = JournalEntry::query()
            ->with('creator')
            ->withSum('items as total_debit', 'debit')
            ->search($request->input('search'))
            ->when($request->input('from'), fn($q, $d) => $q->where('date', '>=', $d))
            ->when($request->input('to'), fn($q, $d) => $q->where('date', '<=', $d))
            ->latest('date')
            ->paginate(20)
            ->withQueryString();

        return view('accounting.journals.index', compact('journals'));
    }

    public function show(JournalEntry $journal)
    {
        $journal->load(['items.account', 'creator']);

        return view('accounting.journals.show', compact('journal'));
    }

    public function create()
    {
        $accounts = ChartOfAccount::active()->orderBy('code')->get();

        return view('accounting.journals.create', compact('accounts'));
    }

    public function store(JournalEntryRequest $request)
    {
        $ref = 'JE-' . now()->format('Ymd') . '-' . str_pad(JournalEntry::whereDate('date', now())->count() + 1, 4, '0', STR_PAD_LEFT);

        $journal = $this->accountingService->createJournalEntry(
            $ref,
            $request->date,
            $request->description,
            $request->items
        );

        $this->logCreate($journal);

        return redirect()->route('accounting.journals.show', $journal)
            ->with('success', "Journal entry {$journal->reference} created.");
    }
}
