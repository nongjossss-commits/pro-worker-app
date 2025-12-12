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
            <th class="text-right" style="width: 15%;">Status</th>
            <th class="text-right" style="width: 15%;">Amount</th>
        </tr>
    </thead>
    <tbody>
        @php
            // Use transactions passed from Controller (already filtered)
            $grandTotal = 0;
            $subtotal = 0;

            // VAT Logic
            $vatRate = $production->financial_data['vat_rate'] ?? 7;
            $vatIncluded = $production->financial_data['vat_included'] ?? false;
        @endphp

        @forelse($transactions as $index => $t)
            @php
                $amount = $t->amount;
                $grandTotal += $amount;

                // Breakdown Calculation Logic
                if ($vatIncluded) {
                     // Inclusive: Total = Base + VAT
                     // Base = Total / (1 + Rate)
                     $subtotal += ($amount / (1 + ($vatRate / 100)));
                } else {
                     // Exclusive: Total = Base + VAT
                     // Subtotal (Base) = Total / (1 + Rate) ... IF the stored Amount is the final total.
                     // But typically for Exclusive, the user enters the BASE amount in pricing?

                     // Re-reading logic in financial-tab:
                     // If !vatIncluded: TotalAmount = SubtotalAmount + VATAmount.
                     // And transactions sum up to 'Total Scheduled'.
                     // So the Transaction Amount stored in DB is the FINAL amount to be paid?

                     // Let's assume Transaction Amount is ALWAYS the Final Payment Amount.
                     // Therefore, to get the Base for the invoice breakdown:
                     // Base = Amount / (1 + Rate) -- WAIT, no.

                     // If I bill 100 + 7% VAT = 107.
                     // The Transaction Amount is likely 107.
                     // So Base = 107 / 1.07 = 100.

                     // So the logic is actually the SAME for both cases if the Transaction Amount is the Gross Total.
                     $subtotal += ($amount / (1 + ($vatRate / 100)));
                }
            @endphp
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>
                    <strong>{{ ucfirst(str_replace('_', ' ', $t->type)) }}</strong><br>
                    <span class="text-sm text-muted">{{ $t->notes }}</span>
                </td>
                <td class="text-center">{{ $t->due_date ? date('d/m/Y', strtotime($t->due_date)) : '-' }}</td>
                <td class="text-right">
                    @if($t->status == 'paid') <span class="badge badge-success">PAID</span>
                    @else <span class="badge">UNPAID</span> @endif
                </td>
                <td class="amount">{{ number_format($t->amount, 2) }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="text-center py-4 text-muted">No items selected.</td>
            </tr>
        @endforelse
    </tbody>
    <tfoot>
        <!-- Breakdown -->
        @php
            // Recalculate VAT based on the derived subtotal
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
            <td class="text-right font-bold text-primary" style="font-size: 1.1em;">Grand Total</td>
            <td class="amount font-bold text-primary" style="font-size: 1.1em;">{{ number_format($grandTotal, 2) }}</td>
        </tr>
         <tr>
             <td colspan="5" class="text-right text-muted text-sm" style="border: none; padding-top: 5px;">
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
