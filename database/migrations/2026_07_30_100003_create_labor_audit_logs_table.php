<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dedicated audit trail for the Pro Walker Labor module, separate from the
 * main app's ActivityLog. Viewable by Super Admin only — the safety net
 * against mistakes/fraud by Accounting Staff, who have full edit rights
 * on every team's ledger.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('labor_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('labor_team_id')->nullable()->constrained('labor_teams')->nullOnDelete();
            $table->unsignedBigInteger('labor_ledger_entry_id')->nullable();
            $table->string('action'); // created | updated | deleted | restored
            $table->json('changes')->nullable(); // { before: {...}, after: {...} }
            $table->string('ip_address')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('labor_audit_logs');
    }
};
