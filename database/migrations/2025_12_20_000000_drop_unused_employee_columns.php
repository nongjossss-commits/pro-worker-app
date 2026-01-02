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
            $table->dropColumn([
                'namelistNo',
                'requestNo',
                'workerRefNo',
                'personalId',
                'companyWorkerId',
                'socialSecurityNo',
                'taxIdNo',
            ]);

            // Drop Legacy Document Columns
            $table->dropColumn([
                'document_1',
                'document_2',
                'document_3',
                'document_4',
                'document_description_4',
                'document_5',
                'document_description_5',
                'document_6',
                'document_description_6',
            ]);

            // Drop Duplicate/Redundant Column (keeping 'visaType')
            $table->dropColumn('visa_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // Restore Legacy Identification Columns
            $table->string('namelistNo')->nullable();
            $table->string('requestNo')->nullable();
            $table->string('workerRefNo')->nullable();
            $table->string('personalId')->nullable();
            $table->string('companyWorkerId')->nullable();
            $table->string('socialSecurityNo')->nullable();
            $table->string('taxIdNo')->nullable();

            // Restore Legacy Document Columns
            $table->string('document_1')->nullable();
            $table->string('document_2')->nullable();
            $table->string('document_3')->nullable();
            $table->string('document_4')->nullable();
            $table->string('document_description_4')->nullable();
            $table->string('document_5')->nullable();
            $table->string('document_description_5')->nullable();
            $table->string('document_6')->nullable();
            $table->string('document_description_6')->nullable();

            // Restore Duplicate Column
            $table->string('visa_type')->nullable();
        });
    }
};
