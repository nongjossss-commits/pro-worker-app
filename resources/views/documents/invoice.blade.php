<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>{{ ucfirst($type ?? 'Document') }}</title>
    <style>
        body { font-family: 'Sarabun', sans-serif; margin: 0; padding: 20px; color: #333; }
        .page { max-width: 210mm; margin: 0 auto; background: white; padding: 40px; border: 1px solid #ddd; box-shadow: 0 0 10px rgba(0,0,0,0.1); position: relative; min-height: 297mm; box-sizing: border-box;}
        @media print {
            .page { border: none; box-shadow: none; padding: 0; margin: 0; width: 100%; height: auto;}
            @page { margin: 10mm; }
            .no-print { display: none; }
        }
        .header { display: flex; justify-content: space-between; margin-bottom: 30px; border-bottom: 2px solid #eee; padding-bottom: 20px; }
        .company-info h2 { margin: 0 0 5px 0; color: #F97316; }
        .company-info p { margin: 2px 0; font-size: 14px; color: #666; }
        .doc-meta { text-align: right; }
        .doc-meta h1 { margin: 0 0 10px 0; font-size: 24px; text-transform: uppercase; letter-spacing: 1px; }
        .meta-row { margin-bottom: 5px; font-size: 14px; }
        .meta-label { font-weight: bold; display: inline-block; width: 80px; }

        .client-info { margin-bottom: 30px; background: #f9fafb; padding: 15px; border-radius: 5px; }
        .section-title { font-weight: bold; margin-bottom: 5px; font-size: 14px; text-transform: uppercase; color: #888; }

        table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        th { background: #f3f4f6; padding: 10px; text-align: left; font-weight: bold; border-bottom: 2px solid #ddd; font-size: 14px; }
        td { padding: 10px; border-bottom: 1px solid #eee; font-size: 14px; vertical-align: top; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }

        .totals { width: 40%; margin-left: auto; }
        .total-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #eee; }
        .total-row.final { border-top: 2px solid #333; border-bottom: double 4px #333; font-weight: bold; font-size: 18px; margin-top: 10px; }

        .footer { position: absolute; bottom: 40px; left: 40px; right: 40px; border-top: 1px solid #eee; padding-top: 20px; font-size: 12px; color: #888; text-align: center; }
        .signatures { display: flex; justify-content: space-between; margin-top: 50px; margin-bottom: 50px; }
        .sig-box { text-align: center; width: 30%; }
        .sig-line { border-bottom: 1px solid #ccc; margin-bottom: 10px; height: 40px; }
    </style>
    <!-- Google Fonts for Sarabun -->
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="no-print" style="text-align: center; margin-bottom: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; font-size: 16px; background: #333; color: white; border: none; cursor: pointer;">Print / Save as PDF</button>
    </div>

    <div class="page">
        <!-- Header -->
        <div class="header">
            <div class="company-info">
                @if($profile->logo_path)
                    <img src="{{ asset('storage/' . $profile->logo_path) }}" alt="Logo" style="height: 60px; margin-bottom: 10px;">
                @endif
                <h2>{{ $profile->name }}</h2>
                <p>{{ $profile->address }}</p>
                <p>Tax ID: {{ $profile->tax_id }}</p>
            </div>
            <div class="doc-meta">
                <h1>{{ ucfirst($type) }}</h1>
                <div class="meta-row"><span class="meta-label">No:</span> {{ $production->financial_data['quotation_no'] ?? $production->id }}</div>
                <div class="meta-row"><span class="meta-label">Date:</span> {{ date('d/m/Y') }}</div>
                <div class="meta-row"><span class="meta-label">Project:</span> {{ $production->project_name }}</div>
            </div>
        </div>

        <!-- Client Info -->
        <div class="client-info">
            <div class="section-title">Bill To:</div>
            <div style="font-size: 16px; font-weight: bold;">{{ $production->employer->name_en ?? $production->employer->name_th ?? 'N/A' }}</div>
            <div>{{ $production->employer->address ?? '' }}</div>
            <div>Tel: {{ $production->employer->phone ?? '-' }}</div>
        </div>

        <!-- Items Table -->
        <table>
            <thead>
                <tr>
                    <th style="width: 5%">#</th>
                    <th style="width: 60%">Description</th>
                    <th class="text-right" style="width: 15%">Due Date</th>
                    <th class="text-right" style="width: 20%">Amount (THB)</th>
                </tr>
            </thead>
            <tbody>
                <!-- If type is Quotation, show full breakdown, otherwise showing payment schedule might be confusing?
                     Usually Invoice/Quotation lists "Services" not "Installments".
                     But user requested installment system.
                     For simplicity, we list the transactions as line items for now if they exist,
                     otherwise use generic "Service Fee".
                -->
                @forelse($transactions as $index => $t)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>
                            <strong>{{ ucfirst(str_replace('_', ' ', $t->type)) }}</strong>
                            @if($t->notes)<br><span style="color: #666; font-size: 12px;">{{ $t->notes }}</span>@endif
                        </td>
                        <td class="text-right">{{ $t->due_date ? $t->due_date->format('d/m/Y') : '-' }}</td>
                        <td class="text-right">{{ number_format($t->amount, 2) }}</td>
                    </tr>
                @empty
                     <tr>
                        <td>1</td>
                        <td>{{ $production->description ?: 'Service Fee' }}</td>
                        <td class="text-right">-</td>
                        <td class="text-right">{{ number_format($production->financial_data['total_amount'] ?? 0, 2) }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Totals -->
        <div class="totals">
            @php
                $subtotal = $transactions->sum('amount') > 0 ? $transactions->sum('amount') : ($production->financial_data['total_amount'] ?? 0);
                $vat = 0; // Logic for VAT if needed later
                $total = $subtotal + $vat;
            @endphp
            <div class="total-row">
                <span>Subtotal</span>
                <span>{{ number_format($subtotal, 2) }}</span>
            </div>
            @if($vat > 0)
            <div class="total-row">
                <span>VAT (7%)</span>
                <span>{{ number_format($vat, 2) }}</span>
            </div>
            @endif
            <div class="total-row final">
                <span>Total</span>
                <span>{{ number_format($total, 2) }} THB</span>
            </div>
        </div>

        <!-- Signatures -->
        <div class="signatures">
            <div class="sig-box">
                <div class="sig-line"></div>
                <div>Customer Signature</div>
            </div>
            <div class="sig-box">
                <div class="sig-line"></div>
                <div>Authorized Signature</div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            Thank you for your business.
            <br>
            Please make payment to: Bank Name, Account No: XXX-X-XXXXX-X
        </div>
    </div>
</body>
</html>
