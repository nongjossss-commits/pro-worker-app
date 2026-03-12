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
            $table->string('importer_doc_other_1')->nullable();
            $table->string('importer_doc_other_1_desc')->nullable();
            $table->string('importer_doc_other_2')->nullable();
            $table->string('importer_doc_other_2_desc')->nullable();
            $table->string('importer_doc_other_3')->nullable();
            $table->string('importer_doc_other_3_desc')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('importers', function (Blueprint $table) {
            $table->dropColumn([
                'importer_doc_other_1',
                'importer_doc_other_1_desc',
                'importer_doc_other_2',
                'importer_doc_other_2_desc',
                'importer_doc_other_3',
                'importer_doc_other_3_desc',
            ]);
        });
    }
};
