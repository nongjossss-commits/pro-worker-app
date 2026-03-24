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
        Schema::create('sales_quotations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_lead_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('financial_profile_id')->nullable();
            $table->date('quotation_date');
            $table->string('payment_terms')->nullable();
            $table->decimal('grand_total', 12, 2)->default(0);
            $table->json('items_data')->nullable(); // Store JSON representation of quotation items for simplicity
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_quotations');
    }
};
