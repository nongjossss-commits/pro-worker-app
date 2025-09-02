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
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('addressable_id');
            $table->string('addressable_type');
            $table->string('type'); // 'registered' or 'workplace'
            $table->string('addrNo')->nullable();
            $table->string('addrMoo')->nullable();
            $table->string('addrSoi')->nullable();
            $table->string('addrRoad')->nullable();
            $table->string('addrProvince')->nullable();
            $table->string('addrDistrict')->nullable();
            $table->string('addrSubDistrict')->nullable();
            $table->string('addrZipCode')->nullable();
            $table->timestamps();

            $table->index(['addressable_id', 'addressable_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};
