<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('production_financial_groups', function (Blueprint $table) {
            $table->unsignedBigInteger('employer_id')->nullable()->after('production_order_id');
            $table->unsignedBigInteger('work_type_id')->nullable()->after('employer_id');
            $table->unsignedBigInteger('production_order_id')->nullable()->change(); // Make nullable as it's now shared

            $table->index(['employer_id', 'work_type_id']);
        });

        // Migrate existing data (Optional but good for consistency)
        // We need to look up the Order to find the Employer and WorkType
        $groups = DB::table('production_financial_groups')->get();
        foreach ($groups as $group) {
            if ($group->production_order_id) {
                $order = DB::table('production_orders')->where('id', $group->production_order_id)->first();
                if ($order) {
                    DB::table('production_financial_groups')
                        ->where('id', $group->id)
                        ->update([
                            'employer_id' => $order->employer_id,
                            'work_type_id' => $order->work_type_id
                        ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('production_financial_groups', function (Blueprint $table) {
            $table->dropIndex(['employer_id', 'work_type_id']);
            $table->dropColumn(['employer_id', 'work_type_id']);
            // Reverting nullable might be risky if we have nulls, so we leave it nullable or handle carefully.
        });
    }
};
