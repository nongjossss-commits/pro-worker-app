<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (!Schema::hasColumn('employees', 'employeeTitleTh')) {
                $table->string('employeeTitleTh')->nullable()->after('employeeNameEn');
            }
            if (!Schema::hasColumn('employees', 'employeeTitleEn')) {
                $table->string('employeeTitleEn')->nullable()->after('employeeTitleTh');
            }
            if (!Schema::hasColumn('employees', 'employeeDob')) {
                $table->date('employeeDob')->nullable()->after('employeeTitleEn');
            }
            if (!Schema::hasColumn('employees', 'passportType')) {
                $table->string('passportType')->nullable()->after('employeePassport');
            }
            if (!Schema::hasColumn('employees', 'namelistNo')) {
                $table->string('namelistNo')->nullable()->after('passportType');
            }
            if (!Schema::hasColumn('employees', 'requestNo')) {
                $table->string('requestNo')->nullable()->after('namelistNo');
            }
            if (!Schema::hasColumn('employees', 'workerRefNo')) {
                $table->string('workerRefNo')->nullable()->after('requestNo');
            }
            if (!Schema::hasColumn('employees', 'personalId')) {
                $table->string('personalId')->nullable()->after('workerRefNo');
            }
            if (!Schema::hasColumn('employees', 'companyWorkerId')) {
                $table->string('companyWorkerId')->nullable()->after('personalId');
            }
            if (!Schema::hasColumn('employees', 'pinkCardNo')) {
                $table->string('pinkCardNo')->nullable()->after('companyWorkerId');
            }
            if (!Schema::hasColumn('employees', 'socialSecurityNo')) {
                $table->string('socialSecurityNo')->nullable()->after('pinkCardNo');
            }
            if (!Schema::hasColumn('employees', 'taxIdNo')) {
                $table->string('taxIdNo')->nullable()->after('socialSecurityNo');
            }
            if (!Schema::hasColumn('employees', 'designatedHospital')) {
                $table->string('designatedHospital')->nullable()->after('taxIdNo');
            }
            if (!Schema::hasColumn('employees', 'startDate')) {
                $table->date('startDate')->nullable()->after('ninetyDayReportDate');
            }
            if (!Schema::hasColumn('employees', 'employeePhone')) {
                $table->string('employeePhone')->nullable()->after('startDate');
            }
            if (!Schema::hasColumn('employees', 'employeePosition')) {
                $table->string('employeePosition')->nullable()->after('employeePhone');
            }
            if (!Schema::hasColumn('employees', 'employeePassportFile')) {
                $table->string('employeePassportFile')->nullable()->after('employeePosition');
            }
            if (!Schema::hasColumn('employees', 'employeeWorkPermitFile')) {
                $table->string('employeeWorkPermitFile')->nullable()->after('employeePassportFile');
            }
            if (!Schema::hasColumn('employees', 'pinkCardFile')) {
                $table->string('pinkCardFile')->nullable()->after('employeeWorkPermitFile');
            }
            if (!Schema::hasColumn('employees', 'workPermitMOUGroup')) {
                $table->string('workPermitMOUGroup')->nullable()->after('workPermitExpiryDate');
            }
            if (!Schema::hasColumn('employees', 'workPermitMOUGroupOther')) {
                $table->string('workPermitMOUGroupOther')->nullable()->after('workPermitMOUGroup');
            }
            if (!Schema::hasColumn('employees', 'employeePhoto')) {
                $table->string('employeePhoto')->nullable()->after('employeePosition');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $columns = [
                'employeeTitleTh',
                'employeeTitleEn',
                'employeeDob',
                'passportType',
                'namelistNo',
                'requestNo',
                'workerRefNo',
                'personalId',
                'companyWorkerId',
                'pinkCardNo',
                'socialSecurityNo',
                'taxIdNo',
                'designatedHospital',
                'startDate',
                'employeePhone',
                'employeePosition',
                'employeePassportFile',
                'employeeWorkPermitFile',
                'pinkCardFile',
                'workPermitMOUGroup',
                'workPermitMOUGroupOther',
                'employeePhoto',
            ];

            $toDrop = [];
            foreach ($columns as $column) {
                if (Schema::hasColumn('employees', $column)) {
                    $toDrop[] = $column;
                }
            }

            if (!empty($toDrop)) {
                $table->dropColumn($toDrop);
            }
        });
    }
};
