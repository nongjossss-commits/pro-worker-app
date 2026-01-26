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
            if (!Schema::hasColumn('employers', 'employerNameEn')) {
                $table->string('employerNameEn')->nullable()->after('employerNameTh');
            }
            if (!Schema::hasColumn('employers', 'minimum_wage')) {
                $table->string('minimum_wage')->nullable()->after('regDate');
            }
            if (!Schema::hasColumn('employers', 'document_company_registration')) {
                $table->string('document_company_registration')->nullable()->after('minimum_wage');
            }
            if (!Schema::hasColumn('employers', 'document_vat_registration')) {
                $table->string('document_vat_registration')->nullable()->after('document_company_registration');
            }
            if (!Schema::hasColumn('employers', 'document_map')) {
                $table->string('document_map')->nullable()->after('document_vat_registration');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employers', function (Blueprint $table) {
            $columns = [
                'employerNameEn',
                'minimum_wage',
                'document_company_registration',
                'document_vat_registration',
                'document_map',
            ];

            $toDrop = [];
            foreach ($columns as $column) {
                if (Schema::hasColumn('employers', $column)) {
                    $toDrop[] = $column;
                }
            }

            if (!empty($toDrop)) {
                $table->dropColumn($toDrop);
            }
        });
    }
};
