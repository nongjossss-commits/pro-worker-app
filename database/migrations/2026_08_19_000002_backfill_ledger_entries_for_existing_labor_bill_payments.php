<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * One-time data correction: LaborBillPaymentService::recordPayment() never
 * wrote the offsetting negative labor_ledger_entries row it was always
 * supposed to (see LaborBillService's docblock), so every payment recorded
 * before this fix left the team's Central Billing Ledger balance untouched
 * despite the money having actually been received. This inserts the
 * missing entry for each such payment — matched by the unique
 * labor_bill_payment_id column added in the previous migration, so this is
 * safe to run even if some payments already got one from the fixed
 * runtime code.
 */
return new class extends Migration {
    public function up(): void
    {
        $payments = DB::table('labor_bill_payments')
            ->whereNull('deleted_at')
            ->whereNotIn('id', function ($query) {
                $query->select('labor_bill_payment_id')
                    ->from('labor_ledger_entries')
                    ->whereNotNull('labor_bill_payment_id');
            })
            ->get();

        foreach ($payments as $payment) {
            $bill = DB::table('labor_bills')->where('id', $payment->labor_bill_id)->first();

            if (!$bill) {
                continue;
            }

            DB::table('labor_ledger_entries')->insert([
                'labor_team_id' => $bill->labor_team_id,
                'entry_date' => $payment->paid_at,
                'description' => 'ชำระเงินตามบิล ' . $bill->bill_no,
                'amount' => -$payment->amount,
                'labor_bill_payment_id' => $payment->id,
                'created_by' => $payment->created_by,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('labor_ledger_entries')->whereNotNull('labor_bill_payment_id')->delete();
    }
};
