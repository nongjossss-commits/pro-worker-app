<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('income_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->nullable()->unique();
            $table->string('name');
            $table->text('description')->nullable();

            // ใช้กับ VAT (ภ.พ.30)
            // taxable  = เสียภาษี — คิด VAT ตามอัตรา
            // exempt   = ยกเว้นภาษี (เช่น ดอกเบี้ย เงินปันผล)
            // zero_rate = อัตรา 0% (เช่น ส่งออก)
            // none     = ไม่เข้าระบบ VAT (สำหรับบัญชีบุคคล)
            $table->enum('default_vat_treatment', ['none', 'taxable', 'exempt', 'zero_rate'])
                ->default('taxable');

            // WHT ที่ผู้จ่าย (ลูกค้า) อาจหักเราตอนชำระ
            // none  = ไม่ถูกหัก
            // pnd3  = บุคคลธรรมดา
            // pnd53 = นิติบุคคล
            $table->enum('default_wht_type', ['none', 'pnd3', 'pnd53'])->default('none');
            $table->decimal('default_wht_rate', 5, 2)->default(0);

            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('income_categories');
    }
};
