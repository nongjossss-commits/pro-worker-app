<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A LaborTeam is who gets billed — but until now it had no tax-invoice-grade
 * identity (name/tax ID/address), since LaborBill is just an internal
 * statement. These pre-fill new LaborTaxInvoice forms; still editable per
 * invoice (snapshot), same as TaxInvoice's manual customer fields.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('labor_teams', function (Blueprint $table) {
            $table->string('customer_tax_id', 15)->nullable()->after('name');
            $table->string('customer_branch', 50)->nullable()->after('customer_tax_id');
            $table->text('customer_address')->nullable()->after('customer_branch');
        });
    }

    public function down(): void
    {
        Schema::table('labor_teams', function (Blueprint $table) {
            $table->dropColumn(['customer_tax_id', 'customer_branch', 'customer_address']);
        });
    }
};
