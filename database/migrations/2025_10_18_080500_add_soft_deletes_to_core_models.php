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
        // Add softDeletes column to 5 core tables
        Schema::table('employers', function (Blueprint $table) {
            $table->softDeletes();
        });
        Schema::table('agents', function (Blueprint $table) {
            $table->softDeletes();
        });
        Schema::table('importers', function (Blueprint $table) {
            $table->softDeletes();
        });
        Schema::table('delegates', function (Blueprint $table) {
            $table->softDeletes();
        });
        Schema::table('addresses', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove softDeletes column from 5 core tables
        Schema::table('employers', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        Schema::table('agents', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        Schema::table('importers', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        Schema::table('delegates', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        Schema::table('addresses', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};