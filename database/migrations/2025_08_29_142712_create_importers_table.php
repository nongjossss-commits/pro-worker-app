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
        Schema::create('importers', function (Blueprint $table) {
            $table->id();
            $table->string('importerNameTh')->nullable();
            $table->string('importerNameEn')->nullable();
            $table->string('importerId')->nullable();
            $table->string('importerLicenseNo')->nullable();
            $table->date('importerLicenseIssueDate')->nullable();
            $table->date('importerLicenseExpiryDate')->nullable();
            $table->string('importerSignerTh')->nullable();
            $table->string('importerSignerEn')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('importers');
    }
};
