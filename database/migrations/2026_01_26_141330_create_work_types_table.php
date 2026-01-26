<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('work_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique(); // notify_in, notify_out, mou_import
            $table->boolean('is_system')->default(false); // Can be deleted?
            $table->integer('order')->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('work_types');
    }
};
