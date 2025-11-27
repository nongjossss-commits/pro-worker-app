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
        Schema::table('job_tickets', function (Blueprint $table) {
            $table->timestamp('hidden_for_admin_at')->nullable()->after('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_tickets', function (Blueprint $table) {
            $table->dropColumn('hidden_for_admin_at');
        });
    }
};
