<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Stores the single "active" system rental / service contract that governs
 * whether the whole installation is in Active, Grace, or Read-Only mode.
 *
 * We keep the row count small: there is exactly ONE active contract at a
 * time (the current agreement); older contracts stay in the table with
 * end_date in the past for audit history. The banner + middleware always
 * read the most recent row (ordered by id DESC).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('service_contracts', function (Blueprint $table) {
            $table->id();

            // 'service' = paid rental, 'trial' = trial contract
            $table->enum('contract_type', ['service', 'trial'])->default('trial');

            $table->string('customer_name')->nullable();

            $table->date('start_date')->nullable();

            // The real contract cut-off. After this date the system enters
            // Read-Only mode UNLESS grace_end_date is set to a future date.
            $table->date('end_date');

            // Temporary "keep it running while we finalize renewal" extension.
            // Null = no grace period active. Non-null = the extended cut-off.
            // effective_end = max(end_date, grace_end_date).
            $table->date('grace_end_date')->nullable();

            // 3 optional contract file slots.
            $table->string('attachment_1_path')->nullable();
            $table->string('attachment_1_original')->nullable();
            $table->timestamp('attachment_1_uploaded_at')->nullable();

            $table->string('attachment_2_path')->nullable();
            $table->string('attachment_2_original')->nullable();
            $table->timestamp('attachment_2_uploaded_at')->nullable();

            $table->string('attachment_3_path')->nullable();
            $table->string('attachment_3_original')->nullable();
            $table->timestamp('attachment_3_uploaded_at')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_contracts');
    }
};
