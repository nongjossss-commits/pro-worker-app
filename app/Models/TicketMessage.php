<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\LogActivity;

class TicketMessage extends Model
{
    use HasFactory, LogActivity;

    // Corrected $fillable
    protected $fillable = [
        'job_ticket_id',
        'user_id',
        'message_type',
        'body',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(JobTicket::class, 'job_ticket_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
