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
        // Convert existing large VARCHAR columns to TEXT to save row space
        Schema::table('employees', function (Blueprint $table) {
            $columnsToConvert = [
                'document_1', 'document_2', 'document_3', 'document_4', 'document_5', 'document_6',
                'document_description_4', 'document_description_5', 'document_description_6',
                'employee_doc_13', 'employee_doc_14', 'employee_doc_15', 'employee_doc_16', 'employee_doc_17', 'employee_doc_18',
                'other_doc_5_desc', 'other_doc_6_desc', 'other_doc_7_desc', 'other_doc_8_desc', 'other_doc_9_desc', 'other_doc_10_desc',
            ];

            foreach ($columnsToConvert as $column) {
                 // We use explicit check to avoid errors if columns are missing in some environments
                if (Schema::hasColumn('employees', $column)) {
                    $table->text($column)->nullable()->change();
                }
            }
        });

        // Add new columns
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
        // Drop new columns first
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn([
                'department',
                'height',
                'weight',
                'passport_issue_place',
                'visa_issue_place',
            ]);
        });

        // Revert TEXT columns back to VARCHAR (string)
        Schema::table('employees', function (Blueprint $table) {
            $columnsToRevert = [
                'document_1', 'document_2', 'document_3', 'document_4', 'document_5', 'document_6',
                'document_description_4', 'document_description_5', 'document_description_6',
                'employee_doc_13', 'employee_doc_14', 'employee_doc_15', 'employee_doc_16', 'employee_doc_17', 'employee_doc_18',
                'other_doc_5_desc', 'other_doc_6_desc', 'other_doc_7_desc', 'other_doc_8_desc', 'other_doc_9_desc', 'other_doc_10_desc',
            ];

            foreach ($columnsToRevert as $column) {
                if (Schema::hasColumn('employees', $column)) {
                    $table->string($column, 255)->nullable()->change();
                }
            }
        });
    }
};
