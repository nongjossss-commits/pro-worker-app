<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Anchors the card-level "finish whole job" 24-hour Undo window for
 * multi-card (allow_multiple_orders) WorkType tabs — mirrors
 * ProductionItem.completed_at exactly, just one level up. See
 * WorkflowController::finalizeOrder()/restoreOrder().
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_orders', function (Blueprint $table) {
            $table->timestamp('completed_at')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('production_orders', function (Blueprint $table) {
            $table->dropColumn('completed_at');
        });
    }
};
