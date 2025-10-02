<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create a default user
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Call the other seeders
        $this->call([
            CounterSeeder::class,
            RoleAndPermissionSeeder::class, // เพิ่มบรรทัดนี้เข้ามา
        ]);
    }
}