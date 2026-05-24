<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * เพิ่ม wht_no_cert_reason: เหตุผลกรณีลูกค้ายืนยันว่าจะไม่ออกใบหัก ณ ที่จ่ายให้
 *  - ใช้คู่กับ wht_status='no_certificate' (status ใหม่)
 *  - ช่วยให้ระบบบัญชีตรวจสอบย้อนหลังได้
 *
 * หมายเหตุ: wht_status เป็น string column อยู่แล้ว ไม่จำเป็นต้องเปลี่ยน enum
 * ค่าที่ใช้: 'not_required' | 'pending' | 'received' | 'no_certificate'
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('financial_transactions', function (Blueprint $table) {
            $table->text('wht_no_cert_reason')->nullable()->after('wht_received_at');
        });
    }

    public function down(): void
    {
        Schema::table('financial_transactions', function (Blueprint $table) {
            $table->dropColumn('wht_no_cert_reason');
        });
    }
};
