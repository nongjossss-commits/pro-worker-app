<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Plain (non-unique) indexes for the new duplicate-detection feature —
 * these columns allow duplicates (see EmployeeController::checkDuplicate()/
 * EmployerController::checkDuplicate()), but the feature adds GROUP BY and
 * lookup queries against them on every relevant page load, and none of
 * them had an index before.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->index('employeePassport');
            $table->index('employeeWorkPermit');
            $table->index('pinkCardNo');
            $table->index('employee_id_number');
        });

        Schema::table('employers', function (Blueprint $table) {
            $table->index('employerTaxId');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropIndex(['employeePassport']);
            $table->dropIndex(['employeeWorkPermit']);
            $table->dropIndex(['pinkCardNo']);
            $table->dropIndex(['employee_id_number']);
        });

        Schema::table('employers', function (Blueprint $table) {
            $table->dropIndex(['employerTaxId']);
        });
    }
};
