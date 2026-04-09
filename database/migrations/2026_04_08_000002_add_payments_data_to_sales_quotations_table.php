<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_quotations', function (Blueprint $table) {
            if (!Schema::hasColumn('sales_quotations', 'payments_data')) {
                $table->json('payments_data')->nullable()->after('items_data');
            }
            if (!Schema::hasColumn('sales_quotations', 'total_paid')) {
                $table->decimal('total_paid', 12, 2)->default(0)->after('grand_total');
            }
            if (!Schema::hasColumn('sales_quotations', 'payment_status')) {
                $table->string('payment_status')->default('unpaid')->after('total_paid');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales_quotations', function (Blueprint $table) {
            $table->dropColumn(['payments_data', 'total_paid', 'payment_status']);
        });
    }
};
