<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-user language preference — previously the session-only `locale` was
 * force-reset to 'th' on every login (AuthenticatedSessionController), so
 * a user's choice never survived logging back in. Null means "never
 * chosen yet", which keeps the existing default-to-Thai behavior.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('locale', 5)->nullable()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('locale');
        });
    }
};
