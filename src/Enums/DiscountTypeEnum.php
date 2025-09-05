<?php

declare(strict_types=1);

namespace AndreiLungeanu\SimpleCart\Enums;

enum DiscountTypeEnum: string
{
    case Fixed = 'fixed';
    case Percentage = 'percentage';
    case FreeShipping = 'free_shipping';
    case BuyXGetY = 'buy_x_get_y';

    public function label(): string
    {
        return match ($this) {
            self::Fixed => 'Fixed Amount',
            self::Percentage => 'Percentage Off',
            self::FreeShipping => 'Free Shipping',
            self::BuyXGetY => 'Buy X Get Y',
        };
    }

    public function isPercentage(): bool
    {
        return $this === self::Percentage;
    }

    public function requiresMinimum(): bool
    {
        return in_array($this, [self::Fixed, self::Percentage]);
    }
}
