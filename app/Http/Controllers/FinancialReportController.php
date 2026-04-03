<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\CompanySetting;
use App\Services\AccountingService;
use App\Services\CashFlowService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
        $company = CompanySetting::getSettings();

        return view('accounting.reports.cash-flow', compact('data', 'company'));
    }

    public function cashFlowPdf(Request $request)
    {
        $data = $this->cashFlowService->getCashFlowStatement(
            $request->input('from', now()->startOfYear()->toDateString()),
            $request->input('to', now()->toDateString())
        );
        $company = CompanySetting::getSettings();

        $pdf = Pdf::loadView('pdf.cash-flow', compact('data', 'company'))
            ->setPaper('A4', 'portrait')
            ->setOptions([
                'dpi'             => 150,
                'isRemoteEnabled' => true,
                'defaultFont'     => 'sans-serif',
            ]);

        $filename = "CashFlow-{$data['start_date']}-to-{$data['end_date']}.pdf";

        return $request->boolean('download')
            ? $pdf->download($filename)
            : $pdf->stream($filename);
    }

    public function cashFlowExcel(Request $request): StreamedResponse
    {
        $data = $this->cashFlowService->getCashFlowStatement(
            $request->input('from', now()->startOfYear()->toDateString()),
            $request->input('to', now()->toDateString())
        );

        $filename = "CashFlow-{$data['start_date']}-to-{$data['end_date']}.csv";

        return response()->streamDownload(function () use ($data) {
            $out = fopen('php://output', 'w');

            // BOM for Excel UTF-8 compatibility
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, ['Cash Flow Statement (Indirect Method)']);
            fputcsv($out, ['Period', $data['start_date'] . ' to ' . $data['end_date']]);
            fputcsv($out, []);

            // Operating Activities
            fputcsv($out, ['OPERATING ACTIVITIES', '']);
            fputcsv($out, ['Net Income', number_format($data['net_income'], 2, '.', '')]);
            fputcsv($out, ['Adjustments:', '']);
            foreach ($data['operating'] as $item) {
                $label = __('messages.cf_' . $item['label']);
                if ($label === 'messages.cf_' . $item['label']) {
                    $label = $item['label'];
                }
                fputcsv($out, ['  ' . $label, number_format($item['amount'], 2, '.', '')]);
            }
            fputcsv($out, ['Net Cash from Operations', number_format($data['total_operating'], 2, '.', '')]);
            fputcsv($out, []);

            // Investing Activities
            fputcsv($out, ['INVESTING ACTIVITIES', '']);
            foreach ($data['investing'] as $item) {
                $label = __('messages.cf_' . $item['label']);
                if ($label === 'messages.cf_' . $item['label']) {
                    $label = $item['label'];
                }
                fputcsv($out, ['  ' . $label, number_format($item['amount'], 2, '.', '')]);
            }
            fputcsv($out, ['Net Cash from Investing', number_format($data['total_investing'], 2, '.', '')]);
            fputcsv($out, []);

            // Financing Activities
            fputcsv($out, ['FINANCING ACTIVITIES', '']);
            foreach ($data['financing'] as $item) {
                $label = __('messages.cf_' . $item['label']);
                if ($label === 'messages.cf_' . $item['label']) {
                    $label = $item['label'];
                }
                fputcsv($out, ['  ' . $label, number_format($item['amount'], 2, '.', '')]);
            }
            fputcsv($out, ['Net Cash from Financing', number_format($data['total_financing'], 2, '.', '')]);
            fputcsv($out, []);

            // Summary
            fputcsv($out, ['SUMMARY', '']);
            fputcsv($out, ['Net Cash Change', number_format($data['net_cash_change'], 2, '.', '')]);
            fputcsv($out, ['Beginning Cash', number_format($data['beginning_cash'], 2, '.', '')]);
            fputcsv($out, ['Ending Cash (Computed)', number_format($data['ending_cash'], 2, '.', '')]);
            fputcsv($out, ['Ending Cash (Actual)', number_format($data['actual_ending_cash'], 2, '.', '')]);

            if ($data['has_discrepancy']) {
                fputcsv($out, ['Unreconciled Difference', number_format($data['discrepancy_amount'], 2, '.', '')]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
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
