<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Single-row settings table: which of the main program's FinancialProfiles
 * (biller-type company letterhead) Labor bills are issued under. A read-only
 * link — Labor never writes into financial_profiles, and this table has no
 * other connection to the main app's own financial data.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('labor_billing_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('financial_profile_id')->nullable()->constrained('financial_profiles')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('labor_billing_settings');
    }
};
