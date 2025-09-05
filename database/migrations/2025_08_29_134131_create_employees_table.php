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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employer_id')->constrained()->onDelete('cascade');
            $table->string('employeeNameTh')->nullable();
            $table->string('employeeNameEn')->nullable();
            $table->string('employeeNationality')->nullable();
            $table->string('employeePassport')->nullable();
            $table->date('passportExpiryDate')->nullable();
            $table->string('employeeWorkPermit')->nullable();
            $table->date('workPermitExpiryDate')->nullable();
            $table->date('visaExpiryDate')->nullable();
            $table->date('ninetyDayReportDate')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
