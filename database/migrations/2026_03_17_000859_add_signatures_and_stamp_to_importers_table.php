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
        Schema::table('importers', function (Blueprint $table) {
            $table->string('signer_2_name_th')->nullable()->after('importerSignerEn');
            $table->string('signer_2_name_en')->nullable()->after('signer_2_name_th');
            $table->string('signature_1_path')->nullable()->after('signer_2_name_en');
            $table->string('signature_2_path')->nullable()->after('signature_1_path');
            $table->string('importer_stamp_path')->nullable()->after('signature_2_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('importers', function (Blueprint $table) {
            $table->dropColumn([
                'signer_2_name_th',
                'signer_2_name_en',
                'signature_1_path',
                'signature_2_path',
                'importer_stamp_path'
            ]);
        });
    }
};
