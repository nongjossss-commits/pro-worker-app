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
        Schema::table('delegates', function (Blueprint $table) {
            $table->string('delegate_doc_other_1')->nullable();
            $table->string('delegate_doc_other_1_desc')->nullable();
            $table->string('delegate_doc_other_2')->nullable();
            $table->string('delegate_doc_other_2_desc')->nullable();
            $table->string('delegate_doc_other_3')->nullable();
            $table->string('delegate_doc_other_3_desc')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delegates', function (Blueprint $table) {
            $table->dropColumn([
                'delegate_doc_other_1',
                'delegate_doc_other_1_desc',
                'delegate_doc_other_2',
                'delegate_doc_other_2_desc',
                'delegate_doc_other_3',
                'delegate_doc_other_3_desc',
            ]);
        });
    }
};
