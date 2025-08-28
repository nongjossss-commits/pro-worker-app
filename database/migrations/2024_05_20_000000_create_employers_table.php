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
        Schema::create('employers', function (Blueprint $table) {
            $table->id();
            $table->string('employerId')->unique()->comment('รหัสนายจ้าง (MC-0001)');
            $table->string('employerNameTh')->nullable();
            $table->string('employerNameEn')->nullable();
            $table->string('employerTaxId')->nullable()->comment('เลขประจำตัวผู้เสียภาษี');
            $table->string('jobOwnerId')->nullable()->comment('รหัสเจ้าของงาน (NN-0001)');
            $table->string('signerNameTh')->nullable()->comment('ผู้มีอำนาจลงนาม (ไทย)');
            $table->string('signerNameEn')->nullable()->comment('ผู้มีอำนาจลงนาม (อังกฤษ)');
            $table->string('businessType')->nullable();
            $table->string('businessTypeEn')->nullable();
            $table->string('regCapital')->nullable()->comment('ทุนจดทะเบียน');
            $table->date('regDate')->nullable()->comment('วันที่จดทะเบียน');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employers');
    }
};
