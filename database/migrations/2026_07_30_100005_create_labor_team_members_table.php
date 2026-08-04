<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Roster of individual people ("ลูกทีม") within a Labor Team — the ones who
 * actually file jobs on an external/government website. Not a login account:
 * just a name record so ledger entries can be attributed to a person, and
 * the team's total can be broken down per member.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('labor_team_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('labor_team_id')->constrained('labor_teams')->cascadeOnDelete();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('labor_team_members');
    }
};
