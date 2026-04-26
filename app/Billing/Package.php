<?php

declare(strict_types=1);

namespace App\Billing;

use App\Chat\Enums\ModelType;
use RuntimeException;

enum Package: string
{
    case Five = 'five';
    case Ten = 'ten';
    case Fifteen = 'fifteen';
    case Premium = 'premium';

    public function priceId(): string
    {
        return config("billing.prices.{$this->value}")
            ?? throw new RuntimeException("Missing Stripe price ID for package {$this->value}. Set STRIPE_PRICE_".strtoupper($this->value).' in .env.');
    }

    public static function fromPriceId(string $priceId): self
    {
        foreach (self::cases() as $package) {
            if (config("billing.prices.{$package->value}") === $priceId) {
                return $package;
            }
        }

        throw new RuntimeException("Unknown Stripe price ID: {$priceId}");
    }

    public function isSubscription(): bool
    {
        return $this === self::Premium;
    }

    public function messageLimit(): ?int
    {
        return match ($this) {
            self::Five => 130,
            self::Ten => 270,
            self::Fifteen => 400,
            self::Premium => null,
        };
    }

    public function priceZloty(): int
    {
        return match ($this) {
            self::Five => 5,
            self::Ten => 10,
            self::Fifteen => 15,
            self::Premium => 30,
        };
    }

    public function model(): ?ModelType
    {
        return match ($this) {
            self::Five, self::Ten, self::Fifteen => ModelType::Gpt4o,
            self::Premium => null,
        };
    }

    public function priority(): int
    {
        return match ($this) {
            self::Five, self::Ten, self::Fifteen => 3,
            self::Premium => 0,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Five => 'Piątak',
            self::Ten => 'Dycha',
            self::Fifteen => 'Piętnastka',
            self::Premium => 'Premium',
        };
    }

    public function tagline(): string
    {
        return match ($this) {
            self::Five => 'Na początek',
            self::Ten => 'Najczęściej wybierane',
            self::Fifteen => 'Dla intensywnych',
            self::Premium => 'Bez limitów',
        };
    }
}
