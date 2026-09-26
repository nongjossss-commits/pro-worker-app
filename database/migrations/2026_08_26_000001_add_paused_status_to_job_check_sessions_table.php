<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Raw ENUM MODIFY is MySQL-only syntax; other drivers (e.g. local SQLite dev)
        // widen the same enum check constraint via the schema builder instead.
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE job_check_sessions MODIFY status ENUM('active', 'paused', 'completed', 'cancelled') DEFAULT 'active'");
        } else {
            Schema::table('job_check_sessions', function (Blueprint $table) {
                $table->enum('status', ['active', 'paused', 'completed', 'cancelled'])->default('active')->change();
            });
        }
    }

    public function down(): void
    {
        DB::statement("UPDATE job_check_sessions SET status = 'active' WHERE status = 'paused'");

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE job_check_sessions MODIFY status ENUM('active', 'completed', 'cancelled') DEFAULT 'active'");
        } else {
            Schema::table('job_check_sessions', function (Blueprint $table) {
                $table->enum('status', ['active', 'completed', 'cancelled'])->default('active')->change();
            });
        }
    }
};
