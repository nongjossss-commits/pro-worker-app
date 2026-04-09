<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('name_suffix', 255)->nullable()->after('employeeNameEn');
        });

        Schema::table('employers', function (Blueprint $table) {
            $table->string('name_suffix', 255)->nullable()->after('employerNameEn');
        });

        Schema::table('sales_lead_employees', function (Blueprint $table) {
            $table->string('name_suffix', 255)->nullable()->after('employeeNameEn');
        });

        Schema::table('sales_leads', function (Blueprint $table) {
            $table->string('name_suffix', 255)->nullable()->after('employerNameEn');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('name_suffix');
        });

        Schema::table('employers', function (Blueprint $table) {
            $table->dropColumn('name_suffix');
        });

        Schema::table('sales_lead_employees', function (Blueprint $table) {
            $table->dropColumn('name_suffix');
        });

        Schema::table('sales_leads', function (Blueprint $table) {
            $table->dropColumn('name_suffix');
        });
    }
};
