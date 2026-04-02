<?php

namespace App\Http\Controllers;

use App\Http\Requests\JournalEntryRequest;
use App\Models\ChartOfAccount;
use App\Models\FiscalPeriod;
use App\Models\JournalEntry;
use App\Models\JournalTemplate;
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
            ->when($request->input('entry_type'), fn($q, $t) => $q->where('entry_type', $t))
            ->latest('date')
            ->paginate(20)
            ->withQueryString();

        return view('accounting.journals.index', compact('journals'));
    }

    public function show(JournalEntry $journal)
    {
        $journal->load(['items.account', 'creator', 'reversedEntry', 'reversingEntry']);

        return view('accounting.journals.show', compact('journal'));
    }

    public function create()
    {
        $accounts  = ChartOfAccount::active()->orderBy('code')->get();
        $templates = JournalTemplate::active()->orderBy('name')->get();

        return view('accounting.journals.create', compact('accounts', 'templates'));
    }

    public function store(JournalEntryRequest $request)
    {
        // Check closed period
        if ($this->accountingService->isDateInClosedPeriod($request->date)) {
            return back()->withInput()
                ->with('error', __('messages.journal_date_in_closed_period'));
        }

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

    /**
     * Create a reversing entry for an existing journal.
     */
    public function reverse(Request $request, JournalEntry $journal)
    {
        $date = $request->input('date', now()->toDateString());

        if ($this->accountingService->isDateInClosedPeriod($date)) {
            return back()->with('error', __('messages.journal_date_in_closed_period'));
        }

        $reversing = $this->accountingService->createReversingEntry($journal, $date);
        $this->logCreate($reversing);

        return redirect()->route('accounting.journals.show', $reversing)
            ->with('success', __('messages.reversing_entry_created'));
    }

    /**
     * Show adjusting entry form.
     */
    public function createAdjusting()
    {
        $accounts = ChartOfAccount::active()->orderBy('code')->get();

        return view('accounting.journals.adjusting', compact('accounts'));
    }

    /**
     * Store an adjusting entry.
     */
    public function storeAdjusting(JournalEntryRequest $request)
    {
        if ($this->accountingService->isDateInClosedPeriod($request->date)) {
            return back()->withInput()
                ->with('error', __('messages.journal_date_in_closed_period'));
        }

        $journal = $this->accountingService->createAdjustingEntry(
            $request->date,
            $request->description,
            $request->items
        );

        $this->logCreate($journal);

        return redirect()->route('accounting.journals.show', $journal)
            ->with('success', __('messages.adjusting_entry_created'));
    }

    // ── Journal Templates ─────────────────────────────────────────

    public function templates()
    {
        $templates = JournalTemplate::with('creator')->latest()->paginate(20);

        return view('accounting.journals.templates', compact('templates'));
    }

    public function createTemplate()
    {
        $accounts = ChartOfAccount::active()->orderBy('code')->get();

        return view('accounting.journals.template-form', compact('accounts'));
    }

    public function storeTemplate(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'items'       => 'required|array|min:2',
            'items.*.account_id' => 'required|exists:chart_of_accounts,id',
            'items.*.debit'      => 'required|numeric|min:0',
            'items.*.credit'     => 'required|numeric|min:0',
        ]);

        $template = JournalTemplate::create([
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'items'       => $data['items'],
            'created_by'  => auth()->id(),
        ]);

        return redirect()->route('accounting.journals.templates')
            ->with('success', __('messages.template_created'));
    }

    public function destroyTemplate(JournalTemplate $template)
    {
        $template->delete();

        return back()->with('success', __('messages.template_deleted'));
    }
}
