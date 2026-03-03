<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Convert large VARCHAR columns to TEXT to save row space
        Schema::table('employees', function (Blueprint $table) {
            $columnsToConvert = [
                'employee_doc_1', 'employee_doc_2', 'employee_doc_3', 'employee_doc_4',
                'employee_doc_5', 'employee_doc_6', 'employee_doc_7', 'employee_doc_8',
                'employee_doc_9', 'employee_doc_10', 'employee_doc_11', 'employee_doc_12',
                'other_doc_1_desc', 'other_doc_2_desc', 'other_doc_3_desc', 'other_doc_4_desc',
            ];

            foreach ($columnsToConvert as $column) {
                if (Schema::hasColumn('employees', $column)) {
                    $table->text($column)->nullable()->change();
                }
            }
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->text('registration_request_number')->nullable()->after('request_number');
            $table->text('renewal_request_number')->nullable()->after('registration_request_number');
        });

        // Backfill data from main request_number
        DB::statement('UPDATE employees SET registration_request_number = request_number, renewal_request_number = request_number WHERE request_number IS NOT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['registration_request_number', 'renewal_request_number']);
        });

        // Revert TEXT columns back to VARCHAR (string)
        Schema::table('employees', function (Blueprint $table) {
            $columnsToRevert = [
                'employee_doc_1', 'employee_doc_2', 'employee_doc_3', 'employee_doc_4',
                'employee_doc_5', 'employee_doc_6', 'employee_doc_7', 'employee_doc_8',
                'employee_doc_9', 'employee_doc_10', 'employee_doc_11', 'employee_doc_12',
                'other_doc_1_desc', 'other_doc_2_desc', 'other_doc_3_desc', 'other_doc_4_desc',
            ];

            foreach ($columnsToRevert as $column) {
                if (Schema::hasColumn('employees', $column)) {
                    $table->string($column, 255)->nullable()->change();
                }
            }
        });
    }
};
