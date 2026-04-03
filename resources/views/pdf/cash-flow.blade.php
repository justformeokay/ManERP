<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Cash Flow Statement</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
            line-height: 1.4;
            color: #333;
            background: #fff;
        }
        .page { width: 100%; padding: 20px 30px; }

        /* Header */
        .header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }
        .company-name { font-size: 16px; font-weight: bold; color: #1a1a1a; }
        .company-address { font-size: 9px; color: #666; margin-top: 2px; }
        .report-title { font-size: 14px; font-weight: bold; color: #2563eb; margin-top: 8px; text-transform: uppercase; letter-spacing: 1px; }
        .report-subtitle { font-size: 10px; color: #666; margin-top: 2px; }
        .report-period { font-size: 10px; color: #555; margin-top: 4px; }

        /* Tables */
        table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .section-header {
            background: #f0f5ff;
            font-weight: bold;
            font-size: 11px;
            color: #1e3a5f;
            padding: 6px 8px;
            border-bottom: 1px solid #ccc;
        }
        .section-header.investing { background: #fff8f0; color: #7c4a00; }
        .section-header.financing { background: #f5f0ff; color: #4a1d80; }
        .section-header.reconciliation { background: #f0f0f0; color: #333; }

        td { padding: 4px 8px; font-size: 10px; }
        .label-cell { width: 70%; }
        .amount-cell { width: 30%; text-align: right; font-variant-numeric: tabular-nums; }
        .indent { padding-left: 24px; }
        .sub-header { font-size: 9px; font-weight: bold; text-transform: uppercase; color: #888; letter-spacing: 0.5px; padding-top: 6px; }

        .total-row { border-top: 2px solid #333; font-weight: bold; font-size: 11px; }
        .total-row td { padding-top: 6px; padding-bottom: 6px; }

        .positive { color: #166534; }
        .negative { color: #dc2626; }

        /* Reconciliation */
        .reconciled { background: #f0fdf4; color: #166534; font-weight: bold; padding: 6px 8px; }
        .discrepancy { background: #fffbeb; color: #92400e; font-weight: bold; padding: 6px 8px; }

        /* Footer */
        .footer {
            margin-top: 20px;
            border-top: 1px solid #ccc;
            padding-top: 8px;
            font-size: 8px;
            color: #999;
            text-align: center;
        }
    </style>
</head>
<body>
<div class="page">
    {{-- Header --}}
    <div class="header">
        <div class="company-name">{{ $company->name ?? config('app.name') }}</div>
        @if($company->address)
            <div class="company-address">{{ $company->address }}@if($company->city), {{ $company->city }}@endif @if($company->phone)| {{ $company->phone }}@endif</div>
        @endif
        <div class="report-title">{{ __('messages.cash_flow_title') }}</div>
        <div class="report-subtitle">{{ __('messages.cf_indirect_method') }}</div>
        <div class="report-period">{{ __('messages.cf_period') }}: {{ \Carbon\Carbon::parse($data['start_date'])->format('d M Y') }} — {{ \Carbon\Carbon::parse($data['end_date'])->format('d M Y') }}</div>
    </div>

    {{-- Operating Activities --}}
    <table>
        <tr><td colspan="2" class="section-header">{{ __('messages.cf_operating') }}</td></tr>
        <tr>
            <td class="label-cell">{{ __('messages.net_income') }}</td>
            <td class="amount-cell {{ $data['net_income'] >= 0 ? '' : 'negative' }}">{{ number_format($data['net_income'], 2) }}</td>
        </tr>
        @if(!empty($data['operating']))
            <tr><td colspan="2" class="sub-header indent">{{ __('messages.cf_adjustments') }}</td></tr>
            @foreach($data['operating'] as $item)
                @php
                    $label = __('messages.cf_' . $item['label']);
                    if ($label === 'messages.cf_' . $item['label']) {
                        $label = ucwords(str_replace(['_', 'change in '], [' ', ''], $item['label']));
                    }
                @endphp
                <tr>
                    <td class="label-cell indent">{{ $label }}</td>
                    <td class="amount-cell {{ $item['amount'] >= 0 ? 'positive' : 'negative' }}">{{ number_format($item['amount'], 2) }}</td>
                </tr>
            @endforeach
        @endif
        <tr class="total-row">
            <td class="label-cell">{{ __('messages.cf_total_operating') }}</td>
            <td class="amount-cell {{ $data['total_operating'] >= 0 ? '' : 'negative' }}">{{ number_format($data['total_operating'], 2) }}</td>
        </tr>
    </table>

    {{-- Investing Activities --}}
    <table>
        <tr><td colspan="2" class="section-header investing">{{ __('messages.cf_investing') }}</td></tr>
        @forelse($data['investing'] as $item)
            @php
                $label = __('messages.cf_' . $item['label']);
                if ($label === 'messages.cf_' . $item['label']) {
                    $label = ucwords(str_replace(['_', 'change in '], [' ', ''], $item['label']));
                }
            @endphp
            <tr>
                <td class="label-cell indent">{{ $label }}</td>
                <td class="amount-cell {{ $item['amount'] >= 0 ? 'positive' : 'negative' }}">{{ number_format($item['amount'], 2) }}</td>
            </tr>
        @empty
            <tr><td class="label-cell indent" style="color:#999;font-style:italic;">{{ __('messages.no_activity') }}</td><td></td></tr>
        @endforelse
        <tr class="total-row">
            <td class="label-cell">{{ __('messages.cf_total_investing') }}</td>
            <td class="amount-cell {{ $data['total_investing'] >= 0 ? '' : 'negative' }}">{{ number_format($data['total_investing'], 2) }}</td>
        </tr>
    </table>

    {{-- Financing Activities --}}
    <table>
        <tr><td colspan="2" class="section-header financing">{{ __('messages.cf_financing') }}</td></tr>
        @forelse($data['financing'] as $item)
            @php
                $label = __('messages.cf_' . $item['label']);
                if ($label === 'messages.cf_' . $item['label']) {
                    $label = ucwords(str_replace(['_', 'change in '], [' ', ''], $item['label']));
                }
            @endphp
            <tr>
                <td class="label-cell indent">{{ $label }}</td>
                <td class="amount-cell {{ $item['amount'] >= 0 ? 'positive' : 'negative' }}">{{ number_format($item['amount'], 2) }}</td>
            </tr>
        @empty
            <tr><td class="label-cell indent" style="color:#999;font-style:italic;">{{ __('messages.no_activity') }}</td><td></td></tr>
        @endforelse
        <tr class="total-row">
            <td class="label-cell">{{ __('messages.cf_total_financing') }}</td>
            <td class="amount-cell {{ $data['total_financing'] >= 0 ? '' : 'negative' }}">{{ number_format($data['total_financing'], 2) }}</td>
        </tr>
    </table>

    {{-- Reconciliation --}}
    <table>
        <tr><td colspan="2" class="section-header reconciliation">{{ __('messages.cf_reconciliation') }}</td></tr>
        <tr>
            <td class="label-cell">{{ __('messages.beginning_cash') }}</td>
            <td class="amount-cell">{{ number_format($data['beginning_cash'], 2) }}</td>
        </tr>
        <tr>
            <td class="label-cell">{{ __('messages.net_cash_change') }}</td>
            <td class="amount-cell {{ $data['net_cash_change'] >= 0 ? 'positive' : 'negative' }}">{{ number_format($data['net_cash_change'], 2) }}</td>
        </tr>
        <tr class="total-row">
            <td class="label-cell">{{ __('messages.cf_ending_cash_computed') }}</td>
            <td class="amount-cell">{{ number_format($data['ending_cash'], 2) }}</td>
        </tr>
        <tr>
            <td class="label-cell" style="font-weight:bold;">{{ __('messages.cf_ending_cash_actual') }}</td>
            <td class="amount-cell" style="font-weight:bold;">{{ number_format($data['actual_ending_cash'], 2) }}</td>
        </tr>
        @if($data['has_discrepancy'])
            <tr>
                <td colspan="2" class="discrepancy">
                    {{ __('messages.cf_unreconciled_diff') }}: {{ number_format($data['discrepancy_amount'], 2) }}
                </td>
            </tr>
        @else
            <tr>
                <td colspan="2" class="reconciled">&#10003; {{ __('messages.cf_reconciled') }}</td>
            </tr>
        @endif
    </table>

    {{-- Footer --}}
    <div class="footer">
        {{ __('messages.cf_generated_at') }}: {{ now()->format('d M Y H:i') }} |
        {{ __('messages.cash_flow_title') }} — {{ __('messages.cf_indirect_method') }} (PSAK 2 / IAS 7)
    </div>
</div>
</body>
</html>
