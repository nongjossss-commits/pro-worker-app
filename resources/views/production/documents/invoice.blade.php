@extends('layouts.document')

@section('title', 'Invoice - ' . $production->project_name)
@section('document_title', 'INVOICE (ใบแจ้งหนี้)')

@section('content')
<div class="mb-4">
    <div class="text-sm">
        <strong>Payment For:</strong> Installments / Project Balance
    </div>
</div>

<table>
    <thead>
        <tr>
            <th style="width: 5%;">#</th>
            <th style="width: 50%;">Description</th>
            <th class="text-center" style="width: 15%;">Due Date</th>
            <th class="text-right" style="width: 15%;">Amount</th>
        </tr>
    </thead>
    <tbody>
        @php
            $grandTotal = 0;
            // Config
            $vatRate = isset($financial['vat_rate']) && $financial['vat_rate'] !== '' ? (float)$financial['vat_rate'] : 7;
            $vatIncluded = $financial['vat_included'] ?? false;
            $whtEnabled = $financial['wht_enabled'] ?? false;
            $whtRate = isset($financial['wht_rate']) ? (float)$financial['wht_rate'] : 3;
            $projectDiscount = $financial['discount'] ?? 0;
            $pricingMode = $financial['pricing_mode'] ?? 'fixed';
            $pricingTiers = $financial['pricing_tiers'] ?? [];

            // Check if we are showing FULL breakdown (all transactions)
            // If so, we might want to show the specific Tier Breakdown instead of generic "Installment"
            // But Invoice is legally tied to the "Transaction" (Milestone).
            // So we stick to listing Transactions.
            // BUT user requested "Tiered Pricing... must appear in every document".
            // If the document is an Invoice for an INSTALLMENT (Partial), showing Tiers is confusing.
            // Tiers explain the "Total Project Value".
            // Installments explain the "Payment Schedule".

            // COMPROMISE:
            // If we are invoicing "Full Payment" or the only transaction matches the total,
            // we show the Tiers Breakdown in the Description column of that item.
            // Otherwise, we show standard installment text, but maybe append a summary of tiers in the footer notes?
            // Or just list transactions.

            // Let's stick to listing Transactions as line items because they match the amount to be paid.
            // We can add a "Project Breakdown" section above the table if needed, but standard invoices don't usually mix concepts.
        @endphp

        @forelse($transactions as $index => $t)
            @php
                $amount = $t->amount;
                $grandTotal += $amount;
            @endphp
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>
                    <strong>{{ ucfirst(str_replace('_', ' ', $t->type)) }}</strong>
                    @if($t->notes)
                        <br><span class="text-sm text-muted">{{ $t->notes }}</span>
                    @endif

                    {{-- If this is a Full Payment, show Tier Details --}}
                    @if($t->type === 'full_payment' && $pricingMode === 'per_head' && count($pricingTiers) > 0)
                        <div class="mt-2 text-xs text-muted">
                            <strong>Pricing Details:</strong>
                            <ul class="mb-0 pl-3" style="list-style-type: disc; padding-left: 15px;">
                            @foreach($pricingTiers as $tier)
                                <li>{{ number_format($tier['count']) }} Pax @ {{ number_format($tier['price']) }} ฿
                                    @if(!empty($tier['note'])) ({{ $tier['note'] }}) @endif
                                </li>
                            @endforeach
                            </ul>
                        </div>
                    @endif
                </td>
                <td class="text-center">{{ $t->due_date ? date('d/m/Y', strtotime($t->due_date)) : '-' }}</td>
                <td class="amount">{{ number_format($t->amount, 2) }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="4" class="text-center py-4 text-muted">No items selected.</td>
            </tr>
        @endforelse
    </tbody>
    <tfoot>
        @php
            // 1. Calculate VAT & Net Base
            if ($vatIncluded) {
                // GrandTotal is Inc VAT
                $netBase = $grandTotal / (1 + ($vatRate / 100));
                $vatAmount = $grandTotal - $netBase;
            } else {
                // GrandTotal is Gross (Net + VAT) if entered that way?
                // Wait, if !vatIncluded, usually "Fixed Total" input is Ex-VAT.
                // But "Transaction Amount" input by user - is it Inc or Ex?
                // The JS logic: "Base (Ex) -> +VAT -> Total (Inc)".
                // And Transaction Amount is usually derived from Total (Inc).
                // So Transaction Amount is ALWAYS Inclusive of VAT in the DB logic we wrote.
                // JS: `totalAmount` (Inc VAT) -> scheduledAmount (Inc VAT).
                // So here, regardless of `vatIncluded` setting, `$grandTotal` IS INCLUSIVE OF VAT.

                // So logic is SAME:
                $netBase = $grandTotal / (1 + ($vatRate / 100));
                $vatAmount = $grandTotal - $netBase;
            }

            // 2. WHT Logic
            // WHT is calculated on the Base Amount (Net Base).
            $whtAmount = 0;
            if ($whtEnabled) {
                $whtAmount = $netBase * ($whtRate / 100);
            }

            // 3. Discount Logic (Visual only, reverse engineered)
            $projectTotal = $financial['total_amount'] ?? $grandTotal;
            $ratio = ($projectTotal > 0) ? ($grandTotal / $projectTotal) : 0;
            $displayDiscount = $projectDiscount * $ratio;

            // Adjust Subtotal for display
            $subtotalDisplay = $netBase + $displayDiscount;
        @endphp

        <!-- Subtotal -->
        <tr>
            <td colspan="2" style="border: none;"></td>
            <td class="text-right text-muted">Subtotal</td>
            <td class="amount">{{ number_format($subtotalDisplay, 2) }}</td>
        </tr>

        <!-- Discount -->
        @if($displayDiscount > 0)
        <tr>
            <td colspan="2" style="border: none;"></td>
            <td class="text-right text-danger">Discount</td>
            <td class="amount text-danger">-{{ number_format($displayDiscount, 2) }}</td>
        </tr>
        @endif

        <!-- Net Before VAT -->
        <tr>
            <td colspan="2" style="border: none;"></td>
            <td class="text-right text-muted">Net Amount</td>
            <td class="amount">{{ number_format($netBase, 2) }}</td>
        </tr>

        <!-- VAT -->
        <tr>
            <td colspan="2" style="border: none;"></td>
            <td class="text-right text-muted">VAT {{ $vatRate }}%</td>
            <td class="amount">{{ number_format($vatAmount, 2) }}</td>
        </tr>

        <!-- Grand Total -->
        <tr>
            <td colspan="2" style="border: none;"></td>
            <td class="text-right font-bold text-primary" style="font-size: 1.1em;">Grand Total</td>
            <td class="amount font-bold text-primary" style="font-size: 1.1em;">{{ number_format($grandTotal, 2) }}</td>
        </tr>

        <!-- WHT (Deduction) -->
        @if($whtEnabled)
        <tr>
            <td colspan="2" style="border: none;"></td>
            <td class="text-right text-danger small">Less WHT {{ $whtRate }}%</td>
            <td class="amount text-danger small">-{{ number_format($whtAmount, 2) }}</td>
        </tr>
        <tr>
            <td colspan="2" style="border: none;"></td>
            <td class="text-right font-bold text-success">Net Payable</td>
            <td class="amount font-bold text-success">{{ number_format($grandTotal - $whtAmount, 2) }}</td>
        </tr>
        @endif

        <!-- Text Amount -->
        <tr>
             <td colspan="4" class="text-right text-muted text-sm" style="border: none; padding-top: 5px;">
                 ( {{ \App\Helpers\ThaiBahtHelper::toText($grandTotal) ?? 'Baht' }} )
             </td>
        </tr>
    </tfoot>
</table>

<div class="mt-8">
    <h4 class="text-sm font-bold mb-2">Payment Information</h4>
    <div class="p-4" style="background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 4px;">
        <div class="text-sm">
            <strong>Bank Name:</strong> {{ $company->bank_name ?? 'KBANK' }}<br>
            <strong>Account Name:</strong> {{ $company->bank_account_name ?? $company->name }}<br>
            <strong>Account Number:</strong> {{ $company->bank_account_number ?? 'XXX-X-XXXXX-X' }}
        </div>
    </div>
</div>
@endsection
