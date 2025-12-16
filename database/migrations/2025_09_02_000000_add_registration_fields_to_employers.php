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
            $table->string('registration_resolution_status')->default('preparing')->after('employer_doc_other_3_desc');
            $table->text('registration_resolution_note')->nullable()->after('registration_resolution_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employers', function (Blueprint $table) {
            $table->dropColumn(['registration_resolution_status', 'registration_resolution_note']);
        });
    }
};
