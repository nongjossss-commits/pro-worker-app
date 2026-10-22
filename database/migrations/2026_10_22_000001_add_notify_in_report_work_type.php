<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Workflow tab 5 (system tab, can be renamed but not deleted):
 * "แจ้งเข้า บต.52 / บต.55 / บต.56".
 *
 * Its cards are built from employees FINISHED in the change-employer tab
 * (slug notify_in) — picked in the "Add Employees" window, grouped by their
 * (new) employer, one new card per employer per round. Steps are ticked on
 * the employer card (every employee on it moves together) and the card is
 * finished as a whole. See WorkflowController::notifyInReport*().
 *
 *  - production_items.source_item_id: the notify_in item an employee came
 *    from, so the same finished change-employer job isn't offered twice.
 *  - work_types row (slug notify_in_report): allow_multiple_orders = true
 *    (a new card each round). Only employees finished AFTER this row was
 *    created are offered (work_types.created_at is the cut-off).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_items', function (Blueprint $table) {
            $table->unsignedBigInteger('source_item_id')->nullable()->after('employee_id')->index();
        });

        if (! DB::table('work_types')->where('slug', 'notify_in_report')->exists()) {
            DB::table('work_types')->insert([
                'name' => 'แจ้งเข้า บต.52 / บต.55 / บต.56',
                'slug' => 'notify_in_report',
                'is_system' => true,
                'allow_multiple_orders' => true,
                'show_mou_fields' => false,
                'order' => ((int) DB::table('work_types')->max('order')) + 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // The tab row is removed only if nothing was ever done in it.
        $tab = DB::table('work_types')->where('slug', 'notify_in_report')->first();
        if ($tab && ! DB::table('production_orders')->where('work_type_id', $tab->id)->exists()) {
            DB::table('work_type_steps')->where('work_type_id', $tab->id)->delete();
            DB::table('work_types')->where('id', $tab->id)->delete();
        }

        Schema::table('production_items', function (Blueprint $table) {
            $table->dropIndex(['source_item_id']);
            $table->dropColumn('source_item_id');
        });
    }
};
