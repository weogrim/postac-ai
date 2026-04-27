<?php

declare(strict_types=1);

namespace App\Chat\Enums;

enum LimitType: string
{
    case Daily = 'daily';
    case Package = 'package';
    case Guest = 'guest';
}
