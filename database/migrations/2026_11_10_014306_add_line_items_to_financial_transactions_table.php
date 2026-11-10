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
        Schema::table('financial_transactions', function (Blueprint $table) {
            // Structured breakdown for a Fixed Total bill: [{description, amount, fixed_item_id}, ...].
            // Snapshot of financial_data.fixed_items at the moment the installment was created,
            // so it stays correct on the document even if the source item is later edited/removed.
            $table->json('line_items')->nullable()->after('notes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('financial_transactions', function (Blueprint $table) {
            $table->dropColumn('line_items');
        });
    }
};
