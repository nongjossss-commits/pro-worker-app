<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('work_type_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_type_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->integer('order')->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('work_type_steps');
    }
};
