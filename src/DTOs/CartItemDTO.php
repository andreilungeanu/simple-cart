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
    ) {
        if ($price < 0 || $price > 999999.99) {
            throw new \InvalidArgumentException('Price must be between 0 and 999,999.99');
        }

        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Quantity must be positive');
        }
    }

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
