<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tracks whether ApplyWorkflowSettings has already pushed the configured
 * MOU group / expiry onto the linked employee after the 24-hour safety
 * window. Reset to false on finalize and on restore so a re-finalize will
 * re-apply the latest admin settings.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_items', function (Blueprint $table) {
            $table->boolean('workflow_settings_applied')->default(false)->after('is_transfer_processed');
            $table->index(['status', 'workflow_settings_applied', 'completed_at'], 'production_items_workflow_apply_idx');
        });
    }

    public function down(): void
    {
        Schema::table('production_items', function (Blueprint $table) {
            $table->dropIndex('production_items_workflow_apply_idx');
            $table->dropColumn('workflow_settings_applied');
        });
    }
};
