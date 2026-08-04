<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Itemized charge/credit history per Labor Team. Positive amount = charge
 * owed to the company, negative amount = payment/credit adjustment.
 * Soft-deleted so an accounting mistake can be restored (see LaborAuditLog
 * for who changed what — Super Admin only).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('labor_ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('labor_team_id')->constrained('labor_teams')->cascadeOnDelete();
            $table->date('entry_date');
            $table->string('description');
            $table->decimal('amount', 12, 2);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('labor_ledger_entries');
    }
};
