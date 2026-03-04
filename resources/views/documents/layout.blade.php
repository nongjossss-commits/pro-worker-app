<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>{{ $page_title ?? $title ?? ucfirst($type) }}</title>
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
            display: flex;
            flex-direction: column;
        }

        /* Print Specifics */
        @media print {
            body { background: white; padding: 0; }
            .page {
                border: none;
                box-shadow: none;
                padding: 0;
                margin: 0;
                width: 100%;
                height: auto;
                min-height: auto;
                display: block;
            }
            @page { margin: 10mm; size: A4 portrait; }
            .no-print { display: none !important; }
            table.items-table, .totals-container, .signatures-container { page-break-inside: auto; }
            tr { page-break-inside: avoid; page-break-after: auto; }
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

        /* Signatures Container */
        .signatures-container {
            margin-top: auto; /* Pushes to bottom in flex container on screen */
            padding-top: 50px;
            page-break-inside: avoid;
        }

        /* Signatures */
        .signatures { display: flex; justify-content: space-between; page-break-inside: avoid; margin-bottom: 40px; }
        .sig-block { width: 40%; text-align: center; position: relative; }
        .sig-line { border-bottom: 1px solid #ccc; height: 40px; margin-bottom: 10px; width: 80%; margin-left: 10%; }
        .sig-text { font-size: 14px; color: #555; }
        .sig-title { font-weight: bold; margin-bottom: 40px; }

        /* Footer */
        .footer {
            border-top: 1px solid #eee;
            padding-top: 15px;
            font-size: 12px;
            color: #888;
            text-align: center;
            page-break-inside: avoid;
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
        <!-- Absolute Placed Assets (Biller Profile) -->
        @if(isset($billerProfile))
            @if($billerProfile->signature_path && $billerProfile->signature_position)
                <img src="{{ asset('storage/' . $billerProfile->signature_path) }}"
                     style="position: absolute;
                            left: {{ $billerProfile->signature_position['x'] ?? 0 }}px;
                            top: {{ $billerProfile->signature_position['y'] ?? 0 }}px;
                            width: {{ $billerProfile->signature_position['width'] ?? 150 }}px;
                            height: {{ $billerProfile->signature_position['height'] ?? 75 }}px;
                            transform: rotate({{ $billerProfile->signature_position['rotate'] ?? 0 }}deg);
                            z-index: 10;" alt="Signature">
            @endif
            @if($billerProfile->stamp_path && $billerProfile->stamp_position)
                <img src="{{ asset('storage/' . $billerProfile->stamp_path) }}"
                     style="position: absolute;
                            left: {{ $billerProfile->stamp_position['x'] ?? 0 }}px;
                            top: {{ $billerProfile->stamp_position['y'] ?? 0 }}px;
                            width: {{ $billerProfile->stamp_position['width'] ?? 100 }}px;
                            height: {{ $billerProfile->stamp_position['height'] ?? 100 }}px;
                            transform: rotate({{ $billerProfile->stamp_position['rotate'] ?? 0 }}deg);
                            z-index: 5; opacity: 0.8;" alt="Stamp">
            @endif
        @endif

        <!-- Header -->
        <table class="header-table">
            <tr>
                <td style="width: 55%;">
                    @if(isset($billerProfile) && $billerProfile->logo_path)
                        <img src="{{ asset('storage/' . $billerProfile->logo_path) }}" class="company-logo" alt="Logo">
                    @elseif(!isset($billerProfile) && $profile->logo_path)
                        <img src="{{ asset('storage/' . $profile->logo_path) }}" class="company-logo" alt="Logo">
                    @endif
                    <div class="company-name">{{ isset($billerProfile) ? $billerProfile->name : $profile->name }}</div>
                    <div class="company-address">{!! nl2br(e(isset($billerProfile) ? $billerProfile->address : $profile->address)) !!}</div>
                    @if(isset($billerProfile) ? $billerProfile->tax_id : $profile->tax_id)<div class="tax-id">Tax ID: {{ isset($billerProfile) ? $billerProfile->tax_id : $profile->tax_id }}</div>@endif
                    @if(isset($billerProfile) ? $billerProfile->phone : $profile->phone)<div class="tax-id">Tel: {{ isset($billerProfile) ? $billerProfile->phone : $profile->phone }}</div>@endif
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
            @if(isset($customerProfile))
                 <div class="client-name">{{ $customerProfile->name }}</div>
                 <div>{!! nl2br(e($customerProfile->address)) !!}</div>
                 <div>Tax ID: {{ $customerProfile->tax_id ?? '-' }}</div>
                 <div>Tel: {{ $customerProfile->phone ?? '-' }}</div>
            @elseif(isset($financial['customer_override']) && $financial['customer_override']['name'])
                 <div class="client-name">{{ $financial['customer_override']['name'] }}</div>
                 <div>{!! nl2br(e($financial['customer_override']['address'] ?? '-')) !!}</div>
                 <div>Tax ID: {{ $financial['customer_override']['tax_id'] ?? '-' }}</div>
                 <div>Tel: {{ $financial['customer_override']['phone'] ?? '-' }}</div>
            @else
                 @php
                     $emp = $production->employer;
                     $empName = array_filter([$emp->employerNameTh, $emp->employerNameEn]);
                     $empNameStr = !empty($empName) ? implode(' / ', $empName) : 'N/A';

                     $empAddress = '';
                     if ($emp->addresses && $emp->addresses->isNotEmpty()) {
                         $addr = $emp->addresses->firstWhere('type', 'registered') ?? $emp->addresses->first();
                         $addrParts = array_filter([$addr->full_address_th, $addr->full_address_en]);
                         $empAddress = implode("\n", $addrParts);
                     }
                 @endphp
                 <div class="client-name">{{ $empNameStr }}</div>
                 <div>{!! nl2br(e($empAddress)) !!}</div>
                 <div>Tax ID: {{ $emp->employerTaxId ?? '-' }}</div>
                 <div>Tel: {{ $emp->employerPhone ?? '-' }}</div>
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
                // If it's a full document (like Quotation without transactions), we must calculate
                // the total service fee correctly if in per_head mode.
                $pricingMode = $financial['pricing_mode'] ?? 'per_head';
                $pricingTiers = $financial['pricing_tiers'] ?? [];

                if ($pricingMode === 'per_head') {
                    $calculatedTotal = 0;
                    foreach ($pricingTiers as $tier) {
                        $count = count($tier['item_ids'] ?? []);
                        $price = $tier['price'] ?? 0;
                        $calculatedTotal += ($count * $price);
                    }
                    // Apply discount if any exists in financial data
                    $discount = $financial['discount'] ?? 0;
                    $serviceTotal = max(0, $calculatedTotal - $discount);
                } else {
                    $serviceTotal = $financial['total_amount'] ?? 0;
                }
            }

            if ($advanceTransactions->isNotEmpty()) {
                 $advanceTotal = $advanceTransactions->sum(function($t) use ($isReceiptContext) {
                    return $isReceiptContext ? ($t->paid_amount ?? 0) : $t->amount;
                });
            } elseif ($showAdvance && !$hasSpecificTransactions && isset($advanceItems)) {
                 $advanceTotal = $advanceItems->sum('total');
            }

            // Helper to get Tier Object for an Item
            $getTierForItem = function($item, $tiers) {
                // Returns the entire tier object or null
                foreach ($tiers as $tier) {
                    $itemIds = array_map('strval', $tier['item_ids'] ?? []);
                    // Manual bills use $item->id without prefix. Standard bills use emp_{employee_id} or just employee_id
                    if (in_array(strval($item->id), $itemIds, true) ||
                        in_array('emp_' . $item->id, $itemIds, true) ||
                        ($item->employee_id && in_array('emp_' . $item->employee_id, $itemIds, true)) ||
                        ($item->employee_id && in_array(strval($item->employee_id), $itemIds, true))) {
                        return $tier;
                    }
                }
                // Fallback to default tier if empty or unnamed
                foreach ($tiers as $tier) {
                    if (empty($tier['item_ids']) || ($tier['name'] ?? '') === 'Default Tier') {
                        return $tier;
                    }
                }
                return null;
            };

            // Helper to generate a unique key for grouping (Price + Note)
            // Actually, we should group by Tier Index or Unique Content
            // Since tiers don't have IDs, we use the tier object itself (or its properties)
            $getTierKey = function($item, $tiers) {
                foreach ($tiers as $idx => $tier) {
                    $itemIds = array_map('strval', $tier['item_ids'] ?? []);
                    if (in_array(strval($item->id), $itemIds, true) ||
                        in_array('emp_' . $item->id, $itemIds, true) ||
                        ($item->employee_id && in_array('emp_' . $item->employee_id, $itemIds, true)) ||
                        ($item->employee_id && in_array(strval($item->employee_id), $itemIds, true))) {
                        return $idx; // Use Index as Key
                    }
                }

                // Fallback: If not found but default tier exists, return default tier index
                foreach ($tiers as $idx => $tier) {
                    if (empty($tier['item_ids']) || ($tier['name'] ?? '') === 'Default Tier') {
                        return $idx;
                    }
                }
                return -1; // Not in tier
            };

            $vatIncluded = $financial['vat_included'] ?? false;
            // Explicitly checking if it's strictly numeric 0 or string '0' to avoid fallback
            $vatRate = isset($financial['vat_rate']) && $financial['vat_rate'] !== '' && $financial['vat_rate'] !== null ? (float)$financial['vat_rate'] : 7;
            $whtEnabled = $financial['wht_enabled'] ?? false;
            $whtRate = isset($financial['wht_rate']) && $financial['wht_rate'] !== '' && $financial['wht_rate'] !== null ? (float)$financial['wht_rate'] : 3;

            if ($vatRate <= 0) {
                $serviceBase = $serviceTotal;
                $serviceVat = 0;
                $totalServiceIncVat = $serviceBase;
            } elseif ($vatIncluded) {
                $totalServiceIncVat = $serviceTotal;
                $serviceBase = $totalServiceIncVat / (1 + ($vatRate/100));
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
                                        return $getTierKey($item, $pricingTiers);
                                    });
                                @endphp

                                @foreach($tierGroups as $tierIdx => $groupedItems)
                                    @php
                                        $count = $groupedItems->count();
                                        // Get tier data
                                        $tier = ($tierIdx >= 0 && isset($pricingTiers[$tierIdx])) ? $pricingTiers[$tierIdx] : null;

                                        // Use the exact tier price for this group instead of averaging the total transaction amount.
                                        // This ensures that employees with different tier prices are billed correctly.
                                        if ($tier && isset($tier['price'])) {
                                            $price = (float)$tier['price'];
                                        } else {
                                            // Fallback to average if tier price is missing for some reason
                                            $totalEmployeesInTransaction = $items->count();
                                            if ($totalEmployeesInTransaction > 0) {
                                                $price = $amount / $totalEmployeesInTransaction;
                                            } else {
                                                $price = 0;
                                            }
                                        }

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

        <div class="signatures-container">
            <!-- Signatures Section -->
            <div class="signatures">
                <div class="sig-block">
                    <div class="sig-title">Received By <span class="en-label">/ ผู้รับเงิน</span></div>
                    <div class="sig-line"></div>
                    <div class="sig-text">Date <span class="en-label">/ วันที่</span>: ____/____/______</div>
                </div>

                <div class="sig-block">
                    <div class="sig-title">Authorized Signature <span class="en-label">/ ผู้มีอำนาจลงนาม</span></div>

                    <div style="position: relative; display: flex; justify-content: center; align-items: end; height: 50px; margin-bottom: 10px;">
                        @if(isset($billerProfile))
                            @if($billerProfile->signature_path)
                                <img src="{{ asset('storage/' . $billerProfile->signature_path) }}"
                                     style="position: absolute;
                                            bottom: 0px;
                                            width: {{ $billerProfile->signature_position['width'] ?? 150 }}px;
                                            height: {{ $billerProfile->signature_position['height'] ?? 75 }}px;
                                            transform: rotate({{ $billerProfile->signature_position['rotate'] ?? 0 }}deg);
                                            z-index: 10;" alt="Signature">
                            @endif
                            @if($billerProfile->stamp_path)
                                <img src="{{ asset('storage/' . $billerProfile->stamp_path) }}"
                                     style="position: absolute;
                                            bottom: -10px; left: -20px;
                                            width: {{ $billerProfile->stamp_position['width'] ?? 100 }}px;
                                            height: {{ $billerProfile->stamp_position['height'] ?? 100 }}px;
                                            transform: rotate({{ $billerProfile->stamp_position['rotate'] ?? 0 }}deg);
                                            z-index: 5; opacity: 0.8;" alt="Stamp">
                            @endif
                        @else
                            @if($profile->use_signature && $profile->signature_path)
                                <img src="{{ asset('storage/' . $profile->signature_path) }}"
                                     style="position: absolute; bottom: 0px; width: 150px; z-index: 10;">
                            @endif
                            @if($profile->use_stamp && $profile->stamp_path)
                                <img src="{{ asset('storage/' . $profile->stamp_path) }}"
                                     style="position: absolute; bottom: -10px; left: -20px; width: 100px; z-index: 5; opacity: 0.8;">
                            @endif
                        @endif

                        <div class="sig-line" style="margin-bottom: 0; position: relative; z-index: 1;"></div>
                    </div>

                    @php $biller = $billerProfile ?? $profile; @endphp
                    <div class="sig-text">{{ $biller->authorized_signatory_name ?: $biller->name }}</div>
                </div>
            </div>

            <!-- Footer -->
            <div class="footer">
                Thank you for your business. <br>
                Please check the correctness of this document.
            </div>
        </div>
    </div>

    @if(!empty($includeEmployeeList) && $includeEmployeeList)
    <div class="page" style="page-break-before: always; margin-top: 20px;">
        <h2 style="text-align: center; margin-bottom: 20px; font-size: 18px;">ตารางรายชื่อพนักงาน / Employee List</h2>
        <table class="items-table" style="font-size: 12px;">
            <thead>
                <tr>
                    <th style="width: 5%; text-align: center;">ลำดับ<br><span class="en-label">No.</span></th>
                    <th style="width: 15%; text-align: center;">รูปถ่าย<br><span class="en-label">Photo</span></th>
                    <th style="width: 15%;">รหัสลูกจ้าง<br><span class="en-label">Employee ID</span></th>
                    <th style="width: 35%;">ชื่อ-นามสกุล<br><span class="en-label">Name</span></th>
                    <th style="width: 15%; text-align: center;">สัญชาติ<br><span class="en-label">Nationality</span></th>
                    <th style="width: 15%; text-align: right;">ราคาต่อหัว<br><span class="en-label">Price</span></th>
                </tr>
            </thead>
            <tbody>
                @foreach($employeeList as $emp)
                <tr>
                    <td style="text-align: center; vertical-align: middle;">{{ $emp['index'] }}</td>
                    <td style="text-align: center; vertical-align: middle;">
                        @if(!empty($emp['image']))
                            <img src="{{ asset('storage/' . $emp['image']) }}" alt="Photo" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
                        @else
                            <div style="width: 50px; height: 50px; background-color: #f3f4f6; border-radius: 4px; display: inline-block; line-height: 50px; color: #9ca3af; font-size: 10px;">No Image</div>
                        @endif
                    </td>
                    <td style="vertical-align: middle;">{{ $emp['employee_id'] ?: '-' }}</td>
                    <td style="vertical-align: middle;">{{ $emp['prefix'] }} {{ $emp['name'] }}</td>
                    <td style="text-align: center; vertical-align: middle;">{{ $emp['nationality'] ?: '-' }}</td>
                    <td style="text-align: right; vertical-align: middle;">{{ number_format($emp['price'], 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</body>
</html>
