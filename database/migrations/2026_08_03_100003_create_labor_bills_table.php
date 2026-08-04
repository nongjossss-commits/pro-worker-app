<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A billing statement snapshot for one team, one period. Deliberately does
 * NOT close out or tag the underlying labor_ledger_entries — charges stay
 * "outstanding" until an actual payment (a negative ledger entry) is
 * recorded, same as before this feature existed. A bill is just a formal,
 * dated, immutable PDF record of "as of this date, you owed this much" —
 * closer to a bank statement than an invoice that gets settled.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('labor_bills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('labor_team_id')->constrained('labor_teams')->cascadeOnDelete();
            $table->string('bill_no')->unique();
            $table->foreignId('financial_profile_id')->nullable()->constrained('financial_profiles')->nullOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('previous_balance', 12, 2)->default(0);
            $table->decimal('period_charges', 12, 2)->default(0);
            $table->decimal('total_due', 12, 2)->default(0);
            $table->string('status')->default('issued'); // issued | void
            $table->boolean('is_auto_generated')->default(false);
            $table->string('pdf_path')->nullable();
            $table->dateTime('issued_at')->nullable();
            $table->dateTime('voided_at')->nullable();
            $table->string('void_reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('labor_bills');
    }
};
