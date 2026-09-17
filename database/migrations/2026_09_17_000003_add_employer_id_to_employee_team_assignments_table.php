<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Team names are meant to be a small, free-form, per-employer
     * vocabulary (e.g. one employer's own "Batch 1"/"Batch 2"), not a
     * tab-wide list every other employer sharing the same tab pollutes.
     * `employer_id` is denormalized here (also derivable via employee_id ->
     * employees.employer_id) so the "team names already used" suggestion
     * list, and the rename/delete actions, can all be scoped to one
     * employer without a join.
     */
    public function up(): void
    {
        Schema::table('employee_team_assignments', function (Blueprint $table) {
            $table->foreignId('employer_id')->nullable()->after('resolution_tab_id')->constrained()->cascadeOnDelete();
        });

        DB::table('employee_team_assignments')
            ->join('employees', 'employees.id', '=', 'employee_team_assignments.employee_id')
            ->update(['employee_team_assignments.employer_id' => DB::raw('employees.employer_id')]);

        Schema::table('employee_team_assignments', function (Blueprint $table) {
            $table->foreignId('employer_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('employee_team_assignments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('employer_id');
        });
    }
};
