<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bank_accounts', function (Blueprint $table) {
            // 'personal' = บัญชีบุคคล (off-book — ไม่เข้ารายงานภาษี ภ.พ.30/ภ.ง.ด.)
            // 'company'  = บัญชีบริษัท (เข้ารายงานภาษีตามปกติ)
            $table->enum('account_type', ['personal', 'company'])
                ->default('company')
                ->after('branch');

            $table->foreignId('financial_profile_id')
                ->nullable()
                ->after('account_type')
                ->constrained('financial_profiles')
                ->nullOnDelete();

            $table->string('tax_id', 15)->nullable()->after('financial_profile_id');
            $table->text('notes')->nullable()->after('is_active');

            $table->index(['account_type', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->dropIndex(['account_type', 'is_active']);
            $table->dropConstrainedForeignId('financial_profile_id');
            $table->dropColumn(['account_type', 'tax_id', 'notes']);
        });
    }
};
