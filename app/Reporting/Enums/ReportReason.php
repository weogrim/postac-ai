<?php

declare(strict_types=1);

namespace App\Reporting\Enums;

use Filament\Support\Contracts\HasLabel;

enum ReportReason: string implements HasLabel
{
    case Nsfw = 'nsfw';
    case Harassment = 'harassment';
    case Misinformation = 'misinformation';
    case Impersonation = 'impersonation';
    case SelfHarmPromotion = 'self_harm_promotion';
    case Other = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::Nsfw => 'Treści NSFW',
            self::Harassment => 'Nękanie / mowa nienawiści',
            self::Misinformation => 'Dezinformacja',
            self::Impersonation => 'Podszywanie się',
            self::SelfHarmPromotion => 'Promowanie samookaleczeń',
            self::Other => 'Inne',
        };
    }
}
