<?php

namespace AndreiLungeanu\SimpleCart\Cart\DTOs;

final readonly class ShippingMethodDTO
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $description = null,
    ) {}
}
