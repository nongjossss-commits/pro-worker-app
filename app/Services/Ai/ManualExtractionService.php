<?php

namespace App\Services\Ai;

use App\Contracts\AiExtractionService;
use Illuminate\Http\UploadedFile;

/**
 * No-op fallback implementation of AiExtractionService.
 *
 * Returns an empty result with `succeeded = false` so the UI prompts
 * the user to fill the form by hand. The uploaded receipt is still
 * attached to the LedgerEntry — only the auto-extraction is skipped.
 *
 * Bound as the default implementation in AiServiceProvider. Swap to
 * a real engine (local model / Claude / etc.) by changing that binding.
 */
class ManualExtractionService implements AiExtractionService
{
    public function extractFromImage(UploadedFile $file): AiExtractionResult
    {
        return AiExtractionResult::empty(
            source: 'manual',
            message: __('AI extraction is not configured. Please fill the form manually — your uploaded receipt will still be attached.'),
        );
    }

    public function extractFromText(string $text): AiExtractionResult
    {
        return AiExtractionResult::empty(
            source: 'manual',
            message: __('AI extraction is not configured. Please fill the form manually.'),
        );
    }

    public function isReady(): bool
    {
        return false;
    }

    public function engineName(): string
    {
        return 'Manual (no AI configured)';
    }
}
