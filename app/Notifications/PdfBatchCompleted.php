<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PdfBatchCompleted extends Notification implements ShouldQueue
{
    use Queueable;

    protected $mode; // 'download' or 'save_to_slot'
    protected $count;
    protected $downloadTaskId;

    public function __construct($mode, $count, $downloadTaskId = null)
    {
        $this->mode = $mode;
        $this->count = $count;
        $this->downloadTaskId = $downloadTaskId;
    }

    public function via($notifiable)
    {
        return ['database']; // Keeping it simple for now, can add 'mail' if requested
    }

    public function toArray($notifiable)
    {
        $message = $this->mode === 'save_to_slot'
            ? "Successfully saved documents for {$this->count} employees."
            : "Your PDF export for {$this->count} employees is ready.";

        $actionUrl = $this->downloadTaskId
            ? route('admin.downloads.download', $this->downloadTaskId)
            : null;

        return [
            'title' => 'PDF Generation Completed',
            'message' => $message,
            'action_url' => $actionUrl,
            'type' => 'success'
        ];
    }
}
