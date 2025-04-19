<?php

namespace AndreiLungeanu\SimpleCart\DTOs;

readonly class ExtraCostDTO
{
    public function __construct(
        public string $name,
        public float $amount,
        public string $type = 'fixed', // fixed or percentage
        public ?string $description = null,
    ) {}
}
