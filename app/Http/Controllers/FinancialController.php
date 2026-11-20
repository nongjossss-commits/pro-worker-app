<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityLogHelper;
use App\Models\IncomeCategory;
use App\Models\ProductionOrder;
use App\Models\FinancialTransaction;
use App\Models\FinancialPayment;
use App\Models\FinancialTransactionItemPayment;
use App\Models\CompanyProfile;
use App\Models\ProductionItem;
use App\Services\LedgerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
// use Barryvdh\DomPDF\Facade\Pdf; // Ensure this package is available, or use view return for print

class FinancialController extends Controller
{
    use Concerns\ResolvesBillOverpayment;

    /**
     * WHT Inbox — แสดง transactions ที่รับเงินแล้ว (paid_amount > 0) แต่ยังไม่ได้รับใบหัก ณ ที่จ่าย
     *
     * กฎ:
     *  - มีการจ่ายเงินแล้ว (paid_amount > 0) → ลูกค้าน่าจะออกใบ ณ ที่จ่ายให้
     *  - withholding_tax_amount > 0 (ต้องหักภาษี ณ ที่จ่าย)
     *  - wht_status !== 'received' (ยังไม่ได้รับใบ)
     *  - wht_status !== 'not_required' (ไม่ใช่กรณีไม่ต้องหัก)
     *
     * @return \Illuminate\View\View
     */
    public function whtInbox(Request $request)
    {
        if (!$this->canManageFinance()) {
            abort(403);
        }

        $employerId = $request->query('employer_id');
        $status = $request->query('status', 'pending'); // pending | all

        $query = FinancialTransaction::with(['productionOrder.employer', 'payments', 'financialGroup'])
            ->where('paid_amount', '>', 0)
            ->where('withholding_tax_amount', '>', 0)
            ->whereIn('wht_status', $status === 'all'
                ? ['pending', 'received', 'no_certificate']
                : ['pending']);

        if ($employerId) {
            $query->whereHas('productionOrder', fn($q) => $q->where('employer_id', $employerId));
        }

        $transactions = $query->orderBy('paid_at', 'asc')->paginate(25)->withQueryString();

        // คำนวณยอด WHT รวมที่ค้างรับ (เฉพาะที่ pending)
        $totalOutstanding = FinancialTransaction::where('paid_amount', '>', 0)
            ->where('withholding_tax_amount', '>', 0)
            ->where('wht_status', 'pending')
            ->sum('withholding_tax_amount');

        // Employer list สำหรับ filter dropdown
        $employers = \App\Models\Employer::orderBy('employerNameTh')->get(['id', 'employerNameTh', 'employerNameEn']);

        return view('finance.wht_inbox', compact('transactions', 'totalOutstanding', 'employers', 'employerId', 'status'));
    }

    /**
     * บันทึกการรับใบ ณ ที่จ่าย — อัปโหลดเอกสาร + เปลี่ยน wht_status = 'received'
     */
    public function markWhtReceived(Request $request, $transactionId)
    {
        if (!$this->canManageFinance()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'wht_document' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        $transaction = FinancialTransaction::findOrFail($transactionId);

        // ลบไฟล์เก่าก่อน save ใหม่
        if ($transaction->wht_document_path) {
            Storage::disk('public')->delete($transaction->wht_document_path);
        }

        $transaction->wht_document_path = $request->file('wht_document')->store('financial_slips/wht', 'public');
        $transaction->wht_status = 'received';
        $transaction->wht_received_at = now();
        $transaction->wht_no_cert_reason = null; // เคลียร์เผื่อเคย mark no_certificate ไว้ก่อน
        $transaction->save();

        return redirect()->back()->with('success', __('WHT Received'));
    }

    /**
     * บันทึกว่าลูกค้าไม่ออกใบ ณ ที่จ่าย — บันทึกเหตุผลเพื่อตรวจสอบย้อนหลังได้
     * (เช่น ลูกค้าเป็นบุคคลธรรมดา หรือยืนยันด้วยปากเปล่า)
     */
    public function markWhtNoCertificate(Request $request, $transactionId)
    {
        if (!$this->canManageFinance()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $transaction = FinancialTransaction::findOrFail($transactionId);
        $transaction->wht_status = 'no_certificate';
        $transaction->wht_no_cert_reason = $request->reason;
        $transaction->wht_received_at = now(); // ใช้ track เวลาที่ resolve รายการ
        $transaction->save();

        return redirect()->back()->with('success', __('Mark as No Certificate'));
    }

    /**
     * Store a new installment or payment plan item.
     */
    public function storeTransaction(Request $request, $productionId)
    {
        // Permission check
        if (!auth()->user()->can('manage-finance') && !auth()->user()->hasAnyRole(['admin', 'super-admin'])) {
             return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'amount' => 'required|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'discount_description' => 'nullable|string|max:255',
            'due_date' => 'nullable|date',
            'type' => 'required|in:installment,down_payment,full_payment,advance_payment',
            'notes' => 'nullable|string',
            'financial_group_id' => 'required|exists:production_financial_groups,id',
            'item_ids' => 'nullable|array',
            'item_ids.*' => 'exists:production_items,id',
            'employee_ids' => 'nullable|array',
            'employee_ids.*' => 'exists:employees,id',
            'payment_collection_mode' => 'nullable|in:lump_sum,per_employee',
            'line_items' => 'nullable|array',
            'line_items.*.description' => 'nullable|string|max:255',
            'line_items.*.amount' => 'required_with:line_items|numeric|min:0',
            'line_items.*.fixed_item_id' => 'nullable|string|max:64',
        ]);

        $transaction = FinancialTransaction::create([
            'production_order_id' => $productionId,
            'production_financial_group_id' => $request->financial_group_id,
            'type' => $request->type,
            'amount' => $request->amount,
            'discount_amount' => $request->discount_amount ?? 0,
            'discount_description' => $request->discount_description,
            'due_date' => $request->due_date,
            'notes' => $request->notes,
            'line_items' => $request->line_items ?: null,
            'status' => 'pending',
            'payment_collection_mode' => $request->payment_collection_mode ?? 'lump_sum',
        ]);

        $finalItemIds = $request->item_ids ?? [];
        $createdItemMap = []; // empId => itemId for syncing pricing tiers

        // Handle direct Employee IDs (Create items on the fly)
        if ($request->has('employee_ids') && is_array($request->employee_ids)) {
            foreach ($request->employee_ids as $empId) {
                // Find or Create ProductionItem for this order/employee
                $item = ProductionItem::firstOrCreate(
                    [
                        'production_order_id' => $productionId,
                        'employee_id' => $empId
                    ],
                    [
                        'status' => 'pending', // Or registration_pending context dependent? Safe default.
                        'group_name' => null
                    ]
                );
                $finalItemIds[] = $item->id;
                $createdItemMap[$empId] = $item->id;
            }
        }

        $finalItemIds = array_unique($finalItemIds);

        if (!empty($finalItemIds)) {
            $transaction->items()->attach($finalItemIds);
        }

        // Sync Pricing Tiers: Replace emp_X with new Item IDs in the pricing group
        // This ensures invoices can map the new item back to its price tier correctly.
        $this->syncPricingTiers($request->financial_group_id, $createdItemMap);

        // Per-head: remember each employee's price on this bill (later tier edits don't change it).
        $transaction->snapshotItemPrices();

        // Return transaction with items to update frontend state if needed
        $transaction->load('items.employee'); // Load employee for name display

        return response()->json([
            'success' => true,
            'message' => 'Transaction added.',
            'transaction' => $transaction
        ]);
    }

    /**
     * Update transaction (e.g., mark paid, upload slip, sync items, etc.)
     */
    public function updateTransaction(Request $request, $id)
    {
        if (!$this->canManageFinance()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $transaction = FinancialTransaction::with('payments')->findOrFail($id);
        $request->validate(self::UPDATE_TRANSACTION_RULES);

        if ($request->has('payment_collection_mode')) {
            $blockReason = $this->paymentCollectionModeSwitchBlockReason($transaction, $request->payment_collection_mode);
            if ($blockReason) {
                return response()->json(['success' => false, 'message' => $blockReason], 422);
            }
        }

        // ค่าใช้จ่ายเพิ่มเติม / employees taken off / a new price tier for one
        // employee — checked BEFORE anything is saved (2026-11-20).
        $extraCharges = $this->parseExtraCharges($request);
        if ($extraCharges instanceof \Illuminate\Http\JsonResponse) {
            return $extraCharges;
        }
        $tierChanges = $this->parseItemTierChanges($request);
        if ($tierChanges instanceof \Illuminate\Http\JsonResponse) {
            return $tierChanges;
        }
        if ($tierChanges) {
            $savedTiers = $transaction->financialGroup()->first()?->financial_data['pricing_tiers'] ?? [];
            foreach ($tierChanges as $c) {
                if (! isset($savedTiers[$c['tier']]) || abs((float) ($savedTiers[$c['tier']]['price'] ?? 0) - $c['price']) > 0.005) {
                    return response()->json(['success' => false, 'message' => __('The price tiers have changes that are not saved yet — save the pricing settings first, then pick the price again.')], 422);
                }
            }
        }
        if ($blocked = $this->billEditBlockReason($transaction, $request, $extraCharges)) {
            return response()->json(['success' => false, 'message' => $blocked], 422);
        }

        // A lower price after money was received: the operator chooses what happens
        // to the extra money (keep / refund / move to another bill / count toward
        // others on this bill) — asked for with code "overpaid" (2026-11-21).
        $plan = $this->overpaymentPlan($transaction, $request, $extraCharges, $tierChanges);
        if ($plan['excess'] > 0) {
            if (! $request->filled('overpayment_action')) {
                return $this->overpaidResponse($transaction, $plan);
            }
            if ($why = $this->overpaymentActionError($transaction, $plan, $request)) {
                return $plan['legacy']
                    ? response()->json(['success' => false, 'message' => $why], 422)
                    : $this->overpaidResponse($transaction, $plan, $why);
            }
        }

        // All or nothing — the bill, its people / prices and the money moved.
        DB::transaction(function () use ($transaction, $request, $extraCharges, $tierChanges, $plan) {
            $this->applyTransactionFields($transaction, $request);
            $this->applyTransactionFiles($transaction, $request);
            if ($extraCharges !== null) {
                $transaction->extra_charges = $extraCharges ?: null;
            }
            // Status follows the money unless the user picked one themselves (e.g. 'overdue').
            if (!$request->has('status')) {
                $this->recalcBillStatus($transaction);
            }
            $transaction->save();

            $this->syncTransactionItems($transaction, $request);
            if ($tierChanges) {
                $this->applyItemTierChanges($transaction, $tierChanges);
            }
            if ($plan['excess'] > 0) {
                $this->applyOverpayment($transaction, $plan, $request);
            }
        });

        $transaction->load(['items.employee', 'payments.bankAccount', 'bankAccount', 'itemPayments']);
        $others = ($plan['excess'] > 0 && $request->input('overpayment_action') === 'transfer')
            ? FinancialTransaction::with(['items.employee', 'payments.bankAccount', 'bankAccount', 'itemPayments'])->whereKey((int) $request->input('overpayment_target_id'))->get()
            : collect();
        return response()->json(['success' => true, 'transaction' => $transaction, 'other_transactions' => $others]);
    }

    /**
     * Store a payment for a transaction.
     */
    public function storePayment(Request $request, $transactionId)
    {
         if (!auth()->user()->can('manage-finance') && !auth()->user()->hasAnyRole(['admin', 'super-admin'])) {
             return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $transaction = FinancialTransaction::findOrFail($transactionId);

        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'paid_at' => 'required|date',
            'bank_account_id' => 'nullable|exists:bank_accounts,id',
            'slip_file' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            // If no bank account is picked, the payment won't post into
            // "บันทึกรายรับรายจ่าย" at all (see below). Notes are NOT
            // required in that case — the frontend instead asks the user
            // to confirm they really mean to save without a bank account,
            // and any explanation is theirs to add voluntarily. (If a hard
            // requirement to always pick a bank account is wanted later,
            // that's a deliberate future change, not this one.)
            'notes' => 'nullable|string'
        ]);

        if ($blocked = $this->collectionModeBlockedBy($transaction, 'lump_sum')) {
            return response()->json(['success' => false, 'message' => $blocked], 422);
        }

        // เก็บไฟล์ก่อน DB transaction — ถ้า DB fail จะลบไฟล์ใน catch
        $slipPath = null;
        if ($request->hasFile('slip_file')) {
            $slipPath = $request->file('slip_file')->store('financial_slips', 'public');
        }

        try {
            DB::transaction(function () use ($request, $transaction, $slipPath) {
                $transaction->payment_collection_mode = 'lump_sum'; // first pay-in decides — saved below

                $payment = $transaction->payments()->create([
                    'amount' => $request->amount,
                    'paid_at' => \Carbon\Carbon::parse($request->paid_at),
                    'bank_account_id' => $request->bank_account_id,
                    'slip_path' => $slipPath,
                    'notes' => $request->notes,
                    'created_by' => auth()->id()
                ]);

                // Recalculate paid_amount จาก DB (ไม่ใช้ in-memory += กัน race condition)
                $transaction->paid_amount = $transaction->payments()->sum('amount');
                $transaction->paid_at = now();

                // Minus issued credit notes too — same rule as CreditNoteService::recalculateTransaction().
                $effectiveAmount = $transaction->amount - ($transaction->discount_amount ?? 0) - ($transaction->credit_amount ?? 0);
                if ($transaction->paid_amount >= $effectiveAmount) {
                    $transaction->status = 'paid';
                } else {
                    $transaction->status = 'partial';
                }
                $transaction->save();

                // Post into "บันทึกรายรับรายจ่าย" (LedgerEntry) instead of
                // mutating current_balance directly — see LedgerService and
                // its 05:00-cutoff lock (AccountingPeriodService). A
                // payment with no bank_account_id has nothing to post
                // against, so it's skipped here — the frontend confirms
                // with the user before allowing that, and any `notes` are
                // whatever explanation they chose to add.
                if ($payment->bank_account_id) {
                    app(LedgerService::class)->createEntry([
                        'entry_date' => $payment->paid_at->toDateString(),
                        'type' => 'income',
                        'bank_account_id' => $payment->bank_account_id,
                        'category_id' => $this->defaultIncomeCategory()->id,
                        'category_type' => 'income',
                        'counterparty_name' => optional(optional($transaction->productionOrder)->employer)->employerNameTh,
                        'gross_amount' => $payment->amount,
                        'description' => $this->paymentLedgerDescription($transaction),
                        'source_type' => FinancialPayment::class,
                        'source_id' => $payment->id,
                    ]);
                }
            });
        } catch (\Exception $e) {
            // DB fail — ลบไฟล์ที่ upload ไปแล้วเพื่อกัน orphan
            if ($slipPath) {
                Storage::disk('public')->delete($slipPath);
            }
            throw $e;
        }

        $transaction->load(['items.employee', 'payments', 'bankAccount', 'itemPayments']);
        return response()->json(['success' => true, 'transaction' => $transaction]);
    }

    /**
     * Human-readable description for the LedgerEntry a bill payment posts
     * — references the production order so the books entry can be traced
     * back to its source bill, same intent as Labor's "(View bill LB-...)"
     * link on team_payment book transactions.
     */
    protected function paymentLedgerDescription(FinancialTransaction $transaction): string
    {
        $employerName = optional(optional($transaction->productionOrder)->employer)->employerNameTh;
        $orderId = $transaction->production_order_id;

        return __('Payment received — Production Order #:order:employer', [
            'order' => $orderId,
            'employer' => $employerName ? " ({$employerName})" : '',
        ]);
    }

    /**
     * The single default IncomeCategory every auto-posted bill-payment
     * entry uses (income_categories was empty before this integration) —
     * created once, on first use, rather than requiring a seeder.
     */
    protected function defaultIncomeCategory(): IncomeCategory
    {
        return IncomeCategory::firstOrCreate(
            ['code' => 'CUSTOMER_PAYMENT'],
            ['name' => __('Customer Bill Payment'), 'is_active' => true]
        );
    }

    /**
     * Update an existing payment.
     */
    public function updatePayment(Request $request, $paymentId)
    {
         if (!auth()->user()->can('manage-finance') && !auth()->user()->hasAnyRole(['admin', 'super-admin'])) {
             return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $payment = FinancialPayment::with('transaction')->findOrFail($paymentId);
        $transaction = $payment->transaction;

        if ($payment->isAdjustment()) {
            return response()->json(['success' => false, 'message' => __('This entry was made by the system when a price was lowered — delete it to undo it.')], 422);
        }

        // A per-employee round's total comes from its entries — edit those instead.
        if (FinancialTransactionItemPayment::where('financial_payment_id', $payment->id)->exists()) {
            return response()->json(['success' => false, 'message' => __('This total comes from each person\'s entries — change those instead.')], 422);
        }

        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'paid_at' => 'required|date',
            'bank_account_id' => 'nullable|exists:bank_accounts,id',
            'slip_file' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            // Same as storePayment() — no bank account no longer forces
            // notes to be filled in; the frontend confirms with the user
            // instead. See the comment there for the full reasoning.
            'notes' => 'nullable|string'
        ]);

        $oldSlipPath = $payment->slip_path;

        // อัพโหลดไฟล์ใหม่ก่อน (ยังไม่ลบของเก่า) — กัน data loss ถ้า upload fail
        $newSlipPath = null;
        if ($request->hasFile('slip_file')) {
            $newSlipPath = $request->file('slip_file')->store('financial_slips', 'public');
        }

        try {
            DB::transaction(function () use ($request, $payment, $transaction, $newSlipPath) {
                if ($newSlipPath) {
                    $payment->slip_path = $newSlipPath;
                }

                $payment->amount = $request->amount;
                $payment->paid_at = \Carbon\Carbon::parse($request->paid_at);
                $payment->bank_account_id = $request->bank_account_id;
                $payment->notes = $request->notes;
                $payment->save();

                // Keep the linked LedgerEntry (if any) in sync — this is
                // where the 05:00 lock applies: if the payment's existing
                // ledger entry is from an already-closed day,
                // LedgerService::updateEntry()/deleteEntry() throws
                // LedgerEntryLockedException, which aborts this whole
                // transaction (self-renders a clear error — see that
                // exception class) instead of silently drifting.
                $ledgerService = app(LedgerService::class);
                $linked = $payment->ledgerEntry()->first();

                if ($linked && !$payment->bank_account_id) {
                    $ledgerService->deleteEntry($linked);
                } elseif ($linked && $payment->bank_account_id) {
                    $ledgerService->updateEntry($linked, [
                        'entry_date' => $payment->paid_at->toDateString(),
                        'type' => 'income',
                        'bank_account_id' => $payment->bank_account_id,
                        'category_id' => $linked->category_id ?? $this->defaultIncomeCategory()->id,
                        'category_type' => 'income',
                        'gross_amount' => $payment->amount,
                        'description' => $this->paymentLedgerDescription($transaction),
                    ]);
                } elseif (!$linked && $payment->bank_account_id) {
                    $ledgerService->createEntry([
                        'entry_date' => $payment->paid_at->toDateString(),
                        'type' => 'income',
                        'bank_account_id' => $payment->bank_account_id,
                        'category_id' => $this->defaultIncomeCategory()->id,
                        'category_type' => 'income',
                        'gross_amount' => $payment->amount,
                        'description' => $this->paymentLedgerDescription($transaction),
                        'source_type' => FinancialPayment::class,
                        'source_id' => $payment->id,
                    ]);
                }

                // Recalculate transaction totals
                $newTotalPaid = $transaction->payments()->sum('amount');
                $transaction->paid_amount = $newTotalPaid;

                // Minus issued credit notes too — same rule as CreditNoteService::recalculateTransaction().
                $effectiveAmount = $transaction->amount - ($transaction->discount_amount ?? 0) - ($transaction->credit_amount ?? 0);
                if ($newTotalPaid >= $effectiveAmount) {
                    $transaction->status = 'paid';
                } else if ($newTotalPaid > 0 || ($transaction->credit_amount ?? 0) > 0) {
                    $transaction->status = 'partial';
                } else {
                    $transaction->status = 'pending';
                }
                $transaction->save();
            });
        } catch (\Exception $e) {
            // DB fail — ลบไฟล์ใหม่ที่ upload ไปแล้ว (ของเก่ายังอยู่ครบ — ไม่มี data loss)
            if ($newSlipPath) {
                Storage::disk('public')->delete($newSlipPath);
            }
            throw $e;
        }

        // DB success แล้ว → ลบไฟล์เก่าได้ (ตอนนี้ DB ชี้ไปที่ไฟล์ใหม่แล้ว)
        if ($newSlipPath && $oldSlipPath) {
            Storage::disk('public')->delete($oldSlipPath);
        }

        $transaction->load(['items.employee', 'payments', 'bankAccount', 'itemPayments']);
        return response()->json(['success' => true, 'transaction' => $transaction]);
    }

    /**
     * Delete an existing payment.
     */
    public function destroyPayment($paymentId)
    {
         if (!auth()->user()->can('manage-finance') && !auth()->user()->hasAnyRole(['admin', 'super-admin'])) {
             return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $payment = FinancialPayment::with('transaction')->findOrFail($paymentId);
        $transaction = $payment->transaction;
        $slipPath = $payment->slip_path; // เก็บ path ไว้ลบหลัง DB commit สำเร็จ

        // Refund / transfer / adjustment made when a price was lowered: undone as a
        // whole (a transfer's other half too) (2026-11-21).
        if ($payment->isAdjustment()) {
            $bills = DB::transaction(fn () => $this->undoOverpaymentRow($payment));
            $transaction->refresh()->load(['items.employee', 'payments.bankAccount', 'bankAccount', 'itemPayments']);
            $others = FinancialTransaction::with(['items.employee', 'payments.bankAccount', 'bankAccount', 'itemPayments'])
                ->whereKey(array_diff(array_keys($bills), [$transaction->id]))->get();

            return response()->json(['success' => true, 'transaction' => $transaction, 'other_transactions' => $others]);
        }

        // A per-employee round whose receipt / tax invoice was issued is final.
        if ($payment->receipt_generated_at && FinancialTransactionItemPayment::where('financial_payment_id', $payment->id)->exists()) {
            return response()->json(['success' => false, 'message' => __('A receipt or tax invoice has been issued for this payment, so it can no longer be changed.')], 422);
        }

        DB::transaction(function () use ($payment, $transaction) {
            // Reverse the linked LedgerEntry (if any) — throws
            // LedgerEntryLockedException (aborting this whole delete) if
            // its accounting day is already closed, same reasoning as
            // updatePayment() above.
            $linked = $payment->ledgerEntry()->first();
            if ($linked) {
                app(LedgerService::class)->deleteEntry($linked);
            }

            $payment->delete();

            // Recalculate transaction totals
            $newTotalPaid = $transaction->payments()->sum('amount');
            $transaction->paid_amount = $newTotalPaid;

            // Minus issued credit notes too — same rule as CreditNoteService::recalculateTransaction().
            $effectiveAmount = $transaction->amount - ($transaction->discount_amount ?? 0) - ($transaction->credit_amount ?? 0);
            if ($newTotalPaid >= $effectiveAmount) {
                $transaction->status = 'paid';
            } else if ($newTotalPaid > 0 || ($transaction->credit_amount ?? 0) > 0) {
                $transaction->status = 'partial';
            } else {
                $transaction->status = 'pending';
            }
            $transaction->save();
        });

        // ลบไฟล์สลิปหลัง DB commit สำเร็จ — กัน orphan files
        if ($slipPath) {
            Storage::disk('public')->delete($slipPath);
        }

        $transaction->load(['items.employee', 'payments', 'bankAccount', 'itemPayments']);
        return response()->json(['success' => true, 'transaction' => $transaction]);
    }

    // =========================================================================
    // Per-Employee Collection (FinancialTransaction.payment_collection_mode
    // === 'per_employee') — each employee pays their own share, possibly in
    // several partial payments. These entries are the detail ledger; a batch
    // of them rolls up into ONE FinancialPayment (see finalizeItemPayments())
    // so the existing receipt/ledger/paid_amount logic above never has to
    // change — it just sees one more payment, like it always has.
    // =========================================================================

    /**
     * Record one employee's one payment. Starts unassigned to any receipt
     * (financial_payment_id null) until finalizeItemPayments() rolls it up.
     */
    public function storeItemPayment(Request $request, $transactionId)
    {
        if (!$this->canManageFinance()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $transaction = FinancialTransaction::findOrFail($transactionId);

        $validated = $request->validate([
            'production_item_id' => 'required|exists:production_items,id',
            'amount' => 'required|numeric|min:0.01',
            'paid_at' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        // The item must actually be attached to this bill — otherwise a
        // payment could be recorded against someone not even on it.
        $attached = $transaction->items()->where('production_items.id', $validated['production_item_id'])->exists();
        if (!$attached) {
            return response()->json(['success' => false, 'message' => 'This employee is not attached to this bill.'], 422);
        }

        if ($blocked = $this->collectionModeBlockedBy($transaction, 'per_employee')) {
            return response()->json(['success' => false, 'message' => $blocked], 422);
        }
        if ($transaction->payment_collection_mode !== 'per_employee') {
            $transaction->update(['payment_collection_mode' => 'per_employee']);
        }

        $entry = $transaction->itemPayments()->create([
            'production_item_id' => $validated['production_item_id'],
            'amount' => $validated['amount'],
            'paid_at' => \Carbon\Carbon::parse($validated['paid_at']),
            'notes' => $validated['notes'] ?? null,
            'created_by' => auth()->id(),
        ]);

        return response()->json(['success' => true, 'entry' => $entry]);
    }

    /**
     * Edit one per-employee payment entry. If it was already rolled into a
     * receipt (financial_payment_id set), that receipt's amount/ledger entry
     * is recalculated from the new sum of its entries — same safeguards
     * (05:00 closed-day lock) as updatePayment() above.
     */
    public function updateItemPayment(Request $request, $id)
    {
        if (!$this->canManageFinance()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $entry = FinancialTransactionItemPayment::with('payment', 'transaction')->findOrFail($id);

        if ($locked = $this->lockedEntryResponse($entry)) {
            return $locked;
        }
        if ($entry->payment && $entry->payment->isAdjustment()) {
            return response()->json(['success' => false, 'message' => __('This entry was made by the system when a price was lowered — delete it to undo it.')], 422);
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'paid_at' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        // The bill can't end up collected for more than it is worth.
        $others = $entry->transaction->itemPayments()->where('id', '!=', $entry->id)->sum('amount');
        if ($others + (float) $validated['amount'] > $this->billEffectiveAmount($entry->transaction) + 0.005) {
            return response()->json(['success' => false, 'message' => __('The total is more than what is left on this bill.')], 422);
        }
        // An additional charge can't be collected for more than it is (2026-11-20).
        if ($entry->extra_charge_id) {
            $charge = collect($entry->transaction->extra_charges ?? [])->firstWhere('id', $entry->extra_charge_id);
            $otherForCharge = $entry->transaction->itemPayments()->where('id', '!=', $entry->id)->where('extra_charge_id', $entry->extra_charge_id)->sum('amount');
            if ($charge && $otherForCharge + (float) $validated['amount'] > (float) $charge['amount'] + 0.005) {
                return response()->json(['success' => false, 'message' => __('More than is left on the additional charge ":name".', ['name' => $charge['description']])], 422);
            }
        }

        $transaction = null;
        DB::transaction(function () use ($entry, $validated, &$transaction) {
            $entry->amount = $validated['amount'];
            $entry->paid_at = \Carbon\Carbon::parse($validated['paid_at']);
            $entry->notes = $validated['notes'] ?? null;
            $entry->save();

            if ($entry->financial_payment_id) {
                $transaction = $this->resyncRoundPayment($entry->financial_payment_id);
            }
        });

        $response = ['success' => true, 'entry' => $entry->fresh()];
        if ($transaction) {
            $transaction->load(['items.employee', 'payments.bankAccount', 'bankAccount', 'itemPayments']);
            $response['transaction'] = $transaction;
        }

        return response()->json($response);
    }

    /**
     * Delete one per-employee payment entry — same round-recalculation as
     * updateItemPayment() above (removes the receipt entirely if it was the
     * last entry left in that round).
     */
    public function destroyItemPayment($id)
    {
        if (!$this->canManageFinance()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $entry = FinancialTransactionItemPayment::with('payment')->findOrFail($id);

        if ($locked = $this->lockedEntryResponse($entry)) {
            return $locked;
        }
        if ($entry->payment && $entry->payment->isAdjustment()) {
            return response()->json(['success' => false, 'message' => __('This entry was made by the system when a price was lowered — delete it to undo it.')], 422);
        }

        $roundPaymentId = $entry->financial_payment_id;

        $transaction = null;
        DB::transaction(function () use ($entry, $roundPaymentId, &$transaction) {
            $entry->delete();
            if ($roundPaymentId) {
                $transaction = $this->resyncRoundPayment($roundPaymentId);
            }
        });

        $response = ['success' => true];
        if ($transaction) {
            $transaction->load(['items.employee', 'payments.bankAccount', 'bankAccount', 'itemPayments']);
            $response['transaction'] = $transaction;
        }

        return response()->json($response);
    }

    /**
     * Roll up every not-yet-assigned per-employee entry on this bill into
     * ONE new FinancialPayment — reuses the exact same recalculation/ledger
     * logic as a normal lump-sum "Add Payment" (storePayment() above), just
     * with the amount computed as a sum instead of typed in directly. The
     * receipt date is always "today" (the day this button is pressed), not
     * each person's own payment date, which can differ per person.
     */
    public function finalizeItemPayments(Request $request, $transactionId)
    {
        if (!$this->canManageFinance()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $transaction = FinancialTransaction::findOrFail($transactionId);

        $request->validate([
            'bank_account_id' => 'nullable|exists:bank_accounts,id',
            'notes' => 'nullable|string',
        ]);

        $pending = $transaction->itemPayments()->whereNull('financial_payment_id')->get();
        if ($pending->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'No new individual payments to collect.'], 422);
        }

        $sum = $pending->sum('amount');

        DB::transaction(function () use ($request, $transaction, $pending, $sum) {
            $this->rollUpIntoPayment($transaction, $pending->pluck('id')->all(), $sum, now(), $request->input('bank_account_id'), $request->input('notes'));
        });

        $transaction->load(['items.employee', 'payments.bankAccount', 'bankAccount', 'itemPayments']);
        return response()->json(['success' => true, 'transaction' => $transaction, 'payment_amount' => $sum]);
    }

    /**
     * Save one collection round in one go: what each ticked-in employee paid
     * now, plus any earlier entries not combined yet, become ONE FinancialPayment
     * dated the day the money was received — all or nothing. This is what
     * the Individual Collection popup's save button calls.
     */
    public function saveItemPaymentRound(Request $request, $transactionId)
    {
        if (!$this->canManageFinance()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $transaction = FinancialTransaction::findOrFail($transactionId);

        $validated = $request->validate([
            'paid_at' => 'required|date',
            'bank_account_id' => 'nullable|exists:bank_accounts,id',
            'notes' => 'nullable|string',
            'rows' => 'nullable|array',
            // a person on the bill, or one of its additional charges (2026-11-20)
            'rows.*.production_item_id' => 'nullable|integer|required_without:rows.*.extra_charge_id',
            'rows.*.extra_charge_id' => 'nullable|string|max:40',
            'rows.*.amount' => 'required|numeric|min:0.01',
            'rows.*.notes' => 'nullable|string',
        ]);

        // The mode chosen when the bill was created is only a starting point: the
        // first money collected decides how this bill is collected from then on.
        if ($blocked = $this->collectionModeBlockedBy($transaction, 'per_employee')) {
            return response()->json(['success' => false, 'message' => $blocked], 422);
        }

        $rows = collect($validated['rows'] ?? []);
        $attached = $transaction->items()->pluck('production_items.id')->map(fn ($id) => (int) $id)->all();
        $personRows = $rows->filter(fn ($row) => empty($row['extra_charge_id']));
        if ($personRows->contains(fn ($row) => !in_array((int) $row['production_item_id'], $attached, true))) {
            return response()->json(['success' => false, 'message' => 'This employee is not attached to this bill.'], 422);
        }
        // Additional-charge rows: must be a charge of this bill, and not more than is left on it.
        $charges = collect($transaction->extra_charges ?? [])->keyBy('id');
        foreach ($rows->filter(fn ($row) => !empty($row['extra_charge_id']))->groupBy('extra_charge_id') as $chargeId => $chargeRows) {
            $charge = $charges->get($chargeId);
            if (!$charge || ($charge['kind'] ?? null) === 'overpayment') {
                return response()->json(['success' => false, 'message' => __('This additional charge is not on this bill.')], 422);
            }
            $already = (float) $transaction->itemPayments()->where('extra_charge_id', $chargeId)->sum('amount');
            if ($already + (float) $chargeRows->sum('amount') > (float) $charge['amount'] + 0.005) {
                return response()->json(['success' => false, 'message' => __('More than is left on the additional charge ":name".', ['name' => $charge['description']])], 422);
            }
        }

        $pending = $transaction->itemPayments()->whereNull('financial_payment_id')->get();
        $sum = round((float) $rows->sum('amount') + (float) $pending->sum('amount'), 2);
        if ($sum <= 0) {
            return response()->json(['success' => false, 'message' => 'No new individual payments to collect.'], 422);
        }

        $collected = (float) $transaction->itemPayments()->sum('amount') + (float) $rows->sum('amount');
        if ($collected > $this->billEffectiveAmount($transaction) + 0.005) {
            return response()->json(['success' => false, 'message' => __('The total is more than what is left on this bill.')], 422);
        }

        $paidAt = \Carbon\Carbon::parse($validated['paid_at']);

        DB::transaction(function () use ($transaction, $rows, $pending, $sum, $paidAt, $validated) {
            $transaction->payment_collection_mode = 'per_employee'; // saved by rollUpIntoPayment()
            $ids = $pending->pluck('id')->all();
            foreach ($rows as $row) {
                $ids[] = $transaction->itemPayments()->create([
                    'production_item_id' => empty($row['extra_charge_id']) ? $row['production_item_id'] : null,
                    'extra_charge_id' => $row['extra_charge_id'] ?? null,
                    'amount' => $row['amount'],
                    'paid_at' => $paidAt,
                    'notes' => $row['notes'] ?? null,
                    'created_by' => auth()->id(),
                ])->id;
            }
            $this->rollUpIntoPayment($transaction, $ids, $sum, $paidAt, $validated['bank_account_id'] ?? null, $validated['notes'] ?? null);
        });

        $transaction->load(['items.employee', 'payments.bankAccount', 'bankAccount', 'itemPayments']);
        return response()->json(['success' => true, 'transaction' => $transaction, 'payment_amount' => $sum]);
    }

    /**
     * Turn the given per-employee entries into one FinancialPayment — the same
     * recalculation / ledger posting as a lump-sum "Add Payment" (storePayment()).
     * Call inside a DB transaction.
     */
    private function rollUpIntoPayment(FinancialTransaction $transaction, array $entryIds, float $sum, $paidAt, $bankAccountId, $notes): FinancialPayment
    {
        $payment = $transaction->payments()->create([
            'amount' => $sum,
            'paid_at' => $paidAt,
            'bank_account_id' => $bankAccountId ?: null,
            'notes' => $notes ?: null,
            'created_by' => auth()->id(),
        ]);

        FinancialTransactionItemPayment::whereIn('id', $entryIds)->update(['financial_payment_id' => $payment->id]);

        // Recalculate paid_amount จาก DB — same as storePayment() above.
        $transaction->paid_amount = $transaction->payments()->sum('amount');
        $transaction->paid_at = $paidAt;

        $effectiveAmount = $transaction->amount - ($transaction->discount_amount ?? 0) - ($transaction->credit_amount ?? 0);
        $transaction->status = $transaction->paid_amount >= $effectiveAmount ? 'paid' : 'partial';
        $transaction->save();

        if ($payment->bank_account_id) {
            app(LedgerService::class)->createEntry([
                'entry_date' => $payment->paid_at->toDateString(),
                'type' => 'income',
                'bank_account_id' => $payment->bank_account_id,
                'category_id' => $this->defaultIncomeCategory()->id,
                'category_type' => 'income',
                'counterparty_name' => optional(optional($transaction->productionOrder)->employer)->employerNameTh,
                'gross_amount' => $payment->amount,
                'description' => $this->paymentLedgerDescription($transaction),
                'source_type' => FinancialPayment::class,
                'source_id' => $payment->id,
            ]);
        }

        return $payment;
    }

    /**
     * Collecting in $mode: free while no money has been recorded on the bill
     * (whatever was picked when it was created), then fixed to the way it was
     * first collected until those payments are deleted. Reason text if refused.
     */
    private function collectionModeBlockedBy(FinancialTransaction $transaction, string $mode): ?string
    {
        $hasMoney = $transaction->payments()->exists() || $transaction->itemPayments()->exists();
        if (!$hasMoney || ($transaction->payment_collection_mode ?? 'lump_sum') === $mode) {
            return null;
        }

        return $mode === 'lump_sum'
            ? __('This bill is already collected individually. Delete those payments first to collect it as a lump sum.')
            : __('This bill is already collected as a lump sum. Delete those payments first to collect it individually.');
    }

    /** What the bill is worth after discount and credit notes. */
    private function billEffectiveAmount(FinancialTransaction $transaction): float
    {
        return (float) $transaction->amount - (float) ($transaction->discount_amount ?? 0) - (float) ($transaction->credit_amount ?? 0);
    }

    /**
     * A round whose receipt / tax invoice was issued (FinancialPayment::
     * receipt_generated_at) is final: the amount on the document must stay
     * what the system says. 422 response if this entry belongs to one.
     */
    private function lockedEntryResponse(FinancialTransactionItemPayment $entry)
    {
        if ($entry->payment && $entry->payment->receipt_generated_at) {
            return response()->json(['success' => false, 'message' => __('A receipt or tax invoice has been issued for this payment, so it can no longer be changed.')], 422);
        }

        return null;
    }

    /**
     * Recompute one collection round's aggregate FinancialPayment from the
     * current sum of its linked item-payments — called after an edit/delete
     * touches an entry that's already part of a finalized round. Deletes the
     * FinancialPayment (and reverses its ledger entry, same as
     * destroyPayment() above) if no entries are left linked to it. Returns
     * the owning FinancialTransaction (refreshed) for the response payload.
     */
    private function resyncRoundPayment(int $paymentId): ?FinancialTransaction
    {
        $payment = FinancialPayment::with('transaction')->find($paymentId);
        if (!$payment) {
            return null;
        }
        $transaction = $payment->transaction;
        $ledgerService = app(LedgerService::class);

        $newSum = FinancialTransactionItemPayment::where('financial_payment_id', $paymentId)->sum('amount');

        if ($newSum <= 0) {
            $linked = $payment->ledgerEntry()->first();
            if ($linked) {
                $ledgerService->deleteEntry($linked);
            }
            $payment->delete();
        } else {
            $payment->amount = $newSum;
            $payment->save();

            $linked = $payment->ledgerEntry()->first();
            if ($linked) {
                $ledgerService->updateEntry($linked, [
                    'entry_date' => $payment->paid_at->toDateString(),
                    'type' => 'income',
                    'bank_account_id' => $payment->bank_account_id,
                    'category_id' => $linked->category_id ?? $this->defaultIncomeCategory()->id,
                    'category_type' => 'income',
                    'gross_amount' => $newSum,
                    'description' => $this->paymentLedgerDescription($transaction),
                ]);
            }
        }

        $newTotalPaid = $transaction->payments()->sum('amount');
        $transaction->paid_amount = $newTotalPaid;
        $effectiveAmount = $transaction->amount - ($transaction->discount_amount ?? 0) - ($transaction->credit_amount ?? 0);
        if ($newTotalPaid >= $effectiveAmount) {
            $transaction->status = 'paid';
        } elseif ($newTotalPaid > 0 || ($transaction->credit_amount ?? 0) > 0) {
            $transaction->status = 'partial';
        } else {
            $transaction->status = 'pending';
        }
        $transaction->save();

        return $transaction;
    }

    /**
     * Helper to update Pricing Tiers: Replace candidate emp_IDs with real ProductionItem IDs.
     */
    private function syncPricingTiers($financialGroupId, $createdItemMap)
    {
        if (empty($createdItemMap)) return;

        $group = \App\Models\ProductionFinancialGroup::find($financialGroupId);
        if (!$group) return;

        $financialData = $group->financial_data;
        $tiers = $financialData['pricing_tiers'] ?? [];
        $updated = false;

        foreach ($tiers as &$tier) {
            if (isset($tier['item_ids']) && is_array($tier['item_ids'])) {
                $newItemIds = [];
                foreach ($tier['item_ids'] as $id) {
                    // Check if this ID is a candidate placeholder (string starting with 'emp_')
                    if (is_string($id) && str_starts_with($id, 'emp_')) {
                        $empId = str_replace('emp_', '', $id);
                        // If we have created a ProductionItem for this Employee ID, swap it
                        if (isset($createdItemMap[$empId])) {
                            $newItemIds[] = $createdItemMap[$empId];
                            $updated = true;
                        } else {
                            $newItemIds[] = $id;
                        }
                    } else {
                        $newItemIds[] = $id;
                    }
                }
                $tier['item_ids'] = $newItemIds;
                // Update count
                $tier['count'] = count($newItemIds);
            }
        }

        if ($updated) {
            $financialData['pricing_tiers'] = $tiers;
            $group->financial_data = $financialData;
            $group->save();
        }
    }

    /**
     * Delete a transaction.
     */
    public function destroyTransaction($id)
    {
        if (!auth()->user()->can('manage-finance') && !auth()->user()->hasAnyRole(['admin', 'super-admin'])) {
             return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $transaction = FinancialTransaction::with('payments.ledgerEntry')->findOrFail($id);

        // A fully paid bill is a closed record — deleting it would erase its
        // payment history. Corrections go through a credit note instead.
        if ($transaction->isFullyPaid()) {
            return response()->json(['success' => false, 'message' => __('A fully paid bill cannot be deleted. To correct the amount, issue a credit note.')], 422);
        }

        // Money moved from / to another bill: undo that first, or the other bill's total breaks.
        if ($transaction->payments->contains(fn ($p) => in_array($p->kind, ['transfer_in', 'transfer_out'], true))) {
            return response()->json(['success' => false, 'message' => __('This bill has money moved from or to another bill — delete that entry first.')], 422);
        }

        // Individually collected bill with an issued receipt / tax invoice: final.
        if ($transaction->payment_collection_mode === 'per_employee' && $transaction->payments->contains(fn ($p) => $p->receipt_generated_at)) {
            return response()->json(['success' => false, 'message' => __('A receipt or tax invoice has been issued for this payment, so it can no longer be changed.')], 422);
        }

        // Wrapped in one transaction so a locked (closed-day) payment
        // aborts the WHOLE delete rather than leaving it half-done.
        DB::transaction(function () use ($transaction) {
            $ledgerService = app(LedgerService::class);
            foreach ($transaction->payments as $payment) {
                if ($payment->ledgerEntry) {
                    $ledgerService->deleteEntry($payment->ledgerEntry);
                }
            }

            $transaction->delete(); // payments will cascade via DB if configured, but let's be safe.
        });

        return response()->json(['success' => true]);
    }

    /**
     * Generate Document (Quotation, Invoice, Receipt, Tax Invoice).
     */
    public function generateDocument(Request $request, $productionId, $type)
    {
        ActivityLogHelper::logAction('generate_document', 'สร้างเอกสารการเงิน (' . $type . ') Production #' . $productionId, ProductionOrder::class, $productionId, [
            'document_type' => $type,
        ]);

        // ... (Keeping existing logic for safety, though likely unused)
        $production = ProductionOrder::with(['employer', 'items.employee', 'financialGroups'])->findOrFail($productionId);

        $transactionIds = $request->query('transaction_ids');
        if ($transactionIds) {
            $ids = explode(',', $transactionIds);
            $transactions = FinancialTransaction::whereIn('id', $ids)->orderBy('due_date')->get();
        } else {
            $transactions = FinancialTransaction::where('production_order_id', $productionId)->orderBy('due_date')->get();
        }

        $activeGroup = null;
        if ($transactions->isNotEmpty()) {
            $activeGroup = $transactions->first()->financialGroup;
        }

        if (!$activeGroup && $request->has('group_id')) {
            $activeGroup = $production->financialGroups->where('id', $request->query('group_id'))->first();
        }

        if (!$activeGroup) {
            $activeGroup = $production->financialGroups->first();
        }

        $financial = $activeGroup ? $activeGroup->financial_data : ($production->financial_data ?? []);

        $profileId = $request->query('profile_id') ?? ($financial['profile_id'] ?? null);
        $profile = $profileId ? CompanyProfile::find($profileId) : CompanyProfile::where('is_default', true)->first();

        $customHeader = $financial['custom_header'] ?? null;
        if (!empty($customHeader) && !empty($customHeader['name'])) {
            $profile = new CompanyProfile([
                'name' => $customHeader['name'],
                'address' => $customHeader['address'] ?? '',
                'tax_id' => $customHeader['tax_id'] ?? '',
                'logo_path' => $customHeader['logo'] ?? null,
                'phone' => $customHeader['phone'] ?? null
            ]);
        }

        if (!$profile) {
            $profile = new CompanyProfile([
                'name' => 'Company Name',
                'address' => 'Company Address',
                'tax_id' => '0000000000000'
            ]);
        }

        $viewName = match ($type) {
            'quotation' => 'documents.quotation',
            'invoice' => 'documents.invoice',
            'receipt' => 'documents.receipt',
            'credit_note' => 'documents.credit_note',
            'tax_invoice' => 'documents.tax_invoice',
            default => 'documents.generic',
        };

        return view($viewName, compact('production', 'profile', 'financial', 'transactions', 'type', 'activeGroup'));
    }

    // --- Settings Methods ---

    public function indexSettings()
    {
        $profiles = CompanyProfile::all();
        return view('admin.settings.financial', compact('profiles'));
    }

    public function storeProfile(Request $request)
    {
        // Allow logo to be a string (path) for JSON requests
        $request->validate([
            'name' => 'required',
            'logo' => 'nullable',
            'signature' => 'nullable',
            'stamp' => 'nullable',
        ]);

        $data = $request->except(['logo', 'signature', 'stamp']);

        if ($request->hasFile('logo')) {
            $data['logo_path'] = $request->file('logo')->store('company_logos', 'public');
        } elseif ($request->filled('logo') && is_string($request->logo)) {
            // If saving via JSON and logo is already a path
            $data['logo_path'] = $request->logo;
        }

        if ($request->hasFile('signature')) {
            $data['signature_path'] = $request->file('signature')->store('company_signatures', 'public');
        }

        if ($request->hasFile('stamp')) {
            $data['stamp_path'] = $request->file('stamp')->store('company_stamps', 'public');
        }

        // JSON Positions
        if ($request->has('signature_pos') && is_string($request->signature_pos)) {
             $data['signature_pos'] = json_decode($request->signature_pos, true);
        }
        if ($request->has('stamp_pos') && is_string($request->stamp_pos)) {
             $data['stamp_pos'] = json_decode($request->stamp_pos, true);
        }

        if ($request->is_default) {
            CompanyProfile::where('is_default', true)->update(['is_default' => false]);
        }

        $data['use_signature'] = $request->has('use_signature');
        $data['use_stamp'] = $request->has('use_stamp');

        $profile = CompanyProfile::create($data);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'profile' => $profile]);
        }

        return back()->with('success', 'Profile created');
    }

    public function updateProfile(Request $request, $id)
    {
        $profile = CompanyProfile::findOrFail($id);

        $request->validate([
            'name' => 'required',
            'logo' => 'nullable',
            'signature' => 'nullable',
            'stamp' => 'nullable',
            'signature_pos' => 'nullable|string',
            'stamp_pos' => 'nullable|string',
        ]);

        $data = $request->except(['logo', 'signature', 'stamp']);

        // Handle File Uploads
        if ($request->hasFile('logo')) {
            if ($profile->logo_path) Storage::disk('public')->delete($profile->logo_path);
            $data['logo_path'] = $request->file('logo')->store('company_logos', 'public');
        }

        if ($request->hasFile('signature')) {
            if ($profile->signature_path) Storage::disk('public')->delete($profile->signature_path);
            $data['signature_path'] = $request->file('signature')->store('company_signatures', 'public');
        }

        if ($request->hasFile('stamp')) {
            if ($profile->stamp_path) Storage::disk('public')->delete($profile->stamp_path);
            $data['stamp_path'] = $request->file('stamp')->store('company_stamps', 'public');
        }

        // Handle JSON positions
        if ($request->has('signature_pos') && is_string($request->signature_pos)) {
             $data['signature_pos'] = json_decode($request->signature_pos, true);
        }
        if ($request->has('stamp_pos') && is_string($request->stamp_pos)) {
             $data['stamp_pos'] = json_decode($request->stamp_pos, true);
        }

        // Handle Default Toggle
        if ($request->is_default) {
            CompanyProfile::where('id', '!=', $id)->where('is_default', true)->update(['is_default' => false]);
        }

        // Handle Booleans
        $data['use_signature'] = $request->has('use_signature');
        $data['use_stamp'] = $request->has('use_stamp');
        $data['is_default'] = $request->has('is_default');

        $profile->update($data);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'profile' => $profile]);
        }

        return back()->with('success', 'Profile updated');
    }

    // === Constants & helpers ของ updateTransaction ===

    /** Validation rules ของ updateTransaction (ย้ายมาจาก inline เพื่อให้ method body กระชับ) */
    private const UPDATE_TRANSACTION_RULES = [
        'status'                  => 'nullable|in:pending,partial,paid,overdue',
        'notes'                   => 'nullable|string',
        'bank_account_id'         => 'nullable|exists:bank_accounts,id',
        'wht_status'              => 'nullable|in:not_required,pending,received',
        'withholding_tax_amount'  => 'nullable|numeric',
        'slip_file'               => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120', // 5MB
        'wht_document'            => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120', // 5MB
        'item_ids'                => 'nullable', // รับเป็น array หรือ FormData string "1,2,3"
        'employee_ids'            => 'nullable', // รับเป็น array หรือ FormData string
        'amount'                  => 'nullable|numeric|min:0',
        'discount_amount'         => 'nullable|numeric|min:0',
        'discount_description'    => 'nullable|string|max:255',
        'payment_collection_mode' => 'nullable|in:lump_sum,per_employee',
        'sync_items'              => 'nullable|boolean',  // the employee list was sent (may be empty)
        'extra_charges_json'      => 'nullable|string',   // [{id, description, amount}]
        'item_tiers_json'         => 'nullable|string',   // {itemId: {tier, price}}
        // what to do with money received above the bill's new total (2026-11-21)
        'overpayment_action'      => 'nullable|in:keep,refund,transfer,reallocate',
        'overpayment_note'        => 'nullable|string|max:255',
        'overpayment_date'        => 'nullable|date',
        'overpayment_bank_account_id' => 'nullable|exists:bank_accounts,id',
        'overpayment_target_id'   => 'nullable|integer',
    ];

    /**
     * ค่าใช้จ่ายเพิ่มเติม sent with the bill: null = not sent (leave as is),
     * [] = none, or the cleaned list. JsonResponse when something is wrong.
     */
    private function parseExtraCharges(Request $request)
    {
        if (! $request->has('extra_charges_json')) {
            return null;
        }
        $raw = json_decode((string) $request->input('extra_charges_json'), true);
        if (! is_array($raw)) {
            return response()->json(['success' => false, 'message' => __('Invalid additional charges.')], 422);
        }
        $out = [];
        foreach ($raw as $i => $c) {
            $amount = round((float) ($c['amount'] ?? 0), 2);
            $desc = trim((string) ($c['description'] ?? ''));
            if ($amount <= 0 || mb_strlen($desc) > 255) {
                return response()->json(['success' => false, 'message' => __('Each additional charge needs an amount above 0.')], 422);
            }
            $id = preg_replace('/[^A-Za-z0-9_\-]/', '', (string) ($c['id'] ?? '')) ?: ('x' . now()->format('YmdHis') . '_' . $i);
            $line = ['id' => substr($id, 0, 40), 'description' => $desc !== '' ? $desc : __('Additional charge'), 'amount' => $amount];
            if (($c['kind'] ?? null) === 'overpayment') {
                $line['kind'] = 'overpayment'; // "overpayment kept as income" line (2026-11-21)
            }
            $out[] = $line;
        }

        return $out;
    }

    /** {itemId: {tier, price}} — a new price tier for an employee on this bill. */
    private function parseItemTierChanges(Request $request)
    {
        if (! $request->filled('item_tiers_json')) {
            return [];
        }
        $raw = json_decode((string) $request->input('item_tiers_json'), true);
        if (! is_array($raw)) {
            return response()->json(['success' => false, 'message' => __('Invalid price tier change.')], 422);
        }
        $out = [];
        foreach ($raw as $itemId => $c) {
            if (! is_numeric($itemId) || ! isset($c['tier']) || ! is_numeric($c['tier'])) {
                return response()->json(['success' => false, 'message' => __('Invalid price tier change.')], 422);
            }
            $out[(int) $itemId] = ['tier' => (int) $c['tier'], 'price' => round((float) ($c['price'] ?? -1), 2)];
        }

        return $out;
    }

    /**
     * Why this edit of the bill can't be saved — or null when it can:
     *  - an employee taken off the bill already has money recorded for them
     *    here (individual collection) → delete those entries first
     *  - an additional charge removed while money was recorded against it
     *  - a per-head bill left with no employee at all (delete the bill instead)
     *  - the new total is below what has already been received
     */
    private function billEditBlockReason(FinancialTransaction $transaction, Request $request, ?array $extraCharges): ?string
    {
        $syncing = $request->boolean('sync_items') || $request->has('item_ids') || $request->has('employee_ids');
        if ($syncing) {
            $keep = array_map('intval', $this->parseIdList($request->input('item_ids')));
            $removed = $transaction->items()->pluck('production_items.id')->map(fn ($id) => (int) $id)
                ->reject(fn ($id) => in_array($id, $keep, true))->values();
            if ($removed->isNotEmpty()) {
                $paidFor = $transaction->itemPayments()->whereIn('production_item_id', $removed)->pluck('production_item_id')->unique();
                if ($paidFor->isNotEmpty()) {
                    $names = ProductionItem::with('employee')->whereIn('id', $paidFor)->get()
                        ->map(fn ($i) => $i->employee?->getRawOriginal('employeeNameEn') ?: $i->employee?->getRawOriginal('employeeNameTh') ?: ('#' . $i->id))
                        ->implode(', ');
                    return __('Money was already received for :names on this bill — delete those payments first, then take them off.', ['names' => $names]);
                }
            }
            $group = $transaction->financialGroup()->first();
            $perHead = (($group?->financial_data['pricing_mode'] ?? 'per_head') === 'per_head');
            $adding = ! empty($this->parseIdList($request->input('employee_ids')));
            if ($perHead && empty($keep) && ! $adding && $transaction->items()->exists()) {
                return __('A per-head bill needs at least one employee. To stop using this bill, delete it instead.');
            }
        }

        if ($extraCharges !== null) {
            $keptIds = collect($extraCharges)->pluck('id')->all();
            $gone = collect($transaction->extra_charges ?? [])->pluck('id')->reject(fn ($id) => in_array($id, $keptIds, true));
            if ($gone->isNotEmpty() && $transaction->itemPayments()->whereIn('extra_charge_id', $gone)->exists()) {
                return __('Money was already received for this additional charge — delete that payment first.');
            }
        }

        if ($request->has('amount')) {
            $amount = (float) $request->input('amount');
            $extras = $extraCharges !== null ? collect($extraCharges)->sum('amount') : $transaction->extraChargesTotal();
            if ($amount + 0.005 < $extras) {
                return __('The bill total is less than its additional charges.');
            }
            // Below what was already received is allowed now — the extra money is
            // handled by overpaymentPlan() (2026-11-21). Below the credit notes is not.
            $discount = (float) ($request->has('discount_amount') ? $request->input('discount_amount') : $transaction->discount_amount);
            if ((float) ($transaction->credit_amount ?? 0) > 0 && $amount - $discount + 0.005 < (float) $transaction->credit_amount) {
                return __('The new total is less than the credit notes issued for this bill (:credit).', ['credit' => number_format((float) $transaction->credit_amount, 2)]);
            }
        }

        return null;
    }

    /**
     * Put employees of this bill on another price tier: moved in the tab's
     * pricing tiers, and the bill uses that tier's price for them. The price
     * sent must still be the tier's saved price (else: settings not saved).
     */
    private function applyItemTierChanges(FinancialTransaction $transaction, array $changes): void
    {
        $group = $transaction->financialGroup()->first();
        if (! $group) {
            return;
        }
        $data = $group->financial_data ?? [];
        $tiers = $data['pricing_tiers'] ?? [];
        $onBill = $transaction->items()->get()->keyBy('id');

        foreach ($changes as $itemId => $c) {
            $item = $onBill->get($itemId);
            if (! $item || ! isset($tiers[$c['tier']])) {
                continue;
            }
            $aliases = array_filter([(string) $itemId, 'emp_' . $itemId, $item->employee_id ? 'emp_' . $item->employee_id : null]);
            foreach ($tiers as $k => $t) {
                $tiers[$k]['item_ids'] = array_values(array_filter($t['item_ids'] ?? [], fn ($id) => ! in_array((string) $id, $aliases, true)));
            }
            $tiers[$c['tier']]['item_ids'][] = (int) $itemId;
            foreach ($tiers as $k => $t) {
                $tiers[$k]['count'] = count($t['item_ids'] ?? []);
            }
            $transaction->items()->updateExistingPivot($itemId, ['unit_price' => (float) ($tiers[$c['tier']]['price'] ?? 0)]);
        }

        $data['pricing_tiers'] = $tiers;
        $group->financial_data = $data;
        $group->save();
    }

    /** Field ที่ apply ตรงๆ จาก request → transaction (apply เฉพาะ field ที่ส่งมาใน request) */
    private const TRANSACTION_FILLABLE_FIELDS = [
        'discount_amount',
        'discount_description',
        'notes',
        'bank_account_id',
        'wht_status',
        'withholding_tax_amount',
        'amount',
        'status',
        'payment_collection_mode',
    ];

    /** ตรวจสิทธิ์การจัดการการเงิน — admin/super-admin หรือมี permission 'manage-finance' */
    private function canManageFinance(): bool
    {
        $user = auth()->user();
        return $user && ($user->can('manage-finance') || $user->hasAnyRole(['admin', 'super-admin']));
    }

    /**
     * บิลเดียวห้ามมีทั้งยอดรวมก้อนเดียวและรายบุคคลปนกัน — สลับโหมดได้อิสระ
     * ตราบใดที่โหมดปัจจุบันยังไม่มีการบันทึกยอดจริง ถ้ามีแล้วต้องลบยอดที่
     * บันทึกไว้ก่อนถึงจะสลับได้ (ผู้ใช้ยืนยันเงื่อนไขนี้แล้ว)
     * คืนค่า string ข้อความ block ถ้าห้ามสลับ, null ถ้าสลับได้
     */
    private function paymentCollectionModeSwitchBlockReason(FinancialTransaction $transaction, ?string $newMode): ?string
    {
        $currentMode = $transaction->payment_collection_mode ?? 'lump_sum';
        if ($newMode === null || $newMode === $currentMode) {
            return null;
        }

        if ($currentMode === 'lump_sum' && $transaction->payments()->exists()) {
            return 'This bill already has lump-sum payments recorded. Delete them first before switching to individual collection.';
        }

        if ($currentMode === 'per_employee' && $transaction->itemPayments()->exists()) {
            return 'This bill already has individual payments recorded. Delete them first before switching to lump-sum collection.';
        }

        return null;
    }

    /** Copy ฟิลด์ scalar จาก request → transaction (skip field ที่ไม่ส่งมา) */
    private function applyTransactionFields(FinancialTransaction $transaction, Request $request): void
    {
        foreach (self::TRANSACTION_FILLABLE_FIELDS as $field) {
            if ($request->has($field)) {
                $transaction->{$field} = $request->{$field};
            }
        }
    }

    /** จัดการการอัพโหลดไฟล์ slip + WHT document — ลบไฟล์เก่าก่อน save ใหม่เพื่อไม่ให้ storage รก */
    private function applyTransactionFiles(FinancialTransaction $transaction, Request $request): void
    {
        if ($request->hasFile('slip_file')) {
            if ($transaction->slip_path) {
                Storage::disk('public')->delete($transaction->slip_path);
            }
            $transaction->slip_path = $request->file('slip_file')->store('financial_slips', 'public');
        }

        if ($request->hasFile('wht_document')) {
            if ($transaction->wht_document_path) {
                Storage::disk('public')->delete($transaction->wht_document_path);
            }
            $transaction->wht_document_path = $request->file('wht_document')->store('financial_slips/wht', 'public');
        }
    }

    /**
     * Sync pivot items ของ transaction
     *  - รวม item_ids (มีอยู่แล้ว) + employee_ids (สร้าง ProductionItem ใหม่)
     *  - sync pivot table financial_transaction_items
     *  - sync pricing tiers (แปลง emp_X → ProductionItem.id ใน tier['item_ids'])
     */
    private function syncTransactionItems(FinancialTransaction $transaction, Request $request): void
    {
        if (!$request->boolean('sync_items') && !$request->has('item_ids') && !$request->has('employee_ids')) {
            return;
        }

        $itemIds = $this->parseIdList($request->input('item_ids'));
        $employeeIds = $this->parseIdList($request->input('employee_ids'));
        $createdItemMap = []; // empId => newly-created itemId

        foreach ($employeeIds as $empId) {
            $item = ProductionItem::firstOrCreate(
                ['production_order_id' => $transaction->production_order_id, 'employee_id' => $empId],
                ['status' => 'pending']
            );
            $itemIds[] = $item->id;
            $createdItemMap[$empId] = $item->id;
        }

        $transaction->items()->sync(array_unique($itemIds));

        if (!empty($createdItemMap)) {
            $this->syncPricingTiers($transaction->production_financial_group_id, $createdItemMap);
        }

        // Newly added employees take today's tier price; those already on the bill keep theirs.
        $transaction->snapshotItemPrices();
    }

    /** Parse list ที่อาจเป็น array หรือ FormData string "1,2,3" */
    private function parseIdList($input): array
    {
        if (is_array($input)) return $input;
        if (is_string($input) && $input !== '') return explode(',', $input);
        return [];
    }
}
