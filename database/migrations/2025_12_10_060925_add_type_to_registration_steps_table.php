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
        if (!Schema::hasColumn('registration_steps', 'type')) {
            Schema::table('registration_steps', function (Blueprint $table) {
                $table->string('type')->default('registration')->after('id')->index();
            });
        }

        // Duplicate existing steps for renewal
        // We filter by 'registration' to get the base steps (either pre-existing or just marked as default)
        $steps = DB::table('registration_steps')->where('type', 'registration')->get();

        foreach ($steps as $step) {
            // Check if this renewal step already exists to prevent duplication
            $exists = DB::table('registration_steps')
                ->where('type', 'renewal')
                ->where('name', $step->name)
                ->exists();

            if (!$exists) {
                DB::table('registration_steps')->insert([
                    'type' => 'renewal',
                    'name' => $step->name,
                    'order' => $step->order,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('registration_steps', 'type')) {
            Schema::table('registration_steps', function (Blueprint $table) {
                $table->dropColumn('type');
            });
        }

        // Optionally remove renewal steps?
        // The original down() only dropped the column.
        // Dropping the column implicitly removes the 'type' data, but rows remain.
        // If we want to be strict, we might remove the 'renewal' rows,
        // but since the column is gone, they just become 'nameless' or duplicate rows without distinction?
        // Original down() just dropped column. I'll stick to that but add the hasColumn check for safety.
    }
};
