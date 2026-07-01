<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add visa endorsement (ตรวจลงตราวีซ่า) fields:
 *  - visaEndorsementDate: date the visa was stamped in the passport
 *  - visaEndorsementNo:   the endorsement reference number
 *
 * Both nullable — existing rows stay untouched, users fill in when they have the data.
 * Placed right before visaExpiryDate so the visa block reads left-to-right:
 *   endorsement date → endorsement no → expiry date.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->date('visaEndorsementDate')->nullable()->after('passportExpiryDate');
            $table->string('visaEndorsementNo', 50)->nullable()->after('visaEndorsementDate');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['visaEndorsementDate', 'visaEndorsementNo']);
        });
    }
};
