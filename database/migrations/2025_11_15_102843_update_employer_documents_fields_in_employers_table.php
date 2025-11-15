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
        Schema::table('employers', function (Blueprint $table) {
            // Drop old columns
            $table->dropColumn('document_company_registration');
            $table->dropColumn('document_vat_registration');
            $table->dropColumn('document_map');

            // Add new columns
            $table->string('employer_doc_company')->nullable()->after('regDate'); // 1. หนังสือรับรองบริษัท
            $table->date('employer_doc_company_expiry')->nullable()->after('employer_doc_company'); // 1.1 วันหมดอายุ
            $table->string('employer_doc_lease')->nullable()->after('employer_doc_company_expiry'); // 2. สัญญาเช่าบ้าน
            $table->string('employer_doc_construction')->nullable()->after('employer_doc_lease'); // 3. สัญญาก่อสร้าง
            $table->string('employer_doc_other_1')->nullable()->after('employer_doc_construction'); // 4. อื่นๆ 1
            $table->string('employer_doc_other_2')->nullable()->after('employer_doc_other_1'); // 5. อื่นๆ 2
            $table->string('employer_doc_other_3')->nullable()->after('employer_doc_other_2'); // 6. อื่นๆ 3
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employers', function (Blueprint $table) {
            // Drop new columns
            $table->dropColumn([
                'employer_doc_company',
                'employer_doc_company_expiry',
                'employer_doc_lease',
                'employer_doc_construction',
                'employer_doc_other_1',
                'employer_doc_other_2',
                'employer_doc_other_3'
            ]);

            // Re-add old columns
            $table->string('document_company_registration')->nullable();
            $table->string('document_vat_registration')->nullable();
            $table->string('document_map')->nullable();
        });
    }
};
