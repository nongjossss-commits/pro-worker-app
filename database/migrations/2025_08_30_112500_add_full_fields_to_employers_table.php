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
        Schema::table('employers', function (Blueprint $table) {
            $table->string('signerNameTh')->nullable()->after('businessType');
            $table->string('signerNameEn')->nullable()->after('signerNameTh');
            $table->string('businessTypeEn')->nullable()->after('signerNameEn');
            $table->string('regCapital')->nullable()->after('businessTypeEn');
            $table->date('regDate')->nullable()->after('regCapital');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employers', function (Blueprint $table) {
            $table->dropColumn([
                'signerNameTh',
                'signerNameEn',
                'businessTypeEn',
                'regCapital',
                'regDate',
            ]);
        });
    }
};
