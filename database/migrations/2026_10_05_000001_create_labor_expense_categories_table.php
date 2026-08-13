<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Expense-side counterpart to labor_charge_types — a Super Admin-managed
 * catalog of expense categories for Company Books. No soft delete /
 * destroy, same as labor_charge_types: deactivate via is_active so
 * historical labor_book_transactions rows never lose their label.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('labor_expense_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_tax_deductible')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('labor_expense_categories');
    }
};
