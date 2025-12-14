<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>{{ $title ?? ucfirst($type) }}</title>
    <!-- Google Fonts for Sarabun -->
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Sarabun', sans-serif; margin: 0; padding: 20px; color: #333; background: #f3f4f6; }

        /* A4 Page Styling */
        .page {
            max-width: 210mm;
            margin: 0 auto;
            background: white;
            padding: 40px;
            border: 1px solid #ddd;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            position: relative;
            min-height: 297mm;
            box-sizing: border-box;
        }

        /* Print Specifics */
        @media print {
            body { background: white; padding: 0; }
            .page { border: none; box-shadow: none; padding: 0; margin: 0; width: 100%; height: auto; min-height: auto; }
            @page { margin: 15mm; size: A4 portrait; }
            .no-print { display: none !important; }
        }

        /* Header Table */
        table.header-table { width: 100%; margin-bottom: 20px; border-collapse: collapse; }
        table.header-table td { vertical-align: top; }

        .company-logo { height: 60px; margin-bottom: 10px; max-width: 200px; object-fit: contain; }
        .company-name { font-size: 20px; font-weight: bold; color: #F97316; margin-bottom: 5px; }
        .company-address { font-size: 14px; color: #555; line-height: 1.4; }
        .tax-id { font-size: 14px; margin-top: 5px; }

        .doc-title { text-align: right; }
        .doc-title h1 { margin: 0 0 10px 0; font-size: 24px; text-transform: uppercase; letter-spacing: 1px; color: #333; }
        .meta-table { float: right; font-size: 14px; border-collapse: collapse; }
        .meta-table td { padding: 2px 0 2px 10px; }
        .meta-label { font-weight: bold; text-align: right; }

        /* Client Info */
        .client-box {
            margin-bottom: 30px;
            border: 1px solid #eee;
            padding: 15px;
            border-radius: 5px;
            background: #fdfdfd;
            font-size: 14px;
        }
        .client-label { font-weight: bold; color: #888; font-size: 12px; text-transform: uppercase; margin-bottom: 5px; }
        .client-name { font-weight: bold; font-size: 16px; margin-bottom: 3px; }

        /* Items Table */
        table.items-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        table.items-table th {
            background: #f3f4f6;
            padding: 10px;
            text-align: left;
            font-weight: bold;
            border-top: 1px solid #ddd;
            border-bottom: 1px solid #ddd;
            font-size: 14px;
        }
        table.items-table td {
            padding: 10px;
            border-bottom: 1px solid #eee;
            font-size: 14px;
            vertical-align: top;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }

        /* Totals Section */
        .totals-container { width: 40%; margin-left: auto; }
        table.totals-table { width: 100%; border-collapse: collapse; font-size: 14px; }
        table.totals-table td { padding: 5px 0; }
        .total-label { text-align: left; color: #555; }
        .total-value { text-align: right; }

        .grand-total-row td {
            border-top: 2px solid #333;
            border-bottom: double 4px #333;
            padding: 10px 0;
            font-weight: bold;
            font-size: 18px;
            color: #000;
        }

        /* Signatures */
        .signatures { margin-top: 60px; display: flex; justify-content: space-between; page-break-inside: avoid; }
        .sig-block { width: 40%; text-align: center; }
        .sig-line { border-bottom: 1px solid #ccc; height: 40px; margin-bottom: 10px; }
        .sig-text { font-size: 14px; color: #555; }

        /* Footer */
        .footer {
            position: absolute;
            bottom: 40px;
            left: 40px;
            right: 40px;
            border-top: 1px solid #eee;
            padding-top: 15px;
            font-size: 12px;
            color: #888;
            text-align: center;
        }

        /* Action Bar */
        .action-bar {
            text-align: center;
            margin-bottom: 20px;
            background: #333;
            padding: 10px;
            border-radius: 5px;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .btn { padding: 8px 15px; background: white; color: #333; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; text-decoration: none; font-size: 14px;}
        .btn:hover { background: #eee; }
    </style>
</head>
<body>
    <div class="no-print action-bar">
        <span>Document Preview</span>
        <div>
            <button class="btn" onclick="window.print()">Print / Save PDF</button>
            <button class="btn" onclick="window.close()" style="margin-left: 10px;">Close</button>
        </div>
    </div>

    <div class="page">
        <!-- Header -->
        <table class="header-table">
            <tr>
                <td style="width: 60%;">
                    @if($profile->logo_path)
                        <img src="{{ asset('storage/' . $profile->logo_path) }}" class="company-logo" alt="Logo">
                    @endif
                    <div class="company-name">{{ $profile->name }}</div>
                    <div class="company-address">{!! nl2br(e($profile->address)) !!}</div>
                    @if($profile->tax_id)<div class="tax-id">Tax ID: {{ $profile->tax_id }}</div>@endif
                    @if($profile->phone)<div class="tax-id">Tel: {{ $profile->phone }}</div>@endif
                </td>
                <td style="width: 40%;" class="doc-title">
                    <h1>{{ $title ?? ucfirst($type) }}</h1>
                    <table class="meta-table">
                        <tr>
                            <td class="meta-label">No:</td>
                            <td>{{ $doc_number ?? 'DRAFT' }}</td>
                        </tr>
                        <tr>
                            <td class="meta-label">Date:</td>
                            <td>{{ date('d/m/Y') }}</td>
                        </tr>
                        <tr>
                            <td class="meta-label">Ref:</td>
                            <td>#{{ $production->id }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <!-- Client Info -->
        <div class="client-box">
            <div class="client-label">Bill To</div>
            @if(isset($financial['customer_override']) && $financial['customer_override']['name'])
                 <div class="client-name">{{ $financial['customer_override']['name'] }}</div>
                 <div>{!! nl2br(e($financial['customer_override']['address'] ?? '-')) !!}</div>
                 <div>Tax ID: {{ $financial['customer_override']['tax_id'] ?? '-' }}</div>
                 <div>Tel: {{ $financial['customer_override']['phone'] ?? '-' }}</div>
            @else
                 <div class="client-name">{{ $production->employer->employerNameTh ?? $production->employer->employerNameEn ?? 'N/A' }}</div>
                 <div>{{ $production->employer->address ?? '' }}</div>
                 <div>Tel: {{ $production->employer->employerPhone ?? '-' }}</div>
            @endif
        </div>

        <!-- Items -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 5%;" class="text-center">#</th>
                    <th style="width: 60%;">Description</th>
                    <th style="width: 15%;" class="text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                @php $runningTotal = 0; @endphp
                @forelse($transactions as $index => $t)
                    @php $runningTotal += $t->amount; @endphp
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>
                            <strong>{{ ucfirst(str_replace('_', ' ', $t->type)) }}</strong>
                            @if($t->notes)<br><span style="color: #666; font-size: 12px;">{{ $t->notes }}</span>@endif
                            @if($t->due_date)<br><span style="color: #999; font-size: 11px;">Due: {{ $t->due_date->format('d/m/Y') }}</span>@endif
                        </td>
                        <td class="text-right">{{ number_format($t->amount, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td class="text-center">1</td>
                        <td>{{ $production->project_name ?? 'Service Fee' }}</td>
                        <td class="text-right">{{ number_format($financial['total_amount'] ?? 0, 2) }}</td>
                    </tr>
                    @php $runningTotal = $financial['total_amount'] ?? 0; @endphp
                @endforelse
            </tbody>
        </table>

        <!-- Calculations -->
        <div class="totals-container">
            @php
                // Logic:
                // If displaying filtered transactions, we sum them up.
                // We then re-calculate VAT/WHT based on the Group's RATIO settings.
                // Or simply rely on the values if it matches the group total?
                // Standard approach for partial billing:
                // Base = Sum of Items
                // Discount = (Group Discount / Group Total) * Base ?? 0 (Simplified: No discount on partials usually)

                $baseAmount = $runningTotal; // This is GROSS usually
                $discount = 0; // Hard to attribute partial discount, assume 0 for installments unless specific.

                // If this is full generation (all transactions), apply group discount?
                // Let's assume these transactions are NET of discount for simplicity or raw.
                // Actually transactions usually store the "Amount to be paid".

                // Let's recalculate tax logic based on settings:
                $vatIncluded = $financial['vat_included'] ?? false;
                $vatRate = $financial['vat_rate'] ?? 7;
                $whtEnabled = $financial['wht_enabled'] ?? false;
                $whtRate = $financial['wht_rate'] ?? 3;

                if ($vatIncluded) {
                    $totalIncVat = $baseAmount;
                    $subtotal = $totalIncVat / (1 + ($vatRate/100));
                    $vatAmount = $totalIncVat - $subtotal;
                } else {
                    $subtotal = $baseAmount;
                    $vatAmount = $subtotal * ($vatRate/100);
                    $totalIncVat = $subtotal + $vatAmount;
                }

                $whtAmount = $whtEnabled ? ($subtotal * ($whtRate/100)) : 0;
                $netPayable = $totalIncVat - $whtAmount;
            @endphp

            <table class="totals-table">
                <tr>
                    <td class="total-label">Subtotal @if($vatIncluded)(Excl. VAT)@endif</td>
                    <td class="total-value">{{ number_format($subtotal, 2) }}</td>
                </tr>
                @if($vatRate > 0)
                <tr>
                    <td class="total-label">VAT ({{ $vatRate }}%)</td>
                    <td class="total-value">{{ number_format($vatAmount, 2) }}</td>
                </tr>
                @endif
                <tr class="grand-total-row">
                    <td>Total</td>
                    <td class="total-value">{{ number_format($totalIncVat, 2) }}</td>
                </tr>
                @if($whtEnabled)
                <tr style="color: #EF4444;">
                    <td class="total-label">Less WHT ({{ $whtRate }}%)</td>
                    <td class="total-value">-{{ number_format($whtAmount, 2) }}</td>
                </tr>
                <tr style="font-weight: bold; border-top: 1px dashed #ccc;">
                    <td class="total-label" style="padding-top: 5px;">Net Payable</td>
                    <td class="total-value" style="padding-top: 5px;">{{ number_format($netPayable, 2) }}</td>
                </tr>
                @endif
            </table>
        </div>

        <!-- Thai Baht Text -->
        <div style="margin-top: 10px; font-style: italic; color: #666; font-size: 13px; text-align: right;">
            ( {{ \App\Helpers\ThaiBaht::convert($totalIncVat) }} )
        </div>

        <!-- Signatures -->
        <div class="signatures">
            <div class="sig-block">
                <div class="sig-text" style="margin-bottom: 40px;">Received By</div>
                <div class="sig-line"></div>
                <div class="sig-text">Date: ____/____/______</div>
            </div>
            <div class="sig-block">
                <div class="sig-text" style="margin-bottom: 40px;">Authorized Signature</div>
                <div class="sig-line"></div>
                <div class="sig-text">{{ $profile->name }}</div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            Thank you for your business.<br>
            Please check the correctness of this document.
        </div>
    </div>
</body>
</html>
