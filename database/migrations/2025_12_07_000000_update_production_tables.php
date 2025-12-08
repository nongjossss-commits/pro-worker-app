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
        // Update production_orders
        Schema::table('production_orders', function (Blueprint $table) {
            // Make employer_id nullable
            $table->unsignedBigInteger('employer_id')->nullable()->change();

            // Add type column for 'employer' or 'independent'
            $table->string('type')->default('employer')->after('employer_id');
        });

        // Update production_items
        Schema::table('production_items', function (Blueprint $table) {
            // Make employee_id nullable to support "New/Temp" employees
            $table->unsignedBigInteger('employee_id')->nullable()->change();

            // Add new_employee_data column
            $table->json('new_employee_data')->nullable()->after('employee_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('production_orders', function (Blueprint $table) {
            $table->unsignedBigInteger('employer_id')->nullable(false)->change();
            $table->dropColumn('type');
        });

        Schema::table('production_items', function (Blueprint $table) {
            $table->unsignedBigInteger('employee_id')->nullable(false)->change();
            $table->dropColumn('new_employee_data');
        });
    }
};
