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
        if (Schema::hasTable('production_orders')) {
            Schema::table('production_orders', function (Blueprint $table) {
                // Make employer_id nullable
                if (Schema::hasColumn('production_orders', 'employer_id')) {
                    $table->unsignedBigInteger('employer_id')->nullable()->change();
                }

                // Add type column for 'employer' or 'independent'
                if (!Schema::hasColumn('production_orders', 'type')) {
                    // Check if employer_id exists to place 'after', otherwise just add it
                    if (Schema::hasColumn('production_orders', 'employer_id')) {
                        $table->string('type')->default('employer')->after('employer_id');
                    } else {
                        $table->string('type')->default('employer');
                    }
                }
            });
        }

        // Update production_items
        if (Schema::hasTable('production_items')) {
            Schema::table('production_items', function (Blueprint $table) {
                // Make employee_id nullable to support "New/Temp" employees
                if (Schema::hasColumn('production_items', 'employee_id')) {
                    $table->unsignedBigInteger('employee_id')->nullable()->change();
                }

                // Add new_employee_data column
                if (!Schema::hasColumn('production_items', 'new_employee_data')) {
                    if (Schema::hasColumn('production_items', 'employee_id')) {
                        $table->json('new_employee_data')->nullable()->after('employee_id');
                    } else {
                        $table->json('new_employee_data')->nullable();
                    }
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('production_orders')) {
            Schema::table('production_orders', function (Blueprint $table) {
                if (Schema::hasColumn('production_orders', 'employer_id')) {
                    $table->unsignedBigInteger('employer_id')->nullable(false)->change();
                }
                if (Schema::hasColumn('production_orders', 'type')) {
                    $table->dropColumn('type');
                }
            });
        }

        if (Schema::hasTable('production_items')) {
            Schema::table('production_items', function (Blueprint $table) {
                if (Schema::hasColumn('production_items', 'employee_id')) {
                    $table->unsignedBigInteger('employee_id')->nullable(false)->change();
                }
                if (Schema::hasColumn('production_items', 'new_employee_data')) {
                    $table->dropColumn('new_employee_data');
                }
            });
        }
    }
};
