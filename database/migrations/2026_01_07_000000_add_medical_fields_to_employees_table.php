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
        // Add medical_certificate_path
        if (!Schema::hasColumn('employees', 'medical_certificate_path')) {
            Schema::table('employees', function (Blueprint $table) {
                if (Schema::hasColumn('employees', 'insurance_document_path')) {
                    $table->string('medical_certificate_path')->nullable()->after('insurance_document_path');
                } else {
                    $table->string('medical_certificate_path')->nullable();
                }
            });
        }

        // Add medical_hospital_name
        if (!Schema::hasColumn('employees', 'medical_hospital_name')) {
            Schema::table('employees', function (Blueprint $table) {
                if (Schema::hasColumn('employees', 'medical_certificate_path')) {
                    $table->string('medical_hospital_name')->nullable()->after('medical_certificate_path');
                } else {
                    $table->string('medical_hospital_name')->nullable();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (Schema::hasColumn('employees', 'medical_hospital_name')) {
                $table->dropColumn('medical_hospital_name');
            }
            if (Schema::hasColumn('employees', 'medical_certificate_path')) {
                $table->dropColumn('medical_certificate_path');
            }
        });
    }
};
