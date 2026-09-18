<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A 5th nationality bucket for workers who aren't Laotian/Myanmar/
     * Cambodian/Vietnamese — kept as its own explicit column (not just
     * lumped silently into "unspecified") so staff can actually record a
     * headcount for them, rather than being stuck unable to reflect the
     * true total. Deliberately still counted as part of the "unspecified"
     * bucket everywhere it's displayed/filtered (see LaborChargeType::
     * nationalityStats() and LaborChargeEntryController::index()) — from
     * this app's perspective "other" and "not yet broken down" are the same
     * practical bucket: headcount not accounted for by the 4 main tracked
     * nationalities.
     */
    public function up(): void
    {
        Schema::table('labor_ledger_entries', function (Blueprint $table) {
            $table->unsignedInteger('qty_other')->nullable()->after('qty_vietnam');
        });
    }

    public function down(): void
    {
        Schema::table('labor_ledger_entries', function (Blueprint $table) {
            $table->dropColumn('qty_other');
        });
    }
};
