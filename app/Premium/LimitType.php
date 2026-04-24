<?php

declare(strict_types=1);

namespace App\Premium;

enum LimitType: string
{
    case Daily = 'daily';
    case Package = 'package';
}
