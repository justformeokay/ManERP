<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ __('messages.cash_flow_title') }}</title>
    <style>
        @page { margin: 25mm 25mm 20mm 25mm; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'DejaVu Sans', 'Helvetica', 'Arial', sans-serif;
            font-size: 9pt;
            line-height: 1.45;
            color: #222;
            background: #fff;
            padding: 10mm 15mm;
        }

        /* ── DRAFT Watermark ─────────────────────────── */
        .watermark {
            position: fixed;
            top: 35%;
            left: 10%;
            font-size: 100pt;
            font-weight: bold;
            color: rgba(200, 0, 0, 0.08);
            transform: rotate(-35deg);
            z-index: -1;
            letter-spacing: 15px;
            white-space: nowrap;
        }

        /* ── Header ──────────────────────────────────── */
        .header-table { width: 100%; margin-bottom: 10px; }
        .header-table td { vertical-align: top; }
        .logo-cell { width: 100px; }
        .logo-cell img { width: 90px; height: 90px; object-fit: contain; }
        .company-info { padding-left: 15px; }
        .company-name { font-size: 14pt; font-weight: bold; color: #1a1a1a; }
        .company-address { font-size: 8pt; color: #666; margin-top: 2px; }

        .report-title-block { text-align: right; }
        .report-title {
            font-size: 12pt;
            font-weight: bold;
            color: #1a1a1a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .report-subtitle { font-size: 8pt; color: #555; margin-top: 1px; }
        .report-period { font-size: 8.5pt; color: #444; margin-top: 3px; }
        .header-divider { border: none; border-top: 2px solid #333; margin: 8px 0 15px 0; }

        /* ── Financial Table ─────────────────────────── */
        .fin-table { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        .fin-table td, .fin-table th { padding: 3.5px 8px; font-size: 9pt; }

        .section-title {
            font-weight: bold;
            font-size: 9.5pt;
            color: #1a1a1a;
            padding-top: 10px;
            padding-bottom: 4px;
            border-bottom: 1px solid #bbb;
        }
        .sub-title {
            font-size: 8pt;
            font-weight: bold;
            text-transform: uppercase;
            color: #777;
            letter-spacing: 0.3px;
            padding-top: 6px;
            padding-bottom: 2px;
        }

        .label-col { text-align: left; padding-left: 0; }
        .amount-col {
            text-align: right;
            width: 140px;
            padding-right: 0;
            font-variant-numeric: tabular-nums;
            white-space: nowrap;
        }
        .indent-1 { padding-left: 20px; }
        .indent-2 { padding-left: 40px; }

        /* Sub-total: single underline */
        .subtotal-row td {
            font-weight: bold;
            border-top: 1px solid #333;
            padding-top: 4px;
            padding-bottom: 5px;
        }
        /* Grand total: double underline */
        .grandtotal-row td {
            font-weight: bold;
            font-size: 10pt;
            border-top: 3px double #333;
            padding-top: 5px;
            padding-bottom: 5px;
        }

        /* ── Reconciliation ──────────────────────────── */
        .reconciliation-box {
            margin-top: 12px;
            border: 1px solid #ccc;
            padding: 8px 12px;
            background: #fafafa;
        }
        .status-ok {
            color: #166534;
            font-weight: bold;
            font-size: 9pt;
        }
        .status-warn {
            color: #92400e;
            font-weight: bold;
            font-size: 9pt;
        }

        /* ── Footer ──────────────────────────────────── */
        .footer-table { width: 100%; margin-top: 18px; border-top: 1px solid #ccc; padding-top: 6px; }
        .footer-table td { font-size: 7.5pt; color: #999; }
    </style>
</head>
<body>
@if($data['is_draft'] ?? false)
    <div class="watermark">DRAFT</div>
@endif

@php
    // Embed logo as base64 data URI so DomPDF always finds it regardless of symlinks
    $logoSrc = null;
    if (!empty($company->logo)) {
        $logoPath = storage_path('app/public/' . $company->logo);
        if (file_exists($logoPath)) {
            $mime = mime_content_type($logoPath) ?: 'image/png';
            $logoSrc = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($logoPath));
        }
    }

    /**
     * Format currency for PDF: negative in parentheses, right-aligned.
     */
    $fmtCurrency = function(float $amount) use ($company) {
        $abs = abs($amount);
        $cur = $company->currency ?? config('app.currency', 'IDR');
        $symbol = match($cur) {
            'IDR' => 'Rp',
            'USD' => '$',
            'CNY', 'JPY' => '¥',
            'KRW' => '₩',
            'EUR' => '€',
            'GBP' => '£',
            default => $cur,
        };
        $decimals = in_array($cur, ['IDR', 'KRW', 'JPY']) ? 0 : 2;
        $sep = $cur === 'IDR' ? '.' : ',';
        $dec = $cur === 'IDR' ? ',' : '.';
        $formatted = $symbol . ' ' . number_format($abs, $decimals, $dec, $sep);
        return $amount < -0.005 ? '(' . $formatted . ')' : $formatted;
    };

    $labelOf = function(string $rawLabel) {
        $key = 'messages.cf_' . $rawLabel;
        $translated = __($key);
        return $translated !== $key
            ? $translated
            : ucwords(str_replace(['_', 'change in '], [' ', ''], $rawLabel));
    };
@endphp

{{-- ═══════════════ HEADER ═══════════════ --}}
<table class="header-table">
    <tr>
        @if($logoSrc)
        <td class="logo-cell">
            <img src="{{ $logoSrc }}" alt="Logo">
        </td>
        @endif
        <td class="company-info">
            <div class="company-name">{{ $company->name ?? config('app.name') }}</div>
            @if($company->address)
                <div class="company-address">
                    {{ $company->address }}@if($company->city), {{ $company->city }}@endif
                    @if($company->phone) &bull; {{ $company->phone }}@endif
                </div>
            @endif
        </td>
        <td class="report-title-block">
            <div class="report-title">{{ __('messages.cash_flow_title') }}</div>
            <div class="report-subtitle">{{ __('messages.cf_indirect_method') }} (PSAK 2 / IAS 7)</div>
            <div class="report-period">
                {{ __('messages.cf_period') }}: {{ \Carbon\Carbon::parse($data['start_date'])->translatedFormat('d F Y') }}
                — {{ \Carbon\Carbon::parse($data['end_date'])->translatedFormat('d F Y') }}
            </div>
            @if($data['is_draft'] ?? false)
                <div style="margin-top:3px; color:#dc2626; font-weight:bold; font-size:8.5pt;">{{ __('messages.cf_draft_notice') }}</div>
            @endif
        </td>
    </tr>
</table>
<hr class="header-divider">

{{-- ═══════════════ I. OPERATING ACTIVITIES ═══════════════ --}}
<table class="fin-table">
    <tr>
        <td class="section-title" colspan="2">I. {{ __('messages.cf_operating') }}</td>
    </tr>
    <tr>
        <td class="label-col indent-1">{{ __('messages.net_income') }}</td>
        <td class="amount-col">{{ $fmtCurrency($data['net_income']) }}</td>
    </tr>

    @if(!empty($data['operating']))
        @php
            $nonCashItems = array_filter($data['operating'], fn($i) => $i['label'] === 'depreciation_amortization');
            $wcItems = array_filter($data['operating'], fn($i) => $i['label'] !== 'depreciation_amortization');
        @endphp

        @if(!empty($nonCashItems))
        <tr>
            <td class="sub-title indent-1" colspan="2">{{ __('messages.cf_non_cash_adjustments') }}</td>
        </tr>
        @foreach($nonCashItems as $item)
        <tr>
            <td class="label-col indent-2">{{ $labelOf($item['label']) }}</td>
            <td class="amount-col">{{ $fmtCurrency($item['amount']) }}</td>
        </tr>
        @endforeach
        @endif

        @if(!empty($wcItems))
        <tr>
            <td class="sub-title indent-1" colspan="2">{{ __('messages.cf_working_capital_changes') }}</td>
        </tr>
        @foreach($wcItems as $item)
        <tr>
            <td class="label-col indent-2">{{ $labelOf($item['label']) }}</td>
            <td class="amount-col">{{ $fmtCurrency($item['amount']) }}</td>
        </tr>
        @endforeach
        @endif
    @endif

    <tr class="subtotal-row">
        <td class="label-col indent-1">{{ __('messages.cf_total_operating') }}</td>
        <td class="amount-col">{{ $fmtCurrency($data['total_operating']) }}</td>
    </tr>
</table>

{{-- ═══════════════ II. INVESTING ACTIVITIES ═══════════════ --}}
<table class="fin-table">
    <tr>
        <td class="section-title" colspan="2">II. {{ __('messages.cf_investing') }}</td>
    </tr>
    @forelse($data['investing'] as $item)
    <tr>
        <td class="label-col indent-1">{{ $labelOf($item['label']) }}</td>
        <td class="amount-col">{{ $fmtCurrency($item['amount']) }}</td>
    </tr>
    @empty
    <tr>
        <td class="label-col indent-1" style="color:#999;font-style:italic;">{{ __('messages.no_activity') }}</td>
        <td class="amount-col">{{ $fmtCurrency(0) }}</td>
    </tr>
    @endforelse
    <tr class="subtotal-row">
        <td class="label-col indent-1">{{ __('messages.cf_total_investing') }}</td>
        <td class="amount-col">{{ $fmtCurrency($data['total_investing']) }}</td>
    </tr>
</table>

{{-- ═══════════════ III. FINANCING ACTIVITIES ═══════════════ --}}
<table class="fin-table">
    <tr>
        <td class="section-title" colspan="2">III. {{ __('messages.cf_financing') }}</td>
    </tr>
    @forelse($data['financing'] as $item)
    <tr>
        <td class="label-col indent-1">{{ $labelOf($item['label']) }}</td>
        <td class="amount-col">{{ $fmtCurrency($item['amount']) }}</td>
    </tr>
    @empty
    <tr>
        <td class="label-col indent-1" style="color:#999;font-style:italic;">{{ __('messages.no_activity') }}</td>
        <td class="amount-col">{{ $fmtCurrency(0) }}</td>
    </tr>
    @endforelse
    <tr class="subtotal-row">
        <td class="label-col indent-1">{{ __('messages.cf_total_financing') }}</td>
        <td class="amount-col">{{ $fmtCurrency($data['total_financing']) }}</td>
    </tr>
</table>

{{-- ═══════════════ NET CHANGE IN CASH ═══════════════ --}}
<table class="fin-table">
    <tr class="grandtotal-row">
        <td class="label-col">{{ __('messages.cf_net_increase_decrease') }}</td>
        <td class="amount-col">{{ $fmtCurrency($data['net_cash_change']) }}</td>
    </tr>
</table>

{{-- ═══════════════ RECONCILIATION ═══════════════ --}}
<div class="reconciliation-box">
    <table class="fin-table" style="margin-bottom:0;">
        <tr>
            <td class="section-title" colspan="2" style="padding-top:0;">{{ __('messages.cf_reconciliation') }}</td>
        </tr>
        <tr>
            <td class="label-col indent-1">{{ __('messages.beginning_cash') }}</td>
            <td class="amount-col">{{ $fmtCurrency($data['beginning_cash']) }}</td>
        </tr>
        <tr>
            <td class="label-col indent-1">{{ __('messages.cf_net_increase_decrease') }}</td>
            <td class="amount-col">{{ $fmtCurrency($data['net_cash_change']) }}</td>
        </tr>
        <tr class="subtotal-row">
            <td class="label-col indent-1">{{ __('messages.cf_ending_cash_computed') }}</td>
            <td class="amount-col">{{ $fmtCurrency($data['ending_cash']) }}</td>
        </tr>
        <tr>
            <td class="label-col indent-1" style="font-weight:bold;">{{ __('messages.cf_ending_cash_actual') }}</td>
            <td class="amount-col" style="font-weight:bold;">{{ $fmtCurrency($data['actual_ending_cash']) }}</td>
        </tr>
        @if($data['has_discrepancy'])
        <tr>
            <td colspan="2" class="status-warn" style="padding-top:6px;">
                &#9888; {{ __('messages.cf_unreconciled_diff') }}: {{ $fmtCurrency($data['discrepancy_amount']) }}
            </td>
        </tr>
        @else
        <tr>
            <td colspan="2" class="status-ok" style="padding-top:6px;">
                &#10003; {{ __('messages.cf_reconciled') }}
            </td>
        </tr>
        @endif
    </table>
</div>

{{-- ═══════════════ FOOTER ═══════════════ --}}
<table class="footer-table">
    <tr>
        <td style="text-align:left;">
            {{ __('messages.cf_generated_at') }}: {{ now()->translatedFormat('d M Y H:i') }}
            @if($data['is_draft'] ?? false)
                &bull; <span style="color:#dc2626; font-weight:bold;">DRAFT — {{ __('messages.cf_draft_notice') }}</span>
            @endif
        </td>
        <td style="text-align:right;">
            {{ __('messages.cash_flow_title') }} — {{ __('messages.cf_indirect_method') }}
        </td>
    </tr>
</table>

</body>
</html>
