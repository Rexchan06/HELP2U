<?php

namespace App\Enums;

enum SupportMode: string
{
    case FaceToFace = 'f2f';
    case Virtual = 'virtual';

    public function label(): string
    {
        return match ($this) {
            self::FaceToFace => 'Face-to-face',
            self::Virtual => 'Virtual',
        };
    }
}
