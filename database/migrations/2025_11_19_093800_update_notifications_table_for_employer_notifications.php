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
        Schema::table('notifications', function (Blueprint $table) {
            // Make employee_id nullable to accommodate employer-only notifications
            $table->foreignId('employee_id')->nullable()->change();

            // Add employer_id for employer-specific notifications
            $table->foreignId('employer_id')->nullable()->after('employee_id')->constrained()->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            // Revert employee_id to non-nullable.
            // NOTE: This might fail if there are records where employee_id is null.
            // Manual data cleanup might be required before rollback.
            $table->foreignId('employee_id')->nullable(false)->change();

            // Drop the foreign key and the column
            $table->dropForeign(['employer_id']);
            $table->dropColumn('employer_id');
        });
    }
};
