<?php

namespace App\Providers;

use App\Contracts\AiExtractionService;
use App\Services\Ai\ManualExtractionService;
use Illuminate\Support\ServiceProvider;

/**
 * Binds the active AI extraction engine.
 *
 * Default = ManualExtractionService (no-op fallback).
 * To plug a real engine in the future, replace the binding below — no
 * controllers or views need to change.
 */
class AiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AiExtractionService::class, ManualExtractionService::class);
    }

    public function boot(): void
    {
        //
    }
}
