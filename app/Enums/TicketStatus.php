<?php

namespace App\Enums;

enum TicketStatus: string
{
    case Pending = 'pending';
    case InProgress = 'in-progress';
    case Closed = 'closed';
    case Resolved = 'resolved';
    case Rejected = 'rejected';
    case InWorkflow = 'in-workflow';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::InProgress => 'In Progress',
            self::Closed => 'Closed',
            self::Resolved => 'Resolved',
            self::Rejected => 'Rejected',
            self::InWorkflow => 'In-Workflow',
        };
    }
}
