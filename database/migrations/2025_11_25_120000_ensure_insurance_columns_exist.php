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
            if (!Schema::hasColumn('employees', 'insurance_document_path')) {
                $table->string('insurance_document_path')->nullable();
            }
            if (!Schema::hasColumn('employees', 'insurance_document_path_private')) {
                $table->string('insurance_document_path_private')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (Schema::hasColumn('employees', 'insurance_document_path')) {
                $table->dropColumn('insurance_document_path');
            }
            if (Schema::hasColumn('employees', 'insurance_document_path_private')) {
                $table->dropColumn('insurance_document_path_private');
            }
        });
    }
};
