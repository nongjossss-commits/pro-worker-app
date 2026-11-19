<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * "แจ้งเข้า บต.52/55/56" items now carry the team (group_name) the employee
 * had in "แจ้งเข้า / เปลี่ยนนายจ้าง" (copied when they are added — see
 * WorkflowController::notifyInReportStore()). This fills it in for the items
 * that already exist: only where the item has no team yet, taken from its
 * source item. Nothing else is changed; a team already set is never overwritten.
 */
return new class extends Migration
{
    public function up(): void
    {
        $rows = DB::table('production_items as i')
            ->join('production_items as src', 'src.id', '=', 'i.source_item_id')
            ->whereNotNull('i.source_item_id')
            ->where(fn ($q) => $q->whereNull('i.group_name')->orWhere('i.group_name', ''))
            ->whereNotNull('src.group_name')
            ->where('src.group_name', '!=', '')
            ->select('i.id', 'src.group_name')
            ->get();

        foreach ($rows as $row) {
            DB::table('production_items')->where('id', $row->id)->update(['group_name' => $row->group_name]);
        }
    }

    public function down(): void
    {
        // data copy only — nothing to undo safely (a team may have been edited since)
    }
};
