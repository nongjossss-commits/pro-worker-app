<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
class TestUserSeeder extends Seeder
{
    public function run()
    {
        User::updateOrCreate(
            ['email' => 'test@example.com'],
            ['name' => 'Super Admin', 'password' => Hash::make('password')]
        );
    }
}
