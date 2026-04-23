<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>@yield('title', 'Document')</title>
    <style>
        /* Reset & Base */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #333;
            background: #fff;
        }

        /* Page Layout */
        .page {
            width: 100%;
            padding: 20px 30px;
        }

        /* Header */
        .header {
            width: 100%;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table td {
            vertical-align: top;
        }

        .company-info {
            width: 60%;
        }

        .company-logo {
            max-width: 120px;
            max-height: 60px;
            margin-bottom: 8px;
        }

        .company-name {
            font-size: 18px;
            font-weight: bold;
            color: #1a1a1a;
            margin-bottom: 4px;
        }

        .company-details {
            font-size: 10px;
            color: #666;
            line-height: 1.5;
        }

        .document-info {
            width: 40%;
            text-align: right;
        }

        .document-title {
            font-size: 22px;
            font-weight: bold;
            color: #2563eb;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .document-meta {
            font-size: 11px;
        }

        .document-meta table {
            margin-left: auto;
            border-collapse: collapse;
        }

        .document-meta td {
            padding: 3px 0;
        }

        .document-meta .label {
            color: #666;
            text-align: right;
            padding-right: 10px;
        }

        .document-meta .value {
            font-weight: bold;
            text-align: left;
        }

        /* Parties Section */
        .parties {
            width: 100%;
            margin-bottom: 20px;
        }

        .parties-table {
            width: 100%;
            border-collapse: collapse;
        }

        .parties-table td {
            width: 50%;
            vertical-align: top;
            padding: 10px;
        }

        .party-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 12px;
        }

        .party-label {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748b;
            margin-bottom: 5px;
        }

        .party-name {
            font-size: 13px;
            font-weight: bold;
            color: #1a1a1a;
            margin-bottom: 5px;
        }

        .party-details {
            font-size: 10px;
            color: #475569;
            line-height: 1.6;
        }

        /* Items Table */
        .items-section {
            margin-bottom: 20px;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
        }

        .items-table th {
            background: #1e293b;
            color: #fff;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 10px 8px;
            text-align: left;
        }

        .items-table th.number {
            width: 6%;
            text-align: center;
        }

        .items-table th.description {
            width: 40%;
        }

        .items-table th.qty {
            width: 12%;
            text-align: center;
        }

        .items-table th.price {
            width: 18%;
            text-align: right;
        }

        .items-table th.total {
            width: 18%;
            text-align: right;
        }

        .items-table td {
            padding: 10px 8px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 10px;
        }

        .items-table td.number {
            text-align: center;
            color: #64748b;
        }

        .items-table td.description .product-name {
            font-weight: 600;
            color: #1a1a1a;
        }

        .items-table td.description .product-sku {
            font-size: 9px;
            color: #94a3b8;
        }

        .items-table td.qty {
            text-align: center;
        }

        .items-table td.price,
        .items-table td.total {
            text-align: right;
            font-family: 'DejaVu Sans Mono', monospace;
        }

        .items-table tr:nth-child(even) {
            background: #f8fafc;
        }

        /* Summary Section */
        .summary-section {
            width: 100%;
            margin-bottom: 20px;
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
        }

        .summary-table td {
            vertical-align: top;
        }

        .summary-table .notes-cell {
            width: 55%;
            padding-right: 20px;
        }

        .summary-table .totals-cell {
            width: 45%;
        }

        .notes-box {
            background: #fffbeb;
            border: 1px solid #fcd34d;
            border-radius: 4px;
            padding: 10px;
        }

        .notes-label {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #92400e;
            margin-bottom: 5px;
        }

        .notes-content {
            font-size: 10px;
            color: #78350f;
            line-height: 1.5;
        }

        .totals-box {
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            overflow: hidden;
        }

        .totals-row {
            display: table;
            width: 100%;
            padding: 8px 12px;
            border-bottom: 1px solid #e2e8f0;
        }

        .totals-row:last-child {
            border-bottom: none;
        }

        .totals-row .label {
            display: table-cell;
            width: 50%;
            font-size: 10px;
            color: #64748b;
        }

        .totals-row .value {
            display: table-cell;
            width: 50%;
            text-align: right;
            font-size: 11px;
            font-weight: 600;
            font-family: 'DejaVu Sans Mono', monospace;
        }

        .totals-row.grand-total {
            background: #1e293b;
        }

        .totals-row.grand-total .label {
            color: #cbd5e1;
            font-weight: bold;
        }

        .totals-row.grand-total .value {
            color: #fff;
            font-size: 14px;
        }

        /* Footer */
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #e2e8f0;
        }

        .signature-section {
            width: 100%;
            margin-top: 30px;
        }

        .signature-table {
            width: 100%;
            border-collapse: collapse;
        }

        .signature-table td {
            width: 33.33%;
            text-align: center;
            padding: 10px;
        }

        .signature-box {
            padding-top: 40px;
            border-bottom: 1px solid #333;
            margin-bottom: 8px;
        }

        .signature-label {
            font-size: 10px;
            color: #64748b;
        }

        .terms {
            font-size: 9px;
            color: #94a3b8;
            line-height: 1.5;
            margin-top: 20px;
            text-align: center;
        }

        /* Watermark */
        .watermark {
            position: fixed;
            top: 40%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 100px;
            font-weight: bold;
            color: rgba(0, 0, 0, 0.05);
            text-transform: uppercase;
            letter-spacing: 10px;
            z-index: -1;
            pointer-events: none;
        }

        /* Status Badge */
        .status-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-draft { background: #f1f5f9; color: #475569; }
        .status-sent { background: #dbeafe; color: #1e40af; }
        .status-posted { background: #dbeafe; color: #1e40af; }
        .status-confirmed { background: #dbeafe; color: #1e40af; }
        .status-partial { background: #fef3c7; color: #92400e; }
        .status-paid { background: #dcfce7; color: #166534; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }

        /* Page break */
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    @if(isset($watermark) && $watermark)
        <div class="watermark">{{ $watermark }}</div>
    @endif

    <div class="page">
        @yield('content')
    </div>
</body>
</html>
