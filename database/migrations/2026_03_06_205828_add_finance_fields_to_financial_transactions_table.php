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
        Schema::table('financial_transactions', function (Blueprint $table) {
            // รับเข้าบัญชีไหน
            $table->foreignId('bank_account_id')->nullable()->constrained()->nullOnDelete();

            // ข้อมูลใบหัก ณ ที่จ่าย (สำหรับรายรับที่ลูกค้าหัก 3%)
            $table->decimal('withholding_tax_amount', 15, 2)->nullable();
            $table->string('wht_document_path')->nullable();
            $table->enum('wht_status', ['not_required', 'pending', 'received'])->default('not_required');

            // ผูกกับ Profile ว่าบิลนี้ออกในนามของใคร
            $table->foreignId('financial_profile_id')->nullable()->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('financial_transactions', function (Blueprint $table) {
            $table->dropForeign(['bank_account_id']);
            $table->dropForeign(['financial_profile_id']);
            $table->dropColumn([
                'bank_account_id',
                'withholding_tax_amount',
                'wht_document_path',
                'wht_status',
                'financial_profile_id',
            ]);
        });
    }
};
