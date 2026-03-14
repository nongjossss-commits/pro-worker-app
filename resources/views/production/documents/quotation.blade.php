@extends('layouts.document')

@section('title', 'Quotation - ' . $production->project_name)
@section('document_title', 'QUOTATION (ใบเสนอราคา)')

@section('content')
<table>
    <thead>
        <tr>
            <th style="width: 5%;">#</th>
            <th style="width: 50%;">{{ __('Description') }}</th>
            <th class="text-center" style="width: 15%;">{{ __('Quantity') }}</th>
            <th class="text-right" style="width: 15%;">{{ __('Unit Price') }}</th>
            <th class="text-right" style="width: 15%;">{{ __('Amount') }}</th>
        </tr>
    </thead>
    <tbody>
        @php
            $financial = $production->financial_data ?? [];
            $pricingMode = $financial['pricing_mode'] ?? 'fixed';
            $pricingTiers = $financial['pricing_tiers'] ?? [];
            $fixedTotal = $financial['fixed_base_amount'] ?? ($financial['total_amount'] ?? 0);

            // Tax Settings
            $vatRate = isset($financial['vat_rate']) && $financial['vat_rate'] !== '' ? (float)$financial['vat_rate'] : 7;
            $vatIncluded = $financial['vat_included'] ?? false;
            $whtEnabled = $financial['wht_enabled'] ?? false;
            $whtRate = isset($financial['wht_rate']) ? (float)$financial['wht_rate'] : 3;
            $discount = $financial['discount'] ?? 0;

            // Calculations
            $subtotalGross = 0;
        @endphp

        @if($pricingMode === 'per_head' && count($pricingTiers) > 0)
            @foreach($pricingTiers as $index => $tier)
                @php
                    $lineTotal = ($tier['price'] ?? 0) * ($tier['count'] ?? 0);
                    $subtotalGross += $lineTotal;
                @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>
                        <strong>Service Fee (Tier {{ $index + 1 }})</strong>
                        @if(!empty($tier['note'])) <br><span class="text-sm text-muted">{{ $tier['note'] }}</span> @endif
                    </td>
                    <td class="text-center">{{ number_format($tier['count']) }} Person(s)</td>
                    <td class="amount">{{ number_format($tier['price'], 2) }}</td>
                    <td class="amount">{{ number_format($lineTotal, 2) }}</td>
                </tr>
            @endforeach
        @elseif($pricingMode === 'per_head')
             {{-- Fallback if array empty --}}
             @php
                $unitPrice = $financial['unit_price'] ?? 0;
                $count = $production->items->count();
                $lineTotal = $unitPrice * $count;
                $subtotalGross = $lineTotal;
             @endphp
             <tr>
                <td>1</td>
                <td><strong>{{ __('Service Fee') }}</strong></td>
                <td class="text-center">{{ $count }} Person(s)</td>
                <td class="amount">{{ number_format($unitPrice, 2) }}</td>
                <td class="amount">{{ number_format($lineTotal, 2) }}</td>
             </tr>
        @else
            {{-- Fixed Mode --}}
            @php $subtotalGross = $fixedTotal; @endphp
            <tr>
                <td>1</td>
                <td>
                    <strong>Project Fee: {{ $production->project_name }}</strong><br>
                    <span class="text-sm text-muted">{{ $production->description }}</span>
                </td>
                <td class="text-center">{{ __('1 Job') }}</td>
                <td class="amount">{{ number_format($fixedTotal, 2) }}</td>
                <td class="amount">{{ number_format($fixedTotal, 2) }}</td>
            </tr>
        @endif

        <!-- Spacer -->
        <tr><td colspan="5" style="border: none; padding: 20px;"></td></tr>
    </tbody>
    <tfoot>
        @php
            // Apply Discount
            $netBase = max(0, $subtotalGross - $discount);

            // Apply VAT
            if ($vatIncluded) {
                // Gross WAS Inc VAT?
                // Logic check: User input "Fixed Total" can be toggle Inc/Ex.
                // But Tier Price input? Usually assumed same rule as VAT setting.
                // If VAT Inc, then SubtotalGross is Inc VAT.
                $totalIncVat = $netBase;
                $realBase = $totalIncVat / (1 + ($vatRate / 100));
                $vatAmount = $totalIncVat - $realBase;
            } else {
                // Ex VAT
                $realBase = $netBase;
                $vatAmount = $realBase * ($vatRate / 100);
                $totalIncVat = $realBase + $vatAmount;
            }

            // WHT
            $whtAmount = 0;
            if ($whtEnabled) {
                // WHT on Real Base
                $whtAmount = $realBase * ($whtRate / 100);
            }
        @endphp

        <!-- Subtotal -->
        <tr>
            <td colspan="3" style="border: none;"></td>
            <td class="text-right text-muted">{{ __('Subtotal') }}</td>
            <td class="amount">{{ number_format($subtotalGross, 2) }}</td>
        </tr>

        @if($discount > 0)
        <tr>
            <td colspan="3" style="border: none;"></td>
            <td class="text-right text-danger">{{ __('Discount') }}</td>
            <td class="amount text-danger">-{{ number_format($discount, 2) }}</td>
        </tr>
        @endif

        <!-- Base before VAT -->
        @if($vatIncluded)
        <tr>
            <td colspan="3" style="border: none;"></td>
            <td class="text-right text-muted">{{ __('Base Amount (Ex-VAT)') }}</td>
            <td class="amount">{{ number_format($realBase, 2) }}</td>
        </tr>
        @endif

        <!-- VAT -->
        <tr>
            <td colspan="3" style="border: none;"></td>
            <td class="text-right text-muted">VAT {{ $vatRate }}%</td>
            <td class="amount">{{ number_format($vatAmount, 2) }}</td>
        </tr>

        <!-- Grand Total -->
        <tr>
            <td colspan="3" style="border: none;"></td>
            <td class="text-right font-bold text-primary" style="font-size: 1.1em;">{{ __('Grand Total') }}</td>
            <td class="amount font-bold text-primary" style="font-size: 1.1em;">{{ number_format($totalIncVat, 2) }}</td>
        </tr>

         <!-- WHT (For Quotation, maybe just show as info?) -->
         @if($whtEnabled)
         <tr>
             <td colspan="3" style="border: none;"></td>
             <td class="text-right text-danger small">Less WHT {{ $whtRate }}%</td>
             <td class="amount text-danger small">-{{ number_format($whtAmount, 2) }}</td>
         </tr>
         <tr>
             <td colspan="3" style="border: none;"></td>
             <td class="text-right font-bold text-success">{{ __('Net Payable') }}</td>
             <td class="amount font-bold text-success">{{ number_format($totalIncVat - $whtAmount, 2) }}</td>
         </tr>
         @endif

        <tr>
             <td colspan="5" class="text-right text-muted text-sm" style="border: none; padding-top: 5px;">
                 ( {{ \App\Helpers\ThaiBahtHelper::toText($totalIncVat) ?? 'Baht' }} )
             </td>
        </tr>
    </tfoot>
</table>

<div class="mt-8">
    <h4 class="text-sm font-bold mb-2">{{ __('Terms & Conditions') }}</h4>
    <ul class="text-sm text-muted" style="padding-left: 20px;">
        <li>{{ __('This quotation is valid for 30 days.') }}</li>
        <li>{{ __('Payment terms as agreed in the contract.') }}</li>
    </ul>

    <div class="mt-8 grid-2">
         <div>
            <div style="border-bottom: 1px solid #ccc; height: 30px; margin-bottom: 5px; width: 80%;"></div>
            <div class="text-sm text-muted">{{ __('Customer Acceptance') }}</div>
         </div>
    </div>
</div>
@endsection
