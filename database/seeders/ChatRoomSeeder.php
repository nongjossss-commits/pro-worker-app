<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ChatRoom;
use App\Models\User;

class ChatRoomSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Create the main community chat room if it doesn't exist
        $communityRoom = ChatRoom::firstOrCreate(
            ['type' => 'community'],
            [
                'name' => 'Community',
                'description' => 'Central community chat for all users.',
                'created_by' => User::whereHas('roles', fn ($q) => $q->where('name', 'admin'))->first()->id ?? 1,
            ]
        );

        // Ensure all relevant users are in the community room
        $users = User::whereHas('roles', function ($query) {
            $query->whereIn('name', ['admin', 'staff', 'employer']);
        })->pluck('id');

        $communityRoom->users()->sync($users);
    }
}
