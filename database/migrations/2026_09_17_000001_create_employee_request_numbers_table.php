<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A single employee's "เลขรับคำขอ" (request-received number), scoped
     * independently per ResolutionTab — same fix as
     * create_employee_appointments_table for the exact same class of bug:
     * a request number set from Registration Resolution was "leaking" into
     * Renewal Resolution (and between separate, user-created Renewal tabs)
     * for the same physical person, because `employees.registration_
     * request_number` / `renewal_request_number` are flat columns shared by
     * every context that touches that row. One row per
     * (employee, resolution_tab_id) pair here instead.
     *
     * The old `employees.registration_request_number` /
     * `renewal_request_number` / `request_number` columns are left in place
     * (unused going forward) rather than dropped — see this migration's
     * `up()` for the one-time backfill of any existing value into this new
     * table, tagged with each employee's current `resolution_tab_id` (using
     * whichever of the two flat columns matches that tab's type) so nothing
     * already set today gets lost.
     */
    public function up(): void
    {
        Schema::create('employee_request_numbers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('resolution_tab_id')->constrained('resolution_tabs')->cascadeOnDelete();
            $table->text('request_number')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'resolution_tab_id']);
        });

        DB::table('employees')
            ->join('resolution_tabs', 'employees.resolution_tab_id', '=', 'resolution_tabs.id')
            ->whereNotNull('employees.resolution_tab_id')
            ->where(function ($q) {
                $q->whereNotNull('employees.registration_request_number')
                    ->orWhereNotNull('employees.renewal_request_number');
            })
            ->orderBy('employees.id')
            ->select(
                'employees.id as employee_id',
                'employees.resolution_tab_id',
                'resolution_tabs.type as tab_type',
                'employees.registration_request_number',
                'employees.renewal_request_number'
            )
            ->chunkById(200, function ($employees) {
                $now = now();
                $rows = $employees->map(function ($e) use ($now) {
                    $value = $e->tab_type === 'renewal' ? $e->renewal_request_number : $e->registration_request_number;

                    if ($value === null) {
                        return null;
                    }

                    return [
                        'employee_id' => $e->employee_id,
                        'resolution_tab_id' => $e->resolution_tab_id,
                        'request_number' => $value,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                })->filter()->values()->all();

                if (!empty($rows)) {
                    DB::table('employee_request_numbers')->insertOrIgnore($rows);
                }
            }, 'employees.id', 'employee_id');
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_request_numbers');
    }
};
