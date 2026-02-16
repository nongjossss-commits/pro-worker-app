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
        Schema::create('employer_user', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employer_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();

            $table->foreign('employer_id')->references('id')->on('employers')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            // Unique constraint to prevent duplicate assignments
            $table->unique(['employer_id', 'user_id']);
        });

        // Migrate existing data
        $employers = DB::table('employers')->whereNotNull('assigned_staff_id')->get();
        foreach ($employers as $employer) {
            // Check if user exists first to avoid foreign key constraint error
            if (DB::table('users')->where('id', $employer->assigned_staff_id)->exists()) {
                DB::table('employer_user')->insert([
                    'employer_id' => $employer->id,
                    'user_id' => $employer->assigned_staff_id,
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
        Schema::dropIfExists('employer_user');
    }
};
