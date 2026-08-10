<?php

namespace App\Http\Controllers\Labor;

use App\Http\Controllers\Controller;
use App\Models\LaborBill;
use App\Models\LaborBillPayment;
use App\Services\LaborBillPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Records payments against a LaborBill (partial payments supported) and
 * issues a receipt PDF per payment. See LaborBillPaymentService for the
 * record→issue numbering discipline.
 */
class LaborBillPaymentController extends Controller
{
    public function __construct(protected LaborBillPaymentService $service)
    {
    }

    public function store(Request $request, LaborBill $bill)
    {
        abort_unless($request->user()->can('manage-labor-ledger'), 403);

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'paid_at' => ['required', 'date'],
            'payment_method' => ['required', 'in:cash,transfer,promptpay,other'],
            'bank_account_id' => ['nullable', 'exists:bank_accounts,id'],
            'wht_certificate_id' => ['nullable', 'exists:labor_wht_certificates,id'],
            'slip' => ['nullable', 'file', 'max:20480'],
            'notes' => ['nullable', 'string'],
        ]);

        if ($request->hasFile('slip')) {
            $validated['slip_path'] = $request->file('slip')->store('labor/payment_slips', 'public');
        }
        unset($validated['slip']);

        $this->service->recordPayment($bill, $validated);

        return back()->with('success', 'บันทึกการชำระเงินเรียบร้อยแล้ว');
    }

    public function issueReceipt(Request $request, LaborBill $bill, LaborBillPayment $payment)
    {
        abort_unless($request->user()->can('manage-labor-ledger'), 403);
        abort_unless($payment->labor_bill_id === $bill->id, 404);

        $this->service->issueReceipt($payment);

        return back()->with('success', 'ออกใบเสร็จเรียบร้อยแล้ว');
    }

    /**
     * Stream the receipt PDF inline (preview in a new tab) — same pattern as
     * the tax-invoice/WHT pdf() endpoints, browser's own viewer lets the
     * user save it from there.
     */
    public function downloadReceipt(Request $request, LaborBill $bill, LaborBillPayment $payment)
    {
        abort_unless($request->user()->can('manage-labor-ledger'), 403);
        abort_unless($payment->labor_bill_id === $bill->id, 404);

        if (!$payment->receipt_pdf_path || !Storage::disk('public')->exists($payment->receipt_pdf_path)) {
            abort(404, 'ไม่พบไฟล์ใบเสร็จ');
        }

        $binary = Storage::disk('public')->get($payment->receipt_pdf_path);

        return response($binary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $payment->receipt_no . '.pdf"',
        ]);
    }
}
