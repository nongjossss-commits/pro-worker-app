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
        Schema::table('users', function (Blueprint $table) {
            // Permission names explicitly revoked for this user, overriding what
            // their role would otherwise grant. Null/empty = no overrides (default).
            $table->json('revoked_permissions')->nullable()->after('labor_access_granted');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('revoked_permissions');
        });
    }
};
