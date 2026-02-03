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
            if (!Schema::hasColumn('production_items', 'appointment_completed_at')) {
                $table->timestamp('appointment_completed_at')->nullable()->after('appointment_location');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('production_items', function (Blueprint $table) {
            if (Schema::hasColumn('production_items', 'appointment_completed_at')) {
                $table->dropColumn('appointment_completed_at');
            }
        });
    }
};
