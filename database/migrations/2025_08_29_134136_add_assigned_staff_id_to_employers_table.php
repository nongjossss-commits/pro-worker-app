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
        if (!Schema::hasColumn('employers', 'assigned_staff_id')) {
            Schema::table('employers', function (Blueprint $table) {
                $column = $table->foreignId('assigned_staff_id')
                    ->nullable();

                if (Schema::hasColumn('employers', 'job_owner_id')) {
                    $column->after('job_owner_id');
                }

                $column->constrained('users')
                    ->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('employers', 'assigned_staff_id')) {
            Schema::table('employers', function (Blueprint $table) {
                $table->dropForeign(['assigned_staff_id']);
                $table->dropColumn('assigned_staff_id');
            });
        }
    }
};
