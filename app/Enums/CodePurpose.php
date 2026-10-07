<?php

namespace App\Enums;

enum CodePurpose: string
{
    case Register = 'REGISTER';
    case Login = 'LOGIN';
    case Reset = 'RESET';
}
