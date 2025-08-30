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
            $table->string('document_1')->nullable()->after('employeePhoto');
            $table->string('document_2')->nullable()->after('document_1');
            $table->string('document_3')->nullable()->after('document_2');
            $table->string('document_4')->nullable()->after('document_3');
            $table->string('document_description_4')->nullable()->after('document_4');
            $table->string('document_5')->nullable()->after('document_description_4');
            $table->string('document_description_5')->nullable()->after('document_5');
            $table->string('document_6')->nullable()->after('document_description_5');
            $table->string('document_description_6')->nullable()->after('document_6');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn([
                'document_1',
                'document_2',
                'document_3',
                'document_4',
                'document_description_4',
                'document_5',
                'document_description_5',
                'document_6',
                'document_description_6',
            ]);
        });
    }
};
