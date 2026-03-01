@extends('layouts.document')

@section('title', 'Receipt - ' . $production->project_name)
@section('document_title', 'RECEIPT (ใบเสร็จรับเงิน)')

@section('content')
<table>
    <thead>
        <tr>
            <th style="width: 5%;">#</th>
            <th style="width: 50%;">Description</th>
            <th class="text-center" style="width: 15%;">Paid Date</th>
            <th class="text-right" style="width: 15%;">Amount Paid</th>
        </tr>
    </thead>
    <tbody>
        @php
            $grandTotal = 0;

            // Tax Config
            $vatRate = isset($financial['vat_rate']) ? (float)$financial['vat_rate'] : 7;
            $vatIncluded = $financial['vat_included'] ?? false;
            $whtEnabled = $financial['wht_enabled'] ?? false;
            $whtRate = isset($financial['wht_rate']) ? (float)$financial['wht_rate'] : 3;
            $discount = $financial['discount'] ?? 0;
        @endphp

        @forelse($transactions as $index => $t)
            @php
                $amount = $t->paid_amount; // Use actual PAID amount
                $grandTotal += $amount;
            @endphp
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>
                    <strong>{{ ucfirst(str_replace('_', ' ', $t->type)) }}</strong>
                    @if($t->notes) <br><span class="text-sm text-muted">{{ $t->notes }}</span> @endif
                </td>
                <td class="text-center">{{ $t->updated_at ? date('d/m/Y', strtotime($t->updated_at)) : '-' }}</td>
                <td class="amount">{{ number_format($t->paid_amount, 2) }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="4" class="text-center py-4 text-muted">No completed payments selected.</td>
            </tr>
        @endforelse
    </tbody>
    <tfoot>
        @php
            // Reverse Calc from Total Received (GrandTotal)
            // GrandTotal = Received (Inc VAT)

            // NetBase + VAT = GrandTotal
            $netBase = $grandTotal / (1 + ($vatRate / 100));
            $vatAmount = $grandTotal - $netBase;

            // WHT Note
            // Receipt usually confirms the GROSS invoice amount (Total Received + WHT if the customer deducted it?)
            // OR checks "Net Received".
            // Standard Thai Receipt: Shows the Invoice Value.
            // But here we are printing based on "Paid Amount".

            // If customer paid 97 (100 - 3 WHT).
            // DB 'paid_amount' = 97.
            // GrandTotal = 97.
            // But the Invoice was 100.

            // Ideally, 'paid_amount' should track the Bank Transfer Amount.
            // If WHT is involved, the Receipt should acknowledge the Full Debt Cleared?
            // "Received 97 Cash + 3 Tax Certificate".

            // Since we don't track WHT deduction per transaction in DB (just 'paid_amount'),
            // we will display the "Amount Received" as the main figure.
            // And maybe calculation of Base/VAT based on that received amount.

            // NOTE: If WHT enabled, likely the 'paid_amount' stored is Net.
            // So we might need to "Gross Up" to show the full receipt value?
            // Gross = Net / (1 - WHT)? No, WHT is on Base.
            // Invoice 107 (100+7). WHT 3 (3% of 100). Net Pay 104.
            // If paid_amount = 104.
            // We need to show: Services 100, VAT 7, Total 107.
            // Less WHT 3. Net Received 104.

            // If we assume `paid_amount` is the Net Received:
            // Let Base = X.
            // Paid = (X * 1.07) - (X * 0.03) = X * (1.04).
            // So Base = Paid / 1.04.

            // However, if VAT Excluded:
            // Paid = (X + X*0.07) - (X*0.03). Same formula (if WHT on base).

            // Let's implement Gross Up logic if WHT enabled to show full receipt.

            if ($whtEnabled) {
                // GrossUp Factor = 1 + VAT - WHT
                $factor = 1 + ($vatRate/100) - ($whtRate/100);
                $realBase = $grandTotal / $factor;

                $vatAmount = $realBase * ($vatRate/100);
                $whtAmount = $realBase * ($whtRate/100);
                $fullInvoiceValue = $realBase + $vatAmount;
            } else {
                $realBase = $netBase; // Calculated earlier
                $whtAmount = 0;
                $fullInvoiceValue = $grandTotal;
            }
        @endphp

        <!-- Breakdown -->
        <tr>
            <td colspan="2" style="border: none;"></td>
            <td class="text-right text-muted">Base Amount</td>
            <td class="amount">{{ number_format($realBase, 2) }}</td>
        </tr>
        <tr>
            <td colspan="2" style="border: none;"></td>
            <td class="text-right text-muted">VAT {{ $vatRate }}%</td>
            <td class="amount">{{ number_format($vatAmount, 2) }}</td>
        </tr>
        <tr>
            <td colspan="2" style="border: none;"></td>
            <td class="text-right font-bold text-primary">Total Invoice Value</td>
            <td class="amount font-bold text-primary">{{ number_format($fullInvoiceValue, 2) }}</td>
        </tr>

        @if($whtEnabled)
        <tr>
            <td colspan="2" style="border: none;"></td>
            <td class="text-right text-danger small">Less WHT {{ $whtRate }}%</td>
            <td class="amount text-danger small">-{{ number_format($whtAmount, 2) }}</td>
        </tr>
        <tr>
            <td colspan="2" style="border: none;"></td>
            <td class="text-right font-bold text-success" style="font-size: 1.1em;">Net Received</td>
            <td class="amount font-bold text-success" style="font-size: 1.1em;">{{ number_format($grandTotal, 2) }}</td>
        </tr>
        @endif

         <tr>
             <td colspan="4" class="text-right text-muted text-sm" style="border: none; padding-top: 5px;">
                 ( {{ \App\Helpers\ThaiBahtHelper::toText($fullInvoiceValue) ?? 'Baht' }} )
             </td>
        </tr>
    </tfoot>
</table>
@endsection
