<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_leads', function (Blueprint $table) {
            $table->string('employer_doc_company')->nullable();
            $table->date('employer_doc_company_expiry')->nullable();
            $table->string('employer_doc_lease')->nullable();
            $table->string('employer_doc_construction')->nullable();
            $table->string('employer_doc_other_1')->nullable();
            $table->string('employer_doc_other_1_desc')->nullable();
            $table->string('employer_doc_other_2')->nullable();
            $table->string('employer_doc_other_2_desc')->nullable();
            $table->string('employer_doc_other_3')->nullable();
            $table->string('employer_doc_other_3_desc')->nullable();
        });

        Schema::table('sales_lead_employees', function (Blueprint $table) {
            $table->string('employee_doc_visa')->nullable();
            $table->string('employee_doc_other_1')->nullable();
            $table->string('employee_doc_other_2')->nullable();
            $table->string('employee_doc_other_3')->nullable();
            $table->string('employee_doc_other_4')->nullable();
            $table->string('employee_doc_other_5')->nullable();
            $table->string('employee_doc_other_6')->nullable();
            $table->string('employee_doc_other_7')->nullable();
            $table->string('employee_doc_other_8')->nullable();
            $table->string('employee_doc_other_9')->nullable();
            $table->string('employee_doc_other_10')->nullable();
            $table->string('other_doc_1_desc')->nullable();
            $table->string('other_doc_2_desc')->nullable();
            $table->string('other_doc_3_desc')->nullable();
            $table->string('other_doc_4_desc')->nullable();
            $table->string('other_doc_5_desc')->nullable();
            $table->string('other_doc_6_desc')->nullable();
            $table->string('other_doc_7_desc')->nullable();
            $table->string('other_doc_8_desc')->nullable();
            $table->string('other_doc_9_desc')->nullable();
            $table->string('other_doc_10_desc')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('sales_leads', function (Blueprint $table) {
            $table->dropColumn([
                'employer_doc_company',
                'employer_doc_company_expiry',
                'employer_doc_lease',
                'employer_doc_construction',
                'employer_doc_other_1',
                'employer_doc_other_1_desc',
                'employer_doc_other_2',
                'employer_doc_other_2_desc',
                'employer_doc_other_3',
                'employer_doc_other_3_desc',
            ]);
        });

        Schema::table('sales_lead_employees', function (Blueprint $table) {
            $table->dropColumn([
                'employee_doc_visa',
                'employee_doc_other_1',
                'employee_doc_other_2',
                'employee_doc_other_3',
                'employee_doc_other_4',
                'employee_doc_other_5',
                'employee_doc_other_6',
                'employee_doc_other_7',
                'employee_doc_other_8',
                'employee_doc_other_9',
                'employee_doc_other_10',
                'other_doc_1_desc',
                'other_doc_2_desc',
                'other_doc_3_desc',
                'other_doc_4_desc',
                'other_doc_5_desc',
                'other_doc_6_desc',
                'other_doc_7_desc',
                'other_doc_8_desc',
                'other_doc_9_desc',
                'other_doc_10_desc',
            ]);
        });
    }
};
