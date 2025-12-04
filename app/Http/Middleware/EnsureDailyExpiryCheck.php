<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class EnsureDailyExpiryCheck
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $today = now()->toDateString();
        $lastCheck = Cache::get('last_expiry_check_date');

        if ($lastCheck !== $today) {
            // Attempt to acquire an atomic lock for 120 seconds to prevent race conditions
            // and multiple concurrent executions.
            $lock = Cache::lock('expiry_check_lock', 120);

            if ($lock->get()) {
                try {
                    Log::info('EnsureDailyExpiryCheck: Triggering automatic expiry check.');

                    // Run the command synchronously to ensure the user sees up-to-date data.
                    // This might cause a slight delay for the first request of the day.
                    Artisan::call('app:check-expiries');

                    // Note: The command itself updates the 'last_expiry_check_date' cache key
                    // upon successful completion. We rely on that to prevent future runs.

                } catch (\Exception $e) {
                    Log::error('EnsureDailyExpiryCheck: Failed to run expiry check. ' . $e->getMessage());
                    // In case of failure, we might want to release the lock or let it expire.
                    // Allowing it to expire prevents a "retry storm" if the command is broken.
                } finally {
                    $lock->release();
                }
            }
        }

        return $next($request);
    }
}
