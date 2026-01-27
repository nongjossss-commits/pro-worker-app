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
            if (!Schema::hasColumn('production_items', 'status')) {
                $table->string('status')->default('pending')->after('group_name'); // pending, completed, cancelled
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('production_items', function (Blueprint $table) {
            if (Schema::hasColumn('production_items', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};
