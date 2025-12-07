<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Workflow Barriers (The "Mai-Kan" / Standardized Statuses)
        Schema::create('workflow_barriers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('color')->default('secondary'); // bootstrap class or hex
            $table->integer('sequence')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        // 2. Production Orders (The Project / Batch)
        Schema::create('production_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employer_id')->constrained('employers');
            $table->string('project_name')->nullable();
            $table->text('description')->nullable();

            // Status: pre_production, active, completed, cancelled
            $table->string('status')->default('pre_production');

            // Financial & Business Logic
            // We'll store flexible data like quotation_no, invoice_no, amounts here
            $table->json('financial_data')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });

        // 3. Production Items (The Individual Employee Tracker)
        Schema::create('production_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_order_id')->constrained('production_orders')->onDelete('cascade');
            $table->foreignId('employee_id')->constrained('employees');

            // Cache the latest barrier ID for easier querying of "Current Status"
            $table->foreignId('current_barrier_id')->nullable()->constrained('workflow_barriers');

            $table->timestamps();
            $table->softDeletes();
        });

        // 4. Workflow Steps (The "Free Design" Timeline)
        Schema::create('workflow_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_item_id')->constrained('production_items')->onDelete('cascade');

            // Types: 'text', 'date', 'file', 'barrier'
            $table->string('step_type');

            // Label for the field (e.g., "Appointment Date", "Receipt Upload")
            $table->string('label')->nullable();

            // Values based on type
            $table->text('value_text')->nullable();
            $table->date('value_date')->nullable();
            $table->string('file_path')->nullable(); // For file uploads

            // If type == 'barrier', this links to the definition
            $table->foreignId('barrier_id')->nullable()->constrained('workflow_barriers');

            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workflow_steps');
        Schema::dropIfExists('production_items');
        Schema::dropIfExists('production_orders');
        Schema::dropIfExists('workflow_barriers');
    }
};
