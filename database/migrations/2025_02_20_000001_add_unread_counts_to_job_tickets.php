<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_tickets', function (Blueprint $table) {
            $table->integer('admin_unread_count')->default(0)->after('status');
            $table->integer('employer_unread_count')->default(0)->after('admin_unread_count');
        });
    }

    public function down(): void
    {
        Schema::table('job_tickets', function (Blueprint $table) {
            $table->dropColumn(['admin_unread_count', 'employer_unread_count']);
        });
    }
};
