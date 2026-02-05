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
        Schema::create('financial_transaction_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('financial_transaction_id');
            $table->unsignedBigInteger('production_item_id');
            $table->timestamps();

            $table->foreign('financial_transaction_id')->references('id')->on('financial_transactions')->onDelete('cascade');
            $table->foreign('production_item_id')->references('id')->on('production_items')->onDelete('cascade');

            // Optional: Prevent duplicate linking of same item to same transaction
            $table->unique(['financial_transaction_id', 'production_item_id'], 'txn_item_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_transaction_items');
    }
};
