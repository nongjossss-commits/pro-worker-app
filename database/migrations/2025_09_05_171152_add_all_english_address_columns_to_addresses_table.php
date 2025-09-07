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
    Schema::table('addresses', function (Blueprint $table) {
        $table->string('addrNoEn')->nullable();
        $table->string('addrMooEn')->nullable();
        $table->string('addrSoiEn')->nullable();
        $table->string('addrRoadEn')->nullable();
        $table->string('addrSubDistrictEn')->nullable();
        $table->string('addrDistrictEn')->nullable();
        $table->string('addrProvinceEn')->nullable();
        $table->string('addrZipCodeEn')->nullable();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            $table->dropColumn([
                'addrNoEn',
                'addrMooEn',
                'addrSoiEn',
                'addrRoadEn',
                'addrSubDistrictEn',
                'addrDistrictEn',
                'addrProvinceEn',
                'addrZipCodeEn',
            ]);
        });
    }
};