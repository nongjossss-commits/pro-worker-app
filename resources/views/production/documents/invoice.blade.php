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
            $transactions = \App\Models\FinancialTransaction::where('production_order_id', $production->id)->orderBy('due_date')->get();
            $grandTotal = 0;
        @endphp

        @forelse($transactions as $index => $t)
            @php $grandTotal += $t->amount; @endphp
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
                <td colspan="5" class="text-center py-4 text-muted">No scheduled payments found.</td>
            </tr>
        @endforelse
    </tbody>
    <tfoot>
        <tr>
            <td colspan="3" style="border: none;"></td>
            <td class="text-right font-bold text-primary" style="font-size: 1.1em;">Total Due</td>
            <td class="amount font-bold text-primary" style="font-size: 1.1em;">{{ number_format($grandTotal, 2) }}</td>
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
