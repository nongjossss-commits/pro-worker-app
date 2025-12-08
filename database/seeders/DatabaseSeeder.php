<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => Hash::make('admin_password_1234'),
            ]
        );

        // Call the other seeders
        $this->call([
            CounterSeeder::class,
            RoleAndPermissionSeeder::class,
            NotificationSettingSeeder::class,
            ApproveProductionPermissionSeeder::class,
        ]);
    }
}
