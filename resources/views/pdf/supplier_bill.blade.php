@extends('pdf.layout')

@section('title', 'Supplier Bill ' . $bill->bill_number)

@section('content')
    {{-- Header --}}
    <div class="header">
        <table class="header-table">
            <tr>
                <td class="company-info">
                    @if($company->logo)
                        <img src="{{ public_path('storage/' . $company->logo) }}" alt="Logo" class="company-logo">
                    @endif
                    <div class="company-name">{{ $company->name }}</div>
                    <div class="company-details">
                        {{ $company->full_address }}<br>
                        @if($company->phone)Phone: {{ $company->phone }}<br>@endif
                        @if($company->email)Email: {{ $company->email }}<br>@endif
                        @if($company->tax_id)Tax ID: {{ $company->tax_id }}@endif
                    </div>
                </td>
                <td class="document-info">
                    <div class="document-title">Supplier Bill</div>
                    <div class="document-meta">
                        <table>
                            <tr>
                                <td class="label">Bill Number:</td>
                                <td class="value">{{ $bill->bill_number }}</td>
                            </tr>
                            <tr>
                                <td class="label">Bill Date:</td>
                                <td class="value">{{ $bill->bill_date->format('M d, Y') }}</td>
                            </tr>
                            <tr>
                                <td class="label">Due Date:</td>
                                <td class="value">{{ $bill->due_date->format('M d, Y') }}</td>
                            </tr>
                            @if($bill->purchaseOrder)
                            <tr>
                                <td class="label">PO Ref:</td>
                                <td class="value">{{ $bill->purchaseOrder->number }}</td>
                            </tr>
                            @endif
                            <tr>
                                <td class="label">Status:</td>
                                <td class="value">
                                    <span class="status-badge status-{{ $bill->status }}">{{ ucfirst($bill->status) }}</span>
                                </td>
                            </tr>
                        </table>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    {{-- Supplier Info --}}
    <div class="parties">
        <table class="parties-table">
            <tr>
                <td>
                    <div class="party-box">
                        <div class="party-label">Supplier / Vendor</div>
                        <div class="party-name">{{ $supplier->name ?? 'N/A' }}</div>
                        <div class="party-details">
                            @if($supplier)
                                @if($supplier->company){{ $supplier->company }}<br>@endif
                                @if($supplier->address){{ $supplier->address }}<br>@endif
                                @if($supplier->city){{ $supplier->city }}@endif
                                @if($supplier->phone)<br>Phone: {{ $supplier->phone }}@endif
                                @if($supplier->email)<br>Email: {{ $supplier->email }}@endif
                            @endif
                        </div>
                    </div>
                </td>
                <td>
                    <div class="party-box">
                        <div class="party-label">Payment Summary</div>
                        <div class="party-details">
                            <strong>Total Amount:</strong> {{ \App\Services\PDFService::formatCurrency($bill->total, $company->currency) }}<br>
                            <strong>Paid:</strong> {{ \App\Services\PDFService::formatCurrency($bill->paid_amount, $company->currency) }}<br>
                            <strong>Outstanding:</strong> {{ \App\Services\PDFService::formatCurrency($bill->total - $bill->paid_amount, $company->currency) }}<br>
                            @php
                                $daysUntilDue = now()->diffInDays($bill->due_date, false);
                            @endphp
                            @if($daysUntilDue < 0)
                                <span style="color: #dc2626;"><strong>OVERDUE</strong> by {{ abs($daysUntilDue) }} days</span>
                            @elseif($daysUntilDue <= 7)
                                <span style="color: #f59e0b;"><strong>Due in {{ $daysUntilDue }} days</strong></span>
                            @endif
                        </div>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    {{-- Items Table --}}
    <div class="items-section">
        <table class="items-table">
            <thead>
                <tr>
                    <th class="number">No</th>
                    <th class="description">Description</th>
                    <th class="qty">Qty</th>
                    <th class="price">Unit Price</th>
                    <th class="total">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $index => $item)
                <tr>
                    <td class="number">{{ $index + 1 }}</td>
                    <td class="description">
                        <div class="product-name">{{ $item->description ?? ($item->product->name ?? 'Item') }}</div>
                        @if($item->product && $item->product->sku)
                            <div class="product-sku">SKU: {{ $item->product->sku }}</div>
                        @endif
                    </td>
                    <td class="qty">{{ number_format($item->quantity, 2) }}</td>
                    <td class="price">{{ \App\Services\PDFService::formatCurrency($item->unit_price, $company->currency) }}</td>
                    <td class="total">{{ \App\Services\PDFService::formatCurrency($item->total ?? ($item->quantity * $item->unit_price), $company->currency) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Summary --}}
    <div class="summary-section">
        <table class="summary-table">
            <tr>
                <td class="notes-cell">
                    @if($bill->notes)
                        <div class="notes-box">
                            <div class="notes-label">Notes</div>
                            <div class="notes-content">{{ $bill->notes }}</div>
                        </div>
                    @endif

                    {{-- Payment History --}}
                    @if($bill->payments && $bill->payments->count() > 0)
                        <div style="margin-top: 15px;">
                            <div class="party-box">
                                <div class="party-label">Payment History</div>
                                <div class="party-details">
                                    @foreach($bill->payments as $payment)
                                        <div style="margin-bottom: 5px;">
                                            {{ $payment->payment_date->format('M d, Y') }} —
                                            {{ \App\Services\PDFService::formatCurrency($payment->amount, $company->currency) }}
                                            ({{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }})
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif
                </td>
                <td class="totals-cell">
                    <div class="totals-box">
                        <div class="totals-row">
                            <span class="label">Subtotal</span>
                            <span class="value">{{ \App\Services\PDFService::formatCurrency($bill->subtotal, $company->currency) }}</span>
                        </div>
                        @if($bill->tax_amount > 0)
                        <div class="totals-row">
                            <span class="label">Tax</span>
                            <span class="value">{{ \App\Services\PDFService::formatCurrency($bill->tax_amount, $company->currency) }}</span>
                        </div>
                        @endif
                        <div class="totals-row grand-total">
                            <span class="label">Grand Total</span>
                            <span class="value">{{ \App\Services\PDFService::formatCurrency($bill->total, $company->currency) }}</span>
                        </div>
                        @if($bill->paid_amount > 0)
                        <div class="totals-row">
                            <span class="label">Paid Amount</span>
                            <span class="value" style="color: #16a34a;">{{ \App\Services\PDFService::formatCurrency($bill->paid_amount, $company->currency) }}</span>
                        </div>
                        <div class="totals-row">
                            <span class="label">Balance Due</span>
                            <span class="value" style="color: #dc2626;">{{ \App\Services\PDFService::formatCurrency($bill->total - $bill->paid_amount, $company->currency) }}</span>
                        </div>
                        @endif
                    </div>
                </td>
            </tr>
        </table>
    </div>

    {{-- Footer --}}
    <div class="footer">
        <div class="signature-section">
            <table class="signature-table">
                <tr>
                    <td>
                        <div class="signature-box"></div>
                        <div class="signature-label">Verified By</div>
                    </td>
                    <td>
                        <div class="signature-box"></div>
                        <div class="signature-label">Approved By</div>
                    </td>
                    <td>
                        <div class="signature-box"></div>
                        <div class="signature-label">Accounts Payable</div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="terms">
            Internal Document — Accounts Payable Record<br>
            Generated on {{ now()->format('M d, Y H:i') }}
        </div>
    </div>
@endsection
