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
            if (!Schema::hasColumn('production_orders', 'document_ready_at')) {
                $table->timestamp('document_ready_at')->nullable()->after('financial_data');
            }
            if (!Schema::hasColumn('production_orders', 'document_ready_by')) {
                $table->unsignedBigInteger('document_ready_by')->nullable()->after('document_ready_at'); // Note: 'after' works best if the previous column exists.
            }
            if (!Schema::hasColumn('production_orders', 'financial_approved_at')) {
                $table->timestamp('financial_approved_at')->nullable()->after('document_ready_by');
            }
            if (!Schema::hasColumn('production_orders', 'financial_approved_by')) {
                $table->unsignedBigInteger('financial_approved_by')->nullable()->after('financial_approved_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('production_orders', function (Blueprint $table) {
            $columnsToDrop = [];

            if (Schema::hasColumn('production_orders', 'document_ready_at')) {
                $columnsToDrop[] = 'document_ready_at';
            }
            if (Schema::hasColumn('production_orders', 'document_ready_by')) {
                $columnsToDrop[] = 'document_ready_by';
            }
            if (Schema::hasColumn('production_orders', 'financial_approved_at')) {
                $columnsToDrop[] = 'financial_approved_at';
            }
            if (Schema::hasColumn('production_orders', 'financial_approved_by')) {
                $columnsToDrop[] = 'financial_approved_by';
            }

            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
