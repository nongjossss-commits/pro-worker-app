<?php

namespace App\Services;

use App\Models\LaborBill;
use App\Models\LaborBillPayment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Records payments against a LaborBill (partial payments supported — a
 * LaborBill never "closes", see LaborBillService docblock) and issues a
 * receipt per payment. Receipt numbering follows the same draft→issue
 * discipline as LaborTaxInvoiceService/LaborWhtCertificateService: the
 * number is assigned at issueReceipt() time, not at recordPayment() time,
 * so a corrected/removed payment entry never burns a receipt number.
 */
class LaborBillPaymentService
{
    public function recordPayment(LaborBill $bill, array $data): LaborBillPayment
    {
        return LaborBillPayment::create(array_merge($data, [
            'labor_bill_id' => $bill->id,
            'created_by' => Auth::id(),
        ]));
    }

    public function issueReceipt(LaborBillPayment $payment): LaborBillPayment
    {
        if ($payment->hasReceipt()) {
            throw new RuntimeException("Payment already has receipt [{$payment->receipt_no}].");
        }

        return DB::transaction(function () use ($payment) {
            $year = (int) $payment->paid_at->year;
            $receiptNo = $this->generateReceiptNo($year);

            $payment->update([
                'receipt_no' => $receiptNo,
                'receipt_generated_at' => now(),
            ]);

            $pdfPath = app(LaborReceiptPdfService::class)
                ->generateAndStore($payment->fresh(['bill.team', 'bill.financialProfile', 'bankAccount']));

            $payment->update(['receipt_pdf_path' => $pdfPath]);

            return $payment->fresh();
        });
    }

    // -------- Internal --------

    /**
     * Format: LRC-YYYY-#### — running per calendar year, same locking
     * pattern as LaborBillService::generateBillNo().
     */
    protected function generateReceiptNo(int $year): string
    {
        $prefix = sprintf('LRC-%04d-', $year);

        $last = LaborBillPayment::withTrashed()
            ->where('receipt_no', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->lockForUpdate()
            ->value('receipt_no');

        $seq = 1;
        if ($last && preg_match('/(\d+)$/', $last, $m)) {
            $seq = (int) $m[1] + 1;
        }

        return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
