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
            if (!Schema::hasColumn('employers', 'signerNameTh')) {
                $table->string('signerNameTh')->nullable()->after('businessType');
            }
            if (!Schema::hasColumn('employers', 'signerNameEn')) {
                $table->string('signerNameEn')->nullable()->after('signerNameTh');
            }
            if (!Schema::hasColumn('employers', 'businessTypeEn')) {
                $table->string('businessTypeEn')->nullable()->after('signerNameEn');
            }
            if (!Schema::hasColumn('employers', 'regCapital')) {
                $table->string('regCapital')->nullable()->after('businessTypeEn');
            }
            if (!Schema::hasColumn('employers', 'regDate')) {
                $table->date('regDate')->nullable()->after('regCapital');
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
                'signerNameTh',
                'signerNameEn',
                'businessTypeEn',
                'regCapital',
                'regDate',
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
