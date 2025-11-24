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
        Schema::create('employee_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['affiliated', 'independent']);
            $table->foreignId('employer_id')->nullable()->constrained()->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('employee_teams', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('employee_group_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('employee_team_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_team_id')->constrained()->onDelete('cascade');
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            // Note: We'll enforce the "one team per group" constraint in the application logic
            // because checking it at database level across these tables is complex for a constraint.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_team_members');
        Schema::dropIfExists('employee_teams');
        Schema::dropIfExists('employee_groups');
    }
};
