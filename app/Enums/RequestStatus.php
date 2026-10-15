<?php

namespace App\Enums;

enum RequestStatus: string
{
    case Open = 'OPEN';
    case Pending = 'PENDING';
    case Matched = 'MATCHED';
    case Closed = 'CLOSED';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Open',
            self::Pending => 'Pending',
            self::Matched => 'Matched',
            self::Closed => 'Closed',
        };
    }
}
