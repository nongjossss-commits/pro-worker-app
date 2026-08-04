<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>{{ $page_title ?? 'ใบทวงถามยอดคงค้าง — ' . ($production->project_name ?? 'โครงการ') }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Sarabun', sans-serif; margin: 0; padding: 20px; background: #f3f4f6; color: #1f2937; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .page { max-width: 210mm; margin: 0 auto; background: #fff; padding: 24px 32px; box-shadow: 0 0 12px rgba(0,0,0,0.08); min-height: 297mm; box-sizing: border-box; position: relative; }
        h1, h2, h3, h4 { margin: 0; font-weight: 700; }
        .text-right { text-align: right; } .text-center { text-align: center; } .text-muted { color: #6b7280; }
        .text-danger { color: #dc2626; } .text-primary { color: #f97316; } .text-sm { font-size: 0.85rem; }
        .fw-bold { font-weight: 700; } .mt-8 { margin-top: 24px; } .mb-4 { margin-bottom: 12px; } .mb-8 { margin-bottom: 24px; }
        .border-b { border-bottom: 1px solid #e5e7eb; padding-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        th, td { padding: 8px 10px; border-bottom: 1px solid #e5e7eb; text-align: left; vertical-align: top; font-size: 0.9rem; }
        th { background: #f9fafb; font-weight: 600; color: #374151; font-size: 0.82rem; text-transform: uppercase; letter-spacing: 0.5px; }
        td.amount { text-align: right; font-variant-numeric: tabular-nums; }
        tfoot td { background: #f9fafb; font-weight: 700; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
        .outstanding-box { background: #fef2f2; border: 2px solid #dc2626; border-radius: 8px; padding: 16px 20px; margin-top: 24px; display: flex; justify-content: space-between; align-items: center; }
        .outstanding-box .amount-huge { font-size: 2.2rem; font-weight: 700; color: #dc2626; line-height: 1; }
        .doc-title { color: #dc2626; letter-spacing: 1px; }
        .no-print { position: fixed; top: 20px; right: 20px; z-index: 999; }
        .no-print button { padding: 10px 18px; background: #f97316; color: #fff; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; }
        @media print { body { background: #fff; padding: 0; } .page { box-shadow: none; padding: 0; margin: 0; } .no-print { display: none !important; } @page { margin: 12mm; size: A4 portrait; } }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()">🖨️ Print / Save as PDF</button>
    </div>

    <div class="page">
        {{-- Header — biller profile (หัวบิลจาก finance tab) wins over the default
             CompanyProfile. Same pattern as documents/layout.blade.php so both
             invoice and reminder show the correct issuer header. --}}
        @php
            $issuer = isset($billerProfile) ? $billerProfile : $profile;
            $issuerLogo = $issuer->logo_path ?? null;
            $issuerName = $issuer->name ?? 'Company Name';
            $issuerAddress = $issuer->address ?? '';
            $issuerTaxId = $issuer->tax_id ?? '-';
            $issuerPhone = $issuer->phone ?? '-';
        @endphp
        <div style="display: flex; justify-content: space-between; align-items: flex-start;" class="border-b mb-8">
            <div style="display: flex; gap: 14px;">
                @if($issuerLogo)
                    <img src="{{ asset('storage/' . $issuerLogo) }}" alt="Logo" style="width: 70px; height: auto; max-height: 70px; object-fit: contain;">
                @endif
                <div>
                    <h3 class="text-primary">{{ $issuerName }}</h3>
                    <div class="text-sm text-muted" style="white-space: pre-line;">{{ $issuerAddress }}</div>
                    <div class="text-sm text-muted">Tax ID: {{ $issuerTaxId }} | Tel: {{ $issuerPhone }}</div>
                </div>
            </div>
            <div class="text-right">
                <h1 class="doc-title">ใบทวงถามยอดคงค้าง</h1>
                <div class="text-sm text-muted" style="margin-top: 4px;">PAYMENT REMINDER</div>
                <div class="text-sm" style="margin-top: 8px;">
                    <strong>Doc No:</strong> RMD-{{ $production->id }}-{{ date('Ymd-Hi') }}<br>
                    <strong>Date:</strong> {{ date('d/m/Y') }}
                </div>
            </div>
        </div>

        {{-- Customer + Project — prefer explicit customer FinancialProfile,
             else fall back to $billTo (customer_override or production->employer). --}}
        <div class="grid-2 mb-8">
            <div>
                <h4 class="text-muted text-sm">BILL TO / เรียกเก็บจาก</h4>
                @if(isset($customerProfile))
                    <div class="fw-bold" style="margin-top: 6px;">{{ $customerProfile->name }}</div>
                    <div class="text-sm text-muted" style="margin-top: 4px;">
                        @if(!empty($customerProfile->address)){!! nl2br(e($customerProfile->address)) !!}<br>@endif
                        @if(!empty($customerProfile->tax_id))Tax ID: {{ $customerProfile->tax_id }}<br>@endif
                        @if(!empty($customerProfile->phone))Tel: {{ $customerProfile->phone }}@endif
                    </div>
                @else
                    <div class="fw-bold" style="margin-top: 6px;">{{ $billTo->employerNameTh ?? 'Customer' }}</div>
                    <div class="text-sm text-muted" style="margin-top: 4px;">
                        @if(!empty($billTo->employerAddress)){{ $billTo->employerAddress }}<br>@endif
                        @if(isset($billTo->tax_id) && $billTo->tax_id !== '-')Tax ID: {{ $billTo->tax_id }}<br>@endif
                        @if(isset($billTo->employerTaxId) && $billTo->employerTaxId !== '-')Tax ID: {{ $billTo->employerTaxId }}<br>@endif
                        @if(!empty($billTo->employerPhone))Tel: {{ $billTo->employerPhone }}@endif
                    </div>
                @endif
            </div>
            <div class="text-right">
                <h4 class="text-muted text-sm">PROJECT / โครงการ</h4>
                <div class="fw-bold" style="margin-top: 6px;">{{ $production->project_name ?? '-' }}</div>
                <div class="text-sm text-muted" style="margin-top: 4px;">Reference: #PROD-{{ $production->id }}</div>
            </div>
        </div>

        <div class="text-sm mb-4">
            <strong>เรียน ท่านลูกค้าที่นับถือ:</strong>
            เอกสารฉบับนี้เป็นการทวงถามยอดค้างชำระของบิลที่แนบไว้ด้านล่าง โปรดตรวจสอบและชำระยอดคงเหลือให้ครบถ้วน
        </div>

        {{-- Bills list --}}
        @php
            $totalBilled = 0;
            $totalPaid = 0;
            $totalOutstanding = 0;
            foreach ($transactions as $txn) {
                $totalBilled += (float) $txn->amount;
                $totalPaid += (float) $txn->paid_amount;
                $totalOutstanding += max(0, (float) $txn->amount - (float) $txn->paid_amount);
            }
        @endphp

        <h4 class="text-muted text-sm mb-4">รายการบิลค้างชำระ</h4>
        <table>
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 38%;">Description (รายการ)</th>
                    <th class="text-center" style="width: 14%;">Due Date</th>
                    <th class="text-right" style="width: 14%;">Bill Amount</th>
                    <th class="text-right" style="width: 14%;">Paid</th>
                    <th class="text-right" style="width: 15%;">Outstanding</th>
                </tr>
            </thead>
            <tbody>
                @foreach($transactions as $index => $txn)
                    @php
                        $rowOutstanding = max(0, (float) $txn->amount - (float) $txn->paid_amount);
                        $isSettled = $rowOutstanding <= 0.005;
                        $desc = trim($txn->description ?? '');
                        if ($desc === '') $desc = 'งวดที่ ' . ($index + 1) . ' (' . ($txn->type ?? 'installment') . ')';
                    @endphp
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>
                            {{ $desc }}
                            <div class="text-sm text-muted">Bill No: #{{ $production->id }}-{{ $txn->id }}</div>
                        </td>
                        <td class="text-center">{{ $txn->due_date ? \Carbon\Carbon::parse($txn->due_date)->format('d/m/Y') : '-' }}</td>
                        <td class="amount">{{ number_format($txn->amount, 2) }}</td>
                        <td class="amount">{{ number_format($txn->paid_amount, 2) }}</td>
                        <td class="amount {{ $isSettled ? '' : 'fw-bold text-danger' }}">{{ number_format($rowOutstanding, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" class="text-right">Total / รวม</td>
                    <td class="amount">{{ number_format($totalBilled, 2) }}</td>
                    <td class="amount">{{ number_format($totalPaid, 2) }}</td>
                    <td class="amount text-danger">{{ number_format($totalOutstanding, 2) }}</td>
                </tr>
            </tfoot>
        </table>

        {{-- Payment History --}}
        @php
            $anyPayments = false;
            foreach ($transactions as $txn) {
                if ($txn->payments && $txn->payments->count() > 0) { $anyPayments = true; break; }
            }
        @endphp

        @if($anyPayments)
            <h4 class="text-muted text-sm mt-8 mb-4">ประวัติการชำระที่บันทึกไว้</h4>
            <table>
                <thead>
                    <tr>
                        <th style="width: 8%;">#</th>
                        <th style="width: 22%;">Bill (บิล)</th>
                        <th class="text-center" style="width: 16%;">Paid On</th>
                        <th style="width: 34%;">Method / Notes</th>
                        <th class="text-right" style="width: 20%;">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @php $rowNum = 0; @endphp
                    @foreach($transactions as $txn)
                        @if($txn->payments && $txn->payments->count() > 0)
                            @foreach($txn->payments as $payment)
                                @php $rowNum++; @endphp
                                <tr>
                                    <td>{{ $rowNum }}</td>
                                    <td>#{{ $production->id }}-{{ $txn->id }}</td>
                                    <td class="text-center">{{ $payment->paid_at ? \Carbon\Carbon::parse($payment->paid_at)->format('d/m/Y') : '-' }}</td>
                                    <td class="text-sm">
                                        @if($payment->bank_account_id && $payment->bankAccount)
                                            <span>{{ $payment->bankAccount->bank_name ?? '' }}</span>
                                            @if($payment->bankAccount->account_number)
                                                <span class="text-muted"> — {{ $payment->bankAccount->account_number }}</span>
                                            @endif
                                            <br>
                                        @endif
                                        @if($payment->notes)
                                            <span class="text-muted">{{ $payment->notes }}</span>
                                        @endif
                                        @if(!$payment->bank_account_id && !$payment->notes)
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="amount">{{ number_format($payment->amount, 2) }}</td>
                                </tr>
                            @endforeach
                        @endif
                    @endforeach
                </tbody>
            </table>
        @endif

        {{-- Outstanding highlight --}}
        <div class="outstanding-box">
            <div>
                <div class="text-sm text-muted" style="letter-spacing: 1px;">TOTAL OUTSTANDING</div>
                <div class="text-sm fw-bold" style="margin-top: 4px;">ยอดคงค้างรวมที่ต้องชำระ</div>
                <div class="text-sm text-muted" style="margin-top: 8px; max-width: 65%;">
                    กรุณาชำระยอดคงเหลือให้ครบถ้วน หากท่านได้ชำระเรียบร้อยแล้ว โปรดละเว้นเอกสารฉบับนี้
                </div>
            </div>
            <div class="text-right">
                <div class="amount-huge">{{ number_format($totalOutstanding, 2) }}</div>
                <div class="text-sm text-muted">THB</div>
            </div>
        </div>

        {{-- Payment methods — only shows what the operator ticked in the
             "Issue Bill" modal (payment_methods=... in the URL). No more
             hardcoded "take the first 3 company bank accounts" query, which
             was leaking every stored bank onto every reminder regardless of
             what the operator picked. Same source & structure as the invoice
             template (documents/layout.blade.php). --}}
        @if(!empty($paymentMethods))
            @php $bankPresets = collect(config('thai_banks', []))->keyBy('code'); @endphp
            <div class="mt-8">
                <h4 class="text-muted text-sm mb-4">ช่องทางการชำระเงิน / Payment Information</h4>
                <table>
                    <thead>
                        <tr>
                            <th style="width: 25%;">Method</th>
                            <th style="width: 40%;">Account Name / Details</th>
                            <th>Account Number</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($paymentMethods as $pm)
                            @php $ptype = $pm['type'] ?? ''; @endphp
                            @if($ptype === 'cash')
                                <tr>
                                    <td>เงินสด / Cash</td>
                                    <td class="text-muted">—</td>
                                    <td class="text-muted">—</td>
                                </tr>
                            @elseif($ptype === 'promptpay')
                                <tr>
                                    <td>PromptPay</td>
                                    <td>{{ $pm['promptpay_id'] ?? '-' }}</td>
                                    <td class="text-muted">—</td>
                                </tr>
                            @elseif($ptype === 'transfer')
                                <tr>
                                    <td>{{ $pm['bank_name'] ?? '-' }}</td>
                                    <td>{{ $pm['account_name'] ?? '-' }}</td>
                                    <td class="fw-bold">{{ $pm['account_number'] ?? '-' }}</td>
                                </tr>
                            @elseif($ptype === 'other')
                                <tr>
                                    <td>อื่นๆ / Other</td>
                                    <td>{{ $pm['note'] ?? '-' }}</td>
                                    <td class="text-muted">—</td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        {{-- Signature --}}
        <div class="mt-8 grid-2" style="margin-top: 40px;">
            <div class="text-center">
                <div style="border-bottom: 1px solid #ccc; height: 40px; margin-bottom: 6px;"></div>
                <div class="text-sm text-muted">ผู้รับ / Customer Signature</div>
            </div>
            <div class="text-center">
                <div style="border-bottom: 1px solid #ccc; height: 40px; margin-bottom: 6px;"></div>
                <div class="text-sm text-muted">ผู้ออกเอกสาร / Authorized</div>
                <div class="text-sm text-muted">{{ $issuerName }}</div>
            </div>
        </div>
    </div>
</body>
</html>
