<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->text('registration_request_number')->nullable()->after('request_number');
            $table->text('renewal_request_number')->nullable()->after('registration_request_number');
        });

        // Backfill data from main request_number
        DB::statement('UPDATE employees SET registration_request_number = request_number, renewal_request_number = request_number WHERE request_number IS NOT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['registration_request_number', 'renewal_request_number']);
        });
    }
};
