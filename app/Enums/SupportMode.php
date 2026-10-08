<?php

namespace App\Enums;

enum SupportMode: string
{
    case F2F = 'F2F';
    case Virtual = 'VIRTUAL';

    public function label(): string
    {
        return match ($this) {
            self::F2F => 'Face-to-face',
            self::Virtual => 'Virtual',
        };
    }
}
