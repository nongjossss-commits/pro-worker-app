<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('financial_profiles', function (Blueprint $table) {
            $table->boolean('is_vat_registered')->default(false)->after('email');
            $table->decimal('vat_rate', 5, 2)->default(7.00)->after('is_vat_registered');
        });
    }

    public function down(): void
    {
        Schema::table('financial_profiles', function (Blueprint $table) {
            $table->dropColumn(['is_vat_registered', 'vat_rate']);
        });
    }
};
