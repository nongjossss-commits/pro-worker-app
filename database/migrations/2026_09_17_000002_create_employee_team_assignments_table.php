<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Porting Workflow's "จัดทีม" (team assignment) feature into Registration
     * and Renewal Resolution. Workflow's own version is just a `group_name`
     * string column on `production_items`, scoped by `production_order_id`
     * — but Registration/Renewal employees don't reliably have a
     * ProductionItem row at all (only ones that came through a Sales/
     * Workflow transition do; employees added directly in Registration
     * never get one), so that column can't be reused here.
     *
     * Mirrors employee_appointments' shape/rationale instead: one row per
     * (employee, resolution_tab_id) pair. A row's existence means that
     * employee is on that team for that specific tab; removing them from a
     * team deletes the row. This automatically isolates team assignments
     * both between Registration and Renewal (different resolution_tab_id
     * values) and between separate Renewal tabs, including for an employee
     * who is dual-listed (via employee_renewal_links) into more than one
     * tab at once.
     */
    public function up(): void
    {
        Schema::create('employee_team_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('resolution_tab_id')->constrained('resolution_tabs')->cascadeOnDelete();
            $table->string('team_name');
            $table->timestamps();

            $table->unique(['employee_id', 'resolution_tab_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_team_assignments');
    }
};
