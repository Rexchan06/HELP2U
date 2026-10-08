<?php

namespace App\Enums;

enum RequestStatus: string
{
    case Open = 'OPEN';
    case Matched = 'MATCHED';
    case Closed = 'CLOSED';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Open',
            self::Matched => 'Matched',
            self::Closed => 'Closed',
        };
    }
}
