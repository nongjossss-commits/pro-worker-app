<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE job_check_sessions MODIFY status ENUM('active', 'paused', 'completed', 'cancelled') DEFAULT 'active'");
    }

    public function down(): void
    {
        DB::statement("UPDATE job_check_sessions SET status = 'active' WHERE status = 'paused'");
        DB::statement("ALTER TABLE job_check_sessions MODIFY status ENUM('active', 'completed', 'cancelled') DEFAULT 'active'");
    }
};
