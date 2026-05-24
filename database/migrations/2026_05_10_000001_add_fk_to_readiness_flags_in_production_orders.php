<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * เพิ่ม foreign key constraint ให้ document_ready_by และ financial_approved_by
 * ที่อ้างอิง users.id แต่ตอน migration เก่า (2025_12_06_000005) ไม่ได้กำหนด FK ไว้
 * → ลบ user แล้ว ProductionOrder จะมี orphan user ID
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_orders', function (Blueprint $table) {
            // ON DELETE SET NULL: ถ้า user ถูกลบ → field กลายเป็น NULL (ไม่ลบ order)
            $table->foreign('document_ready_by')
                ->references('id')->on('users')
                ->nullOnDelete();

            $table->foreign('financial_approved_by')
                ->references('id')->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('production_orders', function (Blueprint $table) {
            $table->dropForeign(['document_ready_by']);
            $table->dropForeign(['financial_approved_by']);
        });
    }
};
