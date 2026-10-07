<?php

namespace App\Enums;

enum RequestStatus: string
{
    case Open = 'OPEN';
    case Matched = 'MATCHED';
    case Closed = 'CLOSED';
}
