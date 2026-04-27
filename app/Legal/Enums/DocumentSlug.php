<?php

declare(strict_types=1);

namespace App\Legal\Enums;

use Filament\Support\Contracts\HasLabel;

enum DocumentSlug: string implements HasLabel
{
    case Terms = 'terms';
    case Privacy = 'privacy';
    case DatingTerms = 'dating-terms';

    public function label(): string
    {
        return match ($this) {
            self::Terms => 'Regulamin',
            self::Privacy => 'Polityka prywatności',
            self::DatingTerms => 'Regulamin sekcji Randki',
        };
    }

    public function getLabel(): string
    {
        return $this->label();
    }
}
