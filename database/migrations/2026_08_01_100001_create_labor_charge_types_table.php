<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catalog of billable service types for the Pro Walker Labor module, managed
 * exclusively by Super Admin. Each has a fixed per-head rate that Accounting
 * Staff picks from when logging a charge — see labor_ledger_entries'
 * unit_rate column, which snapshots this rate at the time of entry so a
 * later price change here never rewrites historical charges.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('labor_charge_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('rate', 10, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('labor_charge_types');
    }
};
