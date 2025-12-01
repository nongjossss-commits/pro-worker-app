<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['community', 'private_group'])->default('private_group');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('chat_group_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_group_id')->constrained('chat_groups')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('role', ['admin', 'member'])->default('member');
            $table->timestamp('joined_at')->useCurrent();

            $table->unique(['chat_group_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_group_members');
        Schema::dropIfExists('chat_groups');
    }
};
