<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('production_orders', function (Blueprint $table) {
            $table->foreignId('work_type_id')->nullable()->after('type')->constrained('work_types')->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::table('production_orders', function (Blueprint $table) {
            $table->dropForeign(['work_type_id']);
            $table->dropColumn('work_type_id');
        });
    }
};
