<?php

namespace AndreiLungeanu\SimpleCart\DTOs;

readonly class DiscountDTO
{
    public function __construct(
        public string $code,
        public string $type = 'fixed', // fixed, percentage, shipping
        public float $value = 0,
        public ?string $appliesTo = null, // null means all items
        public ?string $minimumAmount = null,
        public ?string $expiresAt = null,
    ) {}
}
