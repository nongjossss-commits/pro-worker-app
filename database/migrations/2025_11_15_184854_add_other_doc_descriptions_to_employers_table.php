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
            $table->string('employer_doc_other_1_desc')->nullable()->after('employer_doc_other_1');
            $table->string('employer_doc_other_2_desc')->nullable()->after('employer_doc_other_2');
            $table->string('employer_doc_other_3_desc')->nullable()->after('employer_doc_other_3');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employers', function (Blueprint $table) {
            $table->dropColumn([
                'employer_doc_other_1_desc',
                'employer_doc_other_2_desc',
                'employer_doc_other_3_desc'
            ]);
        });
    }
};
