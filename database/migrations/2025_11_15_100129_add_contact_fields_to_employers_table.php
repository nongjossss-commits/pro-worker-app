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
            $table->string('employerEmail')->nullable()->unique()->after('employerTaxId');
            $table->string('employerPassword')->nullable()->after('employerEmail');
            $table->string('employerPhone')->nullable()->after('employerPassword');
            $table->string('socialSecurityHospital')->nullable()->after('employerPhone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employers', function (Blueprint $table) {
            $table->dropColumn(['employerEmail', 'employerPassword', 'employerPhone', 'socialSecurityHospital']);
        });
    }
};
