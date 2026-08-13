<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * quantity: optional item/line count (e.g. "5 chairs"), separate from
 * amount — informational only, not multiplied against a rate.
 * attachment_path: optional receipt/bill evidence, same nullable
 * `*_path` + Storage::disk('public') convention as
 * labor_bill_payments.slip_path.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('labor_book_transactions', function (Blueprint $table) {
            $table->unsignedInteger('quantity')->nullable()->after('amount');
            $table->string('attachment_path')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('labor_book_transactions', function (Blueprint $table) {
            $table->dropColumn(['quantity', 'attachment_path']);
        });
    }
};
