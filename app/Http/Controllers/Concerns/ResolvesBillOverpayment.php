<?php

namespace App\Http\Controllers\Concerns;

use App\Models\ExpenseCategory;
use App\Models\FinancialPayment;
use App\Models\FinancialTransaction;
use App\Models\FinancialTransactionItemPayment;
use App\Models\ProductionItem;
use App\Services\LedgerService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * A bill whose price goes DOWN after money was received for it (e.g. one
 * employee's per-head price is lowered because their documents changed) ends
 * up holding more money than it is worth. Instead of refusing the edit, the
 * operator picks what happens to that surplus (2026-11-21):
 *
 *  - keep        keep it as extra income: an "overpayment kept" line is added
 *                to the bill so the bill total still equals what was received
 *  - refund      pay it back to the customer: a negative payment (an expense in
 *                the books when a bank account is picked)
 *  - transfer    take it off another unpaid bill of the same employer: a
 *                negative payment here + the same positive payment there
 *  - reallocate  (individual collection only, when others on the bill still
 *                owe) count it toward the people who still owe on this bill
 *
 * Invariants kept for every bill, whatever is picked:
 *   money on the bill (sum of payments) <= what the bill is worth
 *   individual collection: each person / line has received <= their price,
 *   and the sum of the per-person entries == the sum of the bill's payments.
 */
trait ResolvesBillOverpayment
{
    /**
     * What the bill would hold after this edit: the surplus (0 when none) and,
     * for individually collected bills, which person / line is over and which
     * still owe. Keys: "i:<production item id>" or "x:<extra charge id>".
     */
    protected function overpaymentPlan(FinancialTransaction $t, Request $request, ?array $extraCharges, array $tierChanges): array
    {
        $amount = $request->has('amount') ? (float) $request->input('amount') : (float) $t->amount;
        $discount = $request->has('discount_amount') ? (float) $request->input('discount_amount') : (float) ($t->discount_amount ?? 0);
        $effective = round($amount - $discount - (float) ($t->credit_amount ?? 0), 2);
        // an old bill whose money pre-dates payment rows only has paid_amount
        $legacy = ! $t->payments()->exists() && (float) $t->paid_amount > 0.005;
        $received = $legacy ? round((float) $t->paid_amount, 2) : round((float) $t->payments()->sum('amount'), 2);
        $billExcess = round($received - $effective, 2);
        $perEmployee = ($t->payment_collection_mode ?? 'lump_sum') === 'per_employee';

        $over = [];
        $owing = [];
        $collected = [];
        if ($perEmployee) {
            $entries = $t->itemPayments()->get();
            foreach ($entries as $e) {
                $key = $e->extra_charge_id ? 'x:' . $e->extra_charge_id : 'i:' . $e->production_item_id;
                $collected[$key] = round(($collected[$key] ?? 0) + (float) $e->amount, 2);
            }

            $syncing = $request->boolean('sync_items') || $request->has('item_ids') || $request->has('employee_ids');
            $keepIds = $syncing ? array_map('intval', $this->parseIdList($request->input('item_ids'))) : null;
            foreach ($t->items()->get() as $item) {
                if ($keepIds !== null && ! in_array((int) $item->id, $keepIds, true)) {
                    continue; // leaving the bill — refused earlier when money was received for them
                }
                $price = isset($tierChanges[$item->id]) ? (float) $tierChanges[$item->id]['price'] : $this->billUnitPrice($t, $item);
                $this->sortLine('i:' . $item->id, $price, $collected, $over, $owing);
            }
            foreach (($extraCharges ?? ($t->extra_charges ?? [])) as $c) {
                $this->sortLine('x:' . $c['id'], (float) ($c['amount'] ?? 0), $collected, $over, $owing);
            }
        }

        $lineExcess = round(array_sum($over), 2);
        $excess = $perEmployee ? max($lineExcess, $billExcess) : $billExcess;

        return [
            'excess' => $excess > 0.005 ? round($excess, 2) : 0.0,
            'bill_excess' => $billExcess,
            'per_employee' => $perEmployee,
            'legacy' => $legacy,
            'over' => $over,
            'owing' => $owing,
            'collected' => $collected,
            'received' => $received,
            'new_total' => $effective,
        ];
    }

    private function sortLine(string $key, float $price, array $collected, array &$over, array &$owing): void
    {
        $diff = round(($collected[$key] ?? 0) - $price, 2);
        if ($diff > 0.005) {
            $over[$key] = $diff;
        } elseif ($diff < -0.005) {
            $owing[$key] = -$diff;
        }
    }

    /** Price this person was put on the bill at (pivot snapshot) — else today's tier price. */
    protected function billUnitPrice(FinancialTransaction $t, ProductionItem $item): float
    {
        $snap = $item->pivot->unit_price ?? null;
        if ($snap !== null && $snap !== '') {
            return (float) $snap;
        }
        $tiers = $t->financialGroup()->first()?->financial_data['pricing_tiers'] ?? [];
        $aliases = array_filter([(string) $item->id, 'emp_' . $item->id, $item->employee_id ? 'emp_' . $item->employee_id : null]);
        foreach ($tiers as $tier) {
            foreach ($tier['item_ids'] ?? [] as $id) {
                if (in_array((string) $id, $aliases, true)) {
                    return (float) ($tier['price'] ?? 0);
                }
            }
        }

        return 0.0;
    }

    /** Unpaid bills of the same employer the surplus can be moved onto. */
    protected function overpaymentTransferTargets(FinancialTransaction $t, float $excess): array
    {
        $employerId = $t->productionOrder?->employer_id;
        if (! $employerId) {
            return [];
        }

        return FinancialTransaction::with(['productionOrder.employer'])
            ->where('id', '!=', $t->id)
            ->whereNotIn('type', ['quotation'])
            ->whereHas('productionOrder', fn ($q) => $q->where('employer_id', $employerId))
            ->orderByDesc('id')
            ->get()
            ->map(function (FinancialTransaction $b) use ($excess) {
                $outstanding = round((float) $b->amount - (float) ($b->discount_amount ?? 0) - (float) ($b->credit_amount ?? 0) - (float) $b->payments()->sum('amount'), 2);
                $hasMoney = $b->payments()->exists() || $b->itemPayments()->exists();
                $individual = ($b->payment_collection_mode ?? 'lump_sum') === 'per_employee' && $hasMoney;

                return [
                    'id' => $b->id,
                    'label' => '#' . $b->production_order_id . '-' . $b->id . ' ' . ($b->productionOrder?->project_name ?? ''),
                    'outstanding' => $outstanding,
                    // a bill collected per person takes money only through its people
                    'ok' => ! $individual && $outstanding + 0.005 >= $excess,
                ];
            })
            ->filter(fn ($b) => $b['outstanding'] > 0.005)
            ->values()
            ->all();
    }

    /** The 422 that asks the operator what to do with the surplus. */
    protected function overpaidResponse(FinancialTransaction $t, array $plan, ?string $message = null)
    {
        $names = [];
        foreach (array_keys($plan['over']) as $key) {
            $names[$key] = $this->overpaymentLineName($t, $key);
        }

        return response()->json([
            'success' => false,
            'code' => 'overpaid',
            'message' => $message ?? __('More money was received for this bill than it is worth after this change — choose what to do with the extra :amount.', ['amount' => number_format($plan['excess'], 2)]),
            'excess' => $plan['excess'],
            'received' => $plan['received'],
            'new_total' => $plan['new_total'],
            'lines' => collect($plan['over'])->map(fn ($amt, $key) => ['name' => $names[$key], 'excess' => $amt])->values(),
            'can_reallocate' => $plan['per_employee'] && $plan['bill_excess'] <= 0.005 && array_sum($plan['owing']) + 0.005 >= $plan['excess'],
            'targets' => $this->overpaymentTransferTargets($t, $plan['excess']),
        ], 422);
    }

    private function overpaymentLineName(FinancialTransaction $t, string $key): string
    {
        if (str_starts_with($key, 'x:')) {
            return (string) (collect($t->extra_charges ?? [])->firstWhere('id', substr($key, 2))['description'] ?? __('Additional charge'));
        }
        $item = ProductionItem::withTrashed()->with('employeeWithTrashed')->find((int) substr($key, 2));
        $emp = $item?->employeeWithTrashed;

        return $emp ? ($emp->getRawOriginal('employeeNameEn') ?: $emp->getRawOriginal('employeeNameTh') ?: '#' . $item->id) : '#' . substr($key, 2);
    }

    /** Error text when the chosen way can't be used, else null. */
    protected function overpaymentActionError(FinancialTransaction $t, array $plan, Request $request): ?string
    {
        if ($plan['legacy']) {
            return __('This bill\'s earlier payment has no payment record. Record it as a payment first, then change the price.');
        }
        $action = (string) $request->input('overpayment_action');
        switch ($action) {
            case 'keep':
                return mb_strlen((string) $request->input('overpayment_note')) > 255 ? __('The description is too long.') : null;
            case 'refund':
                $date = (string) $request->input('overpayment_date');
                if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                    return __('Please choose the date the money was paid back.');
                }
                $bank = $request->input('overpayment_bank_account_id');
                if ($bank && ! \App\Models\BankAccount::whereKey($bank)->exists()) {
                    return __('Invalid bank account.');
                }

                return null;
            case 'transfer':
                $target = collect($this->overpaymentTransferTargets($t, $plan['excess']))->firstWhere('id', (int) $request->input('overpayment_target_id'));

                return ($target && $target['ok']) ? null : __('Pick another unpaid bill of this employer that still owes at least :amount.', ['amount' => number_format($plan['excess'], 2)]);
            case 'reallocate':
                return ($plan['per_employee'] && $plan['bill_excess'] <= 0.005 && array_sum($plan['owing']) + 0.005 >= $plan['excess'])
                    ? null : __('Nobody else on this bill still owes enough for this.');
            default:
                return __('Choose what to do with the extra money.');
        }
    }

    /**
     * Carry out the chosen way. Call inside the DB transaction, AFTER the bill's
     * new amount / items / prices were saved. Adjusts $t->amount / extra_charges
     * for "keep" (caller saves) and recalculates paid_amount of every bill touched.
     */
    protected function applyOverpayment(FinancialTransaction $t, array $plan, Request $request): void
    {
        $action = (string) $request->input('overpayment_action');
        $x = $plan['excess'];
        $billNo = '#' . $t->production_order_id . '-' . $t->id;
        $date = $action === 'refund' ? Carbon::parse($request->input('overpayment_date')) : now();
        $note = trim((string) $request->input('overpayment_note'));

        if ($action === 'keep') {
            $line = [
                'id' => 'ovp' . now()->format('YmdHis') . substr((string) mt_rand(100, 999), 0, 3),
                'description' => $note !== '' ? $note : __('Overpayment kept as income'),
                'amount' => $x,
                'kind' => 'overpayment',
            ];
            $t->extra_charges = array_merge($t->extra_charges ?? [], [$line]);
            $t->amount = round((float) $t->amount + $x, 2);
            $t->save();
            if ($plan['per_employee']) {
                $pay = $this->overpaymentPayment($t, 0, 'adjustment', now(), null, __('Overpayment kept as income'));
                $this->writeEntries($t, $pay, $this->allocateOver($plan, $x), -1);
                $this->writeEntries($t, $pay, ['x:' . $line['id'] => $x], +1);
            }
        } elseif ($action === 'refund') {
            $bank = $request->input('overpayment_bank_account_id') ?: null;
            $pay = $this->overpaymentPayment($t, -$x, 'refund', $date, $bank, $note !== '' ? $note : __('Refund to the customer'));
            if ($plan['per_employee']) {
                $this->writeEntries($t, $pay, $this->allocateOver($plan, $x), -1);
            }
            if ($bank) {
                app(LedgerService::class)->createEntry([
                    'entry_date' => $pay->paid_at->toDateString(),
                    'type' => 'expense',
                    'bank_account_id' => $bank,
                    'category_id' => ExpenseCategory::firstOrCreate(['code' => 'CUSTOMER_REFUND'], ['name' => __('Customer refund'), 'is_active' => true])->id,
                    'category_type' => 'expense',
                    'counterparty_name' => optional(optional($t->productionOrder)->employer)->employerNameTh,
                    'gross_amount' => $x,
                    'description' => __('Refund to the customer — bill :bill', ['bill' => $billNo]),
                    'source_type' => FinancialPayment::class,
                    'source_id' => $pay->id,
                ]);
            }
        } elseif ($action === 'transfer') {
            $target = FinancialTransaction::lockForUpdate()->findOrFail((int) $request->input('overpayment_target_id'));
            $targetNo = '#' . $target->production_order_id . '-' . $target->id;
            $out = $this->overpaymentPayment($t, -$x, 'transfer_out', $date, null, __('Moved to bill :bill', ['bill' => $targetNo]) . ($note !== '' ? ' — ' . $note : ''));
            if ($plan['per_employee']) {
                $this->writeEntries($t, $out, $this->allocateOver($plan, $x), -1);
            }
            if (! $target->payments()->exists() && ! $target->itemPayments()->exists()) {
                $target->payment_collection_mode = 'lump_sum'; // the first money decides — same rule as storePayment()
            }
            $in = $this->overpaymentPayment($target, $x, 'transfer_in', $date, null, __('Moved from bill :bill', ['bill' => $billNo]) . ($note !== '' ? ' — ' . $note : ''));
            $out->update(['linked_payment_id' => $in->id]);
            $in->update(['linked_payment_id' => $out->id]);
            $this->recalcBillPaid($target);
        } elseif ($action === 'reallocate') {
            $pay = $this->overpaymentPayment($t, 0, 'adjustment', now(), null, __('Counted toward others on this bill'));
            $this->writeEntries($t, $pay, $plan['over'], -1);
            $left = $x;
            $plus = [];
            foreach ($plan['owing'] as $key => $owed) {
                if ($left <= 0.005) {
                    break;
                }
                $take = round(min($owed, $left), 2);
                $plus[$key] = $take;
                $left = round($left - $take, 2);
            }
            $this->writeEntries($t, $pay, $plus, +1);
        }

        $this->recalcBillPaid($t);
    }

    /**
     * Which lines give back how much: first what each line is over by, then —
     * only when the bill-level surplus is larger (a discount was raised) — the
     * rest from lines that still hold money, most recent first.
     */
    private function allocateOver(array $plan, float $x): array
    {
        $out = [];
        $left = $x;
        foreach ($plan['over'] as $key => $amt) {
            $take = round(min($amt, $left), 2);
            if ($take > 0) {
                $out[$key] = $take;
                $left = round($left - $take, 2);
            }
        }
        if ($left > 0.005) {
            foreach (array_reverse($plan['collected'], true) as $key => $amt) {
                $avail = round($amt - ($out[$key] ?? 0), 2);
                if ($avail <= 0.005) {
                    continue;
                }
                $take = round(min($avail, $left), 2);
                $out[$key] = round(($out[$key] ?? 0) + $take, 2);
                $left = round($left - $take, 2);
                if ($left <= 0.005) {
                    break;
                }
            }
        }
        if ($left > 0.005) {
            throw new \RuntimeException('Overpayment could not be spread over the bill\'s entries.');
        }

        return $out;
    }

    private function writeEntries(FinancialTransaction $t, FinancialPayment $pay, array $amounts, int $sign): void
    {
        foreach ($amounts as $key => $amt) {
            if ($amt <= 0.005) {
                continue;
            }
            $isCharge = str_starts_with($key, 'x:');
            $t->itemPayments()->create([
                'production_item_id' => $isCharge ? null : (int) substr($key, 2),
                'extra_charge_id' => $isCharge ? substr($key, 2) : null,
                'financial_payment_id' => $pay->id,
                'amount' => round($sign * $amt, 2),
                'paid_at' => $pay->paid_at,
                'notes' => $pay->notes,
                'created_by' => auth()->id(),
            ]);
        }
    }

    private function overpaymentPayment(FinancialTransaction $t, float $amount, string $kind, $date, $bankId, string $notes): FinancialPayment
    {
        return $t->payments()->create([
            'amount' => round($amount, 2),
            'kind' => $kind,
            'paid_at' => $date,
            'bank_account_id' => $bankId,
            'notes' => $notes,
            'created_by' => auth()->id(),
        ]);
    }

    /** paid_amount from the payments, status from what is left — never typed in. */
    protected function recalcBillPaid(FinancialTransaction $t): void
    {
        $t->paid_amount = round((float) $t->payments()->sum('amount'), 2);
        $this->recalcBillStatus($t);
        $t->save();
    }

    protected function recalcBillStatus(FinancialTransaction $t): void
    {
        $effective = (float) $t->amount - (float) ($t->discount_amount ?? 0) - (float) ($t->credit_amount ?? 0);
        $paid = (float) $t->paid_amount;
        if ($paid > 0.005 && $paid + 0.005 >= $effective) {
            $t->status = 'paid';
        } elseif ($paid > 0.005 || (float) ($t->credit_amount ?? 0) > 0) {
            $t->status = 'partial';
        } elseif ($t->status !== 'overdue') {
            $t->status = 'pending';
        }
    }

    /**
     * Undo a system-made row (refund / transfer / adjustment): its entries go,
     * a transfer's other half goes too, a refund's book entry is reversed, and
     * an "overpayment kept" line that the adjustment paid is taken off the bill.
     * Returns the bills touched. Call inside a DB transaction.
     */
    protected function undoOverpaymentRow(FinancialPayment $payment): array
    {
        $rows = collect([$payment]);
        if ($payment->linked_payment_id && ($other = FinancialPayment::find($payment->linked_payment_id))) {
            $rows->push($other);
        }
        $bills = [];
        foreach ($rows as $row) {
            $bill = $row->transaction()->first();
            if ($row->kind === 'adjustment') {
                $kept = FinancialTransactionItemPayment::where('financial_payment_id', $row->id)
                    ->where('amount', '>', 0)->whereNotNull('extra_charge_id')->pluck('extra_charge_id')->all();
                $lines = collect($bill->extra_charges ?? []);
                $gone = $lines->filter(fn ($c) => ($c['kind'] ?? null) === 'overpayment' && in_array($c['id'], $kept, true));
                if ($gone->isNotEmpty()) {
                    $bill->extra_charges = $lines->reject(fn ($c) => $gone->contains('id', $c['id']))->values()->all() ?: null;
                    $bill->amount = round((float) $bill->amount - $gone->sum(fn ($c) => (float) $c['amount']), 2);
                }
            }
            if ($linked = $row->ledgerEntry()->first()) {
                app(LedgerService::class)->deleteEntry($linked);
            }
            FinancialTransactionItemPayment::where('financial_payment_id', $row->id)->delete();
            $row->delete();
            $bills[$bill->id] = $bill;
        }
        foreach ($bills as $bill) {
            $this->recalcBillPaid($bill);
        }

        return $bills;
    }
}
