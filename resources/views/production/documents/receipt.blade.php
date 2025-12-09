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
            <th class="text-right" style="width: 15%;">Status</th>
            <th class="text-right" style="width: 15%;">Amount Paid</th>
        </tr>
    </thead>
    <tbody>
        @php
            // Filtered Transactions from Controller
            $grandTotal = 0;
            $subtotal = 0;

            // VAT Logic
            $vatRate = $production->financial_data['vat_rate'] ?? 7;
            $vatIncluded = $production->financial_data['vat_included'] ?? false;
        @endphp

        @forelse($transactions as $index => $t)
            @php
                $amount = $t->paid_amount; // Use actual PAID amount
                $grandTotal += $amount;

                // Calculate Subtotal (assuming Paid Amount is inclusive of VAT portion)
                $subtotal += ($amount / (1 + ($vatRate / 100)));
            @endphp
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>
                    <strong>{{ ucfirst(str_replace('_', ' ', $t->type)) }}</strong><br>
                    <span class="text-sm text-muted">{{ $t->notes }}</span>
                </td>
                <td class="text-center">{{ $t->updated_at ? date('d/m/Y', strtotime($t->updated_at)) : '-' }}</td>
                <td class="text-right"><span class="badge badge-success">RECEIVED</span></td>
                <td class="amount">{{ number_format($t->paid_amount, 2) }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="text-center py-4 text-muted">No completed payments selected.</td>
            </tr>
        @endforelse
    </tbody>
    <tfoot>
        <!-- Breakdown -->
        @php
            $vatAmount = $grandTotal - $subtotal;
        @endphp

        <tr>
            <td colspan="3" style="border: none;"></td>
            <td class="text-right text-muted">Subtotal</td>
            <td class="amount">{{ number_format($subtotal, 2) }}</td>
        </tr>
        <tr>
            <td colspan="3" style="border: none;"></td>
            <td class="text-right text-muted">VAT {{ $vatRate }}%</td>
            <td class="amount">{{ number_format($vatAmount, 2) }}</td>
        </tr>
        <tr>
            <td colspan="3" style="border: none;"></td>
            <td class="text-right font-bold text-primary" style="font-size: 1.1em;">Total Received</td>
            <td class="amount font-bold text-primary" style="font-size: 1.1em;">{{ number_format($grandTotal, 2) }}</td>
        </tr>
         <tr>
             <td colspan="5" class="text-right text-muted text-sm" style="border: none; padding-top: 5px;">
                 ( {{ \App\Helpers\ThaiBahtHelper::toText($grandTotal) ?? 'Baht' }} )
             </td>
        </tr>
    </tfoot>
</table>
@endsection
