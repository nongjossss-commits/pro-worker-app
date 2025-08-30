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
        Schema::table('employers', function (Blueprint $table) {
            $table->string('employerNameEn')->nullable()->after('employerNameTh');
            $table->string('minimum_wage')->nullable()->after('regDate');
            $table->string('document_company_registration')->nullable()->after('minimum_wage');
            $table->string('document_vat_registration')->nullable()->after('document_company_registration');
            $table->string('document_map')->nullable()->after('document_vat_registration');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employers', function (Blueprint $table) {
            $table->dropColumn([
                'employerNameEn',
                'minimum_wage',
                'document_company_registration',
                'document_vat_registration',
                'document_map',
            ]);
        });
    }
};
