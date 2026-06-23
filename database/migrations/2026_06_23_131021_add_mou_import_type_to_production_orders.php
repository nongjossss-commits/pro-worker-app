<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * เพิ่ม mou_import_type สำหรับแยกประเภท MOU import
 *  - 'return' = นำเข้าแบบ Return (ลูกจ้างอยู่ในไทยแล้ว — บันทึกข้อมูลลูกจ้างได้ทันที)
 *  - 'new'    = นำเข้าคนใหม่จากต้นทาง (ยังไม่มีข้อมูลลูกจ้าง รอ Demand → Name list)
 *  - NULL     = ยังไม่ระบุ (ผู้ใช้กลับมาเลือกทีหลังได้)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_orders', function (Blueprint $table) {
            $table->string('mou_import_type', 10)->nullable()->after('mou_female_count');
        });
    }

    public function down(): void
    {
        Schema::table('production_orders', function (Blueprint $table) {
            $table->dropColumn('mou_import_type');
        });
    }
};
