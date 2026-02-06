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
        Schema::table('employees', function (Blueprint $table) {
            if (!Schema::hasColumn('employees', 'appointment_date')) {
                $table->dateTime('appointment_date')->nullable();
            }
            if (!Schema::hasColumn('employees', 'appointment_location')) {
                $table->text('appointment_location')->nullable();
            }
            if (!Schema::hasColumn('employees', 'appointment_completed_at')) {
                $table->dateTime('appointment_completed_at')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['appointment_date', 'appointment_location', 'appointment_completed_at']);
        });
    }
};
