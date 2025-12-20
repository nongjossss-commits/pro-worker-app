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
        // 1. Renewal Steps Table
        Schema::create('renewal_steps', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('order')->default(0);
            $table->string('color')->default('primary'); // For badge color
            $table->timestamps();
        });

        // 2. Employee Renewal Status Pivot Table
        Schema::create('employee_renewal_status', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->foreignId('renewal_step_id')->constrained('renewal_steps')->onDelete('cascade');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'renewal_step_id']);
        });

        // 3. Add Renewal Fields to Employers Table
        Schema::table('employers', function (Blueprint $table) {
            if (!Schema::hasColumn('employers', 'renewal_resolution_status')) {
                $table->string('renewal_resolution_status')->default('preparing')->after('employer_doc_other_3_desc');
            }
            if (!Schema::hasColumn('employers', 'renewal_resolution_note')) {
                $table->text('renewal_resolution_note')->nullable()->after('renewal_resolution_status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_renewal_status');
        Schema::dropIfExists('renewal_steps');

        Schema::table('employers', function (Blueprint $table) {
            $table->dropColumn(['renewal_resolution_status', 'renewal_resolution_note']);
        });
    }
};
