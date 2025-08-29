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
        Schema::create('delegates', function (Blueprint $table) {
            $table->id();
            $table->string('delegateNameTh')->nullable();
            $table->string('delegateNameEn')->nullable();
            $table->string('delegateId')->nullable();
            $table->string('delegateEmployeeId')->nullable();
            $table->date('delegateIssueDate')->nullable();
            $table->date('delegateExpiryDate')->nullable();
            $table->string('delegatePhone')->nullable();
            $table->string('delegateEmail')->nullable();
            $table->string('delegatePhoto')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delegates');
    }
};
