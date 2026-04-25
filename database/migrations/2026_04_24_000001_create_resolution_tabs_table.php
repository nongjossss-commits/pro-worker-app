<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resolution_tabs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type')->index(); // 'registration' or 'renewal'
            $table->string('slug')->unique();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_default')->default(false); // marks the original/first tab
            $table->softDeletes(); // 7-day cooldown before permanent delete
            $table->timestamps();
        });

        // Create default tabs for existing data
        $now = now();

        DB::table('resolution_tabs')->insert([
            [
                'name' => 'มติลงทะเบียน',
                'type' => 'registration',
                'slug' => 'default-registration',
                'sort_order' => 0,
                'is_default' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'มติต่ออายุ',
                'type' => 'renewal',
                'slug' => 'default-renewal',
                'sort_order' => 0,
                'is_default' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('resolution_tabs');
    }
};
