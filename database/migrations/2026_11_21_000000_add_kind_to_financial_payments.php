<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Money that comes back off a bill when a price is lowered after it was paid
 * (2026-11-21). A normal payment (money received) keeps kind = null; the
 * system-made rows are:
 *  - refund        money paid back to the customer (amount < 0, posts an expense
 *                  to the books when a bank account is picked)
 *  - transfer_out  the surplus moved off this bill (amount < 0) …
 *  - transfer_in   … onto another unpaid bill of the same employer (amount > 0);
 *                  the two rows point at each other (linked_payment_id) and are
 *                  deleted together. No bank movement — the cash was already in.
 *  - adjustment    amount 0: individually collected money moved between the
 *                  people / lines of the same bill (its per-person entries
 *                  carry the + and − amounts).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('financial_payments', function (Blueprint $table) {
            if (! Schema::hasColumn('financial_payments', 'kind')) {
                $table->string('kind', 20)->nullable()->after('amount');
            }
            if (! Schema::hasColumn('financial_payments', 'linked_payment_id')) {
                $table->unsignedBigInteger('linked_payment_id')->nullable()->after('kind')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('financial_payments', function (Blueprint $table) {
            if (Schema::hasColumn('financial_payments', 'linked_payment_id')) {
                $table->dropIndex(['linked_payment_id']);
                $table->dropColumn('linked_payment_id');
            }
            if (Schema::hasColumn('financial_payments', 'kind')) {
                $table->dropColumn('kind');
            }
        });
    }
};
