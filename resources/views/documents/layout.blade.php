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
            @page { margin: 0; size: A4 portrait; } /* Removed margin to let our padding control it */
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
            font-size: 20px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #333;
            white-space: nowrap;
        }
        .meta-table { float: right; font-size: 14px; border-collapse: collapse; }
        .meta-table td { padding: 3px 0 3px 15px; }
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
        .totals-container { width: 50%; margin-left: auto; }
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
        .sig-block { width: 40%; text-align: center; position: relative; } /* Added relative for positioning context if needed locally */
        .sig-line { border-bottom: 1px solid #ccc; height: 40px; margin-bottom: 10px; }
        .sig-text { font-size: 14px; color: #555; }
        .sig-title { font-weight: bold; margin-bottom: 40px; }

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

        .section-header {
            background-color: #e5e7eb;
            padding: 5px 10px;
            font-weight: bold;
            font-size: 13px;
            text-transform: uppercase;
            margin-top: 10px;
            border-bottom: 1px solid #ccc;
        }

        .en-label { color: #888; font-weight: normal; font-size: 0.9em; margin-left: 3px; }
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
                <td style="width: 55%;">
                    @if($profile->logo_path)
                        <img src="{{ asset('storage/' . $profile->logo_path) }}" class="company-logo" alt="Logo">
                    @endif
                    <div class="company-name">{{ $profile->name }}</div>
                    <div class="company-address">{!! nl2br(e($profile->address)) !!}</div>
                    @if($profile->tax_id)<div class="tax-id">Tax ID: {{ $profile->tax_id }}</div>@endif
                    @if($profile->phone)<div class="tax-id">Tel: {{ $profile->phone }}</div>@endif
                </td>
                <td style="width: 45%;" class="doc-title">
                    <h1>{{ $title ?? ucfirst($type) }}</h1>
                    <table class="meta-table">
                        <tr>
                            <td class="meta-label">No:</td>
                            <td>{{ $doc_number ?? 'DRAFT' }}</td>
                        </tr>
                        <tr>
                            <td class="meta-label">Date <span class="en-label">/ วันที่</span>:</td>
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
            <div class="client-label">Bill To <span class="en-label">/ ลูกค้า</span></div>
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

        @php
            use Illuminate\Support\Str;

            $mode = $mode ?? 'combined';
            $isReceiptContext = Str::contains($type, ['Receipt', 'Tax Invoice']);

            $serviceTransactions = collect();
            $advanceTransactions = collect();
            $hasSpecificTransactions = $transactions->isNotEmpty();

            if ($hasSpecificTransactions) {
                $serviceTransactions = $transactions->filter(fn($t) => in_array($t->type, ['installment', 'down_payment', 'full_payment']));
                $advanceTransactions = $transactions->filter(fn($t) => $t->type === 'advance_payment');
            }

            $showService = ($mode === 'combined' || $mode === 'service_only');
            if ($hasSpecificTransactions && $serviceTransactions->isEmpty()) {
                $showService = false;
            }

            $showAdvance = ($mode === 'combined' || $mode === 'advance_only');

            $serviceTotal = 0;
            $advanceTotal = 0;

            if ($hasSpecificTransactions) {
                $serviceTotal = $serviceTransactions->sum(function($t) use ($isReceiptContext) {
                    return $isReceiptContext ? ($t->paid_amount ?? 0) : $t->amount;
                });
            } elseif ($showService) {
                $serviceTotal = $financial['total_amount'] ?? 0;
            }

            if ($advanceTransactions->isNotEmpty()) {
                 $advanceTotal = $advanceTransactions->sum(function($t) use ($isReceiptContext) {
                    return $isReceiptContext ? ($t->paid_amount ?? 0) : $t->amount;
                });
            } elseif ($showAdvance && !$hasSpecificTransactions && isset($advanceItems)) {
                 $advanceTotal = $advanceItems->sum('total');
            }

            // Helper to get Tier Object for an Item
            $getTierForItem = function($itemId, $tiers) {
                // Returns the entire tier object or null
                foreach ($tiers as $tier) {
                    if (in_array($itemId, $tier['item_ids'] ?? [])) return $tier;
                }
                return null;
            };

            // Helper to generate a unique key for grouping (Price + Note)
            // Actually, we should group by Tier Index or Unique Content
            // Since tiers don't have IDs, we use the tier object itself (or its properties)
            $getTierKey = function($itemId, $tiers) {
                foreach ($tiers as $idx => $tier) {
                    if (in_array($itemId, $tier['item_ids'] ?? [])) return $idx; // Use Index as Key
                }
                return -1; // Not in tier
            };

            $vatIncluded = $financial['vat_included'] ?? false;
            $vatRate = $financial['vat_rate'] ?? 7;
            $whtEnabled = $financial['wht_enabled'] ?? false;
            $whtRate = $financial['wht_rate'] ?? 3;

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
                    <th style="width: 60%;">Description <span class="en-label">/ รายการ</span></th>
                    <th style="width: 10%;" class="text-center">Qty <span class="en-label">/ จำนวน</span></th>
                    <th style="width: 10%;" class="text-right">Unit Price <span class="en-label">/ ราคา</span></th>
                    <th style="width: 15%;" class="text-right">Amount <span class="en-label">/ รวม</span></th>
                </tr>
            </thead>
            <tbody>
                <!-- SERVICE FEE SECTION -->
                @if($showService)
                    @if($hasSpecificTransactions || ($mode !== 'service_only'))
                        <tr>
                            <td colspan="5" class="section-header">Service Charges (ค่าบริการ)</td>
                        </tr>
                    @endif

                    @if($hasSpecificTransactions)
                        @php $lineIdx = 1; @endphp
                        @foreach($serviceTransactions as $t)
                            @php
                                $amount = $isReceiptContext ? ($t->paid_amount ?? 0) : $t->amount;
                                $items = $t->items;
                                $pricingMode = $financial['pricing_mode'] ?? 'per_head';
                                $pricingTiers = $financial['pricing_tiers'] ?? [];
                            @endphp

                            @if($pricingMode === 'per_head' && $items->isNotEmpty())
                                {{-- Group items by Tier Index to preserve Note grouping --}}
                                @php
                                    $tierGroups = $items->groupBy(function($item) use ($getTierKey, $pricingTiers) {
                                        return $getTierKey($item->id, $pricingTiers);
                                    });
                                @endphp

                                @foreach($tierGroups as $tierIdx => $groupedItems)
                                    @php
                                        $count = $groupedItems->count();
                                        // Get tier data
                                        $tier = ($tierIdx >= 0 && isset($pricingTiers[$tierIdx])) ? $pricingTiers[$tierIdx] : null;
                                        $price = $tier ? ($tier['price'] ?? 0) : 0; // Fallback price? Or should we calc from transaction?

                                        // Wait, the transaction amount is fixed. We are just "breaking it down".
                                        // If transaction has partial items from a tier, we show partial count.
                                        $subtotal = $price * $count;

                                        $desc = $t->notes ?: ucfirst(str_replace('_', ' ', $t->type));

                                        // Append Tier Note
                                        if ($tier && !empty($tier['note'])) {
                                            $desc .= " (" . $tier['note'] . ")";
                                        } elseif ($tierGroups->count() > 1) {
                                             $desc .= " (Group: " . number_format($price) . " THB)";
                                        }
                                    @endphp
                                    <tr>
                                        <td class="text-center">{{ $lineIdx++ }}</td>
                                        <td>
                                            <strong>{{ $desc }}</strong>
                                            <br><span class="text-muted" style="font-size: 11px;">{{ $count }} Employees @ {{ number_format($price, 2) }} ฿</span>
                                            @if($t->due_date)<br><span style="color: #999; font-size: 11px;">Due: {{ $t->due_date->format('d/m/Y') }}</span>@endif
                                        </td>
                                        <td class="text-center">{{ $count }}</td>
                                        <td class="text-right">{{ number_format($price, 2) }}</td>
                                        <td class="text-right">{{ number_format($subtotal, 2) }}</td>
                                    </tr>
                                @endforeach
                            @else
                                {{-- Fixed mode or no items --}}
                                <tr>
                                    <td class="text-center">{{ $lineIdx++ }}</td>
                                    <td>
                                        <strong>{{ $t->notes ?: ucfirst(str_replace('_', ' ', $t->type)) }}</strong>
                                        @if($t->due_date)<br><span style="color: #999; font-size: 11px;">Due: {{ $t->due_date->format('d/m/Y') }}</span>@endif
                                    </td>
                                    <td class="text-center">1</td>
                                    <td class="text-right">{{ number_format($amount, 2) }}</td>
                                    <td class="text-right">{{ number_format($amount, 2) }}</td>
                                </tr>
                            @endif
                        @endforeach
                    @else
                        <!-- Fallback: Full Project Summary (Quotation Style) -->
                        @php
                             $pricingMode = $financial['pricing_mode'] ?? 'per_head';
                             $pricingTiers = $financial['pricing_tiers'] ?? [];
                        @endphp

                        @if($pricingMode === 'per_head' && !empty($pricingTiers))
                            @foreach($pricingTiers as $idx => $tier)
                                @php $count = count($tier['item_ids'] ?? []); @endphp
                                @if($count > 0)
                                    <tr>
                                        <td class="text-center">{{ $idx + 1 }}</td>
                                        <td>
                                            <strong>{{ $production->project_name ?? 'Service Fee' }}</strong>
                                            @if(!empty($tier['note'])) <br><small class="text-muted">({{ $tier['note'] }})</small> @endif
                                        </td>
                                        <td class="text-center">{{ $count }}</td>
                                        <td class="text-right">{{ number_format($tier['price'], 2) }}</td>
                                        <td class="text-right">{{ number_format($tier['price'] * $count, 2) }}</td>
                                    </tr>
                                @endif
                            @endforeach
                        @else
                            <tr>
                                <td class="text-center">1</td>
                                <td>{{ $production->project_name ?? 'Service Fee' }}</td>
                                <td class="text-center">1</td>
                                <td class="text-right">{{ number_format($serviceTotal, 2) }}</td>
                                <td class="text-right">{{ number_format($serviceTotal, 2) }}</td>
                            </tr>
                        @endif
                    @endif
                @endif

                <!-- ADVANCE PAYMENT SECTION -->
                @if($showAdvance)
                    @if($advanceTransactions->isNotEmpty())
                         <tr>
                            <td colspan="5" class="section-header" style="background-color: #fff7ed; color: #ea580c;">Advance Payments (เงินสำรองจ่าย)</td>
                        </tr>
                        @foreach($advanceTransactions as $index => $t)
                            @php
                                $amount = $isReceiptContext ? ($t->paid_amount ?? 0) : $t->amount;
                                $description = $t->notes ?: ucfirst(str_replace('_', ' ', $t->type));
                            @endphp
                            <tr>
                                <td class="text-center">{{ $index + 1 }}</td>
                                <td>
                                    <strong>{{ $description }}</strong>
                                     @if($isReceiptContext && $t->amount > $amount)
                                        <br><span class="badge" style="font-size: 10px; background: #eee; padding: 2px 4px; border-radius: 4px;">Partial Payment (Full: {{ number_format($t->amount, 2) }})</span>
                                    @endif
                                </td>
                                <td class="text-center">1</td>
                                <td class="text-right">{{ number_format($amount, 2) }}</td>
                                <td class="text-right">{{ number_format($amount, 2) }}</td>
                            </tr>
                        @endforeach

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
                @if($showService)
                    <tr>
                        <td class="total-label"><strong>Service Base <span class="en-label">/ มูลค่าบริการ (ก่อน VAT)</span></strong></td>
                        <td class="total-value">{{ number_format($serviceBase, 2) }}</td>
                    </tr>
                    @if($vatRate > 0)
                    <tr>
                        <td class="total-label">VAT ({{ $vatRate }}%)</td>
                        <td class="total-value">{{ number_format($serviceVat, 2) }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td class="total-label" style="border-bottom: 1px solid #ddd;">Service Total <span class="en-label">/ รวมค่าบริการ (รวม VAT)</span></td>
                        <td class="total-value" style="border-bottom: 1px solid #ddd;">{{ number_format($totalServiceIncVat, 2) }}</td>
                    </tr>
                @endif

                @if($showAdvance && $advanceTotal > 0)
                    <tr>
                        <td class="total-label" style="color: #ea580c;"><strong>Total Advance Payments <span class="en-label">/ รวมเงินสำรองจ่าย</span></strong></td>
                        <td class="total-value" style="color: #ea580c;">{{ number_format($advanceTotal, 2) }}</td>
                    </tr>
                @endif

                @php
                    $grandTotal = ($showService ? $totalServiceIncVat : 0) + ($showAdvance ? $advanceTotal : 0);
                    $whtAmount = ($showService && $whtEnabled) ? ($serviceBase * ($whtRate/100)) : 0;
                    $netPayable = $grandTotal - $whtAmount;
                @endphp

                <tr class="grand-total-row">
                    <td>Grand Total <span class="en-label">/ รวมทั้งสิ้น</span></td>
                    <td class="total-value">{{ number_format($grandTotal, 2) }}</td>
                </tr>

                @if($showService && $whtEnabled)
                <tr style="color: #EF4444;">
                    <td class="total-label">Less WHT ({{ $whtRate }}% on Service)</td>
                    <td class="total-value">-{{ number_format($whtAmount, 2) }}</td>
                </tr>
                <tr style="font-weight: bold; border-top: 1px dashed #ccc;">
                    <td class="total-label" style="padding-top: 5px;">Net Payable <span class="en-label">/ ยอดสุทธิ</span></td>
                    <td class="total-value" style="padding-top: 5px;">{{ number_format($netPayable, 2) }}</td>
                </tr>
                @endif
            </table>
        </div>

        <!-- Thai Baht Text -->
        <div style="margin-top: 10px; font-style: italic; color: #666; font-size: 13px; text-align: right;">
            ( {{ \App\Helpers\ThaiBaht::convert($grandTotal) }} )
        </div>

        <!-- Signatures Section -->
        <div class="signatures">
            <div class="sig-block">
                <div class="sig-title">Received By <span class="en-label">/ ผู้รับเงิน</span></div>
                <div class="sig-line"></div>
                <div class="sig-text">Date <span class="en-label">/ วันที่</span>: ____/____/______</div>
            </div>

            <div class="sig-block">
                <div class="sig-title">Authorized Signature <span class="en-label">/ ผู้มีอำนาจลงนาม</span></div>
                <div class="sig-line"></div>
                <div class="sig-text">{{ $profile->name }}</div>
            </div>
        </div>

        <!-- Absolute Positioned Elements (Direct children of .page) -->
        @if($profile->use_signature && $profile->signature_path)
            @php
                $sigPos = is_array($profile->signature_pos) ? $profile->signature_pos : (is_string($profile->signature_pos) ? json_decode($profile->signature_pos, true) : null);
                $sigLeft = $sigPos['x'] ?? 50;
                $sigTop = $sigPos['y'] ?? 75;
                $sigWidth = $sigPos['w'] ?? 20;
            @endphp
                <img src="{{ asset('storage/' . $profile->signature_path) }}"
                    style="position: absolute; left: {{ $sigLeft }}%; top: {{ $sigTop }}%; width: {{ $sigWidth }}%; z-index: 10;">
        @endif

        @if($profile->use_stamp && $profile->stamp_path)
            @php
                $stampPos = is_array($profile->stamp_pos) ? $profile->stamp_pos : (is_string($profile->stamp_pos) ? json_decode($profile->stamp_pos, true) : null);
                $stampLeft = $stampPos['x'] ?? 55;
                $stampTop = $stampPos['y'] ?? 70;
                $stampWidth = $stampPos['w'] ?? 20;
            @endphp
                <img src="{{ asset('storage/' . $profile->stamp_path) }}"
                    style="position: absolute; left: {{ $stampLeft }}%; top: {{ $stampTop }}%; width: {{ $stampWidth }}%; z-index: 5; opacity: 0.8;">
        @endif

        <!-- Footer -->
        <div class="footer">
            Thank you for your business. <br>
            Please check the correctness of this document.
        </div>
    </div>
</body>
</html>
