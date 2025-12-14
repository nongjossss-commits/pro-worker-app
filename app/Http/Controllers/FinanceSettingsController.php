<?php

namespace App\Http\Controllers;

use App\Models\SystemConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class FinanceSettingsController extends Controller
{
    /**
     * Store or update finance settings (Password & Email).
     * Accessible only by Admin (protected by middleware in routes).
     */
    public function updateSettings(Request $request)
    {
        $request->validate([
            'finance_password' => 'required|string|min:6|confirmed', // expects finance_password_confirmation
            'recovery_email' => 'required|email',
        ]);

        // Save Password
        SystemConfig::updateOrCreate(
            ['key' => 'finance_password_hash'],
            ['value' => Hash::make($request->finance_password)]
        );

        // Save Email
        SystemConfig::updateOrCreate(
            ['key' => 'finance_recovery_email'],
            ['value' => $request->recovery_email]
        );

        return response()->json(['success' => true, 'message' => 'Finance settings updated successfully.']);
    }

    /**
     * Verify the finance password to unlock the feature.
     */
    public function verify(Request $request)
    {
        $request->validate(['password' => 'required']);

        $config = SystemConfig::where('key', 'finance_password_hash')->first();

        if (!$config || !Hash::check($request->password, $config->value)) {
            return response()->json(['success' => false, 'message' => 'Incorrect password.'], 401);
        }

        // Set session flag (although requirement is "Close window -> Lock",
        // we might need a short-lived session for subsequent AJAX calls if we add backend middleware.
        // For now, we mainly rely on the UI gatekeeper, but setting this allows for stricter checks).
        session(['finance_unlocked' => true]);

        return response()->json(['success' => true]);
    }

    /**
     * Lock the finance feature (clear session).
     */
    public function lock()
    {
        session()->forget('finance_unlocked');
        return response()->json(['success' => true]);
    }

    /**
     * Handle "Forgot Password" request.
     */
    public function forgotPassword()
    {
        $emailConfig = SystemConfig::where('key', 'finance_recovery_email')->first();
        $email = $emailConfig ? $emailConfig->value : null;

        if (!$email) {
            return response()->json(['success' => false, 'message' => 'No recovery email configured. Please contact Admin.'], 404);
        }

        // Simulate Email Sending
        // In a real app, we would generate a signed URL or a token.
        // For this task, we will log the action and return success.

        $resetToken = Str::random(32);
        // Store token if we were implementing a full reset flow,
        // but for now we just acknowledge the request as per "Simulate" instruction.

        Log::info("FINANCE PASSWORD RESET REQUEST: Sending email to {$email}.");
        Log::info("Simulated Reset Link: " . route('dashboard') . "?reset_finance_token=" . $resetToken);

        return response()->json([
            'success' => true,
            'message' => 'Password reset instructions have been sent to ' . $email
        ]);
    }

    /**
     * Get current settings status (for Admin UI).
     * Returns whether password/email are set (not the actual values).
     */
    public function getStatus()
    {
        $hasPassword = SystemConfig::where('key', 'finance_password_hash')->exists();
        $emailConfig = SystemConfig::where('key', 'finance_recovery_email')->first();

        return response()->json([
            'has_password' => $hasPassword,
            'recovery_email' => $emailConfig ? $emailConfig->value : '',
        ]);
    }
}
