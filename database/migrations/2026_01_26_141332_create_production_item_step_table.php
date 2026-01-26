<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('production_item_step', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_item_id')->constrained()->onDelete('cascade');
            $table->foreignId('work_type_step_id')->constrained()->onDelete('cascade');
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['production_item_id', 'work_type_step_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('production_item_step');
    }
};
