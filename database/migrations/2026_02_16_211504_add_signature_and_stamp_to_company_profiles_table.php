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
        Schema::table('company_profiles', function (Blueprint $table) {
            $table->string('signature_path')->nullable();
            $table->string('stamp_path')->nullable();
            $table->json('signature_pos')->nullable(); // Stores {x, y, w, h}
            $table->json('stamp_pos')->nullable(); // Stores {x, y, w, h}
            $table->boolean('use_signature')->default(false);
            $table->boolean('use_stamp')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('company_profiles', function (Blueprint $table) {
            $table->dropColumn(['signature_path', 'stamp_path', 'signature_pos', 'stamp_pos', 'use_signature', 'use_stamp']);
        });
    }
};
