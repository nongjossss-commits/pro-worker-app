<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add visa endorsement fields to the sales_lead_employees table so temp
 * employees created during the sales flow can carry the same visa data
 * as real employees. Values transfer over on lead → production transition.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_lead_employees', function (Blueprint $table) {
            $table->date('visaEndorsementDate')->nullable()->after('passportExpiryDate');
            $table->string('visaEndorsementNo', 50)->nullable()->after('visaEndorsementDate');
        });
    }

    public function down(): void
    {
        Schema::table('sales_lead_employees', function (Blueprint $table) {
            $table->dropColumn(['visaEndorsementDate', 'visaEndorsementNo']);
        });
    }
};
