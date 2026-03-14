<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthController extends Controller
{
    /**
     * Handle user login and return an access token.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'device_name' => 'nullable|string',
        ]);

        $user = User::where('email', $request->email)->first();

        // Check user existence, password, and active status
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'The provided credentials are incorrect.'
            ], 401);
        }

        if ($user->status !== 'active') {
             return response()->json([
                'status' => 'error',
                'message' => 'This account has been deactivated. Please contact support.'
            ], 403);
        }

        // Revoke existing tokens for the specific device if provided,
        // or just let them accumulate. Here we let them accumulate as normal.
        $deviceName = $request->device_name ?? 'mobile_app';

        $token = $user->createToken($deviceName)->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Login successful',
            'data' => [
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->roles->first()?->name ?? 'user',
                    'avatar_url' => $user->avatar_url,
                ]
            ]
        ], 200);
    }

    /**
     * Get the authenticated user's profile.
     */
    public function user(Request $request)
    {
        $user = $request->user();

        // Eager load roles if needed
        $user->load('roles');

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'status' => $user->status,
                'role' => $user->roles->first()?->name ?? 'user',
                'avatar_url' => $user->avatar_url,
                'position_title' => $user->position_title,
                'bio' => $user->bio,
            ]
        ], 200);
    }

    /**
     * Handle user logout (revoke the token used to authenticate the current request).
     */
    public function logout(Request $request)
    {
        // Revoke the token that was used to authenticate the current request
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Successfully logged out'
        ], 200);
    }
}
