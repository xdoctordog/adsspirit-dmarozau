<?php

declare(strict_types=1);

namespace App\Enums;

enum Currency: string
{
    case RUB = 'RUB';
    case USD = 'USD';
    case BYN = 'BYN';
    case CUP = 'CUP';
    case EUR = 'EUR';
    case KZT = 'KZT';

    /**
     * Get the exchange rate relative to 1 RUB.
     */
    public function rate(): float
    {
        return match ($this) {
            self::RUB => 1.0,
            self::USD => 90.0,
            self::BYN => 31.0,
            self::CUP => 3.75,
            self::EUR => 100.0,
            self::KZT => 0.20,
        };
    }

    /**
     * Get the currency symbol.
     */
    public function symbol(): string
    {
        return match ($this) {
            self::RUB => '₽',
            self::USD => '$',
            self::BYN => 'BYN',
            self::CUP => '₱',
            self::EUR => '€',
            self::KZT => '₸',
        };
    }
}
