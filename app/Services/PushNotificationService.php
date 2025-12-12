<?php

namespace App\Services;

use App\Models\PushSubscription;
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;
use Illuminate\Support\Facades\Log;

class PushNotificationService
{
    /**
     * Send a notification to a specific user.
     *
     * @param int $userId
     * @param array $payload ['title' => '...', 'body' => '...', 'url' => '...', 'unread_count' => 5]
     */
    public function sendToUser($userId, array $payload)
    {
        // 1. Fetch Subscriptions
        $subscriptions = PushSubscription::where('user_id', $userId)->get();

        if ($subscriptions->isEmpty()) {
            return;
        }

        // 2. Prepare WebPush
        // Note: Ideally, VAPID keys should be in config/services.php or .env
        // Since we are in a constrained environment and might lack the library or keys,
        // we wrap this in a try-catch to avoid crashing the app.

        $auth = [
            'VAPID' => [
                'subject' => config('app.url'),
                'publicKey' => config('services.webpush.public_key'), // Need to ensure these exist
                'privateKey' => config('services.webpush.private_key'),
            ],
        ];

        try {
            if (!class_exists('Minishlink\WebPush\WebPush')) {
                Log::warning('WebPush library not found. Skipping push notification.');
                return;
            }

            // Check if keys are present. If not, we can't send.
            if (empty($auth['VAPID']['publicKey']) || empty($auth['VAPID']['privateKey'])) {
                Log::warning('WebPush VAPID keys not configured.');
                return;
            }

            $webPush = new WebPush($auth);

            foreach ($subscriptions as $sub) {
                $subscription = Subscription::create([
                    'endpoint' => $sub->endpoint,
                    'publicKey' => $sub->public_key,
                    'authToken' => $sub->auth_token,
                ]);

                $webPush->sendOneNotification(
                    $subscription,
                    json_encode($payload)
                );
            }

            // Flush (send)
            $report = $webPush->flush();

            // Optional: Handle expired subscriptions based on report
            // foreach ($report as $message) { ... }

        } catch (\Exception $e) {
            Log::error('Failed to send push notification: ' . $e->getMessage());
        }
    }
}
