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
        /* Employee list page (same idea as the invoice's) */
        .list-page { margin-top: 20px; min-height: 0; }
        .list-page.list-only { margin-top: 0; }
        .list-head { display: flex; justify-content: space-between; align-items: flex-end; gap: 16px; margin-bottom: 12px; padding-bottom: 8px; border-bottom: 2px solid #e5e7eb; }
        .list-head .doc-title { letter-spacing: 0; }
        .list-table { font-size: 11px; table-layout: auto; }
        .list-table th { font-size: 9.5px; text-transform: none; letter-spacing: 0; padding: 4px 3px; line-height: 1.2; white-space: nowrap; }
        .list-table .en-label { font-weight: 400; color: #6b7280; font-size: 9px; }
        .list-table td { font-size: 10.5px; padding: 3px; vertical-align: middle; white-space: nowrap; }
        .list-table td.list-name { white-space: normal; overflow-wrap: anywhere; line-height: 1.25; }
        .list-table thead { display: table-header-group; }
        .list-table tfoot { display: table-row-group; } /* totals once, at the end */
        .list-table tr { page-break-inside: avoid; break-inside: avoid; }
        .list-photo { width: 42px; height: 42px; object-fit: cover; border-radius: 6px; display: block; margin: 0 auto; }
        .list-no-photo { background: #f1f5f9; color: #94a3b8; font-size: 8px; line-height: 42px; text-align: center; }
        @media print {
            body { background: #fff; padding: 0; }
            .page { box-shadow: none; padding: 0; margin: 0; min-height: 0; max-width: none; }
            .list-page:not(.list-only) { page-break-before: always; break-before: page; }
            .no-print { display: none !important; }
            @page { margin: 12mm; size: A4 portrait; }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()">🖨️ Print / Save as PDF</button>
    </div>

    @php $reminderDocNo = 'RMD-' . $production->id . '-' . date('Ymd-Hi'); @endphp
    @unless(!empty($listOnly))
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
                    <strong>Doc No:</strong> {{ $reminderDocNo }}<br>
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

        {{-- The employee list is its own page after this one (below) — same as the
             invoice — so a bill with 100 people doesn't push the total and
             signatures pages away. Here: just say it's attached. --}}
        @if(!empty($includeEmployeeList) && !empty($employeeList))
            <div class="text-sm text-muted mt-8"><strong>แนบรายชื่อลูกจ้าง / Employee list attached:</strong> {{ count($employeeList) }} คน (หน้าถัดไป / next page)</div>
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
    @endunless

    {{-- Employee list page — same layout as the invoice's (documents/layout.blade.php:
         photo, IDs, price), plus what each person still owes on a bill collected
         individually. Rows never split across printed pages; the header repeats. --}}
    @if(!empty($includeEmployeeList) && !empty($employeeList))
        @php
            // A lump-sum bill leaves paid_so_far null for everyone: no paid/remaining columns.
            $hasPaidColumn = collect($employeeList)->contains(fn ($e) => ($e['paid_so_far'] ?? null) !== null);
            $listPriceTotal = collect($employeeList)->sum(fn ($e) => (float) $e['price']);
            $listPaidTotal = collect($employeeList)->sum(fn ($e) => (float) ($e['paid_so_far'] ?? 0));
            $listRemainingTotal = collect($employeeList)->sum(fn ($e) => max(0, (float) $e['price'] - (float) ($e['paid_so_far'] ?? 0)));
        @endphp
        <div class="page list-page {{ !empty($listOnly) ? 'list-only' : '' }}">
            <div class="list-head">
                <div>
                    <h3 class="doc-title">ตารางรายชื่อพนักงาน / Employee List</h3>
                    <div class="text-sm text-muted">{{ $production->project_name ?? '-' }} · {{ count($employeeList) }} คน</div>
                </div>
                <div class="text-sm text-right text-muted">
                    แนบใบทวงถาม / Attached to reminder<br>
                    <strong>{{ $reminderDocNo }}</strong>
                </div>
            </div>
            <table class="list-table">
                <thead>
                    <tr>
                        <th class="text-center">ลำดับ<br><span class="en-label">No.</span></th>
                        <th class="text-center">รูปถ่าย<br><span class="en-label">Photo</span></th>
                        <th>รหัสลูกจ้าง<br><span class="en-label">Employee ID</span></th>
                        <th style="width: 99%;">ชื่อ-นามสกุล<br><span class="en-label">Name</span></th>
                        <th class="text-center">สัญชาติ<br><span class="en-label">Nationality</span></th>
                        <th>เลขพาสปอร์ต<br><span class="en-label">Passport No.</span></th>
                        <th>เลขประจำตัว<br><span class="en-label">ID No.</span></th>
                        <th>ใบอนุญาต<br><span class="en-label">Work Permit</span></th>
                        <th class="text-right">ราคาต่อหัว<br><span class="en-label">Price</span></th>
                        @if($hasPaidColumn)
                            <th class="text-right">จ่ายแล้ว<br><span class="en-label">Paid</span></th>
                            <th class="text-right">คงเหลือ<br><span class="en-label">Remaining</span></th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach($employeeList as $emp)
                        @php $empRemaining = max(0, (float) $emp['price'] - (float) ($emp['paid_so_far'] ?? 0)); @endphp
                        <tr>
                            <td class="text-center">{{ $emp['index'] }}</td>
                            <td class="text-center">
                                @if(!empty($emp['image']))
                                    <img class="list-photo" src="{{ asset('storage/' . $emp['image']) }}" alt="Photo">
                                @else
                                    <div class="list-photo list-no-photo">No Image</div>
                                @endif
                            </td>
                            <td>{{ $emp['employee_id'] ?: '-' }}</td>
                            <td class="list-name">
                                {{ trim(($emp['prefix'] ?? '') . ' ' . $emp['name']) }}
                                @if(!empty($emp['note']))<div class="text-muted" style="font-size: 10px;">{{ $emp['note'] }}</div>@endif
                            </td>
                            <td class="text-center">{{ $emp['nationality'] ?: '-' }}</td>
                            <td>{{ $emp['passport'] ?: '-' }}</td>
                            <td>{{ $emp['id_number'] ?: '-' }}</td>
                            <td>{{ $emp['work_permit'] ?: '-' }}</td>
                            <td class="amount">{{ number_format($emp['price'], 2) }}</td>
                            @if($hasPaidColumn)
                                <td class="amount">{{ number_format($emp['paid_so_far'] ?? 0, 2) }}</td>
                                <td class="amount {{ $empRemaining > 0 ? 'fw-bold text-danger' : '' }}">{{ number_format($empRemaining, 2) }}</td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="8" class="text-right">Total / รวม</td>
                        <td class="amount">{{ number_format($listPriceTotal, 2) }}</td>
                        @if($hasPaidColumn)
                            <td class="amount">{{ number_format($listPaidTotal, 2) }}</td>
                            <td class="amount text-danger">{{ number_format($listRemainingTotal, 2) }}</td>
                        @endif
                    </tr>
                </tfoot>
            </table>
        </div>
    @endif
</body>
</html>
