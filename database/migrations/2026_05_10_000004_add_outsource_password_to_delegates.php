<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * เพิ่มช่อง outsource_password สำหรับ Delegate (พนักงานบริษัท)
 *  - เก็บเป็น plain text เพื่อให้ copy ไปใช้ได้สะดวก (user requirement)
 *  - ใช้บันทึก password ของระบบ outsource ที่ delegate รับผิดชอบ
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delegates', function (Blueprint $table) {
            $table->string('outsource_password', 255)->nullable()->after('delegateEmail');
        });
    }

    public function down(): void
    {
        Schema::table('delegates', function (Blueprint $table) {
            $table->dropColumn('outsource_password');
        });
    }
};
