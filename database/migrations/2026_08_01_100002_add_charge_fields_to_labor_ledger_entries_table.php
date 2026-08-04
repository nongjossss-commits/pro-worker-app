<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Supports the "central billing" flow: Accounting Staff picks a
 * labor_charge_type, enters the external system's request number and a
 * quantity, and the entry's `amount` (existing column) is computed as
 * quantity * unit_rate. All nullable — manual ledger entries (plain
 * description + amount, no catalog reference) keep working unchanged.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('labor_ledger_entries', function (Blueprint $table) {
            $table->foreignId('labor_charge_type_id')->nullable()->after('labor_team_member_id')
                ->constrained('labor_charge_types')->nullOnDelete();
            $table->string('request_number')->nullable()->unique()->after('labor_charge_type_id');
            $table->unsignedInteger('quantity')->nullable()->after('request_number');
            $table->decimal('unit_rate', 10, 2)->nullable()->after('quantity');
        });
    }

    public function down(): void
    {
        Schema::table('labor_ledger_entries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('labor_charge_type_id');
            $table->dropUnique(['request_number']);
            $table->dropColumn(['request_number', 'quantity', 'unit_rate']);
        });
    }
};
