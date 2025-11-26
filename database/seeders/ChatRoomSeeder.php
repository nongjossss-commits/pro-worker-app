<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ChatRoom;
use App\Models\User;
use Spatie\Permission\Models\Role;

class ChatRoomSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Create the main community chat room
        $communityRoom = ChatRoom::firstOrCreate(
            ['type' => 'community'],
            [
                'name' => 'Community Chat',
                'description' => 'A central place for all staff and admins to communicate.'
            ]
        );

        // 2. Get all admin and staff users
        $adminRole = Role::where('name', 'admin')->first();
        $staffRole = Role::where('name', 'staff')->first();

        $users = User::whereHas('roles', function ($query) use ($adminRole, $staffRole) {
            $query->where('role_id', $adminRole->id)
                  ->orWhere('role_id', $staffRole->id);
        })->get();

        // 3. Attach all these users to the community room
        $communityRoom->users()->syncWithoutDetaching($users->pluck('id'));
    }
}
