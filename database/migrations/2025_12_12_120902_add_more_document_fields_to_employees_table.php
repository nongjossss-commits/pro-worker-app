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
        Schema::table('employees', function (Blueprint $table) {
            $table->string('employee_doc_13')->nullable();
            $table->string('employee_doc_14')->nullable();
            $table->string('employee_doc_15')->nullable();
            $table->string('employee_doc_16')->nullable();
            $table->string('employee_doc_17')->nullable();
            $table->string('employee_doc_18')->nullable();
            $table->string('other_doc_5_desc')->nullable();
            $table->string('other_doc_6_desc')->nullable();
            $table->string('other_doc_7_desc')->nullable();
            $table->string('other_doc_8_desc')->nullable();
            $table->string('other_doc_9_desc')->nullable();
            $table->string('other_doc_10_desc')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn([
                'employee_doc_13',
                'employee_doc_14',
                'employee_doc_15',
                'employee_doc_16',
                'employee_doc_17',
                'employee_doc_18',
                'other_doc_5_desc',
                'other_doc_6_desc',
                'other_doc_7_desc',
                'other_doc_8_desc',
                'other_doc_9_desc',
                'other_doc_10_desc',
            ]);
        });
    }
};
