<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Rename "งานต่ออายุ MOU" to "ต่ออายุ MOU"
        // Also ensure slug matches if needed, but slug "mou_renewal" is fine.
        DB::table('work_types')
            ->where('name', 'งานต่ออายุ MOU')
            ->update(['name' => 'ต่ออายุ MOU']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('work_types')
            ->where('name', 'ต่ออายุ MOU')
            ->update(['name' => 'งานต่ออายุ MOU']);
    }
};
