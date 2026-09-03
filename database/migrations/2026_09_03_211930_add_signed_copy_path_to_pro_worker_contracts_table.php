<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The scanned/photographed copy of the contract the employer signed and
     * returned — attached after issuance (often days/weeks later), separate
     * from `file_path` (the system-generated PDF handed out at issuance
     * time). Presence of this column drives the "สัญญาสมบูรณ์" (Complete
     * Contract) badge — see LaborContractController::uploadSignedCopy().
     */
    public function up(): void
    {
        Schema::table('pro_worker_contracts', function (Blueprint $table) {
            $table->string('signed_copy_path')->nullable()->after('file_path');
        });
    }

    public function down(): void
    {
        Schema::table('pro_worker_contracts', function (Blueprint $table) {
            $table->dropColumn('signed_copy_path');
        });
    }
};
