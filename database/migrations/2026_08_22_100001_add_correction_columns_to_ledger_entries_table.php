<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Supports the Super-Admin-only "correct a closed day" workflow (see
 * LedgerService::createCorrection()) — a closed-day entry is never edited
 * in place; instead a reversal + a corrected replacement are posted today,
 * both pointing back here via `adjustment_of_id` so the full chain is
 * auditable. The original entry itself is never touched.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ledger_entries', function (Blueprint $table) {
            $table->foreignId('adjustment_of_id')->nullable()->after('source_id')
                ->constrained('ledger_entries')->nullOnDelete();
            $table->text('adjustment_reason')->nullable()->after('adjustment_of_id');
        });
    }

    public function down(): void
    {
        Schema::table('ledger_entries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('adjustment_of_id');
            $table->dropColumn('adjustment_reason');
        });
    }
};
