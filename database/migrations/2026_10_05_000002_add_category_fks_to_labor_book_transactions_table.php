<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Replaces the hardcoded CATEGORIES const with admin-managed catalogs:
 * income transactions link to labor_charge_types, expense transactions
 * link to labor_expense_categories. The `category` string column is kept
 * — it remains the sentinel for the 'team_payment' auto-generated rows
 * (a bill can span multiple charge types, so those rows intentionally
 * don't pick one).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('labor_book_transactions', function (Blueprint $table) {
            $table->foreignId('labor_charge_type_id')->nullable()->after('category')
                ->constrained('labor_charge_types')->nullOnDelete();
            $table->foreignId('labor_expense_category_id')->nullable()->after('labor_charge_type_id')
                ->constrained('labor_expense_categories')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('labor_book_transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('labor_charge_type_id');
            $table->dropConstrainedForeignId('labor_expense_category_id');
        });
    }
};
