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
            $table->string('employeeTitleTh')->nullable()->after('employeeNameEn');
            $table->string('employeeTitleEn')->nullable()->after('employeeTitleTh');
            $table->date('employeeDob')->nullable()->after('employeeTitleEn');
            $table->string('passportType')->nullable()->after('employeePassport');
            $table->string('namelistNo')->nullable()->after('passportType');
            $table->string('requestNo')->nullable()->after('namelistNo');
            $table->string('workerRefNo')->nullable()->after('requestNo');
            $table->string('personalId')->nullable()->after('workerRefNo');
            $table->string('companyWorkerId')->nullable()->after('personalId');
            $table->string('pinkCardNo')->nullable()->after('companyWorkerId');
            $table->string('socialSecurityNo')->nullable()->after('pinkCardNo');
            $table->string('taxIdNo')->nullable()->after('socialSecurityNo');
            $table->string('designatedHospital')->nullable()->after('taxIdNo');
            $table->date('startDate')->nullable()->after('ninetyDayReportDate');
            $table->string('employeePhone')->nullable()->after('startDate');
            $table->string('employeePosition')->nullable()->after('employeePhone');
            $table->string('employeePassportFile')->nullable()->after('employeePosition');
            $table->string('employeeWorkPermitFile')->nullable()->after('employeePassportFile');
            $table->string('pinkCardFile')->nullable()->after('employeeWorkPermitFile');
            $table->string('workPermitMOUGroup')->nullable()->after('workPermitExpiryDate');
            $table->string('workPermitMOUGroupOther')->nullable()->after('workPermitMOUGroup');
            $table->string('employeePhoto')->nullable()->after('employeePosition');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn([
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
            ]);
        });
    }
};
