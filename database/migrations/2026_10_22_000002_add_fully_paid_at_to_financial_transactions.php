<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Finance popup "Installments & Payments": a fully paid installment stays in
 * the main list for 24 hours, then moves under the "Fully paid" tab.
 * fully_paid_at = when the status last became 'paid' (kept by
 * FinancialTransaction::booted()). Installments already paid get their last
 * update time, so they start out under the "Fully paid" tab — only this new
 * column is written; amounts / statuses are untouched.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('financial_transactions', function (Blueprint $table) {
            $table->timestamp('fully_paid_at')->nullable()->after('status');
        });

        DB::table('financial_transactions')
            ->where('status', 'paid')
            ->update(['fully_paid_at' => DB::raw('updated_at')]);
    }

    public function down(): void
    {
        Schema::table('financial_transactions', function (Blueprint $table) {
            $table->dropColumn('fully_paid_at');
        });
    }
};
