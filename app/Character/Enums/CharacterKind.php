<?php

declare(strict_types=1);

namespace App\Character\Enums;

use Filament\Support\Contracts\HasLabel;

enum CharacterKind: string implements HasLabel
{
    case Regular = 'regular';
    case Dating = 'dating';

    public function label(): string
    {
        return match ($this) {
            self::Regular => 'Zwykła',
            self::Dating => 'Randki',
        };
    }

    public function getLabel(): string
    {
        return $this->label();
    }
}
