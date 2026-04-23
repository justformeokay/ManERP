@extends('pdf.layout')

@section('title', 'Invoice ' . $invoice->invoice_number)

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
                    <div class="document-title">Invoice</div>
                    <div class="document-meta">
                        <table>
                            <tr>
                                <td class="label">Invoice No:</td>
                                <td class="value">{{ $invoice->invoice_number }}</td>
                            </tr>
                            <tr>
                                <td class="label">Date:</td>
                                <td class="value">{{ $invoice->invoice_date->format('M d, Y') }}</td>
                            </tr>
                            <tr>
                                <td class="label">Due Date:</td>
                                <td class="value">{{ $invoice->due_date->format('M d, Y') }}</td>
                            </tr>
                            @if($invoice->salesOrder)
                            <tr>
                                <td class="label">SO Ref:</td>
                                <td class="value">{{ $invoice->salesOrder->number ?? '-' }}</td>
                            </tr>
                            @endif
                            <tr>
                                <td class="label">Status:</td>
                                <td class="value">
                                    <span class="status-badge status-{{ $invoice->status }}">{{ ucfirst($invoice->status) }}</span>
                                </td>
                            </tr>
                        </table>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    {{-- Bill To --}}
    <div class="parties">
        <table class="parties-table">
            <tr>
                <td>
                    <div class="party-box">
                        <div class="party-label">Bill To</div>
                        <div class="party-name">{{ $client->name ?? 'N/A' }}</div>
                        <div class="party-details">
                            @if($client)
                                @if($client->company){{ $client->company }}<br>@endif
                                @if($client->address){{ $client->address }}<br>@endif
                                @if($client->city){{ $client->city }}@endif
                                @if($client->phone)<br>Phone: {{ $client->phone }}@endif
                                @if($client->email)<br>Email: {{ $client->email }}@endif
                            @endif
                        </div>
                    </div>
                </td>
                <td>
                    <div class="party-box">
                        <div class="party-label">Payment Information</div>
                        <div class="party-details">
                            <strong>Total Due:</strong> {{ \App\Services\PDFService::formatCurrency($invoice->total_amount - $invoice->paid_amount, $company->currency) }}<br>
                            <strong>Payment Terms:</strong> Net {{ $invoice->due_date->diffInDays($invoice->invoice_date) }} days<br>
                            @if($invoice->paid_amount > 0)
                                <strong>Paid:</strong> {{ \App\Services\PDFService::formatCurrency($invoice->paid_amount, $company->currency) }}<br>
                            @endif
                            @if($company->bank_name)
                                <br><strong>Bank:</strong> {{ $company->bank_name }}<br>
                                @if($company->bank_account_number)<strong>Account No:</strong> {{ $company->bank_account_number }}<br>@endif
                                @if($company->bank_account_name)<strong>Account Name:</strong> {{ $company->bank_account_name }}@endif
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
                        <div class="product-name">{{ $item->product->name ?? $item->description ?? 'Item' }}</div>
                        @if($item->product && $item->product->sku)
                            <div class="product-sku">SKU: {{ $item->product->sku }}</div>
                        @endif
                    </td>
                    <td class="qty">{{ number_format($item->quantity, 2) }}</td>
                    <td class="price">{{ \App\Services\PDFService::formatCurrency($item->unit_price, $company->currency) }}</td>
                    <td class="total">{{ \App\Services\PDFService::formatCurrency($item->quantity * $item->unit_price, $company->currency) }}</td>
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
                    @if($invoice->notes)
                        <div class="notes-box">
                            <div class="notes-label">Notes</div>
                            <div class="notes-content">{{ $invoice->notes }}</div>
                        </div>
                    @endif
                </td>
                <td class="totals-cell">
                    <div class="totals-box">
                        <div class="totals-row">
                            <span class="label">Subtotal</span>
                            <span class="value">{{ \App\Services\PDFService::formatCurrency($invoice->subtotal, $company->currency) }}</span>
                        </div>
                        @if($invoice->tax_amount > 0)
                        <div class="totals-row">
                            <span class="label">DPP (Tax Base)</span>
                            <span class="value">{{ \App\Services\PDFService::formatCurrency($invoice->subtotal - ($invoice->discount ?? 0), $company->currency) }}</span>
                        </div>
                        <div class="totals-row">
                            <span class="label">PPN {{ $invoice->tax_rate ?? 11 }}%</span>
                            <span class="value">{{ \App\Services\PDFService::formatCurrency($invoice->tax_amount, $company->currency) }}</span>
                        </div>
                        @endif
                        @if($invoice->discount > 0)
                        <div class="totals-row">
                            <span class="label">Discount</span>
                            <span class="value">-{{ \App\Services\PDFService::formatCurrency($invoice->discount, $company->currency) }}</span>
                        </div>
                        @endif
                        <div class="totals-row grand-total">
                            <span class="label">Grand Total</span>
                            <span class="value">{{ \App\Services\PDFService::formatCurrency($invoice->total_amount, $company->currency) }}</span>
                        </div>
                        @if($invoice->paid_amount > 0)
                        <div class="totals-row">
                            <span class="label">Paid Amount</span>
                            <span class="value">{{ \App\Services\PDFService::formatCurrency($invoice->paid_amount, $company->currency) }}</span>
                        </div>
                        <div class="totals-row">
                            <span class="label">Balance Due</span>
                            <span class="value" style="color: #dc2626;">{{ \App\Services\PDFService::formatCurrency($invoice->total_amount - $invoice->paid_amount, $company->currency) }}</span>
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
                        <div class="signature-label">Prepared By</div>
                    </td>
                    <td>
                        <div class="signature-box"></div>
                        <div class="signature-label">Approved By</div>
                    </td>
                    <td>
                        <div class="signature-box"></div>
                        <div class="signature-label">Received By</div>
                    </td>
                </tr>
            </table>
        </div>

        @if($company->invoice_terms)
            <div class="terms">
                <strong>Terms & Conditions:</strong><br>
                {{ $company->invoice_terms }}
            </div>
        @endif

        <div class="terms">
            Thank you for your business!<br>
            Generated on {{ now()->format('M d, Y H:i') }}
        </div>
    </div>
@endsection
