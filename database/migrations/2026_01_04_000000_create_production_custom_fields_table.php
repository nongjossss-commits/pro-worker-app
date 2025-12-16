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
        Schema::create('production_custom_fields', function (Blueprint $table) {
            $table->id();
            $table->string('model_type'); // App\Models\Employer, App\Models\Employee
            $table->unsignedBigInteger('model_id');
            $table->string('field_name');
            $table->enum('field_type', ['text', 'date', 'file']);
            $table->text('field_value')->nullable();
            $table->string('file_path')->nullable();
            $table->timestamps();

            $table->index(['model_type', 'model_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_custom_fields');
    }
};
