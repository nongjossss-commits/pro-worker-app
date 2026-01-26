<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('production_items', function (Blueprint $table) {
            $table->string('group_name')->nullable()->after('employee_id'); // e.g. "Batch 1", "Applied on Dec 1"
        });
    }

    public function down()
    {
        Schema::table('production_items', function (Blueprint $table) {
            $table->dropColumn('group_name');
        });
    }
};
