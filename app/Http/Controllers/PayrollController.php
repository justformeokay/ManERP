<?php

namespace App\Http\Controllers;

use App\Http\Requests\PayrollGenerateRequest;
use App\Models\Employee;
use App\Models\Payslip;
use App\Models\PayrollPeriod;
use App\Services\PayrollService;
use App\Traits\Auditable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PayrollController extends Controller
{
    use Auditable;

    protected string $model = 'hr';

    public function __construct(private PayrollService $payrollService) {}

    // ── Payroll Periods ──────────────────────────────────────

    public function index(Request $request)
    {
        $periods = PayrollPeriod::query()
            ->search($request->input('search'))
            ->when($request->input('year'), fn($q, $y) => $q->where('year', $y))
            ->latest('year')
            ->latest('month')
            ->paginate(12)
            ->withQueryString();

        return view('hr.payroll.index', compact('periods'));
    }

    public function create()
    {
        $employeeCount = Employee::active()->count();

        return view('hr.payroll.create', compact('employeeCount'));
    }

    /**
     * Generate payslips for all active employees.
     */
    public function store(PayrollGenerateRequest $request)
    {
        $month = $request->month;
        $year  = $request->year;

        // Check duplicate
        $existing = PayrollPeriod::where('month', $month)->where('year', $year)->first();
        if ($existing) {
            return back()->with('error', "Payroll untuk periode {$month}/{$year} sudah ada.")
                ->withInput();
        }

        $period = DB::transaction(function () use ($month, $year, $request) {
            $period = PayrollPeriod::create([
                'month'  => $month,
                'year'   => $year,
                'status' => 'draft',
            ]);

            $overrides = $request->input('overrides', []);
            $count = $this->payrollService->generatePayslips($period, $overrides);

            if ($count === 0) {
                throw new \RuntimeException('Tidak ada karyawan aktif dengan struktur gaji.');
            }

            return $period;
        });

        $this->logCreate($period, 'hr');

        return redirect()->route('hr.payroll.show', $period)
            ->with('success', "Payroll {$period->period_label} berhasil di-generate ({$period->payslips()->count()} karyawan).");
    }

    public function show(PayrollPeriod $period)
    {
        $period->load(['payslips.employee', 'creator', 'approver']);

        $summary = [
            'total_gross'      => $period->payslips->sum('gross_salary'),
            'total_deductions' => $period->payslips->sum('total_deductions'),
            'total_net'        => $period->payslips->sum('net_salary'),
            'total_pph21'      => $period->payslips->sum('pph21_amount'),
            'total_bpjs_co'    => $period->payslips->sum(fn($p) => $p->total_bpjs_company),
            'total_bpjs_emp'   => $period->payslips->sum(fn($p) => $p->total_bpjs_employee),
            'employee_count'   => $period->payslips->count(),
        ];

        return view('hr.payroll.show', compact('period', 'summary'));
    }

    /**
     * View individual payslip detail.
     */
    public function payslip(Payslip $payslip)
    {
        $payslip->load(['employee', 'payrollPeriod', 'items']);

        return view('hr.payroll.payslip', compact('payslip'));
    }

    /**
     * Approve payroll period.
     */
    public function approve(PayrollPeriod $period)
    {
        $this->authorize('approve', $period);

        $check = $period->requireTransition('approved');
        if ($check !== true) {
            return back()->with('error', $check);
        }

        $oldData = $period->toArray();
        $period->transitionTo('approved');
        $period->approved_by = auth()->id();
        $period->approved_at = now();
        $period->save();

        $this->logAction($period, 'approve', "Payroll {$period->period_label} approved", $oldData, 'hr');

        return back()->with('success', "Payroll {$period->period_label} berhasil di-approve.");
    }

    /**
     * Post payroll to accounting — creates journal entry.
     */
    public function postToAccounting(PayrollPeriod $period)
    {
        $this->authorize('post', $period);

        $check = $period->requireTransition('posted');
        if ($check !== true) {
            return back()->with('error', $check);
        }

        $oldData = $period->toArray();

        DB::transaction(function () use ($period) {
            $this->payrollService->postToAccounting($period);

            $period->transitionTo('posted');
            $period->posted_at = now();
            $period->save();
        });

        $this->logAction($period, 'post', "Payroll {$period->period_label} posted to accounting", $oldData, 'hr');

        return back()->with('success', "Payroll {$period->period_label} berhasil di-posting ke Accounting.");
    }

    /**
     * Dashboard overview — workforce cost summary.
     */
    public function dashboard()
    {
        $currentYear = now()->year;

        $monthlySummary = PayrollPeriod::where('year', $currentYear)
            ->orderBy('month')
            ->get()
            ->map(fn($p) => [
                'month'       => $p->month,
                'label'       => $p->period_label,
                'total_gross' => (float) $p->total_gross,
                'total_net'   => (float) $p->total_net,
                'status'      => $p->status,
            ]);

        $employeeCount = Employee::active()->count();
        $latestPeriod  = PayrollPeriod::latest('year')->latest('month')->first();

        return view('hr.payroll.dashboard', compact('monthlySummary', 'employeeCount', 'latestPeriod', 'currentYear'));
    }
}
