<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * เพิ่ม wht_received_at timestamp เพื่อบันทึก "วันที่รับใบหัก ณ ที่จ่ายมาแล้ว"
 *  - ใช้คู่กับ wht_status='received'
 *  - ช่วยให้ report และ aged receivables คำนวณช่วงเวลาที่ค้างใบ ณ ที่จ่ายได้
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('financial_transactions', function (Blueprint $table) {
            $table->timestamp('wht_received_at')->nullable()->after('wht_status');
        });
    }

    public function down(): void
    {
        Schema::table('financial_transactions', function (Blueprint $table) {
            $table->dropColumn('wht_received_at');
        });
    }
};
