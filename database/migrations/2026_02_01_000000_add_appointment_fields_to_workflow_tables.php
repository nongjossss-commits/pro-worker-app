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
        Schema::table('production_items', function (Blueprint $table) {
            if (!Schema::hasColumn('production_items', 'appointment_date')) {
                $table->timestamp('appointment_date')->nullable()->after('group_name');
            }
        });

        Schema::table('work_types', function (Blueprint $table) {
            if (!Schema::hasColumn('work_types', 'notify_days_advance')) {
                $table->integer('notify_days_advance')->default(3)->after('slug');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('work_types', function (Blueprint $table) {
            if (Schema::hasColumn('work_types', 'notify_days_advance')) {
                $table->dropColumn('notify_days_advance');
            }
        });

        Schema::table('production_items', function (Blueprint $table) {
            if (Schema::hasColumn('production_items', 'appointment_date')) {
                $table->dropColumn('appointment_date');
            }
        });
    }
};
