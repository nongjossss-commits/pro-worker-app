<?php

namespace App\Observers;

use App\Models\Employer;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class EmployerObserver
{
    /**
     * Handle the Employer "updated" event.
     */
    public function updated(Employer $employer): void
    {
        if ($employer->isDirty('employer_doc_company_expiry')) {
            Log::info("Document expiry date changed for employer ID: {$employer->id}. Triggering notification re-check.");
            Artisan::call('app:check-expiries');
        }
    }
}
