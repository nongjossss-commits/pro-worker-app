<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Contract-level "which employer is this for" metadata — deliberately
 * separate from the template's freeform field_mapping/field_values (see
 * LaborContractController's docblock for why): employer_id links to a real
 * App\Models\Employer when the issuer has main-app access (main office
 * staff); employer_name_snapshot is always populated (either mirrored from
 * the linked Employer at issuance time, or typed free-text by external
 * teams with no Employer records of their own) so search/reporting never
 * depends on a live join that could silently break if the Employer record
 * is later renamed or removed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pro_worker_contracts', function (Blueprint $table) {
            $table->foreignId('employer_id')->nullable()->after('labor_team_id')->constrained('employers')->nullOnDelete();
            $table->string('employer_name_snapshot')->nullable()->after('employer_id');
            $table->index(['labor_team_id', 'employer_name_snapshot']);
        });
    }

    public function down(): void
    {
        Schema::table('pro_worker_contracts', function (Blueprint $table) {
            $table->dropForeign(['employer_id']);
            $table->dropIndex(['labor_team_id', 'employer_name_snapshot']);
            $table->dropColumn(['employer_id', 'employer_name_snapshot']);
        });
    }
};
