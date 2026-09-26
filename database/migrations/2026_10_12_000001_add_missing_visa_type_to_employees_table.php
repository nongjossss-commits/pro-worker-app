<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * employees.visaType is used by the employee form ("ประเภทวีซ่า") and by
 * 2026_02_10_000000 (…->after('visaType')), but no migration ever created it
 * — it exists on databases that were set up before migrations covered it.
 * A database built only from migrations (a new install, a dev machine) has no
 * such column, so saving an employee failed with "no such column: visaType".
 *
 * Adds it only where it is missing: on a database that already has it
 * (production) this migration changes nothing.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('employees', 'visaType')) {
            return;
        }

        Schema::table('employees', function (Blueprint $table) {
            $table->string('visaType')->nullable();
        });
    }

    public function down(): void
    {
        // Deliberately left in place: on production the column predates this
        // migration and holds real data, so rolling back must never drop it.
    }
};
