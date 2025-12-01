<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('chat_messages')) {
            Schema::table('chat_messages', function (Blueprint $table) {
                if (!Schema::hasColumn('chat_messages', 'chat_group_id')) {
                    $table->foreignId('chat_group_id')->nullable()->after('receiver_id')->constrained('chat_groups')->onDelete('cascade');
                }
                if (!Schema::hasColumn('chat_messages', 'mentions')) {
                    $table->json('mentions')->nullable()->after('context_data');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('chat_messages')) {
             Schema::table('chat_messages', function (Blueprint $table) {
                if (Schema::hasColumn('chat_messages', 'chat_group_id')) {
                     // Drop foreign key first if possible, but difficult to know name.
                     // Usually chat_messages_chat_group_id_foreign
                     try {
                        $table->dropForeign(['chat_group_id']);
                     } catch (\Exception $e) {}
                     $table->dropColumn('chat_group_id');
                }
                if (Schema::hasColumn('chat_messages', 'mentions')) {
                    $table->dropColumn('mentions');
                }
             });
        }
    }
};
