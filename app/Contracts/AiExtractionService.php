<?php

namespace App\Contracts;

use App\Services\Ai\AiExtractionResult;
use Illuminate\Http\UploadedFile;

/**
 * Contract for AI-driven extraction of financial entries from a receipt
 * image or a free-form text description.
 *
 * The contract is intentionally vendor-agnostic — current implementation
 * is `ManualExtractionService` (no-op fallback), future implementations
 * can wire in a local model (Ollama / llama.cpp / etc.) or any cloud API
 * by binding to this interface in AiServiceProvider.
 */
interface AiExtractionService
{
    /**
     * Extract financial entry fields from an uploaded receipt/slip image.
     */
    public function extractFromImage(UploadedFile $file): AiExtractionResult;

    /**
     * Extract financial entry fields from a free-form text description.
     */
    public function extractFromText(string $text): AiExtractionResult;

    /**
     * True if the underlying engine is configured and ready.
     * Manual implementations should return false so the UI can show
     * a "AI is not configured — please fill in manually" hint.
     */
    public function isReady(): bool;

    /**
     * Short human-readable name of the engine ("Manual", "Local Llama 3",
     * "Claude Sonnet 4.6", ...). Shown in the capture page footer.
     */
    public function engineName(): string;
}
