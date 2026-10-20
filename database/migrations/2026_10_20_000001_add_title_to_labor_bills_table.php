<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "ใบวางบิลเรียกเก็บค่าใช้จ่ายรายเดือน": the expense heading is typed freely
 * (e.g. "ค่าจองบิน ชุดตุลาคม") instead of picked from the charge types.
 * Nullable — older bills keep using their charge types.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('labor_bills', function (Blueprint $table) {
            $table->string('title')->nullable()->after('charge_type_ids');
        });
    }

    public function down(): void
    {
        Schema::table('labor_bills', function (Blueprint $table) {
            $table->dropColumn('title');
        });
    }
};
