<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('job_tickets', function (Blueprint $table) {
            $table->id();

            // The user (role: employer) who created the ticket
            // MUST be employer_user_id
            $table->foreignId('employer_user_id')
                ->constrained('users')
                ->onDelete('cascade');

            $table->string('subject');

            // Status Enum definition (Database ENUM)
            $table->enum('status', [
                'pending_staff',
                'pending_employer',
                'in_progress',
                'resolved',
                'rejected'
            ])->default('pending_staff');

            // The user (role: staff/admin) assigned to handle the ticket
            // MUST be assigned_staff_id
            $table->foreignId('assigned_staff_id')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null');

            $table->timestamps();

            // DO NOT add 'priority'.
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_tickets');
    }
};
