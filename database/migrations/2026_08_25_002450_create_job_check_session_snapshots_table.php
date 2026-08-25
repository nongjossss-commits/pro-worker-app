<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_check_session_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_check_session_id')->constrained()->cascadeOnDelete();
            $table->enum('menu', ['pre_production', 'workflow', 'registration_resolution', 'renewal_resolution']);
            $table->string('subject_type'); // App\Models\Employee or App\Models\ProductionItem
            $table->unsignedBigInteger('subject_id');
            $table->unsignedBigInteger('employer_id')->nullable();
            $table->unsignedBigInteger('resolution_tab_id')->nullable();
            $table->unsignedBigInteger('production_order_id')->nullable();
            $table->json('initial_state');
            $table->timestamps();

            $table->index(['job_check_session_id', 'menu']);
            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_check_session_snapshots');
    }
};
