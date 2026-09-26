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
        // The table-creation migration for super_admin_settings is timestamped after this one,
        // so on a fresh install this table may not exist yet.
        if (!Schema::hasTable('super_admin_settings')) {
            Schema::create('super_admin_settings', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->boolean('is_visible')->default(true);
                $table->string('access_password')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasColumn('super_admin_settings', 'value')) {
            Schema::table('super_admin_settings', function (Blueprint $table) {
                $table->text('value')->nullable()->after('key');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('super_admin_settings', function (Blueprint $table) {
            $table->dropColumn('value');
        });
    }
};
