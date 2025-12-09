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
            // $transactions is available from the View Composer or Controller
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

                // Calculate Subtotal contribution
                if ($vatIncluded) {
                     $subtotal += ($amount / (1 + ($vatRate / 100)));
                } else {
                     $subtotal += $amount; // Assuming transactions are stored as Base if Excluded?
                     // Wait, usually Transaction Amount IS the amount to be paid.
                     // If VAT Excluded, is the stored amount Base or Total?
                     // Standard: Transaction Amount is what needs to be paid.
                     // If VAT is Excluded globally, does user enter Base or Total in "Add Installment"?
                     // Usually "Add Installment" asks for "Amount".
                     // Let's assume Transaction Amount = Final Amount (Grand Total) for simplicity in billing,
                     // OR reverse engineer base.

                     // Actually, if VAT is Added on top, the Transaction Amount in DB usually represents the FINAL billing amount.
                     // So we treat $amount as Inclusive for display breakdown.
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
