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
            $table->string('addrNoEn')->nullable()->after('addrZipCode');
            $table->string('addrMooEn')->nullable()->after('addrNoEn');
            $table->string('addrSoiEn')->nullable()->after('addrMooEn');
            $table->string('addrRoadEn')->nullable()->after('addrSoiEn');
            $table->string('addrSubDistrictEn')->nullable()->after('addrRoadEn');
            $table->string('addrDistrictEn')->nullable()->after('addrSubDistrictEn');
            $table->string('addrProvinceEn')->nullable()->after('addrDistrictEn');
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
            ]);
        });
    }
};
