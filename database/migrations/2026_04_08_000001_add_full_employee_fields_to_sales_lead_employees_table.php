<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Convert table to DYNAMIC row format to allow more columns
        DB::statement('ALTER TABLE sales_lead_employees ROW_FORMAT=DYNAMIC');

        // Convert existing large varchar columns to TEXT to free row space
        $existingPathCols = [
            'employee_doc_1', 'employee_doc_2', 'employee_doc_visa', 'employee_doc_3', 'employee_doc_4',
            'employee_doc_other_1', 'employee_doc_other_2', 'employee_doc_other_3',
            'photo_path', 'employeeAddress',
        ];
        foreach ($existingPathCols as $col) {
            if (Schema::hasColumn('sales_lead_employees', $col)) {
                DB::statement("ALTER TABLE sales_lead_employees MODIFY `{$col}` TEXT NULL");
            }
        }

        // Also convert existing other doc columns from previous migration
        for ($i = 4; $i <= 10; $i++) {
            $col = 'employee_doc_other_' . $i;
            if (Schema::hasColumn('sales_lead_employees', $col)) {
                DB::statement("ALTER TABLE sales_lead_employees MODIFY `{$col}` TEXT NULL");
            }
        }

        // Now add missing columns
        $stringCols = [
            'employeeTitleTh', 'employeeTitleEn', 'height', 'weight', 'father_name', 'mother_name',
            'passportType', 'passport_type_cambodia', 'passport_issue_place',
            'visaType', 'visa_issue_place',
            'job_title', 'job_description', 'workPermitMOUGroupOther',
            'request_number', 'employee_id_number', 'tax_id_number', 'department',
            'employee_reference_id', 'bank_name', 'bank_account_number',
            'social_security_number', 'insurance_detail_social',
            'insurance_detail_hospital', 'insurance_detail_private', 'medical_hospital_name',
            'employeeEmail', 'employeePassword', 'outsource_code',
        ];

        $dateCols = [
            'passport_issue_date', 'visaExpiryDate', 'startDate', 'ninetyDayReportDate',
            'sso_issue_date', 'sso_expiry_date', 'insurance_expiry_date_hospital', 'insurance_expiry_date_private',
        ];

        $textCols = [
            'insurance_document_path_social', 'insurance_document_path_hospital',
            'insurance_document_path_private', 'medical_certificate_path',
        ];

        Schema::table('sales_lead_employees', function (Blueprint $table) use ($stringCols, $dateCols, $textCols) {
            foreach ($stringCols as $name) {
                if (!Schema::hasColumn('sales_lead_employees', $name)) {
                    $table->string($name)->nullable();
                }
            }
            foreach ($dateCols as $name) {
                if (!Schema::hasColumn('sales_lead_employees', $name)) {
                    $table->date($name)->nullable();
                }
            }
            foreach ($textCols as $name) {
                if (!Schema::hasColumn('sales_lead_employees', $name)) {
                    $table->text($name)->nullable();
                }
            }

            // Additional Documents (5-18)
            for ($i = 5; $i <= 18; $i++) {
                if (!Schema::hasColumn('sales_lead_employees', 'employee_doc_' . $i)) {
                    $table->text('employee_doc_' . $i)->nullable();
                }
            }
            // Other doc descriptions
            for ($i = 4; $i <= 10; $i++) {
                if (!Schema::hasColumn('sales_lead_employees', 'other_doc_' . $i . '_desc')) {
                    $table->string('other_doc_' . $i . '_desc')->nullable();
                }
            }
        });
    }

    public function down(): void
    {
        $dropCols = [
            'employeeTitleTh', 'employeeTitleEn', 'height', 'weight', 'father_name', 'mother_name',
            'passportType', 'passport_type_cambodia', 'passport_issue_date', 'passport_issue_place',
            'visaType', 'visa_issue_place', 'visaExpiryDate',
            'job_title', 'job_description', 'startDate', 'ninetyDayReportDate', 'workPermitMOUGroupOther',
            'request_number', 'employee_id_number', 'tax_id_number', 'department', 'employee_reference_id',
            'bank_name', 'bank_account_number',
            'social_security_number', 'insurance_detail_social', 'insurance_document_path_social',
            'sso_issue_date', 'sso_expiry_date', 'insurance_detail_hospital', 'insurance_expiry_date_hospital',
            'insurance_document_path_hospital', 'insurance_detail_private', 'insurance_expiry_date_private',
            'insurance_document_path_private', 'medical_certificate_path', 'medical_hospital_name',
            'employeeEmail', 'employeePassword', 'outsource_code',
        ];

        Schema::table('sales_lead_employees', function (Blueprint $table) use ($dropCols) {
            $existing = [];
            foreach ($dropCols as $col) {
                if (Schema::hasColumn('sales_lead_employees', $col)) {
                    $existing[] = $col;
                }
            }
            if (!empty($existing)) {
                $table->dropColumn($existing);
            }
        });
    }
};
