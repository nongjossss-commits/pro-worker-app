<?php

namespace App\Services;

use App\Models\LaborBill;
use App\Models\LaborBillPayment;
use App\Models\LaborBookTransaction;
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
    /**
     * `labor_book_account_id` (optional) is not a LaborBillPayment column —
     * it's pulled out here to decide whether/where to post a matching
     * income entry into the company's books (see LaborBookTransaction).
     * Skipped silently if not provided, so recording a payment never
     * requires picking a book account.
     */
    public function recordPayment(LaborBill $bill, array $data): LaborBillPayment
    {
        $bookAccountId = $data['labor_book_account_id'] ?? null;
        unset($data['labor_book_account_id']);

        return DB::transaction(function () use ($bill, $data, $bookAccountId) {
            $payment = LaborBillPayment::create(array_merge($data, [
                'labor_bill_id' => $bill->id,
                'created_by' => Auth::id(),
            ]));

            if ($bookAccountId) {
                LaborBookTransaction::create([
                    'labor_book_account_id' => $bookAccountId,
                    'type' => 'income',
                    'category' => 'team_payment',
                    'amount' => $payment->amount,
                    'transaction_date' => $payment->paid_at,
                    'description' => __('Payment from :team (Bill :no)', [
                        'team' => $bill->team->name ?? '-',
                        'no' => $bill->bill_no,
                    ]),
                    'source_type' => LaborBillPayment::class,
                    'source_id' => $payment->id,
                    'created_by' => Auth::id(),
                ]);
            }

            return $payment;
        });
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
