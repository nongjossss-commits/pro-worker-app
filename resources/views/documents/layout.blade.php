<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>{{ $page_title ?? $title ?? ucfirst($type) }}</title>
    <!-- Google Fonts for Sarabun -->
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @php
        // Accent colour = the installation's brand colour (Super Admin → Branding).
        try {
            $docBrand = \App\Services\BrandService::current();
            $accent = $docBrand['primary_color'] ?: '#F97316';
            $accentRgb = \App\Services\BrandService::hexToRgb($accent);
            $accentDark = \App\Services\BrandService::darken($accent, 0.18);
        } catch (\Throwable $e) {
            $accent = '#F97316';
            $accentRgb = '249, 115, 22';
            $accentDark = '#C2570F';
        }
    @endphp
    <style>
        :root {
            --accent: {{ $accent }};
            --accent-rgb: {{ $accentRgb }};
            --accent-dark: {{ $accentDark }};
            --ink: #0f172a;
            --ink-2: #334155;
            --muted: #64748b;
            --line: #e2e8f0;
            --soft: #f8fafc;
        }
        * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        body { font-family: 'Sarabun', sans-serif; margin: 0; padding: 24px; color: var(--ink-2); background: #e9edf3; }

        /* A4 Page Styling */
        .page {
            max-width: 210mm;
            margin: 0 auto;
            background: white;
            padding: 0 34px 28px;
            border-radius: 6px;
            box-shadow: 0 20px 50px -20px rgba(15, 23, 42, .35);
            position: relative;
            min-height: 297mm;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        .doc-band {
            height: 8px;
            margin: 0 -34px 22px;
            background: linear-gradient(90deg, var(--accent-dark), var(--accent) 55%, rgba(var(--accent-rgb), .55));
        }

        /* Print Specifics */
        @media print {
            body { background: white; padding: 0; }
            .page {
                box-shadow: none;
                border-radius: 0;
                padding: 0 0 6mm;
                margin: 0;
                width: 100%;
                height: auto;
                min-height: auto;
                display: block;
                overflow: visible;
            }
            .doc-band { margin: 0 0 16px; }
            @page { margin: 10mm; size: A4 portrait; }
            .no-print { display: none !important; }
            table.items-table, .totals-container, .signatures-container { page-break-inside: auto; }
            tr { page-break-inside: avoid; page-break-after: auto; }
        }

        /* ---------- Header ---------- */
        .doc-header { display: flex; justify-content: space-between; align-items: flex-start; gap: 24px; margin-bottom: 18px; }
        .issuer { display: flex; gap: 14px; align-items: flex-start; min-width: 0; flex: 1 1 58%; }
        .company-logo { height: 58px; max-width: 120px; object-fit: contain; flex-shrink: 0; }
        .company-name { font-size: 17px; font-weight: 800; color: var(--accent-dark); margin-bottom: 3px; line-height: 1.25; }
        .company-address { font-size: 11px; color: var(--muted); line-height: 1.45; }
        .tax-id { font-size: 11px; margin-top: 2px; color: var(--muted); }

        .doc-title { text-align: right; flex: 0 0 auto; }
        .doc-title h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 800;
            letter-spacing: -.01em;
            color: var(--ink);
            line-height: 1.15;
            white-space: nowrap;
        }
        .doc-type {
            display: inline-block;
            margin-top: 6px;
            padding: 3px 12px;
            border-radius: 999px;
            background: rgba(var(--accent-rgb), .12);
            color: var(--accent-dark);
            font-size: 10.5px;
            font-weight: 700;
            letter-spacing: .14em;
            text-transform: uppercase;
        }

        /* ---------- Meta chips ---------- */
        .meta-row { display: flex; gap: 10px; margin-bottom: 16px; }
        .meta-chip {
            flex: 1 1 0;
            border: 1px solid var(--line);
            border-radius: 10px;
            padding: 7px 12px;
            background: var(--soft);
        }
        .meta-chip .k { font-size: 10px; color: var(--muted); text-transform: uppercase; letter-spacing: .06em; }
        .meta-chip .v { font-size: 13px; font-weight: 700; color: var(--ink); margin-top: 1px; }
        .meta-chip.due { border-color: rgba(var(--accent-rgb), .45); background: rgba(var(--accent-rgb), .07); }
        .meta-chip.due .v { color: var(--accent-dark); }

        /* ---------- Bill to + amount ---------- */
        .parties { display: flex; gap: 14px; margin-bottom: 18px; align-items: stretch; }
        .client-box {
            flex: 1 1 auto;
            border: 1px solid var(--line);
            border-left: 4px solid var(--accent);
            padding: 10px 14px;
            border-radius: 10px;
            background: #fff;
            font-size: 12px;
            line-height: 1.5;
            color: var(--ink-2);
        }
        .client-label { font-weight: 700; color: var(--accent-dark); font-size: 10px; text-transform: uppercase; letter-spacing: .1em; margin-bottom: 4px; }
        .client-name { font-weight: 800; font-size: 15px; color: var(--ink); margin-bottom: 2px; }
        .amount-card {
            flex: 0 0 34%;
            border-radius: 12px;
            padding: 12px 16px;
            color: #fff;
            background: linear-gradient(135deg, var(--accent) 0%, var(--accent-dark) 100%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            box-shadow: 0 10px 24px -14px rgba(var(--accent-rgb), .9);
        }
        .amount-card .k { font-size: 11px; opacity: .9; font-weight: 600; }
        .amount-card .v { font-size: 26px; font-weight: 800; line-height: 1.15; margin: 2px 0; letter-spacing: -.01em; }
        .amount-card .s { font-size: 10.5px; opacity: .88; }

        /* ---------- Items Table ---------- */
        table.items-table { width: 100%; border-collapse: separate; border-spacing: 0; margin-bottom: 18px; }
        table.items-table th {
            background: rgba(var(--accent-rgb), .1);
            color: var(--ink);
            padding: 9px 10px;
            text-align: left;
            font-weight: 700;
            border-bottom: 2px solid var(--accent);
            font-size: 12px;
            white-space: nowrap;
            vertical-align: bottom;
        }
        table.items-table th .en-label { display: block; margin-left: 0; font-size: 10px; font-weight: 500; }
        table.items-table th:first-child { border-top-left-radius: 8px; }
        table.items-table th:last-child { border-top-right-radius: 8px; }
        table.items-table td {
            padding: 9px 10px;
            border-bottom: 1px solid #edf1f6;
            font-size: 12px;
            vertical-align: top;
            color: var(--ink-2);
        }
        table.items-table tbody tr:nth-child(even) td:not(.section-header) { background: #fafbfd; }
        table.items-table td strong { color: var(--ink); }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-muted { color: var(--muted); }

        .section-header {
            background-color: #f1f5f9;
            padding: 6px 10px !important;
            font-weight: 800;
            font-size: 10.5px !important;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: var(--ink-2) !important;
            border-bottom: 1px solid var(--line) !important;
        }

        /* ---------- Summary: words + payment (left) / totals (right) ---------- */
        .summary { display: flex; gap: 18px; align-items: flex-start; margin-bottom: 6px; }
        .summary-left { flex: 1 1 auto; min-width: 0; display: flex; flex-direction: column; gap: 10px; }
        .totals-container {
            flex: 0 0 46%;
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 10px 14px 12px;
            background: var(--soft);
            box-sizing: border-box;
        }
        table.totals-table { width: 100%; border-collapse: separate; border-spacing: 0; font-size: 12px; }
        table.totals-table td { padding: 5px 0; }
        .total-label { text-align: left; color: var(--muted); }
        .total-value { text-align: right; color: var(--ink); font-variant-numeric: tabular-nums; }

        table.totals-table .grand-total-row td {
            padding: 9px 12px;
            font-weight: 800;
            font-size: 15px;
            color: #fff;
            background: var(--accent);
        }
        .grand-total-row td:first-child { border-radius: 8px 0 0 8px; box-shadow: 2px 0 0 var(--accent); }
        .grand-total-row td:last-child { border-radius: 0 8px 8px 0; color: #fff; }
        .grand-total-row .en-label { color: rgba(255, 255, 255, .85); }
        .grand-spacer td { padding: 3px 0 !important; }

        .words-box {
            border-radius: 10px;
            padding: 8px 12px;
            background: rgba(var(--accent-rgb), .07);
            border: 1px dashed rgba(var(--accent-rgb), .45);
        }
        .words-box .k { font-size: 10px; color: var(--accent-dark); font-weight: 700; letter-spacing: .06em; text-transform: uppercase; }
        .words-box .v { font-size: 13px; font-weight: 700; color: var(--ink); }

        .pay-box { padding: 10px 12px; border: 1px solid var(--line); border-radius: 10px; background: #fff; font-size: 12px; line-height: 1.45; }
        .pay-box .title { font-weight: 800; margin-bottom: 6px; color: var(--ink); font-size: 11.5px; }
        .check { display: inline-block; width: 10px; height: 10px; border: 1.5px solid #94a3b8; border-radius: 2px; margin-right: 6px; vertical-align: middle; }

        .tax-note {
            margin-top: 12px;
            font-size: 11.5px;
            line-height: 1.5;
            color: #9a3412;
            background: #fff7ed;
            border: 1px solid #fed7aa;
            border-radius: 10px;
            padding: 8px 12px;
        }
        .tax-note .en-label { color: #b45309; }

        /* ---------- Signatures ---------- */
        .signatures-container {
            margin-top: auto; /* Pushes to bottom in flex container on screen */
            padding-top: 34px;
            page-break-inside: avoid;
        }
        .signatures { display: flex; justify-content: space-between; page-break-inside: avoid; margin-bottom: 26px; gap: 24px; }
        .sig-block { width: 42%; text-align: center; position: relative; }
        .sig-block.filled { border: 1px solid var(--line); border-radius: 12px; padding: 12px 14px 10px; background: #fff; }
        .sig-line { border-bottom: 1.5px solid #cbd5e1; height: 40px; margin-bottom: 8px; width: 80%; margin-left: 10%; }
        .sig-text { font-size: 12px; color: var(--muted); }
        .sig-title { font-weight: 800; margin-bottom: 30px; color: var(--ink); font-size: 13px; }

        /* ---------- Footer ---------- */
        .footer {
            border-top: 1px solid var(--line);
            padding-top: 12px;
            font-size: 12px;
            color: var(--muted);
            text-align: center;
            page-break-inside: avoid;
        }
        .footer .thanks { font-weight: 800; color: var(--accent-dark); font-size: 13px; }

        /* ---------- Action Bar ---------- */
        .action-bar {
            max-width: 210mm;
            margin: 0 auto 16px;
            background: #0f172a;
            padding: 10px 14px;
            border-radius: 10px;
            color: #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-sizing: border-box;
            font-size: 13px;
        }
        .btn { padding: 8px 16px; background: #fff; color: #0f172a; border: none; border-radius: 8px; cursor: pointer; font-weight: 700; text-decoration: none; font-size: 14px; font-family: inherit; }
        .btn:hover { background: #e2e8f0; }
        .btn-accent { background: var(--accent); color: #fff; }
        .btn-accent:hover { background: var(--accent-dark); }

        .en-label { color: var(--muted); font-weight: normal; font-size: 0.9em; margin-left: 3px; }

        /* Employee list page */
        table.items-table.list-table th { white-space: normal; padding: 8px 6px; }
        table.items-table.list-table td { padding: 8px 6px; }
        .list-title { text-align: center; margin: 22px 0 18px; font-size: 19px; font-weight: 800; color: var(--ink); }
    </style>
</head>
<body>
    <div class="no-print action-bar">
        <span>Document Preview ({{ ucfirst($mode ?? 'standard') }})</span>
        <div>
            <button class="btn btn-accent" onclick="window.print()">Print / Save PDF</button>
            <button class="btn" onclick="window.close()" style="margin-left: 10px;">Close</button>
        </div>
    </div>

    {{-- list-only mode: ข้าม invoice content ทั้งหมด ออกเฉพาะตารางรายชื่อท้ายไฟล์ --}}
    @unless(!empty($listOnly) && $listOnly)
    <div class="page">
        <div class="doc-band"></div>

        @php
            // ใช้ FQN แทน use Illuminate\Support\Str; เพราะบล็อกนี้อยู่ภายใน @unless
            // และ use statement ไม่อนุญาตให้อยู่ใน conditional block ของ PHP
            $mode = $mode ?? 'combined';
            $isReceiptContext = \Illuminate\Support\Str::contains($type, ['Receipt', 'Tax Invoice']);

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
            $discount = 0;
            $discountDescription = '';

            if ($hasSpecificTransactions) {
                $serviceTotal = $serviceTransactions->sum(function($t) use ($isReceiptContext) {
                    return $isReceiptContext ? ($t->paid_amount ?? 0) : $t->amount;
                });
                // Calculate total discount from all service transactions
                $discount = $serviceTransactions->sum(function($t) { return $t->discount_amount ?? 0; });
                $discountDescription = '';
                if ($discount > 0 && $serviceTransactions->count() === 1) {
                    $discountDescription = $serviceTransactions->first()->discount_description ?? '';
                }
                $serviceTotal = max(0, $serviceTotal - $discount);
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
                    // Apply discount from financial_data (group-level) for quotation-style
                    if (isset($financial['discount']) && $financial['discount'] > 0) {
                        $discount = $financial['discount'];
                    }
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
            $vatEnabled = $financial['vat_enabled'] ?? true;
            // Explicitly checking if it's strictly numeric 0 or string '0' to avoid fallback
            $vatRate = isset($financial['vat_rate']) && $financial['vat_rate'] !== '' && $financial['vat_rate'] !== null ? (float)$financial['vat_rate'] : 7;
            $whtEnabled = $financial['wht_enabled'] ?? false;
            $whtRate = isset($financial['wht_rate']) && $financial['wht_rate'] !== '' && $financial['wht_rate'] !== null ? (float)$financial['wht_rate'] : 3;

            if (!$vatEnabled || $vatRate <= 0) {
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

            $grandTotal = ($showService ? $totalServiceIncVat : 0) + ($showAdvance ? $advanceTotal : 0);
            $whtAmount = ($showService && $whtEnabled) ? ($serviceBase * ($whtRate/100)) : 0;
            $netPayable = $grandTotal - $whtAmount;

            // ---- Presentation only (nothing above depends on these) ----
            // $type arrives as "receipt", "tax_invoice", "credit_note" or "Receipt" etc.
            $typeLower = str_replace(['_', '-'], ' ', strtolower((string) $type));
            $isQuotation = str_contains($typeLower, 'quotation');
            if (str_contains($typeLower, 'credit note')) {
                $amountLabel = 'ยอดลดหนี้ / Credit Amount';
            } elseif ($isQuotation) {
                $amountLabel = 'ยอดรวมใบเสนอราคา / Quotation Total';
            } elseif (str_contains($typeLower, 'receipt')) {
                $amountLabel = 'ยอดที่ได้รับชำระ / Amount Received';
            } elseif (str_contains($typeLower, 'tax invoice')) {
                $amountLabel = 'ยอดรวมทั้งสิ้น / Total Amount';
            } else {
                $amountLabel = 'ยอดที่ต้องชำระ / Amount Due';
            }
            $heroAmount = ($showService && $whtEnabled) ? $netPayable : $grandTotal;
            $isPaidDocument = str_contains($typeLower, 'receipt') || str_contains($typeLower, 'tax invoice');

            // Receipt opened from a bill that is not fully paid yet: the rows and
            // totals above stay the bill's; add "paid" and "balance due" and show
            // the money actually received as the headline. A bill counts as paid
            // when paid_amount >= amount - discount - credit (same rule as the
            // bill status). Fully paid bills and per-payment receipts
            // (showPaymentDocument sets paid_amount = amount) are unchanged.
            $receivedAmount = null;
            $balanceDue = null;
            if (str_contains($typeLower, 'receipt') && $hasSpecificTransactions) {
                $shownTransactions = collect()
                    ->merge($showService ? $serviceTransactions : [])
                    ->merge($showAdvance ? $advanceTransactions : []);
                $billNotFullyPaid = $shownTransactions->contains(fn($t) =>
                    (float) ($t->paid_amount ?? 0) + 0.005 < (float) $t->amount - (float) ($t->discount_amount ?? 0) - (float) ($t->credit_amount ?? 0));
                if ($billNotFullyPaid) {
                    $receivedAmount = (float) $shownTransactions->sum(fn($t) => (float) ($t->paid_amount ?? 0));
                    // Same outstanding figure the finance screens show for the bill.
                    $balanceDue = (float) $shownTransactions->sum(fn($t) => max(0,
                        (float) $t->amount - (float) ($t->discount_amount ?? 0) - (float) ($t->credit_amount ?? 0) - (float) ($t->paid_amount ?? 0)));
                }
            }
            $dueDate = (!$isPaidDocument && !$isQuotation && !str_contains($typeLower, 'credit note') && $hasSpecificTransactions)
                ? $transactions->pluck('due_date')->filter()->min()
                : null;
            $titleParts = array_map('trim', explode(' / ', (string) ($title ?? ucfirst($type)), 2));

            // Single source of company info — biller profile wins, else fall through to $profile.
            $issuer = isset($billerProfile) ? $billerProfile : $profile;
            // Collapse multi-line address to one tight line. Saves 1-2 vertical rows.
            $issuerAddress = trim(preg_replace('/\s+/u', ' ', (string) $issuer->address));
            $issuerMeta = [];
            if ($issuer->tax_id) $issuerMeta[] = 'Tax ID: ' . $issuer->tax_id;
            if ($issuer->phone)  $issuerMeta[] = 'Tel: '    . $issuer->phone;
        @endphp

        <!-- Header -->
        <div class="doc-header">
            <div class="issuer">
                @if(isset($billerProfile) && $billerProfile->logo_path)
                    <img src="{{ asset('storage/' . $billerProfile->logo_path) }}" class="company-logo" alt="Logo">
                @elseif(!isset($billerProfile) && $profile->logo_path)
                    <img src="{{ asset('storage/' . $profile->logo_path) }}" class="company-logo" alt="Logo">
                @endif
                <div style="min-width: 0;">
                    <div class="company-name">{{ $issuer->name }}</div>
                    @if($issuerAddress)
                        <div class="company-address">{{ $issuerAddress }}</div>
                    @endif
                    @if(!empty($issuerMeta))
                        <div class="tax-id">{{ implode('   ·   ', $issuerMeta) }}</div>
                    @endif
                </div>
            </div>
            <div class="doc-title">
                <h1>{{ $titleParts[0] }}</h1>
                @if(!empty($titleParts[1]))
                    <div class="doc-type">{{ $titleParts[1] }}</div>
                @endif
            </div>
        </div>

        <!-- Document details -->
        <div class="meta-row">
            <div class="meta-chip">
                <div class="k">No. <span class="en-label">/ เลขที่</span></div>
                <div class="v">{{ $doc_number ?? 'DRAFT' }}</div>
            </div>
            <div class="meta-chip">
                <div class="k">Date <span class="en-label">/ วันที่</span></div>
                <div class="v">{{ date('d/m/Y') }}</div>
            </div>
            <div class="meta-chip">
                <div class="k">Ref <span class="en-label">/ อ้างอิง</span></div>
                <div class="v">#{{ $production->id }}</div>
            </div>
            @if($dueDate)
                <div class="meta-chip due">
                    <div class="k">Due Date <span class="en-label">/ ครบกำหนดชำระ</span></div>
                    <div class="v">{{ \Carbon\Carbon::parse($dueDate)->format('d/m/Y') }}</div>
                </div>
            @endif
        </div>

        <!-- Client Info + amount -->
        <div class="parties">
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
                @elseif($production->employer)
                     @php
                         $emp = $production->employer;
                         $empName = array_filter([$emp->getRawOriginal('employerNameTh'), $emp->employerNameEn]);
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
                @else
                     {{-- No employer at all — e.g. a per-unit quotation with no
                          committed customer yet. --}}
                     <div class="client-name">{{ $production->project_name ?? '-' }}</div>
                @endif
            </div>

            @if($showTotal)
                <div class="amount-card">
                    <div class="k">{{ $amountLabel }}</div>
                    <div class="v">฿{{ number_format($receivedAmount ?? $heroAmount, 2) }}</div>
                    <div class="s">
                        @if($receivedAmount !== null)
                            Partial payment — balance ฿{{ number_format($balanceDue, 2) }} / ชำระบางส่วน คงค้าง
                        @elseif($showService && $whtEnabled)
                            Net after {{ rtrim(rtrim(number_format($whtRate, 2), '0'), '.') }}% WHT / หลังหักภาษี ณ ที่จ่าย
                        @elseif($dueDate)
                            Due {{ \Carbon\Carbon::parse($dueDate)->format('d/m/Y') }} / ครบกำหนดชำระ
                        @else
                            {{ $isPaidDocument ? 'Thank you for your payment / ขอบคุณที่ชำระเงิน' : 'Total incl. all charges / รวมทุกรายการ' }}
                        @endif
                    </div>
                </div>
            @endif
        </div>

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
                                            <br><span class="text-muted" style="font-size: 11px;">{{ $showTotal ? $count.' Employees @ ' : '' }}{{ number_format($price, 2) }} ฿{{ $showTotal ? '' : ' / head' }}</span>
                                            @if($t->due_date)<br><span style="color: #94a3b8; font-size: 11px;">Due: {{ $t->due_date->format('d/m/Y') }}</span>@endif
                                        </td>
                                        <td class="text-center">{{ $showTotal ? $count : '1' }}</td>
                                        <td class="text-right">{{ number_format($price, 2) }}</td>
                                        <td class="text-right"><strong>{{ $showTotal ? number_format($subtotal, 2) : '—' }}</strong></td>
                                    </tr>
                                @endforeach
                            @else
                                {{-- Fixed mode or no items --}}
                                <tr>
                                    <td class="text-center">{{ $lineIdx++ }}</td>
                                    <td>
                                        <strong>{{ $t->notes ?: ucfirst(str_replace('_', ' ', $t->type)) }}</strong>
                                        @if($t->due_date)<br><span style="color: #94a3b8; font-size: 11px;">Due: {{ $t->due_date->format('d/m/Y') }}</span>@endif
                                    </td>
                                    <td class="text-center">1</td>
                                    <td class="text-right">{{ number_format($amount, 2) }}</td>
                                    <td class="text-right"><strong>{{ $showTotal ? number_format($amount, 2) : '—' }}</strong></td>
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
                                {{-- Without a grand total, this is a per-unit rate quote — a tier
                                     with nobody assigned yet still needs to show its price, since
                                     the whole point is "we don't know the headcount yet". --}}
                                @if($count > 0 || !$showTotal)
                                    <tr>
                                        <td class="text-center">{{ $idx + 1 }}</td>
                                        <td>
                                            <strong>{{ $production->project_name ?? 'Service Fee' }}</strong>
                                            @if(!empty($tier['note'])) <br><small class="text-muted">({{ $tier['note'] }})</small> @endif
                                        </td>
                                        <td class="text-center">{{ $showTotal ? $count : '1' }}</td>
                                        <td class="text-right">{{ number_format($tier['price'], 2) }}</td>
                                        <td class="text-right"><strong>{{ $showTotal ? number_format($tier['price'] * $count, 2) : '—' }}</strong></td>
                                    </tr>
                                @endif
                            @endforeach
                        @else
                            <tr>
                                <td class="text-center">1</td>
                                <td>{{ $production->project_name ?? 'Service Fee' }}</td>
                                <td class="text-center">1</td>
                                <td class="text-right">{{ number_format($serviceTotal, 2) }}</td>
                                <td class="text-right"><strong>{{ $showTotal ? number_format($serviceTotal, 2) : '—' }}</strong></td>
                            </tr>
                        @endif
                    @endif
                @endif

                <!-- ADVANCE PAYMENT SECTION -->
                @if($showAdvance)
                    @if($advanceTransactions->isNotEmpty())
                         <tr>
                            <td colspan="5" class="section-header" style="background-color: #fff7ed; color: #c2410c !important;">Advance Payments (เงินสำรองจ่าย)</td>
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
                                        <br><span class="badge" style="font-size: 10px; background: #f1f5f9; padding: 2px 6px; border-radius: 999px;">Partial Payment (Full: {{ number_format($t->amount, 2) }})</span>
                                    @endif
                                </td>
                                <td class="text-center">1</td>
                                <td class="text-right">{{ number_format($amount, 2) }}</td>
                                <td class="text-right"><strong>{{ number_format($amount, 2) }}</strong></td>
                            </tr>
                        @endforeach

                    @elseif(!$hasSpecificTransactions && isset($advanceItems) && $advanceItems->isNotEmpty())
                        <tr>
                            <td colspan="5" class="section-header" style="background-color: #fff7ed; color: #c2410c !important;">Advance Payments (เงินสำรองจ่าย) <span style="font-size: 10px; font-weight: normal; color: #9a3412;">(No VAT)</span></td>
                        </tr>
                        @foreach($advanceItems as $index => $item)
                            <tr>
                                <td class="text-center">{{ $index + 1 }}</td>
                                <td>{{ $item->description }}</td>
                                <td class="text-center">{{ $item->quantity }}</td>
                                <td class="text-right">{{ number_format($item->unit_price, 2) }}</td>
                                <td class="text-right"><strong>{{ number_format($item->total, 2) }}</strong></td>
                            </tr>
                        @endforeach
                    @endif
                @endif
            </tbody>
        </table>

        <div class="summary">
            <div class="summary-left">
                <!-- Thai Baht Text -->
                @if($showTotal)
                    <div class="words-box">
                        <div class="k">Amount in words <span class="en-label">/ จำนวนเงินตัวอักษร</span></div>
                        <div class="v">( {{ \App\Helpers\ThaiBaht::convert($receivedAmount ?? $grandTotal) }} )</div>
                    </div>
                @endif

                {{-- Payment Methods (ช่องทางการชำระเงิน). Bank Transfer rows get a
                     colored brand badge so the recipient recognises the bank at a
                     glance (กสิกร/กรุงไทย/กรุงเทพ all start with ก/ก-, name alone is
                     confusing). Color + initial come from config/thai_banks.php
                     matched by bank_code. --}}
                @if(!empty($paymentMethods))
                    @php $bankPresets = collect(config('thai_banks', []))->keyBy('code'); @endphp
                    <div class="pay-box">
                        <div class="title">
                            ช่องทางการชำระเงิน <span class="en-label">/ Payment Information</span>
                        </div>
                        @foreach($paymentMethods as $pm)
                            @php $ptype = $pm['type'] ?? ''; @endphp

                            @if($ptype === 'cash')
                                <div style="margin-bottom: 3px;">
                                    <span class="check"></span>
                                    ชำระเป็นเงินสด
                                    <span style="border-bottom: 1px dotted #94a3b8; display: inline-block; min-width: 100px; margin: 0 3px;">&nbsp;</span>
                                    บาท
                                </div>

                            @elseif($ptype === 'promptpay')
                                <div style="margin-bottom: 3px;">
                                    <span class="check"></span>
                                    PromptPay: <strong>{{ $pm['promptpay_id'] ?? '-' }}</strong>
                                    <span style="border-bottom: 1px dotted #94a3b8; display: inline-block; min-width: 80px; margin: 0 3px;">&nbsp;</span>
                                    บาท
                                </div>

                            @elseif($ptype === 'transfer')
                                @php
                                    $preset = !empty($pm['bank_code']) ? ($bankPresets[$pm['bank_code']] ?? null) : null;
                                    $badgeColor   = $preset['color']   ?? '#6B7280';
                                    $badgeInitial = $preset['initial'] ?? mb_substr($pm['bank_name'] ?? '?', 0, 1);
                                @endphp
                                <div style="margin-bottom: 5px; display: flex; align-items: flex-start; gap: 7px;">
                                    <span class="check" style="margin-top: 5px; margin-right: 0; flex-shrink: 0;"></span>
                                    <span style="display: inline-flex; align-items: center; justify-content: center; width: 24px; height: 24px; background: {{ $badgeColor }}; color: #fff; font-weight: bold; font-size: 10px; border-radius: 6px; flex-shrink: 0;">{{ $badgeInitial }}</span>
                                    <div style="flex-grow: 1; min-width: 0;">
                                        <div>
                                            <strong style="color: var(--ink);">{{ $pm['bank_name'] ?? '-' }}</strong>
                                            @if(!empty($pm['account_name']))
                                                <span style="color: var(--muted);"> · {{ $pm['account_name'] }}</span>
                                            @endif
                                        </div>
                                        @if(!empty($pm['account_number']))
                                            <div style="color: var(--muted); font-size: 11px;">
                                                เลขที่บัญชี: <strong style="color: var(--ink); font-size: 12.5px; letter-spacing: .03em;">{{ $pm['account_number'] }}</strong>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                            @elseif($ptype === 'other')
                                <div style="margin-bottom: 3px;">
                                    <span class="check"></span>
                                    อื่นๆ: {{ $pm['note'] ?? '-' }}
                                    <span style="border-bottom: 1px dotted #94a3b8; display: inline-block; min-width: 80px; margin: 0 3px;">&nbsp;</span>
                                    บาท
                                </div>
                            @endif
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Calculations -->
            @if($showTotal)
            <div class="totals-container">
                <table class="totals-table">
                    @if($showService)
                        @if(isset($discount) && $discount > 0)
                            <tr>
                                <td class="total-label"><strong>Service Fee (Gross) <span class="en-label">/ ค่าบริการ (ก่อนส่วนลด)</span></strong></td>
                                <td class="total-value">{{ number_format($serviceTotal + $discount, 2) }}</td>
                            </tr>
                            <tr style="color: #dc2626;">
                                <td class="total-label" style="color: #dc2626;"><strong>Discount <span class="en-label">/ ส่วนลด</span></strong> @if(!empty($discountDescription))<small>({{ $discountDescription }})</small>@endif</td>
                                <td class="total-value" style="color: #dc2626;">-{{ number_format($discount, 2) }}</td>
                            </tr>
                        @endif
                        @if($vatEnabled && $vatRate > 0)
                            <tr>
                                <td class="total-label"><strong>Service Base <span class="en-label">/ มูลค่าบริการ (ก่อน VAT)</span></strong></td>
                                <td class="total-value">{{ number_format($serviceBase, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="total-label">VAT ({{ $vatRate }}%)</td>
                                <td class="total-value">{{ number_format($serviceVat, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="total-label" style="border-bottom: 1px solid var(--line);">Service Total <span class="en-label">/ รวมค่าบริการ (รวม VAT)</span></td>
                                <td class="total-value" style="border-bottom: 1px solid var(--line);">{{ number_format($totalServiceIncVat, 2) }}</td>
                            </tr>
                        @else
                            <tr>
                                <td class="total-label" style="border-bottom: 1px solid var(--line);"><strong>Service Total <span class="en-label">/ รวมค่าบริการ</span></strong></td>
                                <td class="total-value" style="border-bottom: 1px solid var(--line);">{{ number_format($totalServiceIncVat, 2) }}</td>
                            </tr>
                        @endif
                    @endif

                    @if($showAdvance && $advanceTotal > 0)
                        <tr>
                            <td class="total-label" style="color: #c2410c;"><strong>Total Advance Payments <span class="en-label">/ รวมเงินสำรองจ่าย</span></strong></td>
                            <td class="total-value" style="color: #c2410c;">{{ number_format($advanceTotal, 2) }}</td>
                        </tr>
                    @endif

                    <tr class="grand-spacer"><td colspan="2"></td></tr>
                    <tr class="grand-total-row">
                        <td>Grand Total <span class="en-label">/ รวมทั้งสิ้น</span></td>
                        <td class="total-value">{{ number_format($grandTotal, 2) }}</td>
                    </tr>

                    @if($showService && $whtEnabled)
                    <tr style="color: #dc2626;">
                        <td class="total-label" style="color: #dc2626; padding-top: 8px;">Less WHT ({{ $whtRate }}% on Service)</td>
                        <td class="total-value" style="color: #dc2626; padding-top: 8px;">-{{ number_format($whtAmount, 2) }}</td>
                    </tr>
                    <tr style="font-weight: bold;">
                        <td class="total-label" style="padding-top: 6px; border-top: 1px dashed #cbd5e1; color: var(--ink);">Net Payable <span class="en-label">/ ยอดสุทธิ</span></td>
                        <td class="total-value" style="padding-top: 6px; border-top: 1px dashed #cbd5e1; font-size: 14px;">{{ number_format($netPayable, 2) }}</td>
                    </tr>
                    @endif

                    @if($receivedAmount !== null)
                    <tr style="font-weight: bold; color: #15803d;">
                        <td class="total-label" style="padding-top: 8px; border-top: 1px dashed #cbd5e1; color: #15803d;">Paid <span class="en-label">/ ชำระแล้ว</span></td>
                        <td class="total-value" style="padding-top: 8px; border-top: 1px dashed #cbd5e1; color: #15803d;">{{ number_format($receivedAmount, 2) }}</td>
                    </tr>
                    <tr style="font-weight: bold;">
                        <td class="total-label" style="color: #b91c1c;">Balance Due <span class="en-label">/ คงค้าง</span></td>
                        <td class="total-value" style="color: #b91c1c;">{{ number_format($balanceDue, 2) }}</td>
                    </tr>
                    @endif
                </table>
            </div>
            @endif
        </div>

        {{-- Tax status note — states plainly whether the price shown is VAT-
             inclusive/exclusive and whether WHT applies, so the customer
             can't misread the amount due. Tied to $showService because VAT/
             WHT in this document only ever apply to the service fee. --}}
        @if($showService)
            <div class="tax-note">
                @if(!$vatEnabled)
                    <div>Note: This price does not yet include Value Added Tax (VAT). Please contact us if a VAT-inclusive quotation or tax invoice is required.
                        <span class="en-label">/ หมายเหตุ: ราคานี้ยังไม่รวมภาษีมูลค่าเพิ่ม (VAT) หากต้องการใบเสนอราคาหรือใบกำกับภาษีที่รวม VAT กรุณาติดต่อเจ้าหน้าที่</span></div>
                @else
                    <div>Note: This price already includes Value Added Tax (VAT).
                        <span class="en-label">/ หมายเหตุ: ราคานี้รวมภาษีมูลค่าเพิ่ม (VAT) แล้ว</span></div>
                @endif
                @if($whtEnabled)
                    <div>Note: This amount is subject to a {{ rtrim(rtrim(number_format($whtRate, 2), '0'), '.') }}% Withholding Tax deduction as required by law.
                        <span class="en-label">/ หมายเหตุ: ยอดนี้มีการหักภาษี ณ ที่จ่าย {{ rtrim(rtrim(number_format($whtRate, 2), '0'), '.') }}% ตามที่กฎหมายกำหนด</span></div>
                @endif
            </div>
        @endif

        <div class="signatures-container">
            <!-- Signatures Section -->
            <div class="signatures">
                @if($type !== 'quotation')
                <div class="sig-block filled">
                    <div class="sig-title">Received By <span class="en-label">/ ผู้รับเงิน</span></div>
                    <div class="sig-line"></div>
                    <div class="sig-text">Date <span class="en-label">/ วันที่</span>: ____/____/______</div>
                </div>
                @else
                {{-- A quotation isn't a request for payment — no "Received By" line.
                     Empty spacer keeps the flex layout (justify-content: space-between)
                     identical so the Authorized Signature block stays on the right. --}}
                <div class="sig-block"></div>
                @endif

                <div class="sig-block filled">
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
                <span class="thanks">Thank you for your business.</span><br>
                Please check the correctness of this document.
            </div>
        </div>
    </div>
    @endunless

    @if(!empty($includeEmployeeList) && $includeEmployeeList)
    {{-- ใน list-only mode ไม่ใส่ page-break-before เพราะไม่มีหน้าก่อนหน้า --}}
    <div class="page" style="@unless(!empty($listOnly) && $listOnly) page-break-before: always; @endunless margin-top: 20px;">
        <div class="doc-band"></div>
        <h2 class="list-title">ตารางรายชื่อพนักงาน / Employee List</h2>
        <table class="items-table list-table" style="font-size: 12px;">
            <thead>
                <tr>
                    <th style="width: 3%; text-align: center;">ลำดับ<br><span class="en-label">No.</span></th>
                    <th style="width: 9%; text-align: center;">รูปถ่าย<br><span class="en-label">Photo</span></th>
                    <th style="width: 8%;">รหัสลูกจ้าง<br><span class="en-label">Employee ID</span></th>
                    <th style="width: 17%;">ชื่อ-นามสกุล<br><span class="en-label">Name</span></th>
                    <th style="width: 9%; text-align: center;">สัญชาติ<br><span class="en-label">Nationality</span></th>
                    <th style="width: 11%;">เลขพาสปอร์ต<br><span class="en-label">Passport No.</span></th>
                    <th style="width: 11%;">เลขประจำตัว<br><span class="en-label">ID No.</span></th>
                    <th style="width: 11%;">เลขใบอนุญาตทำงาน<br><span class="en-label">Work Permit No.</span></th>
                    <th style="width: 11%;">หมายเหตุ<br><span class="en-label">Notes</span></th>
                    <th style="width: 10%; text-align: right;">ราคาต่อหัว<br><span class="en-label">Price</span></th>
                </tr>
            </thead>
            <tbody>
                @foreach($employeeList as $emp)
                <tr>
                    <td style="text-align: center; vertical-align: middle;">{{ $emp['index'] }}</td>
                    <td style="text-align: center; vertical-align: middle;">
                        @if(!empty($emp['image']))
                            <img src="{{ asset('storage/' . $emp['image']) }}" alt="Photo" style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px;">
                        @else
                            <div style="width: 50px; height: 50px; background-color: #f1f5f9; border-radius: 8px; display: inline-block; line-height: 50px; color: #94a3b8; font-size: 10px;">No Image</div>
                        @endif
                    </td>
                    <td style="vertical-align: middle;">{{ $emp['employee_id'] ?: '-' }}</td>
                    <td style="vertical-align: middle;">{{ $emp['prefix'] }} {{ $emp['name'] }}</td>
                    <td style="text-align: center; vertical-align: middle;">{{ $emp['nationality'] ?: '-' }}</td>
                    <td style="vertical-align: middle;">{{ $emp['passport'] ?: '-' }}</td>
                    <td style="vertical-align: middle;">{{ $emp['id_number'] ?: '-' }}</td>
                    <td style="vertical-align: middle;">{{ $emp['work_permit'] ?: '-' }}</td>
                    <td style="vertical-align: middle;">{{ !empty($emp['note']) ? $emp['note'] : '-' }}</td>
                    <td style="text-align: right; vertical-align: middle;">{{ number_format($emp['price'], 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</body>
</html>
