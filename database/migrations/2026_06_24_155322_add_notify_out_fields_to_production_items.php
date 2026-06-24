<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add per-item notify_out fields:
 *  - notify_out_date   = the date the employee is officially notified out (required before completing)
 *  - notify_out_reason = freeform reason text (saved to employee.termination_reason on finalize)
 *
 * Used only for production_items whose order.work_type slug = 'notify_out'.
 * NULL for all other workflow types.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_items', function (Blueprint $table) {
            $table->date('notify_out_date')->nullable()->after('group_name');
            $table->string('notify_out_reason', 500)->nullable()->after('notify_out_date');
        });
    }

    public function down(): void
    {
        Schema::table('production_items', function (Blueprint $table) {
            $table->dropColumn(['notify_out_date', 'notify_out_reason']);
        });
    }
};
