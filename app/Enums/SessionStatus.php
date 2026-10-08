<?php

namespace App\Enums;

enum SessionStatus: string
{
    case Scheduled = 'SCHEDULED';
    case Completed = 'COMPLETED';
    case Cancelled = 'CANCELLED';
}
