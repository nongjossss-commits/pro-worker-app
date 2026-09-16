<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('labor_ledger_entries', function (Blueprint $table) {
            $table->unsignedInteger('qty_laos')->nullable()->after('quantity');
            $table->unsignedInteger('qty_myanmar')->nullable()->after('qty_laos');
            $table->unsignedInteger('qty_cambodia')->nullable()->after('qty_myanmar');
            $table->unsignedInteger('qty_vietnam')->nullable()->after('qty_cambodia');
        });
    }

    public function down(): void
    {
        Schema::table('labor_ledger_entries', function (Blueprint $table) {
            $table->dropColumn(['qty_laos', 'qty_myanmar', 'qty_cambodia', 'qty_vietnam']);
        });
    }
};
