<?php

namespace Database\Migrations;

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
        Schema::create('sales_leads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employer_id')->nullable()->comment('Linked to real employer if selected from search');

            // Replicating Employer fields, but mostly nullable
            $table->string('employerNameTh'); // Only required field
            $table->string('employerNameEn')->nullable();
            $table->string('employerId')->nullable()->comment('Temporary ID for display before confirmed');
            $table->string('employerTaxId')->nullable();
            $table->string('employerPhone')->nullable();
            $table->string('jobOwner')->nullable();
            $table->boolean('requires_special_attention')->default(false);

            $table->string('status')->default('quoted')->comment('quoted, deciding, confirmed, transitioned, cancelled');
            $table->string('workflow_destination')->nullable()->comment('To remember where it went (e.g. renewal, registration, notify_out)');

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('confirmed_by')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_leads');
    }
};
