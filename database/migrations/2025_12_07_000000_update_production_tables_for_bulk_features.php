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
        Schema::table('production_orders', function (Blueprint $table) {
            $table->string('type')->default('employer')->after('id'); // 'employer' or 'independent'
            $table->foreignId('employer_id')->nullable()->change();
            $table->json('custom_field_definitions')->nullable()->after('financial_data');
        });

        Schema::table('production_items', function (Blueprint $table) {
            $table->foreignId('employee_id')->nullable()->change();
            $table->json('new_employee_data')->nullable()->after('employee_id');
            $table->json('custom_field_values')->nullable()->after('current_barrier_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('production_items', function (Blueprint $table) {
            $table->dropColumn('custom_field_values');
            $table->dropColumn('new_employee_data');
            // We cannot easily revert nullable foreign keys in SQLite/some drivers without full recreation,
            // but we can try setting it back if data allows.
            // For safety in dev, we skip reverting the nullable constraint change.
        });

        Schema::table('production_orders', function (Blueprint $table) {
            $table->dropColumn('custom_field_definitions');
            $table->dropColumn('type');
        });
    }
};
