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
        // 1. Update Employers Table
        Schema::table('employers', function (Blueprint $table) {
            $table->string('signer_2_name_th')->nullable()->after('signerNameEn');
            $table->string('signer_2_name_en')->nullable()->after('signer_2_name_th');
            $table->string('signature_1_path')->nullable()->after('signer_2_name_en');
            $table->string('signature_2_path')->nullable()->after('signature_1_path');
        });

        // 2. Update Employees Table
        Schema::table('employees', function (Blueprint $table) {
            $table->string('signature_path')->nullable()->after('status');
        });

        // 3. Create Global Witnesses Table
        Schema::create('global_witnesses', function (Blueprint $table) {
            $table->id();
            $table->string('alias')->unique(); // 'witness_1', 'witness_2', etc.
            $table->string('name_th')->nullable();
            $table->string('name_en')->nullable();
            $table->string('signature_path')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('global_witnesses');

        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['signature_path']);
        });

        Schema::table('employers', function (Blueprint $table) {
            $table->dropColumn([
                'signer_2_name_th',
                'signer_2_name_en',
                'signature_1_path',
                'signature_2_path'
            ]);
        });
    }
};
