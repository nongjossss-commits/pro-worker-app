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
        Schema::table('work_type_steps', function (Blueprint $table) {
            $table->string('stage')->default('workflow')->after('order'); // 'preparation' or 'workflow'
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('work_type_steps', function (Blueprint $table) {
            $table->dropColumn('stage');
        });
    }
};
