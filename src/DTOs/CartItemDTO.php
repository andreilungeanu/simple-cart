<?php

namespace AndreiLungeanu\SimpleCart\DTOs;

readonly class CartItemDTO
{
    public function __construct(
        public string $id,
        public string $name,
        public float $price,
        public int $quantity,
        public ?string $category = null,
        public array $metadata = [],
    ) {}

    public function withQuantity(int $quantity): self
    {
        return new self(
            id: $this->id,
            name: $this->name,
            price: $this->price,
            quantity: $quantity,
            category: $this->category,
            metadata: $this->metadata,
        );
    }
}
