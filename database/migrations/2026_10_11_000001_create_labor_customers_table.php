<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A team's own external customer — the team's accountant/shareholder sends
 * this info to ProWalker's own accounting, who records it here once and
 * reuses it for every future invoice to that customer (see LaborTaxInvoice's
 * new labor_customer_id). Kept separate from LaborTeam.customer_* (that's
 * the TEAM's own identity as a bill-to party, not a third party the team
 * itself bills).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('labor_customers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('labor_team_id')->constrained('labor_teams')->cascadeOnDelete();

            $table->string('name');
            $table->string('tax_id', 15)->nullable();
            $table->string('branch', 50)->nullable();
            $table->text('address')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('contact_phone', 30)->nullable();
            $table->text('notes')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['labor_team_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('labor_customers');
    }
};
