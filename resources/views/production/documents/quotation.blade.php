@extends('layouts.document')

@section('title', 'Quotation - ' . $production->project_name)
@section('document_title', 'QUOTATION (ใบเสนอราคา)')

@section('content')
<table>
    <thead>
        <tr>
            <th style="width: 5%;">#</th>
            <th style="width: 50%;">Description</th>
            <th class="text-center" style="width: 15%;">Quantity</th>
            <th class="text-right" style="width: 15%;">Unit Price</th>
            <th class="text-right" style="width: 15%;">Total</th>
        </tr>
    </thead>
    <tbody>
        @php
            $pricingMode = $production->financial_data['pricing_mode'] ?? 'fixed';
            $unitPrice = $production->financial_data['unit_price'] ?? 0;
            $count = $production->items->count();
            $total = $production->financial_data['total_amount'] ?? 0;
        @endphp

        @if($pricingMode === 'per_head')
            <tr>
                <td>1</td>
                <td>
                    <strong>Service Fee for Foreign Worker Management</strong><br>
                    <span class="text-sm text-muted">Management and processing fee per employee</span>
                </td>
                <td class="text-center">{{ $count }} Person(s)</td>
                <td class="amount">{{ number_format($unitPrice, 2) }}</td>
                <td class="amount">{{ number_format($total, 2) }}</td>
            </tr>
        @else
            <tr>
                <td>1</td>
                <td>
                    <strong>Project Fee: {{ $production->project_name }}</strong><br>
                    <span class="text-sm text-muted">{{ $production->description }}</span>
                </td>
                <td class="text-center">1 Job</td>
                <td class="amount">{{ number_format($total, 2) }}</td>
                <td class="amount">{{ number_format($total, 2) }}</td>
            </tr>
        @endif

        <!-- Spacer rows to fill space if needed, or just leave blank -->
        <tr><td colspan="5" style="border: none; padding: 20px;"></td></tr>
    </tbody>
    <tfoot>
        <tr>
            <td colspan="3" style="border: none;"></td>
            <td class="text-right font-bold">Subtotal</td>
            <td class="amount">{{ number_format($total, 2) }}</td>
        </tr>
        <tr>
            <td colspan="3" style="border: none;"></td>
            <td class="text-right font-bold text-primary" style="font-size: 1.1em;">Grand Total</td>
            <td class="amount font-bold text-primary" style="font-size: 1.1em;">{{ number_format($total, 2) }}</td>
        </tr>
        <tr>
             <td colspan="5" class="text-right text-muted text-sm" style="border: none; padding-top: 5px;">
                 ( {{ \App\Helpers\ThaiBahtHelper::toText($total) ?? 'Baht' }} )
             </td>
        </tr>
    </tfoot>
</table>

<div class="mt-8">
    <h4 class="text-sm font-bold mb-2">Terms & Conditions</h4>
    <ul class="text-sm text-muted" style="padding-left: 20px;">
        <li>This quotation is valid for 30 days.</li>
        <li>Payment terms as agreed in the contract.</li>
    </ul>
</div>
@endsection
