<?php
use App\Models\User;
use Illuminate\Support\Facades\Hash;

$user = User::where('email', 'test@example.com')->first();
if ($user) {
    $user->update(['password' => Hash::make('password')]);
    echo "Password updated.\n";
} else {
    echo "User not found.\n";
}
