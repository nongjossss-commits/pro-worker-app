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
            $table->string('insurance_detail_hospital')->nullable();
            $table->string('insurance_expiry_date_hospital')->nullable();
            $table->string('insurance_detail_private')->nullable();
            $table->string('insurance_expiry_date_private')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn([
                'insurance_detail_hospital',
                'insurance_expiry_date_hospital',
                'insurance_detail_private',
                'insurance_expiry_date_private',
             ]);
        });
    }
};
