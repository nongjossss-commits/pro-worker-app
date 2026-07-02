<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // วันที่ออกใบอนุญาตทำงาน — placed just before workPermitExpiryDate
            // to keep the "issue → expiry" pair visually together in DB dumps.
            $table->date('workPermitIssueDate')->nullable()->after('employeeWorkPermit');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('workPermitIssueDate');
        });
    }
};
