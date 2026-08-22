<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agent (เอเยนซี่ประเทศต้นทาง) had no file attachment support at all —
 * adding the same "3 other documents + description" pattern already used
 * by Importer/Delegate (agent_documents disk folder, 30MB per file).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->string('agent_doc_other_1')->nullable()->after('agentAddress');
            $table->string('agent_doc_other_1_desc')->nullable()->after('agent_doc_other_1');
            $table->string('agent_doc_other_2')->nullable()->after('agent_doc_other_1_desc');
            $table->string('agent_doc_other_2_desc')->nullable()->after('agent_doc_other_2');
            $table->string('agent_doc_other_3')->nullable()->after('agent_doc_other_2_desc');
            $table->string('agent_doc_other_3_desc')->nullable()->after('agent_doc_other_3');
        });
    }

    public function down(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->dropColumn([
                'agent_doc_other_1', 'agent_doc_other_1_desc',
                'agent_doc_other_2', 'agent_doc_other_2_desc',
                'agent_doc_other_3', 'agent_doc_other_3_desc',
            ]);
        });
    }
};
