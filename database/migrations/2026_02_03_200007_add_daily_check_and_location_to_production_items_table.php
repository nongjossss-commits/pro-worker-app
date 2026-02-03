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
            if (!Schema::hasColumn('production_items', 'appointment_location')) {
                $table->string('appointment_location')->nullable()->after('appointment_date');
            }
            if (!Schema::hasColumn('production_items', 'last_checked_at')) {
                $table->timestamp('last_checked_at')->nullable()->after('appointment_location');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('production_items', function (Blueprint $table) {
            if (Schema::hasColumn('production_items', 'appointment_location')) {
                $table->dropColumn('appointment_location');
            }
            if (Schema::hasColumn('production_items', 'last_checked_at')) {
                $table->dropColumn('last_checked_at');
            }
        });
    }
};
