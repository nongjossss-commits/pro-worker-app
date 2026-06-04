<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expense_categories', function (Blueprint $table) {
            $table->string('code', 20)->nullable()->unique()->after('id');

            // WHT ที่เราต้องหัก ณ ที่จ่ายให้ supplier
            $table->enum('default_wht_type', ['none', 'pnd3', 'pnd53'])
                ->default('none')
                ->after('is_tax_deductible');
            $table->decimal('default_wht_rate', 5, 2)->default(0)->after('default_wht_type');
        });
    }

    public function down(): void
    {
        Schema::table('expense_categories', function (Blueprint $table) {
            $table->dropColumn(['code', 'default_wht_type', 'default_wht_rate']);
        });
    }
};
