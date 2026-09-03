<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lets a Super Admin delete a custom work tab WITHOUT destroying the
     * ProductionOrder/ProductionItem/financial data that was ever processed
     * under it — see WorkTypeController::destroy(), which previously
     * force-deleted every order under the tab before hard-deleting the
     * WorkType row itself. With this trait, deleting a tab now just hides
     * it (excluded from the normal WorkType::orderBy(...)->get() listing
     * queries automatically, via Eloquent's own SoftDeletingScope) — every
     * order/item/employee assignment that existed under it stays exactly
     * as it was.
     */
    public function up(): void
    {
        Schema::table('work_types', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('work_types', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
