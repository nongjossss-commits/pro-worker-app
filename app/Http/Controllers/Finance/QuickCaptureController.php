<?php

namespace App\Http\Controllers\Finance;

use App\Contracts\AiExtractionService;
use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\ExpenseCategory;
use App\Models\IncomeCategory;
use App\Services\Ai\AiExtractionResult;
use App\Services\LedgerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Quick Capture — receipt or text → AI extract → LedgerEntry.
 *
 * Engine is bound through the AiExtractionService contract; this controller
 * does not know which engine is active. Manual fallback returns an empty
 * AiExtractionResult and the form starts blank.
 */
class QuickCaptureController extends Controller
{
    public function __construct(
        protected AiExtractionService $ai,
        protected LedgerService $ledger,
    ) {
    }

    /**
     * GET /finance/ledger/capture — render the dual-mode capture page.
     */
    public function show()
    {
        $accounts = BankAccount::where('is_active', true)
            ->orderBy('account_type')
            ->orderBy('bank_name')
            ->get();
        $incomeCategories = IncomeCategory::where('is_active', true)->orderBy('name')->get();
        $expenseCategories = ExpenseCategory::where('is_active', true)->orderBy('name')->get();

        return view('financial.ledger.capture', [
            'accounts' => $accounts,
            'incomeCategories' => $incomeCategories,
            'expenseCategories' => $expenseCategories,
            'engineName' => $this->ai->engineName(),
            'aiReady' => $this->ai->isReady(),
        ]);
    }

    /**
     * POST /finance/ledger/capture/extract — run extraction, return prefill JSON.
     * Used by the page's "Extract" button to populate the form.
     */
    public function extract(Request $request)
    {
        $request->validate([
            'mode' => 'required|in:image,text',
            'image' => 'nullable|file|max:20480',
            'text' => 'nullable|string|max:5000',
        ]);

        $result = match ($request->input('mode')) {
            'image' => $request->hasFile('image')
                ? $this->ai->extractFromImage($request->file('image'))
                : AiExtractionResult::empty('manual', __('No image uploaded.')),
            'text'  => $this->ai->extractFromText((string) $request->input('text', '')),
            default => AiExtractionResult::empty(),
        };

        return response()->json([
            'engine' => $this->ai->engineName(),
            'ready' => $this->ai->isReady(),
            'succeeded' => $result->succeeded,
            'message' => $result->message,
            'prefill' => $result->toPrefill(),
            'raw' => $result->toJsonPayload(),
        ]);
    }

    /**
     * POST /finance/ledger/capture — finalise the form into a LedgerEntry.
     * Accepts the same fields as the regular ledger create flow + the AI
     * payload to persist in ai_extracted_data for the audit trail.
     */
    public function save(Request $request)
    {
        $data = $request->validate([
            'entry_date' => 'required|date',
            'type' => 'required|in:income,expense',
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'category_id' => 'nullable|integer',
            'category_type' => 'nullable|in:income,expense',
            'counterparty_name' => 'nullable|string|max:255',
            'counterparty_tax_id' => 'nullable|string|max:15',
            'gross_amount' => 'required|numeric|min:0',
            'vat_treatment' => 'nullable|in:none,taxable,exempt,zero_rate',
            'vat_rate' => 'nullable|numeric|min:0|max:100',
            'vat_inclusive' => 'nullable|boolean',
            'wht_type' => 'nullable|in:none,pnd3,pnd53',
            'wht_rate' => 'nullable|numeric|min:0|max:100',
            'description' => 'nullable|string',
            'notes' => 'nullable|string',
            'receipt' => 'nullable|file|max:20480',
            'attachments.*' => 'nullable|file|max:20480',
            'ai_source' => 'nullable|in:manual,ocr,text',
            'ai_confidence' => 'nullable|numeric|min:0|max:100',
            'ai_extracted_data' => 'nullable|string',  // JSON-encoded blob from extract step
        ]);

        if (!empty($data['category_id']) && empty($data['category_type'])) {
            $data['category_type'] = $data['type'];
        }
        if (empty($data['vat_treatment'])) {
            $data['vat_treatment'] = 'none';
        }
        if (empty($data['wht_type'])) {
            $data['wht_type'] = 'none';
        }

        // Receipt + attachments
        if ($request->hasFile('receipt')) {
            $data['receipt_path'] = $request->file('receipt')->store('ledger/receipts', 'public');
        }
        if ($request->hasFile('attachments')) {
            $files = [];
            foreach ($request->file('attachments') as $file) {
                $files[] = [
                    'path' => $file->store('ledger/attachments', 'public'),
                    'name' => $file->getClientOriginalName(),
                    'size' => $file->getSize(),
                ];
            }
            $data['attached_files'] = $files;
        }

        // AI metadata for audit trail
        if (!empty($data['ai_extracted_data'])) {
            $decoded = json_decode($data['ai_extracted_data'], true);
            $data['ai_extracted_data'] = is_array($decoded) ? $decoded : null;
        }
        $data['ai_source'] = $data['ai_source'] ?? 'manual';
        $data['ai_status'] = 'confirmed';

        unset($data['receipt'], $data['attachments'], $data['vat_inclusive']);

        $entry = $this->ledger->createEntry($data);

        return redirect()->route('finance.ledger.show', $entry)
            ->with('success', __('Ledger entry :no saved via Quick Capture.', ['no' => $entry->entry_no]));
    }
}
