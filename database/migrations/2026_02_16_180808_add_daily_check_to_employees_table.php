<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->boolean('daily_check_enabled')->default(false)->after('status');
            $table->timestamp('last_daily_checked_at')->nullable()->after('daily_check_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['daily_check_enabled', 'last_daily_checked_at']);
        });
    }
};
