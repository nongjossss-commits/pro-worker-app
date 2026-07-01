<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Audit log for every grace-period action the Super Admin takes:
 * enabling temporary access, extending it further, or stopping it early.
 *
 * Provides a paper trail so the operator can review "who gave the client
 * that extra 7 days on 2026-05-01 and why".
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('service_contract_extensions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('service_contract_id')
                  ->constrained('service_contracts')
                  ->cascadeOnDelete();

            $table->foreignId('extended_by_user_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            $table->enum('action', ['grace_enabled', 'grace_extended', 'grace_stopped', 'contract_renewed'])
                  ->default('grace_extended');

            $table->date('previous_end')->nullable();
            $table->date('new_end')->nullable();
            $table->integer('days_added')->nullable();

            $table->text('reason')->nullable();

            $table->timestamps();

            $table->index('service_contract_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_contract_extensions');
    }
};
