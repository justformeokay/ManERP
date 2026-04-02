<?php

namespace App\Services;

use App\Models\ChartOfAccount;
use App\Models\Invoice;
use App\Models\SupplierBill;
use Illuminate\Support\Facades\DB;

class TaxService
{
    public const PPN_RATE = 11; // PPN 11% (effective since April 2022)

    public const PPN_KELUARAN = '2110';  // PPN Keluaran (Output VAT — liability)
    public const PPN_MASUKAN  = '1140';  // PPN Masukan (Input VAT — asset)

    public function __construct(private AccountingService $accountingService) {}

    // ════════════════════════════════════════════════════════════════
    // PPN CALCULATION
    // ════════════════════════════════════════════════════════════════

    /**
     * Calculate PPN from DPP (Dasar Pengenaan Pajak).
     */
    public function calculatePPN(float $dpp, ?float $rate = null): array
    {
        $rate = $rate ?? self::PPN_RATE;
        $ppn  = round($dpp * $rate / 100, 2);

        return [
            'dpp'      => round($dpp, 2),
            'rate'     => $rate,
            'ppn'      => $ppn,
            'total'    => round($dpp + $ppn, 2),
        ];
    }

    /**
     * Reverse-calculate DPP from total (inclusive of PPN).
     */
    public function extractPPNFromTotal(float $totalInclusive, ?float $rate = null): array
    {
        $rate = $rate ?? self::PPN_RATE;
        $dpp  = round($totalInclusive / (1 + $rate / 100), 2);
        $ppn  = round($totalInclusive - $dpp, 2);

        return [
            'dpp'      => $dpp,
            'rate'     => $rate,
            'ppn'      => $ppn,
            'total'    => round($totalInclusive, 2),
        ];
    }

    // ════════════════════════════════════════════════════════════════
    // TAX REPORTING — SPT Masa PPN
    // ════════════════════════════════════════════════════════════════

    /**
     * Get the SPT Masa PPN summary for a given month.
     */
    public function getSptMasaPPN(int $year, int $month): array
    {
        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate   = date('Y-m-t', strtotime($startDate));

        // PPN Keluaran (from sales invoices)
        $ppnKeluaran = Invoice::whereBetween('invoice_date', [$startDate, $endDate])
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->select(
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(dpp) as total_dpp'),
                DB::raw('SUM(tax_amount) as total_ppn')
            )
            ->first();

        // PPN Masukan (from supplier bills)
        $ppnMasukan = SupplierBill::whereBetween('bill_date', [$startDate, $endDate])
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->select(
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(dpp) as total_dpp'),
                DB::raw('SUM(tax_amount) as total_ppn')
            )
            ->first();

        $keluaranAmount = (float) ($ppnKeluaran->total_ppn ?? 0);
        $masukanAmount  = (float) ($ppnMasukan->total_ppn ?? 0);
        $ppnKurangBayar = round($keluaranAmount - $masukanAmount, 2);

        return [
            'year'  => $year,
            'month' => $month,
            'period_label' => date('F Y', strtotime($startDate)),
            'ppn_keluaran' => [
                'count'     => (int) ($ppnKeluaran->count ?? 0),
                'total_dpp' => round((float) ($ppnKeluaran->total_dpp ?? 0), 2),
                'total_ppn' => round($keluaranAmount, 2),
            ],
            'ppn_masukan' => [
                'count'     => (int) ($ppnMasukan->count ?? 0),
                'total_dpp' => round((float) ($ppnMasukan->total_dpp ?? 0), 2),
                'total_ppn' => round($masukanAmount, 2),
            ],
            'ppn_kurang_bayar' => $ppnKurangBayar,
            'ppn_lebih_bayar'  => $ppnKurangBayar < 0 ? abs($ppnKurangBayar) : 0,
            'status' => $ppnKurangBayar > 0 ? 'kurang_bayar' : ($ppnKurangBayar < 0 ? 'lebih_bayar' : 'nihil'),
        ];
    }

    /**
     * Get invoice details for PPN Keluaran reporting.
     */
    public function getPPNKeluaranDetail(int $year, int $month): \Illuminate\Support\Collection
    {
        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate   = date('Y-m-t', strtotime($startDate));

        return Invoice::with('client')
            ->whereBetween('invoice_date', [$startDate, $endDate])
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->where('tax_amount', '>', 0)
            ->orderBy('invoice_date')
            ->get();
    }

    /**
     * Get supplier bill details for PPN Masukan reporting.
     */
    public function getPPNMasukanDetail(int $year, int $month): \Illuminate\Support\Collection
    {
        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate   = date('Y-m-t', strtotime($startDate));

        return SupplierBill::with('supplier')
            ->whereBetween('bill_date', [$startDate, $endDate])
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->where('tax_amount', '>', 0)
            ->orderBy('bill_date')
            ->get();
    }

    /**
     * Get annual tax summary.
     */
    public function getAnnualTaxSummary(int $year): array
    {
        $months = [];
        $totalKeluaran = 0;
        $totalMasukan  = 0;

        for ($m = 1; $m <= 12; $m++) {
            $spt = $this->getSptMasaPPN($year, $m);
            $months[] = $spt;
            $totalKeluaran += $spt['ppn_keluaran']['total_ppn'];
            $totalMasukan  += $spt['ppn_masukan']['total_ppn'];
        }

        return [
            'year'            => $year,
            'months'          => $months,
            'total_keluaran'  => round($totalKeluaran, 2),
            'total_masukan'   => round($totalMasukan, 2),
            'net_ppn'         => round($totalKeluaran - $totalMasukan, 2),
        ];
    }

    // ════════════════════════════════════════════════════════════════
    // AUTO-APPLY TAX ON TRANSACTIONS
    // ════════════════════════════════════════════════════════════════

    /**
     * Apply PPN to an invoice during creation/update.
     */
    public function applyPPNToInvoice(Invoice $invoice, ?float $rate = null): void
    {
        $rate = $rate ?? self::PPN_RATE;
        $dpp  = (float) $invoice->subtotal - (float) $invoice->discount;
        $ppn  = round($dpp * $rate / 100, 2);

        $invoice->update([
            'dpp'          => $dpp,
            'tax_rate'     => $rate,
            'tax_amount'   => $ppn,
            'total_amount' => round($dpp + $ppn, 2),
        ]);
    }

    /**
     * Apply PPN to a supplier bill.
     */
    public function applyPPNToBill(SupplierBill $bill, ?float $rate = null): void
    {
        $rate = $rate ?? self::PPN_RATE;
        $dpp  = (float) $bill->subtotal;
        $ppn  = round($dpp * $rate / 100, 2);

        $bill->update([
            'dpp'        => $dpp,
            'tax_rate'   => $rate,
            'tax_amount' => $ppn,
            'total'      => round($dpp + $ppn, 2),
        ]);
    }
}
