<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the columns that let a BankAccount represent more than a plain
 * Thai bank deposit account:
 *
 *   - `bank_code`    short code that maps to config('thai_banks') for
 *                    looking up the brand colour / official names. NULL
 *                    when bank_type is `other`.
 *   - `bank_type`    'thai_bank' | 'promptpay' | 'other'. Lets the UI
 *                    pick the right field set and the PDF pick the
 *                    right rendering.
 *   - `promptpay_id` phone (10 digits) or tax-id (13 digits) carried
 *                    on the QR. Only used when bank_type='promptpay'.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->string('bank_code', 20)->nullable()->after('bank_name');
            $table->enum('bank_type', ['thai_bank', 'promptpay', 'other'])
                ->default('thai_bank')
                ->after('bank_code');
            $table->string('promptpay_id', 20)->nullable()->after('account_number');
        });
    }

    public function down(): void
    {
        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->dropColumn(['bank_code', 'bank_type', 'promptpay_id']);
        });
    }
};
