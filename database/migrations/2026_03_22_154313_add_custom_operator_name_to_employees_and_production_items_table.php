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
            $table->string('custom_operator_name')->nullable()->after('operator_id');
        });

        Schema::table('production_items', function (Blueprint $table) {
            $table->string('custom_operator_name')->nullable()->after('operator_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('custom_operator_name');
        });

        Schema::table('production_items', function (Blueprint $table) {
            $table->dropColumn('custom_operator_name');
        });
    }
};