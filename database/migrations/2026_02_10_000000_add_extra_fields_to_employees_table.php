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
            $table->string('department')->nullable()->after('employer_employee_id');
            $table->string('height')->nullable()->after('employeeNameEn');
            $table->string('weight')->nullable()->after('height');
            $table->string('passport_issue_place')->nullable()->after('employeePassport');
            $table->string('visa_issue_place')->nullable()->after('visaType');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn([
                'department',
                'height',
                'weight',
                'passport_issue_place',
                'visa_issue_place',
            ]);
        });
    }
};
