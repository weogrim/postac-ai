<?php

declare(strict_types=1);

namespace App\Billing;

use App\AI\ModelType;
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
            self::Five => 5,
            self::Ten => 10,
            self::Fifteen => 15,
            self::Premium => null,
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
            self::Five => '5 wiadomości',
            self::Ten => '10 wiadomości',
            self::Fifteen => '15 wiadomości',
            self::Premium => 'Premium (nielimitowane)',
        };
    }
}
