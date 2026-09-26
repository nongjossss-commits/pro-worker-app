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
        // The table-creation migration for labor_expense_categories is timestamped after
        // this one, so on a fresh install it may not exist yet.
        if (!Schema::hasTable('labor_expense_categories')) {
            Schema::create('labor_expense_categories', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->boolean('is_tax_deductible')->default(false);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasColumn('labor_expense_categories', 'note')) {
            Schema::table('labor_expense_categories', function (Blueprint $table) {
                $table->text('note')->nullable()->after('name');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('labor_expense_categories', function (Blueprint $table) {
            $table->dropColumn('note');
        });
    }
};
