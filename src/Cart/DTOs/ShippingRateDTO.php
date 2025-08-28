<?php

namespace AndreiLungeanu\SimpleCart\Cart\DTOs;

final readonly class ShippingRateDTO
{
    public function __construct(
        public float $amount,
        public ?float $vatRate = null,
        public bool $vatIncluded = false,
    ) {}
}
