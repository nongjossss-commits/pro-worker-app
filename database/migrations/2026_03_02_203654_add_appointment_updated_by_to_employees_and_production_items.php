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
            $table->unsignedBigInteger('appointment_updated_by')->nullable();
            $table->timestamp('appointment_updated_at')->nullable();
            $table->foreign('appointment_updated_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('production_items', function (Blueprint $table) {
            $table->unsignedBigInteger('appointment_updated_by')->nullable();
            $table->timestamp('appointment_updated_at')->nullable();
            $table->foreign('appointment_updated_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['appointment_updated_by']);
            $table->dropColumn(['appointment_updated_by', 'appointment_updated_at']);
        });

        Schema::table('production_items', function (Blueprint $table) {
            $table->dropForeign(['appointment_updated_by']);
            $table->dropColumn(['appointment_updated_by', 'appointment_updated_at']);
        });
    }
};
