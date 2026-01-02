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
            // Drop Legacy Identification Columns
            $columnsToDrop = [
                'namelistNo',
                'requestNo',
                'workerRefNo',
                'personalId',
                'companyWorkerId',
                'socialSecurityNo',
                'taxIdNo',
                // Legacy Document Columns
                'document_1',
                'document_2',
                'document_3',
                'document_4',
                'document_description_4',
                'document_5',
                'document_description_5',
                'document_6',
                'document_description_6',
                // Duplicate Column
                'visa_type',
            ];

            foreach ($columnsToDrop as $column) {
                if (Schema::hasColumn('employees', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // Restore Legacy Identification Columns if they don't exist
            if (!Schema::hasColumn('employees', 'namelistNo')) $table->string('namelistNo')->nullable();
            if (!Schema::hasColumn('employees', 'requestNo')) $table->string('requestNo')->nullable();
            if (!Schema::hasColumn('employees', 'workerRefNo')) $table->string('workerRefNo')->nullable();
            if (!Schema::hasColumn('employees', 'personalId')) $table->string('personalId')->nullable();
            if (!Schema::hasColumn('employees', 'companyWorkerId')) $table->string('companyWorkerId')->nullable();
            if (!Schema::hasColumn('employees', 'socialSecurityNo')) $table->string('socialSecurityNo')->nullable();
            if (!Schema::hasColumn('employees', 'taxIdNo')) $table->string('taxIdNo')->nullable();

            // Restore Legacy Document Columns
            if (!Schema::hasColumn('employees', 'document_1')) $table->string('document_1')->nullable();
            if (!Schema::hasColumn('employees', 'document_2')) $table->string('document_2')->nullable();
            if (!Schema::hasColumn('employees', 'document_3')) $table->string('document_3')->nullable();
            if (!Schema::hasColumn('employees', 'document_4')) $table->string('document_4')->nullable();
            if (!Schema::hasColumn('employees', 'document_description_4')) $table->string('document_description_4')->nullable();
            if (!Schema::hasColumn('employees', 'document_5')) $table->string('document_5')->nullable();
            if (!Schema::hasColumn('employees', 'document_description_5')) $table->string('document_description_5')->nullable();
            if (!Schema::hasColumn('employees', 'document_6')) $table->string('document_6')->nullable();
            if (!Schema::hasColumn('employees', 'document_description_6')) $table->string('document_description_6')->nullable();

            // Restore Duplicate Column
            if (!Schema::hasColumn('employees', 'visa_type')) $table->string('visa_type')->nullable();
        });
    }
};
