@extends('layouts.document')

@section('title', 'Credit Note - ' . $production->project_name)
@section('document_title', 'DEBT REDUCTION NOTE (ใบลดหนี้)')

@section('content')
<div class="mb-4 p-4 bg-gray-50 border rounded text-sm">
    <strong>Reason for Adjustment:</strong> Refund for difference between Paid Head Count and Actual Delivered Head Count.
</div>

<table>
    <thead>
        <tr>
            <th style="width: 50%;">Description</th>
            <th class="text-center" style="width: 15%;">Head Count</th>
            <th class="text-right" style="width: 15%;">Unit Price</th>
            <th class="text-right" style="width: 20%;">Amount</th>
        </tr>
    </thead>
    <tbody>
        @php
            $actualCount = request('actual_count', $production->items->count());
            $unitPrice = $production->financial_data['unit_price'] ?? 0;
            $refundAmount = request('refund_amount', 0);

            // VAT Logic
            $vatRate = $production->financial_data['vat_rate'] ?? 7;
            $vatIncluded = $production->financial_data['vat_included'] ?? false;

            // Calculate Paid Heads (reverse engineer)
            // Assuming Total Paid represents a certain number of heads
            $totalPaid = \App\Models\FinancialTransaction::where('production_order_id', $production->id)->sum('paid_amount');
            $paidHeads = ($unitPrice > 0) ? floor($totalPaid / $unitPrice) : 0;

            $diffHeads = max(0, $paidHeads - $actualCount);

            // Calculate Refund Breakdown
            $refundSubtotal = 0;
            $refundVat = 0;

            if ($vatIncluded) {
                // Refund Amount is Total (Inc VAT)
                $refundSubtotal = $refundAmount / (1 + ($vatRate / 100));
                $refundVat = $refundAmount - $refundSubtotal;
            } else {
                // Refund Amount was likely calculated as (Diff * UnitPrice) + VAT?
                // Wait, JS logic for refundAmount:
                // if (!vatIncluded) deliveredValue = deliveredValue * (1 + Rate);
                // refund = Paid - deliveredValue.
                // So refundAmount IS the Total Refund (Inc VAT if applicable).
                $refundSubtotal = $refundAmount / (1 + ($vatRate / 100));
                 $refundVat = $refundAmount - $refundSubtotal;
            }
        @endphp

        <tr>
            <td>
                <strong>Paid Amount / Head Count</strong><br>
                <span class="text-muted text-sm">Total amount received from customer</span>
            </td>
            <td class="text-center">{{ $paidHeads }}</td>
            <td class="amount">{{ number_format($unitPrice, 2) }}</td>
            <td class="amount">{{ number_format($paidHeads * $unitPrice, 2) }}</td>
        </tr>

        <tr>
            <td>
                <strong>Actual Delivered Head Count</strong><br>
                <span class="text-muted text-sm">Final count of employees processed</span>
            </td>
            <td class="text-center">{{ $actualCount }}</td>
            <td class="amount">{{ number_format($unitPrice, 2) }}</td>
            <td class="amount">{{ number_format($actualCount * $unitPrice, 2) }}</td>
        </tr>

        <tr style="background-color: #fff1f2;">
            <td>
                <strong class="text-danger">Refund / Credit Adjustment</strong><br>
                <span class="text-muted text-sm">Return for {{ $diffHeads }} person(s)</span>
            </td>
            <td class="text-center text-danger font-bold">-{{ $diffHeads }}</td>
            <td class="amount text-danger">{{ number_format($unitPrice, 2) }}</td>
            <td class="amount text-danger font-bold">-{{ number_format($refundAmount, 2) }}</td>
        </tr>
    </tbody>
    <tfoot>
        <!-- Refund Breakdown -->
        <tr>
            <td colspan="2" style="border: none;"></td>
            <td class="text-right text-muted">Refund Subtotal</td>
            <td class="amount text-danger">{{ number_format($refundSubtotal, 2) }}</td>
        </tr>
        <tr>
            <td colspan="2" style="border: none;"></td>
            <td class="text-right text-muted">VAT Correction ({{ $vatRate }}%)</td>
            <td class="amount text-danger">{{ number_format($refundVat, 2) }}</td>
        </tr>
        <tr>
            <td colspan="2" style="border: none;"></td>
            <td class="text-right font-bold text-danger" style="font-size: 1.1em;">Total Refund</td>
            <td class="amount font-bold text-danger" style="font-size: 1.1em;">{{ number_format($refundAmount, 2) }}</td>
        </tr>
         <tr>
             <td colspan="4" class="text-right text-muted text-sm" style="border: none; padding-top: 5px;">
                 ( {{ \App\Helpers\ThaiBahtHelper::toText($refundAmount) ?? 'Baht' }} )
             </td>
        </tr>
    </tfoot>
</table>

<div class="mt-8">
    <h4 class="text-sm font-bold mb-2">Refund Method</h4>
    <div class="flex items-center mb-2">
        <div style="width: 20px; height: 20px; border: 1px solid #ccc; margin-right: 10px;"></div>
        <span class="text-sm">Cash</span>
    </div>
    <div class="flex items-center mb-2">
        <div style="width: 20px; height: 20px; border: 1px solid #ccc; margin-right: 10px;"></div>
        <span class="text-sm">Bank Transfer</span>
    </div>
    <div class="flex items-center">
        <div style="width: 20px; height: 20px; border: 1px solid #ccc; margin-right: 10px;"></div>
        <span class="text-sm">Credit Balance (Next Job)</span>
    </div>
</div>
@endsection
