<?php

declare(strict_types=1);

namespace AndreiLungeanu\SimpleCart\Enums;

enum CartStatusEnum: string
{
    case Active = 'active';
    case Abandoned = 'abandoned';
    case Converted = 'converted';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Abandoned => 'Abandoned',
            self::Converted => 'Converted',
            self::Expired => 'Expired',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Active => 'green',
            self::Abandoned => 'yellow',
            self::Converted => 'blue',
            self::Expired => 'red',
        };
    }

    public static function default(): self
    {
        return self::Active;
    }
}
