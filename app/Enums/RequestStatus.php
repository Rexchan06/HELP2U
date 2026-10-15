<?php

namespace App\Enums;

enum RequestStatus: string
{
    case Open = 'OPEN';
    case Matched = 'MATCHED';
    case Scheduled = 'SCHEDULED';
    case Closed = 'CLOSED';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Open',
            self::Matched => 'Matched',
            self::Scheduled => 'Scheduled',
            self::Closed => 'Closed',
        };
    }
}
