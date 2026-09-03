<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Was previously hardcoded everywhere as `in_array($workType->slug, ['mou',
     * 'mou_import'])` (11 occurrences across 5 files — see WorkflowController,
     * ImportEmployeeController, and the workflow/production Blade views) —
     * replaced by these 2 real, independent columns so a Super Admin can pick
     * "allow multiple cards per employer" for a brand-new custom tab without
     * that tab automatically inheriting the MOU-specific fields too.
     *
     * `show_mou_fields` is deliberately NOT exposed anywhere in the tab
     * create/edit UI — it stays true only for the pre-existing MOU Import
     * system tab(s), set once below. A new custom tab that opts into
     * `allow_multiple_orders` still gets none of the MOU nationality/gender-
     * count/import-type fields, by construction.
     */
    public function up(): void
    {
        Schema::table('work_types', function (Blueprint $table) {
            $table->boolean('allow_multiple_orders')->default(false)->after('is_system');
            $table->boolean('show_mou_fields')->default(false)->after('allow_multiple_orders');
        });

        // Preserve the MOU Import tab's existing behavior exactly — every
        // other existing tab keeps both new columns at their false default,
        // i.e. no behavior change for anything already in the system.
        DB::table('work_types')
            ->whereIn('slug', ['mou', 'mou_import'])
            ->update(['allow_multiple_orders' => true, 'show_mou_fields' => true]);
    }

    public function down(): void
    {
        Schema::table('work_types', function (Blueprint $table) {
            $table->dropColumn(['allow_multiple_orders', 'show_mou_fields']);
        });
    }
};
