<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A single employee's appointment, scoped independently per
     * ResolutionTab — fixes appointments set from Registration Resolution
     * "leaking" into Renewal Resolution (and between separate, user-created
     * Renewal tabs) for the same physical person. Mirrors
     * employee_renewal_links' exact shape/rationale: one shared table,
     * one row per (employee, resolution_tab_id) pair, rather than a single
     * un-scoped set of columns on `employees` shared by every context that
     * touches that row.
     *
     * Pre-Production/Workflow are NOT affected — their appointment fields
     * already live on `production_items` (one row per work item), already
     * naturally isolated, and already reset on every stage transition.
     *
     * The old `employees.appointment_date/location/completed_at` columns
     * are left in place (unused going forward) rather than dropped — see
     * this migration's `up()` for the one-time backfill of any existing
     * values into this new table, tagged with each employee's current
     * `resolution_tab_id` so nothing already set today gets lost.
     */
    public function up(): void
    {
        Schema::create('employee_appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('resolution_tab_id')->constrained('resolution_tabs')->cascadeOnDelete();
            $table->dateTime('appointment_date')->nullable();
            $table->text('appointment_location')->nullable();
            $table->dateTime('appointment_completed_at')->nullable();
            $table->unsignedBigInteger('appointment_updated_by')->nullable();
            $table->dateTime('appointment_updated_at')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'resolution_tab_id']);
            $table->foreign('appointment_updated_by')->references('id')->on('users')->nullOnDelete();
        });

        // One-time backfill: any employee who already has an appointment
        // set today (on the old, un-scoped employees columns) keeps it,
        // tagged to whichever ResolutionTab they currently belong to —
        // the only tab that value could plausibly have been meant for
        // before this fix existed.
        DB::table('employees')
            ->whereNotNull('resolution_tab_id')
            ->where(function ($q) {
                $q->whereNotNull('appointment_date')
                    ->orWhereNotNull('appointment_location')
                    ->orWhereNotNull('appointment_completed_at');
            })
            ->orderBy('id')
            ->select('id', 'resolution_tab_id', 'appointment_date', 'appointment_location', 'appointment_completed_at', 'appointment_updated_by', 'appointment_updated_at')
            ->chunkById(200, function ($employees) {
                $now = now();
                $rows = $employees->map(fn ($e) => [
                    'employee_id' => $e->id,
                    'resolution_tab_id' => $e->resolution_tab_id,
                    'appointment_date' => $e->appointment_date,
                    'appointment_location' => $e->appointment_location,
                    'appointment_completed_at' => $e->appointment_completed_at,
                    'appointment_updated_by' => $e->appointment_updated_by,
                    'appointment_updated_at' => $e->appointment_updated_at,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all();

                DB::table('employee_appointments')->insertOrIgnore($rows);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_appointments');
    }
};
