<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('sales_lead_employees', function (Blueprint $table) {
            $table->date('workPermitIssueDate')->nullable()->after('employeeWorkPermit');
        });
    }

    public function down(): void
    {
        Schema::table('sales_lead_employees', function (Blueprint $table) {
            $table->dropColumn('workPermitIssueDate');
        });
    }
};
