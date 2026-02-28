<?php

namespace Database\Migrations;

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
        Schema::create('financial_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('type')->index(); // 'biller' or 'customer'
            $table->string('name');
            $table->string('tax_id')->nullable();
            $table->string('branch')->nullable();
            $table->text('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();

            // Assets
            $table->string('logo_path')->nullable();
            $table->string('signature_path')->nullable();
            $table->json('signature_position')->nullable(); // {x, y, width, height, rotate}
            $table->string('stamp_path')->nullable();
            $table->json('stamp_position')->nullable(); // {x, y, width, height, rotate}

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_profiles');
    }
};
