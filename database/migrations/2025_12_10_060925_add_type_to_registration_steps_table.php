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
        Schema::table('registration_steps', function (Blueprint $table) {
            $table->string('type')->default('registration')->after('id')->index();
        });

        // Duplicate existing steps for renewal
        $steps = DB::table('registration_steps')->get();
        foreach ($steps as $step) {
            DB::table('registration_steps')->insert([
                'type' => 'renewal',
                'name' => $step->name,
                'order' => $step->order,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('registration_steps', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
