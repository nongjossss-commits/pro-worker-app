<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * เพิ่ม fields สำหรับ MOU import demand card
 *  - mou_nationality: สัญชาติแรงงานที่จะนำเข้า (myanmar/laos/cambodia/vietnam)
 *  - mou_male_count: จำนวนชายที่ต้องการ
 *  - mou_female_count: จำนวนหญิงที่ต้องการ
 *
 * Field เหล่านี้ใช้เฉพาะ ProductionOrder ของ work_type = mou_import (NULL สำหรับอื่นๆ)
 * รองรับ 1 นายจ้าง = หลาย demand cards (เคสนำเข้าหลายชุด/หลายสัญชาติ)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_orders', function (Blueprint $table) {
            $table->string('mou_nationality', 20)->nullable()->after('project_name');
            $table->unsignedInteger('mou_male_count')->nullable()->after('mou_nationality');
            $table->unsignedInteger('mou_female_count')->nullable()->after('mou_male_count');
        });
    }

    public function down(): void
    {
        Schema::table('production_orders', function (Blueprint $table) {
            $table->dropColumn(['mou_nationality', 'mou_male_count', 'mou_female_count']);
        });
    }
};
