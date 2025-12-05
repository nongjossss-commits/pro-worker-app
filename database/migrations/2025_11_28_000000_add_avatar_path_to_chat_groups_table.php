<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('chat_groups') && !Schema::hasColumn('chat_groups', 'avatar_path')) {
            Schema::table('chat_groups', function (Blueprint $table) {
                $table->string('avatar_path')->nullable()->after('name');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('chat_groups') && Schema::hasColumn('chat_groups', 'avatar_path')) {
            Schema::table('chat_groups', function (Blueprint $table) {
                $table->dropColumn('avatar_path');
            });
        }
    }
};
