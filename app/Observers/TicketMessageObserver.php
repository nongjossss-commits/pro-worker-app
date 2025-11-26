<?php

namespace App\Observers;

use App\Models\TicketMessage;

class TicketMessageObserver
{
    /**
     * Handle the TicketMessage "created" event.
     */
    public function created(TicketMessage $ticketMessage): void
    {
        $jobTicket = $ticketMessage->ticket;

        if ($jobTicket && $jobTicket->hidden_at) {
            $jobTicket->update(['hidden_at' => null]);
        }
    }
}
