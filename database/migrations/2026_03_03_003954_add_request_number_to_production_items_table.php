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
        if (!Schema::hasColumn('production_items', 'request_number')) {
            Schema::table('production_items', function (Blueprint $table) {
                $table->string('request_number')->nullable()->after('employee_id');
            });
        }

        // Backfill existing data using a SQLite compatible query or Eloquent
        $items = \App\Models\ProductionItem::with('employee')->get();
        foreach ($items as $item) {
            if ($item->employee && !empty($item->employee->request_number)) {
                $item->request_number = $item->employee->request_number;
                $item->save();
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('production_items', 'request_number')) {
            Schema::table('production_items', function (Blueprint $table) {
                $table->dropColumn('request_number');
            });
        }
    }
};
