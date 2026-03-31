@extends('pdf.layout')

@section('title', 'Purchase Order ' . $po->number)

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
                    <div class="document-title">Purchase Order</div>
                    <div class="document-meta">
                        <table>
                            <tr>
                                <td class="label">PO Number:</td>
                                <td class="value">{{ $po->number }}</td>
                            </tr>
                            <tr>
                                <td class="label">Order Date:</td>
                                <td class="value">{{ $po->order_date->format('M d, Y') }}</td>
                            </tr>
                            @if($po->expected_date)
                            <tr>
                                <td class="label">Expected:</td>
                                <td class="value">{{ $po->expected_date->format('M d, Y') }}</td>
                            </tr>
                            @endif
                            <tr>
                                <td class="label">Status:</td>
                                <td class="value">
                                    <span class="status-badge status-{{ $po->status }}">{{ ucfirst($po->status) }}</span>
                                </td>
                            </tr>
                        </table>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    {{-- Vendor / Ship To --}}
    <div class="parties">
        <table class="parties-table">
            <tr>
                <td>
                    <div class="party-box">
                        <div class="party-label">Vendor / Supplier</div>
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
                        <div class="party-label">Ship To</div>
                        <div class="party-name">{{ $company->name }}</div>
                        <div class="party-details">
                            @if($po->warehouse)
                                {{ $po->warehouse->name }}<br>
                                @if($po->warehouse->address){{ $po->warehouse->address }}@endif
                            @else
                                {{ $company->full_address }}
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
                        <div class="product-name">{{ $item->product->name ?? 'Item' }}</div>
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
                    @if($po->notes)
                        <div class="notes-box">
                            <div class="notes-label">Special Instructions</div>
                            <div class="notes-content">{{ $po->notes }}</div>
                        </div>
                    @endif
                </td>
                <td class="totals-cell">
                    <div class="totals-box">
                        <div class="totals-row">
                            <span class="label">Subtotal</span>
                            <span class="value">{{ \App\Services\PDFService::formatCurrency($po->subtotal, $company->currency) }}</span>
                        </div>
                        @if($po->tax_amount > 0)
                        <div class="totals-row">
                            <span class="label">Tax</span>
                            <span class="value">{{ \App\Services\PDFService::formatCurrency($po->tax_amount, $company->currency) }}</span>
                        </div>
                        @endif
                        <div class="totals-row grand-total">
                            <span class="label">Grand Total</span>
                            <span class="value">{{ \App\Services\PDFService::formatCurrency($po->total, $company->currency) }}</span>
                        </div>
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
                        <div class="signature-label">Authorized By</div>
                    </td>
                    <td>
                        <div class="signature-box"></div>
                        <div class="signature-label">Vendor Confirmation</div>
                    </td>
                </tr>
            </table>
        </div>

        @if($company->po_terms)
            <div class="terms">
                <strong>Terms & Conditions:</strong><br>
                {{ $company->po_terms }}
            </div>
        @endif

        <div class="terms">
            This Purchase Order is subject to our standard terms and conditions.<br>
            Generated on {{ now()->format('M d, Y H:i') }}
        </div>
    </div>
@endsection
