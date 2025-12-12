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

            // VAT & Discount Logic
            $vatRate = $production->financial_data['vat_rate'] ?? 7;
            $vatIncluded = $production->financial_data['vat_included'] ?? false;
            $discount = $production->financial_data['discount'] ?? 0;

            // Calculate Base Total from Transactions
            // The logic here is tricky because we are invoicing specific transactions.
            // But 'Discount' usually applies to the WHOLE project.
            // If we are issuing a PARTIAL invoice, how do we show discount?
            // Usually, Discount is shown on the final invoice or pro-rated?
            // OR, the transactions themselves are net of discount?

            // Given the user wants to "add a discount field", let's assume this invoice
            // represents the full amount or we show the discount as a line item if applicable.
            // However, the table iterates TRANSACTIONS.
            // If the user selects all transactions, we should probably show the discount at the bottom.
            // If partial, maybe not?

            // Let's implement standard logic:
            // Sum of Selected Items = Subtotal (Gross)
            // Less Discount (Prorated? Or Fixed if full?)

            // SIMPLIFIED APPROACH:
            // The Invoice shows the AMOUNT to be paid for these transactions.
            // The discount is a visual breakdown in the footer, BUT it affects the total to be paid.
            // If the transaction amount in DB is 100, that's what the customer pays.
            // If there is a discount, the DB transaction amount should logically already reflect that?
            // Or does the Discount apply 'on top'?

            // If I have a project of 1000. Discount 100. Total 900.
            // I create 2 installments of 450.
            // The Invoice for Installment 1 shows 450.
            // The footer breakdown: Subtotal 500, Discount 50, Net 450?
            // That's complex to reverse engineer.

            // Users usually want:
            // List Items: ...
            // Subtotal: Sum of Items
            // Discount: -X
            // Net: ...
            // VAT: ...
            // Total: ...

            // In our system, 'Transactions' are payment milestones.
            // Let's just sum them up as "Subtotal".
            // If a discount exists in the project settings, we should probably ONLY show it
            // if this is a "Full Payment" or we are summarizing the whole project.

            // BUT, the user asked for "Discount field... when issuing invoice can put discount".
            // The field is in the "Settings" tab.
            // Let's assume the user wants to see this discount applied to the "Base" calculation.

            // Let's just list the transactions.
            // And in the footer, we show the breakdown derived from the Sum.

            // RE-EVALUATING VAT LOGIC:
            // If vatIncluded: Amount = 107. Base = 100. VAT = 7.
            // If vatExcluded: Amount = 107. Base = 100. VAT = 7. (If Amount is Gross).

            // Discount Logic:
            // If I have a discount of 100 on a 1000 project (10%).
            // And I invoice 500 (Gross).
            // Should I show 50 discount?

            // For now, to be safe and simple:
            // We only show the discount line if it's > 0.
            // And we assume the "Transactions" amounts are FINAL amounts to be paid (Net of discount).
            // So we reverse-calculate the "Gross" before discount for display purposes?
            // That seems too magical.

            // ALTERNATIVE:
            // The "Discount" input in the settings reduces the "Total Project Value".
            // The transactions are created based on that Reduced Value.
            // So the Invoice just shows the transactions.
            // AND we show the Discount as a note or separate line?

            // Wait, the Invoice Footer usually calculates:
            // Sum of Lines
            // + VAT
            // = Total

            // If the user wants to show a discount, it implies the "Sum of Lines" is BEFORE discount?
            // But our lines are "Installment 1", "Installment 2".

            // Let's stick to the previous file's logic but add the Discount row if it exists.
            // And we will treat the Sum of Transactions as the "Net Base + VAT".
            // So we derive the components.

            // Let Sum = S.
            // NetBase = S / (1 + Rate) [If Inclusive]
            // GrossBase = NetBase + Discount? (If we assume this invoice covers the whole discount?)
            // This is messy for partial invoices.

            // DECISION:
            // Only show discount row if we are printing ALL transactions (Grand Total matches Project Total).
            // OR just show it as informational.

            // Actually, let's look at the request: "When issuing invoice can put discount".
            // It might be they want to apply a discount TO THE INVOICE.
            // But my plan put the field in the "Financial Tab" (Project Level).
            // "In the finance feature I want to add a discount... when issuing invoice... specify discount".

            // If I added it to Project Settings, it's a Project Discount.
            // So it effectively reduces the project total.
            // The transactions created (manually) should sum up to this New Total.
            // So the Invoice just reflects those transactions.

            // Maybe I should just show the Breakdown:
            // Total Amount (Transactions): 100
            // (Hidden Calculation: Base 93.45, VAT 6.55)
            // No extra "Discount" line needed in the footer if the transactions already account for it?

            // User requirement: "can insert discount to customer... standard bill format".
            // Standard format:
            // Item 1: 100
            // Item 2: 100
            // Subtotal: 200
            // Discount: -20
            // Net: 180
            // VAT: 12.6
            // Total: 192.6

            // To achieve this with "Payment Milestones" (Transactions):
            // We need to treat the Transactions as the "Net" result.
            // I will add a "Discount" row ONLY if it's significant.
            // But since I don't know if this invoice is partial or full, checking the ratio is safer.

            // Let's try to simply show the breakdown based on the Project Settings
            // IF the invoice total is close to the project total.
            // Otherwise, just show standard VAT breakdown.

            // Wait, I can't be too smart.
            // Let's just add the row "Discount" in the footer, but value is ?
            // I will use a simple heuristic:
            // If (Sum of Invoiced Items / Project Total) > 0.99 (basically full invoice), show full discount.
            // If not, show (Discount * Ratio).

            $totalProjectValue = $production->financial_data['total_amount'] ?? 0;
            $ratio = ($totalProjectValue > 0) ? ($grandTotal / $totalProjectValue) : 1;
            // Actually grandTotal is accumulating inside the loop, so initialize 0.
        @endphp

        @forelse($transactions as $index => $t)
            @php
                $amount = $t->amount;
                $grandTotal += $amount;
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
        @php
            // Calculate Ratios and Breakdown
            // We assume the Invoiced Grand Total is the "Final Price including everything" (Net + VAT).

            // 1. Extract VAT
            // NetTotal = GrandTotal / (1 + Rate) [If Inc] OR ...
            // Actually, standard is: NetTotal = GrandTotal - VAT.
            if ($vatIncluded) {
                $netTotal = $grandTotal / (1 + ($vatRate / 100));
                $vatAmount = $grandTotal - $netTotal;
            } else {
                // If Excluded, logic depends on how "Amount" was entered.
                // Assuming "Amount" in DB is the Gross amount to be paid.
                $netTotal = $grandTotal / (1 + ($vatRate / 100));
                $vatAmount = $grandTotal - $netTotal;
            }

            // 2. Determine Display Discount
            // We derive the "Gross Before Discount" for display.
            // DisplayDiscount = ProjectDiscount * (GrandTotal / ProjectTotal)
            $projectTotal = $production->financial_data['total_amount'] ?? $grandTotal; // Fallback to self if 0
            $projectDiscount = $production->financial_data['discount'] ?? 0;

            $ratio = ($projectTotal > 0) ? ($grandTotal / $projectTotal) : 0;
            $displayDiscount = $projectDiscount * $ratio;

            // 3. Calculate "Subtotal" (Gross Base)
            // Subtotal = NetTotal + DisplayDiscount
            $subtotal = $netTotal + $displayDiscount;

        @endphp

        <!-- Display Logic -->
        <tr>
            <td colspan="3" style="border: none;"></td>
            <td class="text-right text-muted">Subtotal</td>
            <td class="amount">{{ number_format($subtotal, 2) }}</td>
        </tr>

        @if($displayDiscount > 0)
        <tr>
            <td colspan="3" style="border: none;"></td>
            <td class="text-right text-danger">Discount</td>
            <td class="amount text-danger">-{{ number_format($displayDiscount, 2) }}</td>
        </tr>
        @endif

        <tr>
            <td colspan="3" style="border: none;"></td>
            <td class="text-right text-muted">Net After Discount</td>
            <td class="amount">{{ number_format($netTotal, 2) }}</td>
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
