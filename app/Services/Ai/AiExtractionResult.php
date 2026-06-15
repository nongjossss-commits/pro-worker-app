<?php

namespace App\Services\Ai;

/**
 * DTO returned by AiExtractionService implementations.
 *
 * Every field is nullable — manual fallback returns a result with
 * everything null and `succeeded = false`, while a real AI engine
 * fills in whatever it can with a confidence score (0–100).
 *
 * The `source` value is persisted to LedgerEntry.ai_source so the audit
 * trail records how each entry originated.
 */
class AiExtractionResult
{
    public function __construct(
        public readonly bool $succeeded = false,
        public readonly string $source = 'manual',  // manual | ocr | text
        public readonly ?float $confidence = null,
        public readonly ?string $entryDate = null,
        public readonly ?string $type = null,        // income | expense
        public readonly ?float $grossAmount = null,
        public readonly ?string $counterpartyName = null,
        public readonly ?string $counterpartyTaxId = null,
        public readonly ?string $description = null,
        public readonly ?string $message = null,     // info to surface in UI
        public readonly array $raw = [],             // full engine payload for audit
    ) {
    }

    public static function empty(string $source = 'manual', ?string $message = null): self
    {
        return new self(
            succeeded: false,
            source: $source,
            message: $message,
        );
    }

    /**
     * Subset shaped for prefilling the ledger form. Skips null fields
     * so the controller can `array_merge($defaults, $prefill)` cleanly.
     */
    public function toPrefill(): array
    {
        return array_filter([
            'entry_date' => $this->entryDate,
            'type' => $this->type,
            'gross_amount' => $this->grossAmount,
            'counterparty_name' => $this->counterpartyName,
            'counterparty_tax_id' => $this->counterpartyTaxId,
            'description' => $this->description,
        ], fn ($v) => $v !== null && $v !== '');
    }

    /**
     * Persistence shape for LedgerEntry.ai_extracted_data (JSON column).
     * Includes the raw engine payload for audit.
     */
    public function toJsonPayload(): array
    {
        return [
            'succeeded' => $this->succeeded,
            'source' => $this->source,
            'confidence' => $this->confidence,
            'fields' => $this->toPrefill(),
            'message' => $this->message,
            'raw' => $this->raw,
        ];
    }
}
