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
            $table->string('name_list_number')->nullable();
            $table->string('request_number')->nullable();
            $table->string('employee_id_number')->nullable();
            $table->string('tax_id_number')->nullable();
            $table->string('employer_employee_id')->nullable();
            $table->string('employee_reference_id')->nullable();
            $table->string('father_name')->nullable();
            $table->string('mother_name')->nullable();
            $table->date('passport_issue_date')->nullable();
            $table->string('passport_type_cambodia')->nullable();
            $table->string('insurance_type')->nullable();
            $table->string('insurance_detail')->nullable();
            $table->date('insurance_expiry_date')->nullable();
            $table->string('social_security_number')->nullable();
            $table->string('employee_doc_5')->nullable();
            $table->string('employee_doc_6')->nullable();
            $table->string('employee_doc_7')->nullable();
            $table->string('employee_doc_8')->nullable();
            $table->string('employee_doc_9')->nullable();
            $table->string('employee_doc_10')->nullable();
            $table->string('employee_doc_11')->nullable();
            $table->string('employee_doc_12')->nullable();
            $table->string('other_doc_1_desc')->nullable();
            $table->string('other_doc_2_desc')->nullable();
            $table->string('other_doc_3_desc')->nullable();
            $table->string('other_doc_4_desc')->nullable();
            $table->string('employeePassport')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn([
                'name_list_number',
                'request_number',
                'employee_id_number',
                'tax_id_number',
                'employer_employee_id',
                'employee_reference_id',
                'father_name',
                'mother_name',
                'passport_issue_date',
                'passport_type_cambodia',
                'insurance_type',
                'insurance_detail',
                'insurance_expiry_date',
                'social_security_number',
                'employee_doc_1',
                'employee_doc_2',
                'employee_doc_3',
                'employee_doc_4',
                'employee_doc_5',
                'employee_doc_6',
                'employee_doc_7',
                'employee_doc_8',
                'employee_doc_9',
                'employee_doc_10',
                'employee_doc_11',
                'employee_doc_12',
                'other_doc_1_desc',
                'other_doc_2_desc',
                'other_doc_3_desc',
                'other_doc_4_desc',
            ]);
            $table->string('employeePassport')->nullable(false)->change();
        });
    }
};
