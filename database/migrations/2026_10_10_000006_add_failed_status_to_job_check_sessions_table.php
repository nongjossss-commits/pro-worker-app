<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Lets JobCheckService::completeSession() mark a session 'failed'
        // instead of leaving it stuck 'active' forever when an exception
        // hits partway through diffing/exporting — 'failed' is excluded
        // from JobCheckSession::scopeCurrent() (active|paused) and from
        // EnforceJobCheckMode's confinement check, so the user is freed
        // immediately instead of needing to log out.
        DB::statement("ALTER TABLE job_check_sessions MODIFY status ENUM('active', 'paused', 'completed', 'cancelled', 'failed') DEFAULT 'active'");
    }

    public function down(): void
    {
        DB::statement("UPDATE job_check_sessions SET status = 'cancelled' WHERE status = 'failed'");
        DB::statement("ALTER TABLE job_check_sessions MODIFY status ENUM('active', 'paused', 'completed', 'cancelled') DEFAULT 'active'");
    }
};
