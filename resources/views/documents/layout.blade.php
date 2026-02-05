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

        .doc-title { text-align: right; vertical-align: top; }
        .doc-title h1 {
            margin: 0 0 10px 0;
            font-size: 20px; /* Reduced from 24px to prevent wrapping */
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #333;
            white-space: nowrap; /* Force single line */
        }
        .meta-table { float: right; font-size: 14px; border-collapse: collapse; }
        .meta-table td { padding: 3px 0 3px 15px; } /* Increased spacing */
        .meta-label { font-weight: bold; text-align: right; color: #555; }

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
        .totals-container { width: 45%; margin-left: auto; }
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

        /* New: Section Separator */
        .section-header {
            background-color: #e5e7eb;
            padding: 5px 10px;
            font-weight: bold;
            font-size: 13px;
            text-transform: uppercase;
            margin-top: 10px;
            border-bottom: 1px solid #ccc;
        }
    </style>
</head>
<body>
    <div class="no-print action-bar">
        <span>Document Preview ({{ ucfirst($mode ?? 'standard') }})</span>
        <div>
            <button class="btn" onclick="window.print()">Print / Save PDF</button>
            <button class="btn" onclick="window.close()" style="margin-left: 10px;">Close</button>
        </div>
    </div>

    <div class="page">
        <!-- Header -->
        <table class="header-table">
            <tr>
                <!-- Increased width for company info to avoid cramping, but kept balance -->
                <td style="width: 55%;">
                    @if($profile->logo_path)
                        <img src="{{ asset('storage/' . $profile->logo_path) }}" class="company-logo" alt="Logo">
                    @endif
                    <div class="company-name">{{ $profile->name }}</div>
                    <div class="company-address">{!! nl2br(e($profile->address)) !!}</div>
                    @if($profile->tax_id)<div class="tax-id">Tax ID: {{ $profile->tax_id }}</div>@endif
                    @if($profile->phone)<div class="tax-id">Tel: {{ $profile->phone }}</div>@endif
                </td>
                <!-- Widen the title column to support longer Thai/English titles on one line -->
                <td style="width: 45%;" class="doc-title">
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

        <!-- Logic Setup -->
        @php
            use Illuminate\Support\Str;

            $mode = $mode ?? 'combined'; // combined, service_only, advance_only
            // Receipt Context: If true, show 'paid_amount' if available. If false (Invoice), show 'amount'.
            $isReceiptContext = Str::contains($type, ['Receipt', 'Tax Invoice']);

            // 1. Classify Transactions (if filtering/selection is used)
            $serviceTransactions = collect();
            $advanceTransactions = collect();
            $hasSpecificTransactions = $transactions->isNotEmpty();

            if ($hasSpecificTransactions) {
                // Split logic
                $serviceTransactions = $transactions->filter(fn($t) => in_array($t->type, ['installment', 'down_payment', 'full_payment']));
                $advanceTransactions = $transactions->filter(fn($t) => $t->type === 'advance_payment');
            }

            // 2. Determine Display Logic
            // Show Service Section IF:
            // - Mode allows it ('combined' OR 'service_only')
            // - AND (We are in "Project View" [no specific transactions] OR We have specific Service transactions to show)
            $showService = ($mode === 'combined' || $mode === 'service_only');
            if ($hasSpecificTransactions && $serviceTransactions->isEmpty()) {
                $showService = false; // Hide service section if we only selected advance payments
            }

            // Show Advance Section IF:
            // - Mode allows it ('combined' OR 'advance_only')
            // - AND (We have specific Advance transactions OR We are in "Project View" and have planned items)
            $showAdvance = ($mode === 'combined' || $mode === 'advance_only');

            // 3. Calculate Totals
            $serviceTotal = 0;
            $advanceTotal = 0;

            // Service Fee Calculation
            if ($hasSpecificTransactions) {
                // Sum based on context
                $serviceTotal = $serviceTransactions->sum(function($t) use ($isReceiptContext) {
                    return $isReceiptContext ? ($t->paid_amount ?? 0) : $t->amount;
                });
            } elseif ($showService) {
                // If no specific transactions, assume full project value for Quotation/Project context
                // For Receipts in Project Context (Rare?), we might still use total_amount
                $serviceTotal = $financial['total_amount'] ?? 0;
            }

            // Advance Calculation
            // Scenario A: Specific Advance Transactions selected (e.g. Receipt for Deposit)
            // Scenario B: Project View (Quotation) -> Sum of planned items
            if ($advanceTransactions->isNotEmpty()) {
                 $advanceTotal = $advanceTransactions->sum(function($t) use ($isReceiptContext) {
                    return $isReceiptContext ? ($t->paid_amount ?? 0) : $t->amount;
                });
            } elseif ($showAdvance && !$hasSpecificTransactions && isset($advanceItems)) {
                 // Only show planned items sum if NO specific transactions are selected
                 $advanceTotal = $advanceItems->sum('total');
            }

            // 4. VAT & Tax Logic
            $vatIncluded = $financial['vat_included'] ?? false;
            $vatRate = $financial['vat_rate'] ?? 7;
            $whtEnabled = $financial['wht_enabled'] ?? false;
            $whtRate = $financial['wht_rate'] ?? 3;

            // Deconstruct Service Fee
            if ($vatIncluded) {
                $totalServiceIncVat = $serviceTotal;
                $serviceBase = ($vatRate > 0) ? $totalServiceIncVat / (1 + ($vatRate/100)) : $totalServiceIncVat;
                $serviceVat = $totalServiceIncVat - $serviceBase;
            } else {
                $serviceBase = $serviceTotal;
                $serviceVat = $serviceBase * ($vatRate/100);
                $totalServiceIncVat = $serviceBase + $serviceVat;
            }
        @endphp

        <!-- Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 5%;" class="text-center">#</th>
                    <th style="width: 60%;">Description</th>
                    <th style="width: 10%;" class="text-center">Qty</th>
                    <th style="width: 10%;" class="text-right">Unit Price</th>
                    <th style="width: 15%;" class="text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                <!-- SERVICE FEE SECTION -->
                @if($showService)
                    @if($hasSpecificTransactions || ($mode !== 'service_only'))
                       <!-- Show Header if mixed content or specific transactions -->
                        <tr>
                            <td colspan="5" class="section-header">Service Charges (ค่าบริการ)</td>
                        </tr>
                    @endif

                    @if($hasSpecificTransactions)
                         @foreach($serviceTransactions as $index => $t)
                            @php
                                $amount = $isReceiptContext ? ($t->paid_amount ?? 0) : $t->amount;
                                $itemCount = $t->items->count();
                                $pricingMode = $financial['pricing_mode'] ?? 'per_head';

                                $qty = 1;
                                $unitPrice = $amount;
                                $description = ucfirst(str_replace('_', ' ', $t->type));

                                if ($itemCount > 0) {
                                    if ($pricingMode === 'per_head') {
                                        $qty = $itemCount;
                                        $unitPrice = ($qty > 0) ? ($amount / $qty) : 0;
                                    } else {
                                        $description .= " (" . $itemCount . " Employees)";
                                    }
                                }
                            @endphp
                            <tr>
                                <td class="text-center">{{ $index + 1 }}</td>
                                <td>
                                    <strong>{{ $description }}</strong>
                                    @if($t->notes)<br><span style="color: #666; font-size: 12px;">{{ $t->notes }}</span>@endif
                                    @if($t->due_date)<br><span style="color: #999; font-size: 11px;">Due: {{ $t->due_date->format('d/m/Y') }}</span>@endif
                                    @if($isReceiptContext && $t->amount > $amount)
                                        <br><span class="badge" style="font-size: 10px; background: #eee; padding: 2px 4px; border-radius: 4px;">Partial Payment (Full: {{ number_format($t->amount, 2) }})</span>
                                    @endif
                                </td>
                                <td class="text-center">{{ $qty }}</td>
                                <td class="text-right">{{ number_format($unitPrice, 2) }}</td>
                                <td class="text-right">{{ number_format($amount, 2) }}</td>
                            </tr>
                        @endforeach
                    @else
                        <!-- Fallback: Full Project Summary (Quotation Style) -->
                        @php
                             $pricingMode = $financial['pricing_mode'] ?? 'per_head';
                             $empCount = $production->items->count();

                             $qty = 1;
                             $unitPrice = $serviceTotal;
                             $description = $production->project_name ?? 'Service Fee for Recruitment';

                             if ($pricingMode === 'per_head') {
                                 $qty = $empCount;
                                 $unitPrice = ($qty > 0) ? ($serviceTotal / $qty) : 0;
                             } else {
                                 $description .= " ({$empCount} Employees)";
                             }
                        @endphp
                        <tr>
                            <td class="text-center">1</td>
                            <td>{{ $description }}</td>
                            <td class="text-center">{{ $qty }}</td>
                            <td class="text-right">{{ number_format($unitPrice, 2) }}</td>
                            <td class="text-right">{{ number_format($serviceTotal, 2) }}</td>
                        </tr>
                    @endif
                @endif

                <!-- ADVANCE PAYMENT SECTION -->
                @if($showAdvance)
                    <!-- CASE 1: Specific Advance Transactions (Actual Receipts) -->
                    @if($advanceTransactions->isNotEmpty())
                         <tr>
                            <td colspan="5" class="section-header" style="background-color: #fff7ed; color: #ea580c;">Advance Payments (เงินสำรองจ่าย)</td>
                        </tr>
                        @foreach($advanceTransactions as $index => $t)
                            @php
                                $amount = $isReceiptContext ? ($t->paid_amount ?? 0) : $t->amount;
                            @endphp
                            <tr>
                                <td class="text-center">{{ $index + 1 }}</td>
                                <td>
                                    <strong>{{ ucfirst(str_replace('_', ' ', $t->type)) }}</strong>
                                    @if($t->notes)<br><span style="color: #666; font-size: 12px;">{{ $t->notes }}</span>@endif
                                     @if($isReceiptContext && $t->amount > $amount)
                                        <br><span class="badge" style="font-size: 10px; background: #eee; padding: 2px 4px; border-radius: 4px;">Partial Payment (Full: {{ number_format($t->amount, 2) }})</span>
                                    @endif
                                </td>
                                <td class="text-center">1</td>
                                <td class="text-right">{{ number_format($amount, 2) }}</td>
                                <td class="text-right">{{ number_format($amount, 2) }}</td>
                            </tr>
                        @endforeach

                    <!-- CASE 2: Planned Items List (Quotation / Project Overview) -->
                    <!-- Only show if NO specific transactions were selected -->
                    @elseif(!$hasSpecificTransactions && isset($advanceItems) && $advanceItems->isNotEmpty())
                        <tr>
                            <td colspan="5" class="section-header" style="background-color: #fff7ed; color: #ea580c;">Advance Payments (เงินสำรองจ่าย) <span style="font-size: 10px; font-weight: normal; color: #666;">(No VAT)</span></td>
                        </tr>
                        @foreach($advanceItems as $index => $item)
                            <tr>
                                <td class="text-center">{{ $index + 1 }}</td>
                                <td>{{ $item->description }}</td>
                                <td class="text-center">{{ $item->quantity }}</td>
                                <td class="text-right">{{ number_format($item->unit_price, 2) }}</td>
                                <td class="text-right">{{ number_format($item->total, 2) }}</td>
                            </tr>
                        @endforeach
                    @endif
                @endif
            </tbody>
        </table>

        <!-- Calculations -->
        <div class="totals-container">
            <table class="totals-table">
                <!-- Service Fee Breakdown -->
                @if($showService)
                    <tr>
                        <td class="total-label"><strong>Service Base (Excl. VAT)</strong></td>
                        <td class="total-value">{{ number_format($serviceBase, 2) }}</td>
                    </tr>
                    @if($vatRate > 0)
                    <tr>
                        <td class="total-label">VAT ({{ $vatRate }}%)</td>
                        <td class="total-value">{{ number_format($serviceVat, 2) }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td class="total-label" style="border-bottom: 1px solid #ddd;">Service Total (Inc. VAT)</td>
                        <td class="total-value" style="border-bottom: 1px solid #ddd;">{{ number_format($totalServiceIncVat, 2) }}</td>
                    </tr>
                @endif

                <!-- Advance Breakdown -->
                @if($showAdvance && $advanceTotal > 0)
                    <tr>
                        <td class="total-label" style="color: #ea580c;"><strong>Total Advance Payments</strong></td>
                        <td class="total-value" style="color: #ea580c;">{{ number_format($advanceTotal, 2) }}</td>
                    </tr>
                @endif

                <!-- Grand Total -->
                @php
                    $grandTotal = ($showService ? $totalServiceIncVat : 0) + ($showAdvance ? $advanceTotal : 0);
                    $whtAmount = ($showService && $whtEnabled) ? ($serviceBase * ($whtRate/100)) : 0;
                    $netPayable = $grandTotal - $whtAmount;
                @endphp

                <tr class="grand-total-row">
                    <td>Grand Total</td>
                    <td class="total-value">{{ number_format($grandTotal, 2) }}</td>
                </tr>

                <!-- WHT -->
                @if($showService && $whtEnabled)
                <tr style="color: #EF4444;">
                    <td class="total-label">Less WHT ({{ $whtRate }}% on Service)</td>
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
            ( {{ \App\Helpers\ThaiBaht::convert($grandTotal) }} )
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
