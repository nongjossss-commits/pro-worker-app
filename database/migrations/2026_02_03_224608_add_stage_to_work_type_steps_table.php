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
            $table->enum('stage', ['preparation', 'workflow'])->default('workflow')->after('order');
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
