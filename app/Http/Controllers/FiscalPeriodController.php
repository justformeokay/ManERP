<?php

namespace App\Http\Controllers;

use App\Models\FiscalPeriod;
use App\Services\AccountingService;
use App\Traits\Auditable;
use Illuminate\Http\Request;

class FiscalPeriodController extends Controller
{
    use Auditable;

    protected string $model = 'accounting';

    public function __construct(private AccountingService $accountingService) {}

    public function index()
    {
        $periods = FiscalPeriod::orderByDesc('start_date')->paginate(20);

        return view('accounting.fiscal-periods.index', compact('periods'));
    }

    public function create()
    {
        return view('accounting.fiscal-periods.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'       => 'required|string|max:50',
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after:start_date',
        ]);

        // Check for overlapping periods
        $overlap = FiscalPeriod::where(function ($q) use ($data) {
            $q->whereBetween('start_date', [$data['start_date'], $data['end_date']])
              ->orWhereBetween('end_date', [$data['start_date'], $data['end_date']])
              ->orWhere(function ($q2) use ($data) {
                  $q2->where('start_date', '<=', $data['start_date'])
                     ->where('end_date', '>=', $data['end_date']);
              });
        })->exists();

        if ($overlap) {
            return back()->withInput()
                ->with('error', __('messages.fiscal_period_overlap'));
        }

        $period = FiscalPeriod::create($data);
        $this->logCreate($period);

        return redirect()->route('accounting.fiscal-periods.index')
            ->with('success', __('messages.fiscal_period_created'));
    }

    public function close(Request $request, FiscalPeriod $period)
    {
        $this->authorize('close', $period);

        try {
            $oldData = $period->toArray();

            $this->accountingService->closePeriod(
                $period,
                $request->input('closing_notes')
            );

            $this->logUpdate($period, $oldData);

            return back()->with('success', __('messages.fiscal_period_closed'));
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function reopen(FiscalPeriod $period)
    {
        $this->authorize('reopen', $period);

        try {
            $oldData = $period->toArray();

            $this->accountingService->reopenPeriod($period);
            $this->logUpdate($period, $oldData);

            return back()->with('success', __('messages.fiscal_period_reopened'));
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Generate monthly fiscal periods for a year.
     */
    public function generateYear(Request $request)
    {
        $year = (int) $request->validate(['year' => 'required|integer|min:2020|max:2099'])['year'];

        $created = 0;
        for ($m = 1; $m <= 12; $m++) {
            $start = sprintf('%04d-%02d-01', $year, $m);
            $end   = date('Y-m-t', strtotime($start));
            $name  = date('F Y', strtotime($start));

            $exists = FiscalPeriod::where('start_date', $start)->where('end_date', $end)->exists();
            if (!$exists) {
                FiscalPeriod::create([
                    'name'       => $name,
                    'start_date' => $start,
                    'end_date'   => $end,
                ]);
                $created++;
            }
        }

        return back()->with('success', __('messages.fiscal_periods_generated', ['count' => $created, 'year' => $year]));
    }
}
