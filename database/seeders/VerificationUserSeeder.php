<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class VerificationUserSeeder extends Seeder
{
    public function run()
    {
        User::where('email', 'test@example.com')->delete();
        User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);
    }
}
