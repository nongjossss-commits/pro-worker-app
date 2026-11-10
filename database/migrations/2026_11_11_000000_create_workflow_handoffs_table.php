<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Workflow "send to another tab": staff tick employees and send them to
 * another Workflow tab; whoever opens that tab sees them waiting and adds
 * them to an employer card there. One row = one employee sent to one tab.
 *
 * status: pending (waiting in the target tab) → received (added to a card,
 * production_item_id set) or dismissed (not needed after all).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_handoffs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('target_work_type_id')->constrained('work_types')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            // employer of the card the employee was ticked on → card in the target tab
            $table->foreignId('employer_id')->nullable()->constrained('employers')->nullOnDelete();
            $table->foreignId('source_work_type_id')->nullable()->constrained('work_types')->nullOnDelete();
            $table->string('status', 20)->default('pending');
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('handled_at')->nullable();
            $table->foreignId('production_item_id')->nullable()->constrained('production_items')->nullOnDelete();
            $table->timestamps();

            $table->index(['target_work_type_id', 'status']);
            $table->index(['employee_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_handoffs');
    }
};
