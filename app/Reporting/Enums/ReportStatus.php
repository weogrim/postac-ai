<?php

declare(strict_types=1);

namespace App\Reporting\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ReportStatus: string implements HasColor, HasLabel
{
    case Pending = 'pending';
    case Resolved = 'resolved';
    case Dismissed = 'dismissed';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => 'Oczekuje',
            self::Resolved => 'Rozwiązany',
            self::Dismissed => 'Odrzucony',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Resolved => 'success',
            self::Dismissed => 'gray',
        };
    }
}
