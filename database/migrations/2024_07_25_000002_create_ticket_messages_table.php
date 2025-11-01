<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ticket_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_ticket_id')
                ->constrained('job_tickets')
                ->onDelete('cascade');

            // MUST be nullable and onDelete('set null') for audit trail
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null');

            // Message Type Enum definition (Database ENUM)
            // MUST include message_type
            $table->enum('message_type', [
                'comment',
                'attachment_file',
                'attachment_employee',
                'attachment_new_employee',
                'system_activity',
            ]);

            // Body: Stores text, file path, Employee ID, or JSON data.
            // MUST be named 'body', type TEXT, and nullable
            $table->text('body')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_messages');
    }
};
