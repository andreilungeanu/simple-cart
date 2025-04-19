<?php

namespace AndreiLungeanu\SimpleCart\DTOs;

readonly class ExtraCostDTO
{
    public function __construct(
        public string $name,
        public float $amount,
        public string $type = 'fixed', // fixed or percentage
        public ?string $description = null,
        public ?float $vatRate = null,
        public bool $vatIncluded = false,
    ) {}

    public function shouldCalculateVat(): bool
    {
        return ! $this->vatIncluded && $this->vatRate !== null;
    }
}
