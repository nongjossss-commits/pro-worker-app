<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Distinguishes "Quotation" from "Invoice" manual bills (ProductionOrder
     * with work_type_id = null, created via Finance Hub's "Create Manual
     * Bill" flow) so their history can be listed in separate tabs instead of
     * mixed together. Every existing manual-bill row predates the Quotation
     * option — they were all created through the old invoice-only form — so
     * they're backfilled to 'invoice' here, keeping the existing "Manual
     * Bills" tab showing exactly what it showed before this migration.
     */
    public function up(): void
    {
        Schema::table('production_orders', function (Blueprint $table) {
            $table->string('manual_bill_type')->nullable()->after('work_type_id');
        });

        DB::table('production_orders')
            ->whereNull('work_type_id')
            ->update(['manual_bill_type' => 'invoice']);
    }

    public function down(): void
    {
        Schema::table('production_orders', function (Blueprint $table) {
            $table->dropColumn('manual_bill_type');
        });
    }
};
