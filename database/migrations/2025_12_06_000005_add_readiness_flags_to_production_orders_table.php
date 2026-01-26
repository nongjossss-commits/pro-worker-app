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
        Schema::table('production_orders', function (Blueprint $table) {
            $table->timestamp('document_ready_at')->nullable()->after('financial_data');
            $table->unsignedBigInteger('document_ready_by')->nullable()->after('document_ready_at');
            $table->timestamp('financial_approved_at')->nullable()->after('document_ready_by');
            $table->unsignedBigInteger('financial_approved_by')->nullable()->after('financial_approved_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('production_orders', function (Blueprint $table) {
            $table->dropColumn([
                'document_ready_at',
                'document_ready_by',
                'financial_approved_at',
                'financial_approved_by'
            ]);
        });
    }
};
