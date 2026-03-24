<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_leads', function (Blueprint $table) {
            $table->string('employerEmail')->nullable();
            $table->string('employerPassword')->nullable();
            $table->string('outsource_re_code')->nullable();
            $table->string('outsource_password')->nullable();
            $table->string('socialSecurityHospital')->nullable();
            $table->string('businessType')->nullable();
            $table->string('businessTypeEn')->nullable();
            $table->string('signerNameTh')->nullable();
            $table->string('signerNameEn')->nullable();
            $table->string('signer_2_name_th')->nullable();
            $table->string('signer_2_name_en')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('sales_leads', function (Blueprint $table) {
            $table->dropColumn([
                'employerEmail',
                'employerPassword',
                'outsource_re_code',
                'outsource_password',
                'socialSecurityHospital',
                'businessType',
                'businessTypeEn',
                'signerNameTh',
                'signerNameEn',
                'signer_2_name_th',
                'signer_2_name_en'
            ]);
        });
    }
};
